#!/usr/bin/env python3
"""ITI CareerTrack — end-to-end acceptance & security tests.

Drives the REAL website over HTTP (like a browser) as different users and checks
the mandatory acceptance tests of the design document (section 15) plus security
rules. The database is only READ to confirm results.

Prerequisites: schema installed, DEMO data seeded (python/seed_demo.py), website running.
Usage:  python3 python/tests/test_e2e.py [--base http://127.0.0.1:8080]
"""
from __future__ import annotations

import argparse
import html
import http.cookiejar
import os
import random
import re
import string
import sys
import urllib.error
import urllib.parse
import urllib.request
import uuid
from datetime import date, timedelta

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
from ctconfig import connect  # noqa: E402

PASSWORD = "Demo@1234"
RESULTS = {"pass": 0, "fail": 0}


def ok(cond: bool, msg: str) -> None:
    if cond:
        RESULTS["pass"] += 1
        print(f"  PASS  {msg}")
    else:
        RESULTS["fail"] += 1
        print(f"  FAIL  {msg}")


def luhn_ok(pcid: str) -> bool:
    m = re.fullmatch(r"[A-Z]+-[A-Z]{2}-(\d{4})-(\d{6})-(\d)", pcid)
    if not m:
        return False
    digits, s, dbl = m.group(1) + m.group(2), 0, True
    for ch in reversed(digits):
        d = int(ch)
        if dbl:
            d = d * 2 - 9 if d * 2 > 9 else d * 2
        s += d
        dbl = not dbl
    return str((10 - s % 10) % 10) == m.group(3)


class Client:
    def __init__(self, base: str):
        self.base = base.rstrip("/")
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))
        self.csrf = ""
        self.body = ""

    def _remember(self, body: str) -> None:
        m = re.search(r'name="csrf-token" content="([^"]+)"', body) or re.search(r'name="_csrf" value="([^"]+)"', body)
        if m:
            self.csrf = m.group(1)

    def request(self, path: str, data: dict | None = None, files: dict | None = None, csrf: bool = True):
        url = self.base + path
        headers = {}
        payload = None
        if data is not None or files:
            fields = dict(data or {})
            if csrf:
                fields.setdefault("_csrf", self.csrf)
            if files:
                boundary = uuid.uuid4().hex
                parts = []
                for k, v in fields.items():
                    for item in (v if isinstance(v, list) else [v]):
                        parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{item}\r\n'.encode())
                for k, (fname, content, ctype) in files.items():
                    parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{fname}"\r\nContent-Type: {ctype}\r\n\r\n'.encode() + content + b"\r\n")
                parts.append(f"--{boundary}--\r\n".encode())
                payload = b"".join(parts)
                headers["Content-Type"] = f"multipart/form-data; boundary={boundary}"
            else:
                payload = urllib.parse.urlencode(fields, doseq=True).encode()
                headers["Content-Type"] = "application/x-www-form-urlencoded"
        req = urllib.request.Request(url, data=payload, headers=headers)
        try:
            with self.opener.open(req) as r:
                raw = r.read()
                self.body = raw.decode("utf-8", "replace")
                self._remember(self.body)
                return r.status, self.body, r.geturl(), raw
        except urllib.error.HTTPError as e:
            raw = e.read()
            self.body = raw.decode("utf-8", "replace")
            self._remember(self.body)
            return e.code, self.body, url, raw

    def get(self, path: str):
        return self.request(path)

    def post(self, path: str, data: dict, **kw):
        return self.request(path, data, **kw)

    def login(self, email: str, password: str = PASSWORD):
        self.get("/login")
        return self.post("/login", {"email": email, "password": password})


def as_user(base: str, email: str) -> Client:
    c = Client(base)
    status, body, url, _ = c.login(email)
    assert "/dashboard" in url or "/follow-ups" in url or "/choose-role" in url, f"login failed for {email}: {url}"
    return c


def flash(body: str) -> str:
    m = re.search(r'<div class="alert alert-(\w+)"[^>]*>(.*?)</div>', body, re.S)
    return html.unescape(m.group(2)).strip() if m else ""


