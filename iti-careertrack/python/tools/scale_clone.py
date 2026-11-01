"""
Load-test data: multiplies the DEMO student data K times (copies of every student with
their admission, PCID, training, results, career records, contacts and consents), then adds
daily attendance for students in training. Use ONLY on a test database.

    CT_DB_NAME=ct_scale python3 python/tools/scale_clone.py --times 40 --attendance-days 60
"""
from __future__ import annotations

import argparse
import os
import sys
import time

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from ctconfig import connect  # noqa: E402

# Parent tables first. Columns that point at a cloned table are shifted with the same offset.
TABLES = ["students", "admissions", "pcid_registry", "institute_affiliations", "training_records", "apprenticeships",
          "employments", "business_records", "higher_education_records", "career_status_history", "exam_results",
          "student_contacts", "consents"]
SHIFT = {"student_id": "students", "merged_into_student_id": "students", "admission_id": "admissions",
         "previous_admission_id": "admissions", "issued_for_admission_id": "admissions", "redirect_to_pcid_id": "pcid_registry",
         "training_record_id": "training_records", "apprenticeship_id": "apprenticeships", "from_apprenticeship_id": "apprenticeships",
         "employment_id": "employments", "business_record_id": "business_records",
         "higher_education_record_id": "higher_education_records"}
UNIQUE_TEXT = {("students", "ssc_seat_number"), ("pcid_registry", "pcid"), ("exam_results", "certificate_number"),
               ("admissions", "external_admission_ref")}
NULLED = {("students", "aadhaar_hash"), ("students", "photo_document_id"), ("exam_results", "certificate_document_id")}


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--times", type=int, default=40)
    ap.add_argument("--attendance-days", type=int, default=60)
    a = ap.parse_args()
    db = connect(autocommit=True)
    import pymysql
    cur = db.cursor(pymysql.cursors.Cursor)
    cur.execute("SET SESSION foreign_key_checks = 0, unique_checks = 0")
    base, off = {}, {}
    for t in TABLES:
        cur.execute(f"SELECT COALESCE(MAX(id), 0) FROM {t}")
        base[t] = int(cur.fetchone()[0])
        off[t] = max(1_000_000, base[t] * 2)    # leave room
    cols = {}
    for t in TABLES:
        cur.execute("SELECT column_name, extra FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = %s "
                    "ORDER BY ordinal_position", (t,))
        cols[t] = [c for c, extra in cur.fetchall() if "GENERATED" not in (extra or "").upper()]
    started = time.time()
    for k in range(1, a.times + 1):
        for t in TABLES:
            exprs = []
            for c in cols[t]:
                if c == "id":
                    exprs.append(f"id + {k * off[t]}")
                elif c in SHIFT and SHIFT[c] in off:
                    exprs.append(f"{c} + {k * off[SHIFT[c]]}")
                elif (t, c) in NULLED:
                    exprs.append("NULL")
                elif (t, c) in UNIQUE_TEXT:
                    exprs.append(f"CONCAT({c}, 'x{k}')")
                elif t == "pcid_registry" and c == "serial":
                    exprs.append(f"serial + {k * 1_000_000}")
                else:
                    exprs.append(c)
            cur.execute(f"INSERT INTO {t} ({', '.join(cols[t])}) SELECT {', '.join(exprs)} FROM {t} WHERE id <= {base[t]}")
        print(f"copy {k}/{a.times} done ({time.time() - started:.0f}s)", flush=True)

    if a.attendance_days:
        cur.execute("SELECT MIN(id) FROM users")
        marker = cur.fetchone()[0]
        cur.execute("DROP TEMPORARY TABLE IF EXISTS days")
        cur.execute("CREATE TEMPORARY TABLE days (d DATE PRIMARY KEY)")
        cur.executemany("INSERT INTO days VALUES (CURDATE() - INTERVAL %s DAY)", [(i,) for i in range(1, a.attendance_days + 1)])
        cur.execute(f"""INSERT IGNORE INTO attendance_records (student_id, training_record_id, iti_id, attendance_date, session, status, marked_by)
                        SELECT tr.student_id, tr.id, tr.iti_id, days.d, 'FULL_DAY', IF(RAND() < .88, 'PRESENT', 'ABSENT'), {marker}
                          FROM training_records tr JOIN days ON days.d >= tr.start_date
                         WHERE tr.completion_status = 'IN_PROGRESS' AND DAYOFWEEK(days.d) <> 1""")
        print(f"attendance rows added: {cur.rowcount}", flush=True)
    cur.execute("SET SESSION foreign_key_checks = 1, unique_checks = 1")
    for t in TABLES + ["attendance_records"]:
        cur.execute(f"ANALYZE TABLE {t}")
        cur.fetchall()
    cur.execute("SELECT (SELECT COUNT(*) FROM students), (SELECT COUNT(*) FROM training_records WHERE completion_status = 'IN_PROGRESS'), "
                "(SELECT COUNT(*) FROM attendance_records), (SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024) "
                "FROM information_schema.tables WHERE table_schema = DATABASE())")
    s, cur_n, att, mb = cur.fetchone()
    print(f"students={s} in_training={cur_n} attendance={att} size_mb={mb} total_time={time.time() - started:.0f}s")
    return 0


if __name__ == "__main__":
    sys.exit(main())
