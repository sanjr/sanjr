"""
Writes the list of all logins to an Excel file and (optionally) checks that every one of them
can really log in on the running website.

    python3 python/tools/export_logins.py --out LOGINS.xlsx --password Demo@1234 --check http://127.0.0.1:8080
"""
from __future__ import annotations

import argparse
import http.cookiejar
import os
import re
import sys
import urllib.parse
import urllib.request
from concurrent.futures import ThreadPoolExecutor

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from ctconfig import connect  # noqa: E402

SQL = """
SELECT u.username, u.email, u.full_name, r.code AS role_code, r.name AS role_name,
       COALESCE(rg.name, rgd.name, rgi.name) AS region, COALESCE(d.name, di.name) AS district,
       i.iti_code, i.name AS iti, c.legal_name AS company
  FROM users u
  JOIN user_role_assignments ura ON ura.user_id = u.id AND ura.status = 'ACTIVE'
  JOIN roles r ON r.id = ura.role_id
  LEFT JOIN regions rg ON rg.id = ura.region_id
  LEFT JOIN districts d ON d.id = ura.district_id
  LEFT JOIN regions rgd ON rgd.id = d.region_id
  LEFT JOIN itis i ON i.id = ura.iti_id
  LEFT JOIN districts di ON di.id = i.district_id
  LEFT JOIN regions rgi ON rgi.id = di.region_id
  LEFT JOIN companies c ON c.id = ura.company_id
 WHERE u.status = 'ACTIVE'
 ORDER BY FIELD(r.code,'STATE_ADMIN','RO_ADMIN','DVTO','APPRENTICE_ADVISOR','PRINCIPAL','TEACHER','PLACEMENT_OFFICER','ITI_STAFF','EMPLOYER','STUDENT'),
          region, district, i.iti_code, u.username
"""
SHEETS = [("State & RO", {"STATE_ADMIN", "RO_ADMIN"}), ("DVTO (district)", {"DVTO"}), ("Principals (ITI)", {"PRINCIPAL"}),
          ("Other roles", {"APPRENTICE_ADVISOR", "TEACHER", "PLACEMENT_OFFICER", "ITI_STAFF", "EMPLOYER", "STUDENT"})]


def try_login(base: str, login: str, password: str) -> tuple[str, bool, str]:
    jar = http.cookiejar.CookieJar()
    op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    html = op.open(base + "/login", timeout=30).read().decode()
    token = re.search(r'name="_csrf" value="([^"]+)"', html).group(1)
    data = urllib.parse.urlencode({"_csrf": token, "email": login, "password": password, "role": ""}).encode()
    resp = op.open(base + "/login", data=data, timeout=30)
    url = resp.geturl()
    ok = any(p in url for p in ("/dashboard", "/follow-ups", "/choose-role", "/settings"))
    return login, ok, url.replace(base, "")


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--out", default="LOGINS.xlsx")
    ap.add_argument("--password", default="Demo@1234")
    ap.add_argument("--check", help="website base URL: log in with every account")
    ap.add_argument("--skip-students", action="store_true", help="leave student logins out of the file")
    a = ap.parse_args()
    db = connect()
    with db.cursor() as cur:
        cur.execute(SQL)
        rows = cur.fetchall()
    if a.skip_students:
        rows = [r for r in rows if r["role_code"] != "STUDENT"]

    results = {}
    if a.check:
        logins = sorted({r["username"] or r["email"] for r in rows})
        with ThreadPoolExecutor(max_workers=4) as ex:
            for login, ok, url in ex.map(lambda l: try_login(a.check.rstrip("/"), l, a.password), logins):
                results[login] = (ok, url)
        bad = [k for k, (ok, _) in results.items() if not ok]
        print(f"checked {len(results)} logins: {len(results) - len(bad)} OK, {len(bad)} failed")
        for b in bad[:20]:
            print("  FAILED", b, results[b][1])

    from openpyxl import Workbook
    from openpyxl.styles import Alignment, Font, PatternFill
    wb = Workbook()
    wb.remove(wb.active)
    head_fill = PatternFill("solid", fgColor="0F3D8A")
    for title, codes in SHEETS:
        ws = wb.create_sheet(title)
        ws.append(["User ID (login)", "Password", "Role", "Region", "District", "NCVT MIS code", "ITI / Company", "Name"]
                  + (["Login checked"] if a.check else []))
        for c in ws[1]:
            c.font = Font(bold=True, color="FFFFFF")
            c.fill = head_fill
            c.alignment = Alignment(vertical="center")
        for r in rows:
            if r["role_code"] not in codes:
                continue
            login = r["username"] or r["email"]
            line = [login, a.password, r["role_name"], (r["region"] or "").replace(" Region", ""), r["district"] or "",
                    r["iti_code"] or "", r["iti"] or r["company"] or "", r["full_name"]]
            if a.check:
                line.append("OK" if results.get(login, (False,))[0] else "FAILED")
            ws.append(line)
        for col, w in zip("ABCDEFGHI", (30, 12, 20, 22, 26, 16, 52, 40, 14)):
            ws.column_dimensions[col].width = w
        ws.freeze_panes = "A2"
        ws.auto_filter.ref = ws.dimensions
    wb.save(a.out)
    print(f"wrote {a.out}: " + ", ".join(f"{ws.title} {ws.max_row - 1}" for ws in wb.worksheets))
    return 0 if not results or all(ok for ok, _ in results.values()) else 1


if __name__ == "__main__":
    sys.exit(main())
