#!/usr/bin/env python3
"""Export a STATIC HTML + CSS + JavaScript preview of the website.

Logs in to the running website as each demo role, saves every page as a plain
.html file, and rewrites links so the pages open by double-clicking (no PHP,
no MySQL, no server). Forms are disabled in the preview (saving data needs the
real PHP + MySQL website).

Usage (website must be running with DEMO data):
    python3 python/tools/export_static_html.py --base http://127.0.0.1:8080 --out html-demo
"""
from __future__ import annotations

import argparse
import os
import re
import shutil
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
sys.path.insert(0, os.path.join(os.path.dirname(__file__), "..", "tests"))
from ctconfig import connect  # noqa: E402
from test_e2e import Client  # noqa: E402

ROLES = {
    "state": ("state@demo.local", "State Admin", ["/dashboard", "/itis", "/students", "/reports", "/reports?type=outcome", "/users", "/audit", "/settings"]),
    "district": ("district.pune@demo.local", "District Admin (Pune)", ["/dashboard", "/itis", "/students", "/reports", "/audit", "/settings"]),
    "principal": ("principal.pune@demo.local", "Principal (Govt ITI Pune)", ["/dashboard", "/students", "/admissions/new", "/trades", "/users",
                  "/transfers", "/transfers?pcid={PCID}", "/placements", "/follow-ups", "/reports", "/audit", "/settings",
                  "/students/{ROHIT}", "/students/{ROHIT}?tab=training", "/students/{ROHIT}?tab=results", "/students/{ROHIT}?tab=employment",
                  "/students/{ROHIT}?tab=documents", "/students/{ROHIT}?tab=admissions", "/students/{ROHIT}?tab=contacts",
                  "/students/{ROHIT}?tab=status", "/students/{ROHIT}/career"]),
    "teacher": ("teacher.pune@demo.local", "Teacher (Fitter, Pune)", ["/dashboard", "/students", "/students?tab=previous", "/attendance", "/training",
                "/assessments", "/results", "/career-tracking", "/follow-ups", "/settings"]),
    "placement": ("placement.pune@demo.local", "Placement Officer (Pune)", ["/dashboard", "/companies", "/vacancies", "/vacancies/new", "/placements",
                  "/apprenticeships", "/follow-ups", "/reports", "/settings"]),
    "staff": ("staff.pune@demo.local", "ITI Staff (Pune)", ["/follow-ups", "/settings"]),
    "employer": ("hr.sahyadri@demo.local", "Employer / HR", ["/dashboard", "/employer/company", "/vacancies/new", "/employer/applications",
                 "/employer/applications?status=SHORTLISTED", "/employer/hired", "/employer/apprentices", "/employer/confirmations",
                 "/employer/reports", "/settings", "/employer/candidates/{ROHIT}"]),
    "student": ("rohit.deshmukh@demo.local", "Student / Alumni", ["/dashboard", "/me/profile", "/me/profile?tab=training", "/me/profile?tab=results",
                "/me/profile?tab=employment", "/me/profile?tab=documents", "/me/profile?tab=admissions", "/me/profile?tab=contacts",
                "/me/profile?tab=status", "/me/training", "/me/results", "/me/apprenticeship", "/me/employment", "/me/higher-education",
                "/me/business", "/jobs", "/me/update", "/me/update?type=apprenticeship", "/me/update?type=business", "/me/update?type=higher",
                "/me/update?type=status", "/settings"]),
}

DEMO_JS = """/* Static preview: pages are plain HTML. Saving data needs the real PHP + MySQL website. */
(function () {
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (f.getAttribute('data-static-nav')) { return; }
    ev.preventDefault();
    window.alert('This is the static HTML preview.\\nSaving data needs the real website (PHP + MySQL) — see README.md.');
  }, true);
})();
"""


