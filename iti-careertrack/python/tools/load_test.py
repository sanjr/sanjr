"""
Load test: N virtual users log in at the same time and keep opening the pages of their role.

    python3 python/tools/load_test.py --base http://127.0.0.1:8181/ITI-CareerTrack/app/public --users 200 --seconds 60

Prints requests per second, response-time percentiles and errors per page type.
"""
from __future__ import annotations

import argparse
import http.cookiejar
import random
import re
import statistics
import threading
import time
import urllib.parse
import urllib.request
from collections import defaultdict

PAGES = {
    "DVET-ADMIN": ["/dashboard", "/students", "/itis", "/reports", "/verifications", "/students?page=7"],
    "RO-PUN": ["/dashboard", "/students", "/itis", "/reports?type=outcome", "/apprenticeships"],
    "DVTO-PUNE": ["/dashboard", "/students", "/itis", "/reports?type=placement", "/verifications"],
    "AA-PUNE": ["/dashboard", "/apprenticeships", "/companies", "/students"],
    "DEMO0001": ["/dashboard", "/students", "/placements", "/results", "/follow-ups", "/trades"],
    "teacher.pune@demo.local": ["/dashboard", "/students", "/attendance", "/results", "/training"],
    "placement.pune@demo.local": ["/dashboard", "/companies", "/placements", "/apprenticeships"],
    "staff.pune@demo.local": ["/dashboard", "/follow-ups"],
    "hr.sahyadri@demo.local": ["/dashboard", "/employer/hired", "/employer/applications"],
    "rohit.deshmukh@demo.local": ["/dashboard", "/me/profile", "/me/card", "/jobs", "/me/employment"],
}
# Accounts that use the same pages as a template key (spread the load over many ITIs / districts like real use)
POOL = {"DEMO0001": [f"DEMO{n:04d}" for n in range(1, 47)],
        "DVTO-PUNE": ["DVTO-PUNE", "DVTO-NASHIK", "DVTO-SATARA", "DVTO-THANE", "DVTO-NAGPUR", "DVTO-LATUR", "DVTO-AKOLA", "DVTO-JALGAON"],
        "RO-PUN": ["RO-PUN", "RO-MUM", "RO-NSK", "RO-CSN", "RO-AMR", "RO-NGP"]}
PUBLIC = ["/", "/verify?pcid=ITIC-MH-2022-001077-9", "/careers", "/public-reports"]

lock = threading.Lock()
times: dict[str, list[float]] = defaultdict(list)
errors: dict[str, int] = defaultdict(int)


def record(kind: str, ms: float, ok: bool) -> None:
    with lock:
        times[kind].append(ms)
        if not ok:
            errors[kind] += 1


def get(op, url: str, data: bytes | None = None):
    t0 = time.perf_counter()
    try:
        r = op.open(url, data=data, timeout=60)
        body = r.read()
        return (time.perf_counter() - t0) * 1000, r.status == 200, body.decode("utf-8", "replace"), r.geturl()
    except Exception:  # noqa: BLE001
        return (time.perf_counter() - t0) * 1000, False, "", ""


def user(base: str, template: str, password: str, until: float, public: bool) -> None:
    login = random.choice(POOL.get(template, [template]))
    jar = http.cookiejar.CookieJar()
    op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    if public:
        while time.time() < until:
            p = random.choice(PUBLIC)
            ms, ok, body, _ = get(op, base + p)
            record("public " + p.split("?")[0], ms, ok and "Fatal error" not in body)
            time.sleep(random.uniform(0.5, 2))
        return
    ms, ok, html, _ = get(op, base + "/login")
    m = re.search(r'name="_csrf" value="([^"]+)"', html)
    if not m:
        record("login", ms, False)
        return
    data = urllib.parse.urlencode({"_csrf": m.group(1), "email": login, "password": password, "role": ""}).encode()
    ms, ok, _, url = get(op, base + "/login", data)
    record("login", ms, ok and "/login" not in url)
    while time.time() < until:
        p = random.choice(PAGES[template])
        ms, ok, body, _ = get(op, base + p)
        record(p.split("?")[0], ms, ok and "Fatal error" not in body)
        time.sleep(random.uniform(1, 3))   # think time: a person reads the page


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", required=True)
    ap.add_argument("--users", type=int, default=100)
    ap.add_argument("--seconds", type=int, default=60)
    ap.add_argument("--password", default="Demo@1234")
    ap.add_argument("--public-share", type=float, default=0.2, help="share of visitors on the public website")
    a = ap.parse_args()
    base = a.base.rstrip("/")
    logins = list(PAGES)
    until = time.time() + a.seconds
    threads = []
    for i in range(a.users):
        t = threading.Thread(target=user, args=(base, logins[i % len(logins)], a.password, until, random.random() < a.public_share), daemon=True)
        threads.append(t)
        t.start()
    started = time.time()
    for t in threads:
        t.join(a.seconds + 120)
    elapsed = time.time() - started
    total = sum(len(v) for v in times.values())
    allt = sorted(x for v in times.values() for x in v)
    pct = lambda xs, q: xs[min(len(xs) - 1, int(len(xs) * q))] if xs else 0  # noqa: E731
    print(f"virtual users={a.users} duration={elapsed:.0f}s requests={total} throughput={total / elapsed:.1f} req/s "
          f"errors={sum(errors.values())}")
    print(f"ALL  p50={pct(allt, .5):.0f}ms p95={pct(allt, .95):.0f}ms p99={pct(allt, .99):.0f}ms max={allt[-1] if allt else 0:.0f}ms")
    for k in sorted(times, key=lambda k: -statistics.median(times[k])):
        v = sorted(times[k])
        print(f"  {k:28s} n={len(v):5d} p50={pct(v, .5):6.0f}ms p95={pct(v, .95):6.0f}ms errors={errors[k]}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