def rand_name(n: int = 6) -> str:
    return random.choice(string.ascii_uppercase) + "".join(random.choice(string.ascii_lowercase) for _ in range(n))


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", default=os.environ.get("CT_BASE_URL", "http://127.0.0.1:8080"))
    base = ap.parse_args().base
    db = connect(autocommit=True)
    cur = db.cursor()

    def one(sql, *p):
        cur.execute(sql, p or None)
        return cur.fetchone()

    def val(sql, *p):
        r = one(sql, *p)
        return list(r.values())[0] if r else None

    today = date.today()
    cur_year = val("SELECT id FROM academic_years WHERE CURDATE() BETWEEN start_date AND end_date")
    pune = one("SELECT * FROM itis WHERE iti_code = 'DEMO0001'")
    nashik = one("SELECT i.* FROM itis i JOIN users u ON u.email = 'principal.nashik@demo.local' JOIN user_role_assignments r ON r.user_id = u.id AND r.iti_id = i.id")
    fitter = val("SELECT id FROM trades WHERE trade_code = 'FIT'")
    teacher_pune = val("SELECT id FROM users WHERE email = 'teacher.pune@demo.local'")
    sahyadri = one("SELECT * FROM companies WHERE legal_name LIKE 'Sahyadri Auto%'")
    other_co = one("SELECT * FROM companies WHERE id <> %s ORDER BY id LIMIT 1", sahyadri["id"])

    print("\n[1] Default deny")
    anon = Client(base)
    _, _, url, _ = anon.get("/students")
    ok(url.endswith("/login"), "Anonymous request to /students is sent to the login page")
    _, body, _, _ = anon.post("/login", {"email": "x@y.z", "password": "bad"}, csrf=False)
    ok("expired" in body.lower() or "419" in body, "POST without CSRF token is rejected (419)")

    print("\n[2] Admission creates ONE PCID (server-side)")
    pp = as_user(base, "principal.pune@demo.local")
    first, last = rand_name(), rand_name()
    ssc_seat = "T" + "".join(random.choice(string.digits) for _ in range(7))
    mobile = "9" + "".join(random.choice(string.digits) for _ in range(9))
    dob = (today - timedelta(days=365 * 17 + 40)).isoformat()
    form = {"first_name": first, "middle_name": "Sanjay", "last_name": last, "gender": "MALE", "date_of_birth": dob, "mobile": mobile,
            "ssc_board": "MSBSHSE", "ssc_seat_number": ssc_seat, "ssc_passing_year": str(today.year - 1), "trade_id": str(fitter),
            "academic_year_id": str(cur_year), "admission_date": today.isoformat(), "consent": "1", "address": "DEMO test address"}
    png = bytes.fromhex("89504e470d0a1a0a0000000d4948445200000001000000010806000000"
                        "1f15c4890000000d49444154789c6360000002000154a24f5d0000000049454e44ae426082")
    _, body, url, _ = pp.post("/admissions", form, files={"photo": ("photo.png", png, "image/png")})
    msg = flash(body)
    m = re.search(r"PCID\): ([A-Z0-9-]+)", msg)
    sid_m = re.search(r"/students/(\d+)", url)
    ok(bool(m and sid_m), f"Admission confirmed with PCID ({msg[:80]})")
    pcid, sid = m.group(1), int(sid_m.group(1))
    ok(luhn_ok(pcid), f"PCID {pcid} has a valid check digit")
    ok(val("SELECT COUNT(*) FROM pcid_registry WHERE student_id = %s", sid) == 1, "Exactly one PCID row for the student")
    ok(val("SELECT COUNT(*) FROM institute_affiliations WHERE student_id = %s AND status = 'ACTIVE' AND iti_id = %s", sid, pune["id"]) == 1,
       "Institute affiliation created automatically")
    photo_id = val("SELECT photo_document_id FROM students WHERE id = %s", sid)
    ok(photo_id is not None, "Photo uploaded and stored as a private document")

    print("\n[3] Duplicate prevention")
    before = val("SELECT COUNT(*) FROM students")
    _, body, _, _ = pp.post("/admissions", dict(form, mobile="9000000001", first_name=first + "x"))
    ok("already exists" in flash(body) and val("SELECT COUNT(*) FROM students") == before,
       "Same SSC seat number -> blocked, no second student/PCID")
    form2 = dict(form, ssc_seat_number="", ssc_board="", ssc_passing_year="")
    _, body, _, _ = pp.post("/admissions", form2)
    ok("very similar student" in flash(body) and val("SELECT COUNT(*) FROM students") == before,
       "Same name + DOB + mobile (no SSC) -> strong duplicate warning, nothing saved")
    twin = dict(form2, first_name=rand_name())
    _, body, url, _ = pp.post("/admissions", twin)
    ok("PCID" in flash(body) or "very similar" in flash(body), "Twin sibling (different first name) handled without hard block")

    print("\n[4] Institute isolation & teacher scope")
    pn = as_user(base, "principal.nashik@demo.local")
    status, body, _, _ = pn.get(f"/students/{sid}")
    ok(status == 404, "Unrelated ITI Principal gets 404 for the student")
    _, body, _, _ = pn.get("/students?q=" + urllib.parse.quote(first))
    ok(pcid not in body and "No students match" in body, "Student does not appear in the other ITI's search")
    tp = as_user(base, "teacher.pune@demo.local")              # Fitter instructor at Pune
    te = as_user(base, "teacher.pune.ele@demo.local")          # Electrician instructor at Pune
    status, body, _, _ = tp.get(f"/students/{sid}")
    ok(status == 200 and "Career Timeline" in body, "Instructor of the student's trade sees the full lifecycle (no assignment needed)")
    status, _, _, _ = te.get(f"/students/{sid}")
    ok(status == 404, "Instructor of ANOTHER trade at the same ITI cannot open the student")
    tn = as_user(base, "teacher.nashik@demo.local")
    status, _, _, _ = tn.get(f"/students/{sid}")
    ok(status == 404, "Instructor of another ITI cannot open the student")
    pp.get(f"/students/{sid}?tab=admissions")
    pp.post(f"/students/{sid}/teachers", {"teacher_user_id": str(teacher_pune), "assignment_role": "TRADE_INSTRUCTOR"})
    tp.get(f"/students/{sid}?tab=contacts")
    ok(mobile in tp.body, "Trade instructor has full access including contact details")
    status, _, _, _ = tp.post(f"/students/{sid}/employments", {"company_id": sahyadri["id"], "designation": "X", "employment_type": "FULL_TIME",
                                                                 "joining_date": today.isoformat()})
    ok(status == 403, "Teacher cannot write employment records (403)")
    tr_id = val("SELECT id FROM training_records WHERE student_id = %s AND completion_status = 'IN_PROGRESS'", sid)
    tp.get("/attendance")
    tp.post("/attendance", {"date": today.isoformat(), f"status[{tr_id}]": "PRESENT", f"status[{tr_id + 99999}]": "PRESENT"})
    ok(val("SELECT COUNT(*) FROM attendance_records WHERE training_record_id = %s", tr_id) == 1,
       "Teacher marked attendance for the assigned student only (foreign id ignored)")
    other_tr = val("SELECT tr.id FROM training_records tr WHERE tr.iti_id = %s AND tr.completion_status = 'IN_PROGRESS'", nashik["id"])
    tp.post("/attendance", {"date": today.isoformat(), f"status[{other_tr}]": "ABSENT"})
    ok(val("SELECT COUNT(*) FROM attendance_records WHERE training_record_id = %s AND attendance_date = CURDATE()", other_tr) == 0,
       "Teacher cannot mark attendance for another ITI's student")

    print("\n[5] Privilege escalation, injection, XSS")
    state_role = val("SELECT id FROM roles WHERE code = 'STATE_ADMIN'")
    pp.get("/users")
    _, body, _, _ = pp.post("/users", {"full_name": "Evil", "email": "evil@demo.local", "password": "Abcdefgh12", "role_id": str(state_role)})
    ok(val("SELECT COUNT(*) FROM users WHERE email = 'evil@demo.local'") == 0, "Principal cannot create a State Admin")
    status, body, _, _ = pp.get("/students?q=" + urllib.parse.quote("' OR 1=1 -- "))
    ok(status == 200 and "No students match" in body, "SQL injection in search is harmless")

    print("\n[6] Transfer without changing PCID")
    pp.get("/transfers?pcid=" + pcid)
    _, body, _, _ = pp.post("/transfers", {"student_id": str(sid), "to_iti_id": str(nashik["id"]), "to_trade_id": str(fitter),
                                           "to_academic_year_id": str(cur_year), "effective_date": today.isoformat(),
                                           "reason": "<script>alert(1)</script> family moved"})
    t_id = val("SELECT id FROM transfer_requests WHERE student_id = %s AND status = 'REQUESTED'", sid)
    ok(t_id is not None, "Origin principal created the transfer request")
    status, body, _, _ = pn.get("/transfers")
    ok("&lt;script&gt;" in body and "<script>alert(1)" not in body, "User text is HTML-escaped (no XSS)")
    status, body, _, _ = pn.get(f"/students/{sid}")
    ok(status == 200 and "Summary view only" in body, "Destination sees only a summary before accepting")
    status, _, _, _ = pp.post(f"/transfers/{t_id}/accept", {})
    ok(val("SELECT status FROM transfer_requests WHERE id = %s", t_id) == "REQUESTED", "Origin principal cannot accept its own outbound request")
    pn.get("/transfers")
    pn.post(f"/transfers/{t_id}/accept", {"batch_id": ""})
    ok(val("SELECT status FROM transfer_requests WHERE id = %s", t_id) == "ACCEPTED", "Destination principal accepted")
    ok(val("SELECT pcid FROM pcid_registry WHERE student_id = %s AND status = 'ACTIVE'", sid) == pcid, "PCID unchanged after transfer")
    ok(val("SELECT COUNT(*) FROM admissions WHERE student_id = %s", sid) == 2 and
       val("SELECT status FROM admissions WHERE student_id = %s AND iti_id = %s", sid, pune["id"]) == "TRANSFERRED_OUT",
       "Old admission kept as TRANSFERRED_OUT, new admission created")
    ok(val("SELECT COUNT(*) FROM attendance_records WHERE training_record_id = %s", tr_id) == 1, "Previous attendance history intact")
    ok(val("SELECT COUNT(*) FROM teacher_assignments WHERE student_id = %s AND end_date IS NULL", sid) == 0, "Old teacher assignment ended")
    status, body, _, _ = pp.get(f"/students/{sid}")
    ok(status == 200 and "Summary view only" in body and mobile not in body, "Origin principal now has summary only (no contacts)")
    status, body, _, _ = tp.get(f"/students/{sid}")
    ok("Summary view only" in body and "Career Timeline" not in body, "Previous teacher keeps summary only")
    status, body, _, _ = pn.get(f"/students/{sid}?tab=admissions")
    ok(status == 200 and "Transfer In" in body, "Destination principal sees full history incl. old admission")

    print("\n[7] Exam attempts, completion")
    cur.execute("SELECT id FROM exam_sessions ORDER BY session_month DESC LIMIT 3")
    sess = [r["id"] for r in cur.fetchall()]
    new_adm = val("SELECT id FROM admissions WHERE student_id = %s AND iti_id = %s", sid, nashik["id"])
    for s_id, res in zip(sess, ["FAIL", "SUPPLEMENTARY", "PASS"]):
        pn.post(f"/students/{sid}/results", {"admission_id": str(new_adm), "exam_session_id": str(s_id), "result": res,
                                             "result_declared_on": today.isoformat(), "marks_obtained": "300", "max_marks": "600"})
    ok(val("SELECT COUNT(*) FROM exam_results WHERE student_id = %s", sid) == 3, "Fail, Supplementary and Pass stored as 3 attempts")
    pn.post(f"/students/{sid}/admission-status", {"status": "COMPLETED", "end_date": today.isoformat(), "reason": "Course completed"})
    ok(val("SELECT status FROM institute_affiliations WHERE admission_id = %s", new_adm) == "COMPLETED", "Completed -> student is an alumnus of ITI Nashik")

    print("\n[8] Multiple pathways, job changes never overwrite")
    po = as_user(base, "placement.nashik@demo.local")
    b_other = val("SELECT id FROM company_branches WHERE company_id = %s LIMIT 1", other_co["id"])
    b_sah = val("SELECT id FROM company_branches WHERE company_id = %s LIMIT 1", sahyadri["id"])
    d = lambda n: (today - timedelta(days=n)).isoformat()  # noqa: E731
    po.post(f"/students/{sid}/apprenticeships", {"company_id": other_co["id"], "branch_id": b_other, "scheme": "NAPS", "start_date": d(200), "stipend_amount": "9000"})
    app1 = val("SELECT MAX(id) FROM apprenticeships WHERE student_id = %s", sid)
    po.post(f"/apprenticeships/{app1}/complete", {"student_id": sid, "end_date": d(150), "status": "COMPLETED"})
    po.post(f"/students/{sid}/apprenticeships", {"company_id": sahyadri["id"], "branch_id": b_sah, "scheme": "APPRENTICES_ACT", "start_date": d(140), "stipend_amount": "9500"})
    app2 = val("SELECT MAX(id) FROM apprenticeships WHERE student_id = %s", sid)
    po.post(f"/apprenticeships/{app2}/complete", {"student_id": sid, "end_date": d(100), "status": "COMPLETED"})
    for i, (co, br, start, end) in enumerate([(other_co, b_other, 90, 60), (sahyadri, b_sah, 59, 30), (sahyadri, b_sah, 29, None)]):
        po.post(f"/students/{sid}/employments", {"company_id": co["id"], "branch_id": br, "designation": f"Technician L{i + 1}", "employment_type": "FULL_TIME",
                                                 "work_location": "DEMO", "joining_date": d(start), "salary_amount": str(15000 + i * 2000), "salary_period": "MONTHLY"})
        if end:
            eid = val("SELECT MAX(id) FROM employments WHERE student_id = %s", sid)
            po.post(f"/employments/{eid}/end", {"student_id": sid, "leaving_date": d(end), "leaving_reason": "Better offer"})
    rows = (cur.execute("SELECT company_id, branch_id, designation, salary_period, joining_date, leaving_date FROM employments WHERE student_id = %s ORDER BY id", (sid,)), cur.fetchall())[1]
    ok(len(rows) == 3 and val("SELECT COUNT(*) FROM apprenticeships WHERE student_id = %s", sid) == 2, "3 jobs + 2 apprenticeships all retained")
    ok(all(r["branch_id"] and r["salary_period"] and r["joining_date"] for r in rows) and rows[0]["leaving_date"] is not None,
       "Branch, designation, salary period, joining/leaving dates stored")
    _, body, _, _ = po.post(f"/students/{sid}/employments", {"company_id": sahyadri["id"], "branch_id": b_other, "designation": "X",
                                                              "employment_type": "FULL_TIME", "joining_date": d(1)})
    ok("does not belong" in flash(body), "Branch of a different company is rejected")
    try:
        cur.execute("UPDATE employments SET company_id = %s WHERE id = (SELECT id FROM (SELECT MIN(id) id FROM employments WHERE student_id = %s) x)", (sahyadri["id"], sid))
        ok(False, "Database blocks re-pointing an old job to another employer")
    except Exception as e:  # noqa: BLE001
        ok("cannot be moved" in str(e), "Database blocks re-pointing an old job to another employer")
    college = val("SELECT id FROM colleges LIMIT 1")
    po.post(f"/students/{sid}/business", {"business_name": "DEMO Repairs", "business_type": "Repair shop", "start_date": d(5)})
    po.post(f"/students/{sid}/higher-education", {"college_id": college, "course_name": "DEMO Diploma", "course_level": "DIPLOMA",
                                                  "study_mode": "PART_TIME", "admission_year": str(today.year)})
    ok(val("SELECT COUNT(*) FROM business_records WHERE student_id = %s", sid) == 1 and
       val("SELECT COUNT(*) FROM higher_education_records WHERE student_id = %s", sid) == 1, "Business and higher-education records added")

    print("\n[9] Not Updated is never Unemployed")
    po.post(f"/students/{sid}/status", {"status": "NOT_UPDATED", "effective_from": today.isoformat(), "note": "test"})
    ok(val("SELECT status FROM v_student_current_status WHERE student_id = %s", sid) == "NOT_UPDATED", "Current status NOT_UPDATED")
    ok(val("SELECT COUNT(*) FROM career_status_history WHERE student_id = %s AND effective_to IS NULL", sid) == 1, "Exactly one current status; history kept")
    pn.get("/reports?type=placement&iti=" + str(nashik["id"]))
    ok("never counted as unemployed" in pn.body, "Report states Not Updated is never counted as unemployed")

    print("\n[10] Follow-ups")
    pn.get("/follow-ups")
    po_id = val("SELECT id FROM users WHERE email = 'placement.nashik@demo.local'")
    pn.post("/follow-ups", {"pcid": pcid, "assigned_to_user_id": str(po_id), "reason": "NO_RECENT_UPDATE", "channel": "PHONE", "due_date": d(-3)})
    _, body, _, _ = po.get("/follow-ups")
    ok(pcid[-8:] in body, "Staff created a follow-up and the assignee sees it")

    print("\n[11] Employer confirmation & employer isolation")
    hr = as_user(base, "hr.sahyadri@demo.local")
    _, body, _, _ = hr.get("/employer/confirmations")
    eid = val("SELECT MAX(id) FROM employments WHERE student_id = %s AND company_id = %s", sid, sahyadri["id"])
    ok(pcid in body, "Employer sees its own pending engagement")
    hr.post("/employer/confirm", {"kind": "employment", "record_id": str(eid), "evidence": "HR/DEMO/42", "decision": "VERIFIED"})
    vr = one("SELECT * FROM verification_records WHERE employment_id = %s ORDER BY id DESC LIMIT 1", eid)
    ok(vr and vr["verifier_company_id"] == sahyadri["id"] and vr["evidence_reference"] == "HR/DEMO/42" and vr["decided_at"],
       "Confirmation stores verifier, company, time and evidence")
    ok(val("SELECT verification_status FROM employments WHERE id = %s", eid) == "VERIFIED", "Job marked VERIFIED")
    other_eid = val("SELECT MIN(id) FROM employments WHERE student_id = %s AND company_id = %s", sid, other_co["id"])
    status, _, _, _ = hr.post("/employer/confirm", {"kind": "employment", "record_id": str(other_eid), "evidence": "x", "decision": "VERIFIED"})
    ok(status == 404, "Employer cannot confirm another company's job")
    stranger = val("SELECT ia.student_id FROM institute_affiliations ia WHERE ia.iti_id = %s AND ia.student_id NOT IN "
                   "(SELECT ja.student_id FROM job_applications ja) AND ia.student_id NOT IN (SELECT student_id FROM employments WHERE company_id = %s) "
                   "AND ia.student_id NOT IN (SELECT student_id FROM apprenticeships WHERE company_id = %s) LIMIT 1", pune["id"], sahyadri["id"], sahyadri["id"])
    status, _, _, _ = hr.get(f"/employer/candidates/{stranger}")
    ok(status == 404, "Employer cannot open a student who never applied / worked there")
    status, _, _, _ = hr.get(f"/students/{stranger}")
    ok(status == 404, "Employer cannot open staff student pages")

    print("\n[12] Reports, documents, audit, student self-service")
    state = as_user(base, "state@demo.local")
    _, body, _, raw = state.get("/reports/export?type=outcome")
    ok("Pathway EVENTS" in body and "Unique" in body, "State report separates unique students from pathway events")
    status, _, _, raw = pn.get(f"/documents/{photo_id}")
    ok(status == 200 and raw.startswith(b"\x89PNG"), "Authorised user can download the private photo")
    status, _, _, _ = pp.get(f"/documents/{photo_id}")
    ok(status == 404, "Origin ITI (summary access) cannot download documents")
    status, _, _, _ = Client(base).get("/storage/documents/")
    ok(status in (403, 404) or "login" in Client(base).get("/storage/documents/")[2], "Storage folder is not publicly reachable")
    ok(val("SELECT COUNT(*) FROM audit_logs WHERE student_id = %s", sid) >= 15, "Sensitive changes are in the audit log")
    try:
        cur.execute("DELETE FROM audit_logs WHERE student_id = %s", (sid,))
        ok(False, "Audit log cannot be deleted")
    except Exception:  # noqa: BLE001
        ok(True, "Audit log cannot be deleted (append-only trigger)")
    rohit = as_user(base, "rohit.deshmukh@demo.local")
    status, _, _, _ = rohit.get(f"/students/{sid}")
    ok(status == 404, "A student cannot open another student's record")
    status, body, _, _ = rohit.get("/me/profile")
    ok(status == 200 and "ITIC-MH-2022" in body, "Student sees own Career Passport")
    dist = as_user(base, "dvto.pune@demo.local")
    status, body, _, _ = dist.get(f"/students/{sid}")
    ok("Summary view only" in body, "Pune DVTO has summary only after the student moved to Nashik district")

    print("\n[13] Account lockout")
    lock = Client(base)
    for _ in range(5):
        lock.login("staff.pune@demo.local", "wrong-password")
    _, body, _, _ = lock.login("staff.pune@demo.local")
    ok("locked" in flash(body).lower(), "Account locks after 5 wrong passwords (even the right password is refused)")
    cur.execute("UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE email = 'staff.pune@demo.local'")

    print("\n[14] Public website, login by role / mobile, PCID card, verification")
    pub = Client(base)
    for path in ["/", "/about", "/courses", "/careers", "/public-reports", "/contact", "/verify", "/login", "/forgot-password"]:
        status, body, _, _ = pub.get(path)
        ok(status == 200 and "ITI CareerTrack" in body, f"Public page {path} loads without login")
    _, body, _, _ = pub.get("/public-reports")
    ok("@demo.local" not in body and "ITIC-MH" not in body, "Public reports show no personal data")
    rohit_pcid = val("SELECT pcid FROM v_student_identity WHERE student_id = (SELECT student_id FROM users WHERE email = 'rohit.deshmukh@demo.local')")
    rohit_dob = str(val("SELECT date_of_birth FROM students WHERE id = (SELECT student_id FROM users WHERE email = 'rohit.deshmukh@demo.local')"))
    _, body, _, _ = pub.get("/verify?pcid=" + rohit_pcid)
    ok("Genuine PCID" in body and "Deshmukh" not in body and rohit_dob not in body, "Verify page: genuine, name masked, no date of birth")
    bad = rohit_pcid[:-1] + str((int(rohit_pcid[-1]) + 1) % 10)
    _, body, _, _ = pub.get("/verify?pcid=" + bad)
    ok("Not a valid PCID" in body, "Mistyped PCID is rejected by the check digit")
    c = Client(base)
    c.get("/login")
    _, body, url, _ = c.post("/login", {"email": "principal.pune@demo.local", "password": PASSWORD, "role": "STATE_ADMIN"})
    ok("/login" in url and "not registered as State Admin" in flash(body), "Login with a role the account does not hold is refused")
    c = Client(base)
    c.get("/login")
    _, _, url, _ = c.post("/login", {"email": "principal.pune@demo.local", "password": PASSWORD, "role": "PRINCIPAL"})
    ok(url.endswith("/dashboard"), "Login with the correct role works")
    pn.get(f"/students/{sid}")
    _, body, _, _ = pn.post(f"/students/{sid}/account", {})
    m = re.search(r"Student login: (\S+)\s+·\s+temporary password: (\S+)", flash(body))
    ok(bool(m), "Principal created the student's own login with a temporary password")
    if m:
        st = Client(base)
        st.get("/login")
        _, body, url, _ = st.post("/login", {"email": m.group(1), "password": m.group(2)})
        ok(url.endswith("/dashboard") and pcid in body, "Student logs in with mobile number + temporary password and sees own PCID")
        status, body, _, _ = st.get("/me/card")
        ok(status == 200 and "data-qr=" in body and "/verify?pcid=" + pcid in html.unescape(body), "Student PCID card has a QR code that links to verification")
    status, _, _, _ = tp.get(f"/students/{sid}/card")
    ok(status == 404, "Previous teacher cannot open the student's PCID card")
    tid = val("SELECT id FROM users WHERE email = 'teacher.pune@demo.local'")
    pp.get("/users")
    _, body, _, _ = pp.post(f"/users/{tid}/reset-password", {})
    m = re.search(r"Temporary password for \S+: (\S+)", flash(body))
    ok(bool(m), "Principal reset a teacher's password")
    if m:
        t2 = Client(base)
        t2.get("/login")
        _, _, url, _ = t2.post("/login", {"email": "teacher.pune@demo.local", "password": m.group(1)})
        ok(url.endswith("/settings"), "Teacher logs in with the temporary password and must change it first")
        cur.execute("UPDATE users SET password_hash = (SELECT h FROM (SELECT password_hash AS h FROM users WHERE email = 'teacher.nashik@demo.local') x) WHERE id = %s", (tid,))
    state_id = val("SELECT id FROM users WHERE email = 'state@demo.local'")
    status, _, _, _ = pp.post(f"/users/{state_id}/reset-password", {})
    ok(status == 404, "Principal cannot reset the State Admin's password")
    fu = val("SELECT id FROM follow_ups WHERE student_id = %s AND status = 'PENDING' LIMIT 1", sid)
    po.get("/follow-ups")
    po.post(f"/follow-ups/{fu}/start", {})
    ok(val("SELECT status FROM follow_ups WHERE id = %s", fu) == "IN_PROGRESS", "Follow-up task moved to In Progress")

    print("\n[15] DVET hierarchy: RO Admin, DVTO, Apprentice Advisor, Instructor trades, User ID login")
    rohit_sid = val("SELECT student_id FROM users WHERE email = 'rohit.deshmukh@demo.local'")
    def uid_login(user_id, password=PASSWORD):
        c = Client(base)
        c.get("/login")
        _, body, url, _ = c.post("/login", {"email": user_id, "password": password})
        return c, url, body
    st_c, url, _ = uid_login("DVET-ADMIN")
    ok(url.endswith("/dashboard"), "State Admin logs in with User ID DVET-ADMIN")
    pr_c, url, _ = uid_login("demo0001")
    ok(url.endswith("/dashboard"), "Principal logs in with the ITI's NCVT MIS code as User ID (case-insensitive)")
    ro_pun, _, _ = uid_login("RO-PUN")
    ro_nsk, _, _ = uid_login("RO-NSK")
    ok(ro_pun.get(f"/students/{rohit_sid}")[0] == 200, "RO Admin (Pune region) has full access to a Pune ITI student")
    ok(ro_nsk.get(f"/students/{rohit_sid}")[0] == 404, "RO Admin of another region (Nashik) cannot open the Pune student")
    ok(ro_nsk.get(f"/students/{sid}")[0] == 200, "RO Admin (Nashik region) sees the student now enrolled at ITI Nashik")
    _, body, _, _ = ro_pun.get("/itis")
    ok("Government ITI, Pune" in body and "Government ITI, Nashik" not in body, "RO Admin's ITI list contains only ITIs of its region")
    aa, _, _ = uid_login("AA-PUNE")
    ok(aa.get(f"/students/{rohit_sid}")[0] == 200 and aa.get(f"/students/{sid}")[0] in (200, 404),
       "Apprentice Advisor has full access to students of the assigned ITIs")
    other = val("SELECT ia.student_id FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id JOIN districts d ON d.id = i.district_id "
                "JOIN regions r ON r.id = d.region_id WHERE r.code = 'NGP' AND ia.status = 'ACTIVE' LIMIT 1")
    ok(aa.get(f"/students/{other}")[0] == 404, "Apprentice Advisor cannot open a student of an ITI not assigned to them")
    dv, _, _ = uid_login("DVTO-PUNE")
    ok(dv.get(f"/students/{rohit_sid}")[0] == 200 and dv.get(f"/students/{other}")[0] == 404, "DVTO sees own district only")
    ok(te.get(f"/students/{rohit_sid}")[0] == 200, "Electrician instructor sees an Electrician ALUMNUS of the ITI (full lifecycle)")

    print("\n[16] State Admin creates user IDs, default password Test@12345, forced change")
    st_c.get("/users")
    region = val("SELECT id FROM regions WHERE code = 'MUM'")
    ro_role = val("SELECT id FROM roles WHERE code = 'RO_ADMIN'")
    _, body, _, _ = st_c.post("/users", {"role_id": str(ro_role), "full_name": "Test RO Mumbai", "username": "ro-test-mum", "region_id": str(region)})
    ok("RO-TEST-MUM" in flash(body) and "Test@12345" in flash(body), "State Admin created an RO Admin with the default password")
    c, url, _ = uid_login("RO-TEST-MUM", "Test@12345")
    ok(url.endswith("/settings"), "New user is sent to Settings to change the default password")
    ok(c.get("/dashboard")[2].endswith("/settings"), "Other pages are blocked until the password is changed")
    c.post("/settings/password", {"current_password": "Test@12345", "new_password": "Mumbai@2026x", "confirm_password": "Mumbai@2026x"})
    ok(c.get("/dashboard")[2].endswith("/dashboard"), "After changing the password the RO Admin reaches the dashboard")
    _, body, _, _ = pp.post("/users", {"role_id": str(ro_role), "full_name": "X", "username": "HACK", "region_id": str(region)})
    ok(val("SELECT COUNT(*) FROM users WHERE username = 'HACK'") == 0, "A Principal cannot create user IDs")
    st_c.get("/itis")
    st_c.post("/itis", {"iti_code": "NCVT9999", "name": "DEMO New ITI", "ownership_type": "GOVERNMENT", "district_id": str(val("SELECT id FROM districts WHERE code = 'PUNE'")),
                        "trades[]": [str(fitter)]})
    st_c.get("/users")
    _, body, _, _ = st_c.post("/users/bulk-iti-logins", {})
    ok("Principal logins created" in flash(body), "State Admin created Principal logins for ITIs without one")
    c, url, _ = uid_login("NCVT9999", "Test@12345")
    ok(url.endswith("/settings"), "New ITI logs in with NCVT MIS code + Test@12345 and must change the password")
    ins = val("SELECT id FROM roles WHERE code = 'TEACHER'")
    _, body, _, _ = st_c.post("/users", {"role_id": str(ins), "full_name": "Test Instructor", "username": "INS-TEST", "iti_id": str(pune["id"]),
                                          "trades[]": [str(val("SELECT id FROM trades WHERE trade_code = 'ELE'"))]})
    c, _, _ = uid_login("INS-TEST", "Test@12345")
    c.post("/settings/password", {"current_password": "Test@12345", "new_password": "Instructor@2026", "confirm_password": "Instructor@2026"})
    ok(c.get(f"/students/{rohit_sid}")[0] == 200 and c.get(f"/students/{sid}")[0] == 404, "New Electrician instructor sees Electrician students only")

    print("\n[17] Employer: full history (view only) + add records for own company")
    sid_new = val("SELECT ia.student_id FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id WHERE i.iti_code = 'DEMO0001' AND ia.status = 'COMPLETED' "
                  "AND ia.student_id NOT IN (SELECT student_id FROM employments WHERE company_id = %s) "
                  "AND ia.student_id NOT IN (SELECT student_id FROM apprenticeships WHERE company_id = %s) "
                  "AND ia.student_id NOT IN (SELECT student_id FROM job_applications) LIMIT 1", sahyadri["id"], sahyadri["id"])
    new_pcid = val("SELECT pcid FROM pcid_registry WHERE student_id = %s AND status = 'ACTIVE'", sid_new)
    new_dob = str(val("SELECT date_of_birth FROM students WHERE id = %s", sid_new))
    ok(hr.get(f"/students/{sid_new}")[0] == 404, "Employer cannot see a student with no link to the company")
    hr.get("/employer/add")
    _, body, _, _ = hr.post("/employer/add", {"kind": "employment", "pcid": new_pcid, "date_of_birth": "2001-01-01", "designation": "Technician",
                                               "start_date": d(3), "amount": "17000"})
    ok("do not match" in flash(body), "Wrong date of birth: employer cannot add a record")
    _, body, url, _ = hr.post("/employer/add", {"kind": "employment", "pcid": new_pcid, "date_of_birth": new_dob, "designation": "Technician",
                                                 "branch_id": str(b_sah), "start_date": d(3), "amount": "17000"})
    e_new = one("SELECT * FROM employments WHERE student_id = %s AND company_id = %s ORDER BY id DESC LIMIT 1", sid_new, sahyadri["id"])
    ok(e_new and e_new["source"] == "EMPLOYER" and e_new["verification_status"] == "VERIFIED", "Employer added a verified job for its own company")
    status, body, _, _ = hr.get(f"/students/{sid_new}?tab=employment")
    ok(status == 200 and "Career Timeline" in body, "Employer now sees the student's full career history")
    status, _, _, _ = hr.post(f"/employments/{e_new['id']}/end", {"student_id": sid_new, "leaving_date": d(1), "leaving_reason": "x"})
    ok(status == 403, "Employer cannot edit/end records (view only)")
    status, _, _, _ = hr.post(f"/students/{sid_new}/employments", {"company_id": other_co["id"], "designation": "X", "employment_type": "FULL_TIME", "joining_date": d(1)})
    ok(status == 403, "Employer cannot add records for another company")

    print("\n[18] Student adds a new company; placement cell approves it")
    st2 = as_user(base, "rohit.deshmukh@demo.local")
    st2.get("/me/update")
    name = "DEMO " + rand_name(5) + " Engineering"
    st2.post("/me/update", {"kind": "employment", "new_company_name": name, "new_company_city": "Pune", "designation": "Senior Technician",
                            "employment_type": "FULL_TIME", "joining_date": d(2), "salary_amount": "24000", "salary_period": "MONTHLY"})
    co = one("SELECT * FROM companies WHERE legal_name = %s", name)
    ok(co is not None and co["status"] == "PENDING_APPROVAL", "Student added a new company (pending approval)")
    ok(co is not None and val("SELECT COUNT(*) FROM employments WHERE company_id = %s AND student_id = %s AND source = 'STUDENT'", co["id"], rohit_sid) == 1,
       "Student's new job is saved at that company (source: student, pending verification)")
    ppo = as_user(base, "placement.pune@demo.local")
    ppo.get("/companies")
    ppo.post(f"/companies/{co['id']}/approve", {})
    ok(val("SELECT status FROM companies WHERE id = %s", co["id"]) == "ACTIVE", "Placement Officer approved the company")

    print(f"\n==== {RESULTS['pass']} passed, {RESULTS['fail']} failed ====")
    return 1 if RESULTS["fail"] else 0


if __name__ == "__main__":
    sys.exit(main())