def fname(path: str) -> str:
    name = path.strip("/").replace("/", "-") or "index"
    name = re.sub(r"[?&=]", "-", name)
    return re.sub(r"-+", "-", name).strip("-") + ".html"


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", default="http://127.0.0.1:8080")
    ap.add_argument("--out", default=os.path.join(os.path.dirname(__file__), "..", "..", "html-demo"))
    args = ap.parse_args()
    out = os.path.abspath(args.out)
    app_public = os.path.join(os.path.dirname(__file__), "..", "..", "app", "public", "assets")

    with connect() as conn, conn.cursor() as cur:
        cur.execute("SELECT student_id FROM users WHERE email = 'rohit.deshmukh@demo.local'")
        rohit = cur.fetchone()["student_id"]
        cur.execute("""SELECT p.pcid FROM pcid_registry p JOIN institute_affiliations ia ON ia.student_id = p.student_id
                       JOIN itis i ON i.id = ia.iti_id WHERE i.iti_code = 'DEMO0001' AND ia.status = 'ACTIVE' LIMIT 1""")
        pcid = cur.fetchone()["pcid"]

    if os.path.isdir(out):
        shutil.rmtree(out)
    for d in ("css", "js", "img"):
        os.makedirs(os.path.join(out, d), exist_ok=True)
    shutil.copy(os.path.join(app_public, "css", "app.css"), os.path.join(out, "css", "app.css"))
    shutil.copy(os.path.join(app_public, "js", "app.js"), os.path.join(out, "js", "app.js"))
    shutil.copy(os.path.join(app_public, "img", "logo.svg"), os.path.join(out, "img", "logo.svg"))
    with open(os.path.join(out, "js", "static-demo.js"), "w", encoding="utf-8") as f:
        f.write(DEMO_JS)

    total = 0
    saved: dict[str, list] = {}
    for role, (email, label, paths) in ROLES.items():
        mapping = {t.replace("{ROHIT}", str(rohit)).replace("{PCID}", pcid): f"{role}/{fname(t.replace('{ROHIT}', 'rohit').replace('{PCID}', 'search'))}"
                   for t in paths}
        paths = list(mapping)
        c = Client(args.base)
        c.login(email)
        os.makedirs(os.path.join(out, role), exist_ok=True)
        for p in paths:
            status, body, _, _ = c.get(p)
            if status != 200:
                print(f"  skip {role} {p} ({status})")
                continue
            body = rewrite(body, mapping, role)
            with open(os.path.join(out, mapping[p]), "w", encoding="utf-8") as f:
                f.write(body)
            total += 1
            saved.setdefault(role, []).append((p.replace(str(rohit), 'rohit').replace(pcid, 'PCID'), mapping[p]))
        print(f"  {role}: {len(paths)} pages")

    login = Client(args.base)
    _, body, _, _ = login.get("/login")
    body = rewrite(body, {}, "")
    body = body.replace('<form method="post" action="#"', '<form method="get" action="index.html" data-static-nav="1"', 1)
    body = body.replace(' required autofocus', ' autofocus').replace(' required autocomplete="current-password"', ' autocomplete="current-password"')
    body = body.replace('name="email"', 'data-x="email"').replace('name="password"', 'data-x="password"')
    with open(os.path.join(out, "login.html"), "w", encoding="utf-8") as f:
        f.write(body)
    write_index(out, saved)
    print(f"Exported {total + 1} HTML pages to {out}")
    return 0


def rewrite(body: str, mapping: dict, role: str) -> str:
    up = "../" if role else ""
    body = re.sub(r'/assets/(css|js|img)/([\w.-]+)\?v=\d+', lambda m: f"{up}{m.group(1)}/{m.group(2)}", body)
    body = re.sub(r'<input type="hidden" name="_csrf" value="[^"]*">', "", body)
    body = re.sub(r'<meta name="csrf-token" content="[^"]*">', "", body)

    def href(m: re.Match) -> str:
        url = m.group(2).replace("&amp;", "&")
        if url.startswith(("http", "#", "mailto:")) or url.startswith(tuple(up + x for x in ("css/", "js/", "img/"))):
            return m.group(0)
        target = mapping.get(url) or mapping.get(url.split("?")[0])
        if url.startswith("/documents/"):
            target = None
        return f'{m.group(1)}="{up}{target}"' if target else f'{m.group(1)}="#"'

    body = re.sub(r'(href)="([^"]*)"', href, body)
    body = re.sub(r'<img class="photo" src="#"[^>]*>', '<div class="photo">RD</div>', body)
    body = re.sub(r'(<form[^>]*?)action="[^"]*"', r'\1action="#"', body)
    body = body.replace("DEMO DATA — all names", "STATIC HTML PREVIEW (no saving) · <a href=\"" + up + "index.html\">all pages</a> · DEMO DATA — all names")
    body = body.replace("</body>", f'<script src="{up}js/static-demo.js"></script>\n</body>')
    return body


def write_index(out: str, saved: dict) -> None:
    rows = []
    for role, pages in saved.items():
        links = ' · '.join(f'<a href="{f}">{p}</a>' for p, f in pages)
        rows.append(f"<tr><td><b>{ROLES[role][1]}</b><br><small class='code'>{ROLES[role][0]}</small></td><td>{links}</td></tr>")
    html = f"""<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ITI CareerTrack — HTML preview</title><link rel="stylesheet" href="css/app.css"></head>
<body class="auth-body"><div class="auth-hero"><div class="auth-hero-inner"><img src="img/logo.svg" width="64" height="64" alt="">
<div><h1>ITI CareerTrack</h1><p>Static HTML + CSS + JavaScript preview of every page (DEMO data)</p></div></div></div>
<main class="auth-main" style="max-width:1200px"><div class="card"><h2>Open a page</h2>
<p class="muted">Each page is a plain .html file. Links and charts work; saving forms needs the real PHP + MySQL website (see README.md).
Start with the <a href="login.html">login page</a> or a dashboard below.</p>
<div class="table-wrap"><table class="table"><tr><th>Role</th><th>Pages</th></tr>{''.join(rows)}</table></div></div></main></body></html>"""
    with open(os.path.join(out, "index.html"), "w", encoding="utf-8") as f:
        f.write(html)


if __name__ == "__main__":
    sys.exit(main())
