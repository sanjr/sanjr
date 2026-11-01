#!/usr/bin/env python3
"""Offline report export (CSV, opens in Excel) for the State / District office.

Uses the SAME definitions as the website:
  * Alumni   = students whose admission was COMPLETED
  * Placed   = current status Employed, Apprentice or Self-employed
  * Not Updated / Not Contactable are counted separately — NEVER as unemployed
  * Unique students (PCIDs) are reported separately from pathway events (job records)

Usage:
    python3 python/reports/export_report.py --out placement.csv
    python3 python/reports/export_report.py --district Pune --year 2023-24 --out pune.csv
"""
from __future__ import annotations

import argparse
import csv
import os
import sys
from datetime import datetime

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from ctconfig import connect  # noqa: E402

SQL = """
SELECT d.name AS district, i.name AS iti, t.name AS trade,
       COUNT(DISTINCT a.student_id) AS alumni_unique,
       COUNT(DISTINCT CASE WHEN cs.status IN ('EMPLOYED','APPRENTICE','SELF_EMPLOYED') THEN a.student_id END) AS placed,
       COUNT(DISTINCT CASE WHEN cs.status IN ('EMPLOYED','APPRENTICE','SELF_EMPLOYED') AND cs.verification_status = 'VERIFIED' THEN a.student_id END) AS placed_verified,
       COUNT(DISTINCT CASE WHEN cs.status = 'HIGHER_EDUCATION' THEN a.student_id END) AS higher_education,
       COUNT(DISTINCT CASE WHEN cs.status = 'SEEKING_EMPLOYMENT' THEN a.student_id END) AS seeking_employment,
       COUNT(DISTINCT CASE WHEN cs.status IN ('NOT_UPDATED','NOT_CONTACTABLE') OR cs.status IS NULL THEN a.student_id END) AS not_updated,
       (SELECT COUNT(*) FROM employments e WHERE e.voided_at IS NULL AND e.student_id IN
            (SELECT a2.student_id FROM admissions a2 WHERE a2.iti_id = i.id AND a2.trade_id = t.id AND a2.status = 'COMPLETED')) AS job_records_events
  FROM admissions a
  JOIN itis i ON i.id = a.iti_id JOIN districts d ON d.id = i.district_id JOIN trades t ON t.id = a.trade_id
  JOIN academic_years ay ON ay.id = a.academic_year_id
  LEFT JOIN v_student_current_status cs ON cs.student_id = a.student_id
 WHERE a.status = 'COMPLETED' {where}
 GROUP BY d.name, i.id, i.name, t.id, t.name
 ORDER BY d.name, i.name, t.name
"""


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--district", help="district name, e.g. Pune")
    ap.add_argument("--year", help="intake academic year label, e.g. 2023-24")
    ap.add_argument("--out", default="placement_report.csv")
    args = ap.parse_args()

    where, params = "", []
    if args.district:
        where += " AND d.name = %s"
        params.append(args.district)
    if args.year:
        where += " AND ay.label = %s"
        params.append(args.year)
    with connect() as conn, conn.cursor() as cur:
        cur.execute(SQL.format(where=where), params)
        rows = cur.fetchall()
    with open(args.out, "w", newline="", encoding="utf-8-sig") as f:
        w = csv.writer(f)
        w.writerow(["ITI CareerTrack – Placement report", args.district or "Maharashtra", args.year or "all intake years"])
        w.writerow(["Generated", datetime.now().strftime("%Y-%m-%d %H:%M"), "Denominator", "unique alumni (completed course)"])
        w.writerow(["Note", "Not updated is shown separately and never counted as unemployed. job_records_events counts records, not students."])
        w.writerow([])
        if rows:
            w.writerow(list(rows[0].keys()) + ["placement_rate_%"])
            for r in rows:
                rate = round(r["placed"] * 100 / r["alumni_unique"], 1) if r["alumni_unique"] else 0
                w.writerow(list(r.values()) + [rate])
    total = sum(r["alumni_unique"] for r in rows)
    placed = sum(r["placed"] for r in rows)
    print(f"Wrote {len(rows)} rows to {args.out} — alumni {total}, placed {placed} ({placed * 100 / total if total else 0:.1f}%)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
