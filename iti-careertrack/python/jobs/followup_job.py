#!/usr/bin/env python3
"""Nightly follow-up job.

Finds alumni whose career information is stale (no status change for N months,
or status NOT_UPDATED) and creates a follow-up task for their ITI's placement
officer (or principal if the ITI has none).

RULE: this job NEVER changes anyone's career status. "No information" is not
"unemployed" — it only creates a task for a human to call the student.

Usage:
    python3 python/jobs/followup_job.py --dry-run     # show what would be created
    python3 python/jobs/followup_job.py               # create tasks
Schedule (Linux cron, 1:30 AM daily):
    30 1 * * * cd /path/to/iti-careertrack && python3 python/jobs/followup_job.py >> app/storage/logs/followup_job.log 2>&1
"""
from __future__ import annotations

import argparse
import json
import os
import sys
from datetime import date, timedelta

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from ctconfig import connect  # noqa: E402

STALE_SQL = """
SELECT ia.student_id, ia.iti_id, cs.status, cs.effective_from
  FROM institute_affiliations ia
  JOIN v_student_current_status cs ON cs.student_id = ia.student_id
 WHERE ia.status = 'COMPLETED'
   AND ia.end_date = (SELECT MAX(x.end_date) FROM institute_affiliations x WHERE x.student_id = ia.student_id AND x.status = 'COMPLETED')
   AND NOT EXISTS (SELECT 1 FROM institute_affiliations a2 WHERE a2.student_id = ia.student_id AND a2.status = 'ACTIVE')
   AND (cs.status = 'NOT_UPDATED' OR cs.effective_from < CURDATE() - INTERVAL %s MONTH)
   AND cs.status <> 'NOT_CONTACTABLE'
   AND NOT EXISTS (SELECT 1 FROM follow_ups f WHERE f.student_id = ia.student_id AND f.status IN ('PENDING','IN_PROGRESS'))
   AND NOT EXISTS (SELECT 1 FROM follow_ups f WHERE f.student_id = ia.student_id AND f.created_at > NOW() - INTERVAL 30 DAY)
 LIMIT %s
"""

ASSIGNEE_SQL = """
SELECT ura.user_id FROM user_role_assignments ura JOIN roles r ON r.id = ura.role_id
 WHERE ura.iti_id = %s AND ura.status = 'ACTIVE' AND r.code IN ('PLACEMENT_OFFICER','PRINCIPAL')
   AND CURDATE() >= ura.valid_from AND (ura.valid_to IS NULL OR CURDATE() <= ura.valid_to)
 ORDER BY r.code = 'PLACEMENT_OFFICER' DESC, ura.id LIMIT 1
"""


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--limit", type=int, default=5000, help="max tasks per run")
    args = ap.parse_args()

    conn = connect()
    with conn.cursor() as cur:
        cur.execute("SELECT setting_value AS v FROM settings WHERE setting_key = 'stale_update_months'")
        row = cur.fetchone()
        months = int(row["v"]) if row else 6
        cur.execute(STALE_SQL, (months, args.limit))
        stale = cur.fetchall()
        created, skipped = 0, 0
        assignees: dict[int, int | None] = {}
        due = date.today() + timedelta(days=7)
        for s in stale:
            iti = s["iti_id"]
            if iti not in assignees:
                cur.execute(ASSIGNEE_SQL, (iti,))
                r = cur.fetchone()
                assignees[iti] = r["user_id"] if r else None
            if not assignees[iti]:
                skipped += 1
                continue
            if not args.dry_run:
                cur.execute("""INSERT INTO follow_ups (student_id, iti_id, assigned_to_user_id, reason, due_date, channel)
                               VALUES (%s, %s, %s, 'NO_RECENT_UPDATE', %s, 'PHONE')""", (s["student_id"], iti, assignees[iti], due))
                cur.execute("""INSERT INTO audit_logs (action, entity_table, entity_id, student_id, iti_id, after_data, reason)
                               VALUES ('SYSTEM_FOLLOWUP', 'follow_ups', %s, %s, %s, %s, %s)""",
                            (cur.lastrowid, s["student_id"], iti, json.dumps({"status": s["status"], "since": str(s["effective_from"])}),
                             f"No career update for {months}+ months (status left unchanged)"))
            created += 1
        if not args.dry_run:
            conn.commit()
    print(f"[followup_job] stale threshold={months} months, candidates={len(stale)}, "
          f"{'would create' if args.dry_run else 'created'}={created}, skipped(no staff)={skipped}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
