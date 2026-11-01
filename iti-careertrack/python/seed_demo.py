#!/usr/bin/env python3
"""ITI CareerTrack - synthetic DEMO data generator.

Fills an EMPTY CareerTrack database (schema already installed) with clearly
synthetic data so every dashboard has something to show. All ITIs, companies,
users and students are flagged is_demo = 1, and every login e-mail ends in
@demo.local. Company and college names are fictional.

PCIDs are NOT generated here: inserting an admission makes the database
trigger issue the PCID, which is exactly what happens in production.

Usage:
    python3 python/seed_demo.py            # refuses to run if students exist
    python3 python/seed_demo.py --force    # seed anyway (still empty DB expected)

Demo password for every account: Demo@1234
"""
from __future__ import annotations

import argparse
import random
import shutil
import subprocess
import sys
from datetime import date, timedelta

from ctconfig import connect

DEMO_PASSWORD = "Demo@1234"
TODAY = date.today()
RNG = random.Random(2026)

DISTRICTS = [
    ("PUNE", "Pune", "पुणे", "Pune"), ("MUMBAI", "Mumbai City", "मुंबई शहर", "Konkan"),
    ("MUMSUB", "Mumbai Suburban", "मुंबई उपनगर", "Konkan"), ("THANE", "Thane", "ठाणे", "Konkan"),
    ("PALGHAR", "Palghar", "पालघर", "Konkan"), ("RAIGAD", "Raigad", "रायगड", "Konkan"),
    ("RATNAGIRI", "Ratnagiri", "रत्नागिरी", "Konkan"), ("SINDHU", "Sindhudurg", "सिंधुदुर्ग", "Konkan"),
    ("NASHIK", "Nashik", "नाशिक", "Nashik"), ("AHMEDNAGAR", "Ahilyanagar", "अहिल्यानगर", "Nashik"),
    ("DHULE", "Dhule", "धुळे", "Nashik"), ("JALGAON", "Jalgaon", "जळगाव", "Nashik"),
    ("NANDURBAR", "Nandurbar", "नंदुरबार", "Nashik"), ("SATARA", "Satara", "सातारा", "Pune"),
    ("SANGLI", "Sangli", "सांगली", "Pune"), ("KOLHAPUR", "Kolhapur", "कोल्हापूर", "Pune"),
    ("SOLAPUR", "Solapur", "सोलापूर", "Pune"), ("CSN", "Chhatrapati Sambhajinagar", "छत्रपती संभाजीनगर", "Chh. Sambhajinagar"),
    ("JALNA", "Jalna", "जालना", "Chh. Sambhajinagar"), ("BEED", "Beed", "बीड", "Chh. Sambhajinagar"),
    ("DHARASHIV", "Dharashiv", "धाराशिव", "Chh. Sambhajinagar"), ("LATUR", "Latur", "लातूर", "Chh. Sambhajinagar"),
    ("NANDED", "Nanded", "नांदेड", "Chh. Sambhajinagar"), ("PARBHANI", "Parbhani", "परभणी", "Chh. Sambhajinagar"),
    ("HINGOLI", "Hingoli", "हिंगोली", "Chh. Sambhajinagar"), ("AMRAVATI", "Amravati", "अमरावती", "Amravati"),
    ("AKOLA", "Akola", "अकोला", "Amravati"), ("WASHIM", "Washim", "वाशिम", "Amravati"),
    ("BULDHANA", "Buldhana", "बुलढाणा", "Amravati"), ("YAVATMAL", "Yavatmal", "यवतमाळ", "Amravati"),
    ("NAGPUR", "Nagpur", "नागपूर", "Nagpur"), ("WARDHA", "Wardha", "वर्धा", "Nagpur"),
    ("BHANDARA", "Bhandara", "भंडारा", "Nagpur"), ("GONDIA", "Gondia", "गोंदिया", "Nagpur"),
    ("CHANDRAPUR", "Chandrapur", "चंद्रपूर", "Nagpur"), ("GADCHIROLI", "Gadchiroli", "गडचिरोली", "Nagpur"),
]

TRADES = [
    ("FIT", "Fitter", "फिटर", 24, ["Safety & Hand Tools", "Marking & Measuring", "Filing & Fitting", "Drilling & Tapping", "Assembly Practice", "Machine Maintenance"]),
    ("ELE", "Electrician", "वीजतंत्री", 24, ["Electrical Safety", "Wiring Practice", "DC & AC Circuits", "Transformers", "Motors & Starters", "Domestic Installation"]),
    ("WEL", "Welder", "वेल्डर", 12, ["Welding Safety", "Gas Welding", "Arc Welding", "Joint Preparation", "Pipe Welding", "Inspection & Testing"]),
    ("COPA", "COPA", "कोपा", 12, ["Computer Fundamentals", "Office Applications", "Networking Basics", "Web Design", "Database Basics", "Accounting Software"]),
    ("MMV", "Mechanic Motor Vehicle", "मोटार मेकॅनिक", 24, ["Workshop Safety", "Engine Systems", "Fuel Systems", "Transmission", "Brakes & Steering", "Vehicle Electricals"]),
    ("TUR", "Turner", "टर्नर", 24, ["Lathe Safety", "Plain Turning", "Taper Turning", "Thread Cutting", "Grinding", "CNC Basics"]),
    ("ELM", "Electronics Mechanic", "इलेक्ट्रॉनिक्स मेकॅनिक", 24, ["Components", "Soldering", "Power Supplies", "Digital Electronics", "Microcontrollers", "Troubleshooting"]),
    ("PLU", "Plumber", "प्लंबर", 12, ["Plumbing Safety", "Pipe Fitting", "Sanitary Fittings", "Water Supply Systems", "Drainage", "Estimation"]),
]

COMPANIES = [
    ("Sahyadri Auto Components Pvt Ltd", "Automotive", "LARGE", "Pune"),
    ("Deccan Engineering Works", "Engineering", "MEDIUM", "Pune"),
    ("Konkan Power Systems Ltd", "Power", "LARGE", "Thane"),
    ("Godavari Steel Fabricators", "Fabrication", "MEDIUM", "Nashik"),
    ("Western Ghats Electricals", "Electrical", "SMALL", "Kolhapur"),
    ("Shivneri Motors", "Automotive", "MEDIUM", "Pune"),
    ("Indrayani Tools Ltd", "Tooling", "MEDIUM", "Pune"),
    ("Krishna Valley Plastics", "Manufacturing", "SMALL", "Sangli"),
    ("Vidarbha Infra Projects", "Construction", "LARGE", "Nagpur"),
    ("Pune Precision Machining", "Engineering", "SMALL", "Pune"),
    ("Nashik Agro Equipments", "Agri Machinery", "MEDIUM", "Nashik"),
    ("Mula-Mutha Technologies", "IT Services", "MEDIUM", "Pune"),
    ("Tapi Textile Machinery", "Textile", "MEDIUM", "Jalgaon"),
    ("Bhima Automotive Services", "Automotive", "SMALL", "Solapur"),
    ("Sinhagad Digital Services", "IT Services", "SMALL", "Pune"),
]

COLLEGES = [("Krishna Valley Polytechnic", "Demo State Board"), ("Sahyadri Institute of Technology", "Demo University"),
            ("Godavari Open Learning Centre", "Demo Open University")]

MALE = ["Rohit", "Amit", "Rahul", "Sagar", "Akash", "Pratik", "Omkar", "Sachin", "Vishal", "Nikhil", "Ganesh", "Aniket",
        "Sandesh", "Tushar", "Mahesh", "Sanket", "Yash", "Aditya", "Shubham", "Kunal", "Prathamesh", "Swapnil", "Vaibhav", "Rushikesh"]
FEMALE = ["Pooja", "Sneha", "Priya", "Komal", "Shruti", "Pallavi", "Rutuja", "Sayali", "Ankita", "Snehal", "Prajakta", "Neha",
          "Aishwarya", "Vaishnavi", "Tejaswini", "Dipali"]
SURNAMES = ["Patil", "Jadhav", "Shinde", "Pawar", "Deshmukh", "Kale", "More", "Kulkarni", "Gaikwad", "Chavan", "Joshi",
            "Bhosale", "Salunkhe", "Mane", "Kadam", "Thorat", "Sawant", "Pathak", "Wagh", "Shelke", "Kamble", "Mohite", "Nikam", "Bhagat"]
FATHERS = ["Sanjay", "Ramesh", "Suresh", "Prakash", "Vijay", "Dilip", "Anil", "Ashok", "Balasaheb", "Dattatray", "Shivaji", "Vilas"]

# Which ITIs get created: (district code, slug, ownership, trade codes, students per batch, units)
MAIN_ITIS = [
    ("PUNE", "pune", "GOVERNMENT", ["FIT", "ELE", "WEL", "COPA", "MMV"], 16, 2),
    ("NASHIK", "nashik", "GOVERNMENT", ["FIT", "ELE", "WEL", "TUR"], 9, 1),
    ("SATARA", "satara", "GOVERNMENT", ["FIT", "ELE", "WEL", "COPA"], 8, 1),
    ("KOLHAPUR", "kolhapur", "GOVERNMENT", ["FIT", "ELE", "MMV", "COPA"], 8, 1),
]
PRIVATE_DISTRICTS = ["PUNE", "THANE", "NAGPUR", "NASHIK", "CSN", "SOLAPUR", "KOLHAPUR", "SANGLI", "AMRAVATI", "JALGAON"]


def demo_password_hash() -> str:
    """Hash with PHP itself so the website verifies it with password_verify()."""
    php = shutil.which("php")
    if not php:
        sys.exit("PHP CLI is required to create demo password hashes (php not found on PATH).")
    algo = 'defined("PASSWORD_ARGON2ID") ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT'
    out = subprocess.run([php, "-r", f'echo password_hash($argv[1], {algo});', DEMO_PASSWORD],
                         check=True, capture_output=True, text=True)
    return out.stdout.strip()


class Seeder:
    def __init__(self, conn):
        self.conn = conn
        self.cur = conn.cursor()
        self.pw = demo_password_hash()
        self.ssc_serial = 100000
        self.mobile_serial = 0
        self.role_ids: dict[str, int] = {}
        self.district_ids: dict[str, int] = {}
        self.trade_ids: dict[str, int] = {}
        self.trade_duration: dict[str, int] = {}
        self.module_ids: dict[str, list[int]] = {}
        self.ay: dict[int, dict] = {}            # start year -> row
        self.sessions: dict[tuple, int] = {}     # (type, date) -> id
        self.companies: list[dict] = []
        self.college_ids: list[int] = []
        self.itis: list[dict] = []
        self.counts = {"students": 0, "employments": 0, "apprenticeships": 0}

    # ------------------------------------------------------------------ helpers
    def ins(self, table: str, row: dict) -> int:
        cols = ", ".join(f"`{c}`" for c in row)
        marks = ", ".join(["%s"] * len(row))
        self.cur.execute(f"INSERT INTO `{table}` ({cols}) VALUES ({marks})", list(row.values()))
        return self.cur.lastrowid

    def user(self, name: str, email: str, role: str, iti_id=None, district_id=None, company_id=None, student_id=None) -> int:
        uid = self.ins("users", {"full_name": name, "email": email, "password_hash": self.pw, "status": "ACTIVE",
                                 "student_id": student_id, "is_demo": 1})
        scope = {"STATE_ADMIN": "STATE", "DISTRICT_ADMIN": "DISTRICT", "EMPLOYER": "COMPANY", "STUDENT": "SELF"}.get(role, "ITI")
        self.ins("user_role_assignments", {"user_id": uid, "role_id": self.role_ids[role], "scope_type": scope,
                                           "district_id": district_id, "iti_id": iti_id, "company_id": company_id,
                                           "valid_from": "2020-01-01"})
        return uid

    def mobile(self) -> str:
        self.mobile_serial += 1
        return f"9{RNG.randint(1, 9)}{self.mobile_serial:08d}"

    # ------------------------------------------------------------------ master data
    def masters(self):
        self.cur.execute("SELECT id, code FROM roles")
        self.role_ids = {r["code"]: r["id"] for r in self.cur.fetchall()}
        for code, name, name_mr, division in DISTRICTS:
            self.district_ids[code] = self.ins("districts", {"code": code, "name": name, "name_mr": name_mr, "division": division})
        for code, name, name_mr, duration, modules in TRADES:
            tid = self.ins("trades", {"trade_code": code, "name": name, "name_mr": name_mr, "scheme": "NCVT",
                                      "duration_months": duration, "sector": "Engineering" if code != "COPA" else "IT"})
            self.trade_ids[code] = tid
            self.trade_duration[code] = duration
            self.module_ids[code] = [self.ins("trade_modules", {"trade_id": tid, "module_code": f"M{i + 1:02d}", "name": m,
                                                                "sequence_no": i + 1}) for i, m in enumerate(modules)]
        for y in range(2021, TODAY.year + 1):
            start, end = date(y, 8, 1), date(y + 1, 7, 31)
            status = "OPEN" if start <= TODAY <= end else ("CLOSED" if end < TODAY else "PLANNED")
            ay_id = self.ins("academic_years", {"label": f"{y}-{(y + 1) % 100:02d}", "start_date": start, "end_date": end, "status": status})
            self.ay[y] = {"id": ay_id, "start": start, "end": end}
        for y in range(2022, TODAY.year + 2):
            self.sessions[("MAIN", y)] = self.ins("exam_sessions", {"name": f"AITT Main July {y}", "exam_body": "NCVT",
                                                                     "exam_type": "MAIN", "session_month": date(y, 7, 1)})
            self.sessions[("SUPP", y)] = self.ins("exam_sessions", {"name": f"AITT Supplementary Jan {y + 1}", "exam_body": "NCVT",
                                                                     "exam_type": "SUPPLEMENTARY", "session_month": date(y + 1, 1, 1)})
        for name, sector, size, city in COMPANIES:
            cid = self.ins("companies", {"legal_name": name, "display_name": name, "industry_sector": sector,
                                         "company_size": size, "hq_city": city, "is_demo": 1})
            dist = next((d for c, n, _, _ in DISTRICTS for d in [self.district_ids[c]] if n == city), None)
            b1 = self.ins("company_branches", {"company_id": cid, "branch_name": f"{city} Plant", "city": city, "district_id": dist})
            b2 = self.ins("company_branches", {"company_id": cid, "branch_name": "Chakan Unit", "city": "Chakan",
                                               "district_id": self.district_ids["PUNE"]})
            self.companies.append({"id": cid, "name": name, "branches": [b1, b2], "city": city})
        for name, uni in COLLEGES:
            self.college_ids.append(self.ins("colleges", {"name": name, "university": uni, "district_id": self.district_ids["PUNE"]}))

    def create_itis(self):
        n = 1
        specs = list(MAIN_ITIS)
        main_codes = {s[0] for s in MAIN_ITIS}
        for code, _, _, _ in DISTRICTS:
            if code not in main_codes:
                specs.append((code, code.lower(), "GOVERNMENT", RNG.sample(["FIT", "ELE", "WEL", "COPA", "MMV", "TUR", "ELM", "PLU"], 3), 6, 1))
        for code in PRIVATE_DISTRICTS:
            specs.append((code, f"{code.lower()}-pvt", "PRIVATE", RNG.sample(["ELE", "COPA", "ELM", "PLU", "FIT"], 3), 6, 1))
        name_of = {c: nm for c, nm, _, _ in DISTRICTS}
        for dcode, slug, owner, trades, size, units in specs:
            dname = name_of[dcode]
            name = f"Government ITI, {dname}" if owner == "GOVERNMENT" else f"Shri Sai Private ITI, {dname}"
            iti_id = self.ins("itis", {"iti_code": f"DEMO{n:04d}", "name": name, "ownership_type": owner,
                                       "district_id": self.district_ids[dcode], "address_line": f"Industrial Area, {dname}",
                                       "is_demo": 1})
            n += 1
            for t in trades:
                self.ins("iti_trades", {"iti_id": iti_id, "trade_id": self.trade_ids[t], "sanctioned_units": units, "seats_per_unit": 24})
            principal = self.user(f"Principal {dname}{' (Pvt)' if owner == 'PRIVATE' else ''}", f"principal.{slug}@demo.local",
                                  "PRINCIPAL", iti_id=iti_id)
            teachers = {}
            if slug == "pune":
                tnames = {"FIT": "Suresh Kulkarni", "ELE": "Anita Joshi", "WEL": "Mahesh Pawar", "COPA": "Kavita Deshpande", "MMV": "Rajesh Gore"}
                for t in trades:
                    email = "teacher.pune@demo.local" if t == "FIT" else f"teacher.pune.{t.lower()}@demo.local"
                    teachers[t] = self.user(tnames[t], email, "TEACHER", iti_id=iti_id)
                placement = self.user("Meera Kale", "placement.pune@demo.local", "PLACEMENT_OFFICER", iti_id=iti_id)
                staff = self.user("Dinesh Shelke", "staff.pune@demo.local", "ITI_STAFF", iti_id=iti_id)
            else:
                teacher = self.user(f"Instructor {dname}{' (Pvt)' if owner == 'PRIVATE' else ''}", f"teacher.{slug}@demo.local",
                                    "TEACHER", iti_id=iti_id)
                teachers = {t: teacher for t in trades}
                placement = self.user(f"Placement Officer {dname}{' (Pvt)' if owner == 'PRIVATE' else ''}",
                                      f"placement.{slug}@demo.local", "PLACEMENT_OFFICER", iti_id=iti_id) if slug in ("nashik", "satara", "kolhapur") else None
                staff = None
            self.itis.append({"id": iti_id, "slug": slug, "name": name, "district": dcode, "trades": trades, "size": size,
                              "units": units, "principal": principal, "teachers": teachers, "placement": placement, "staff": staff,
                              "batches": {}})
        # state & district admins
        self.user("State Admin (DVET)", "state@demo.local", "STATE_ADMIN")
        self.user("District Admin Pune", "district.pune@demo.local", "DISTRICT_ADMIN", district_id=self.district_ids["PUNE"])

    def batches(self):
        for iti in self.itis:
            for y in self.ay:
                for t in iti["trades"]:
                    for u in range(1, iti["units"] + 1):
                        shift = "FIRST" if u == 1 else "SECOND"
                        bid = self.ins("batches", {"iti_id": iti["id"], "trade_id": self.trade_ids[t], "academic_year_id": self.ay[y]["id"],
                                                   "unit_no": u, "shift": shift, "batch_code": f"{t}-{str(y)[2:]}-U{u}", "capacity": 24})
                        iti["batches"][(y, t, u)] = bid

    # ------------------------------------------------------------------ students
    def new_student(self, iti, first=None, last=None, gender=None, dob=None, intake_year=2024):
        gender = gender or ("FEMALE" if RNG.random() < 0.22 else "MALE")
        first = first or RNG.choice(FEMALE if gender == "FEMALE" else MALE)
        last = last or RNG.choice(SURNAMES)
        dob = dob or date(intake_year - RNG.randint(15, 18), RNG.randint(1, 12), RNG.randint(1, 28))
        self.ssc_serial += 1
        sid = self.ins("students", {
            "first_name": first, "middle_name": RNG.choice(FATHERS), "last_name": last, "gender": gender,
            "date_of_birth": dob, "guardian_name": f"{RNG.choice(FATHERS)} {last}",
            "social_category": RNG.choice(["OPEN", "OBC", "SC", "ST", "VJNT", "EWS"]),
            "ssc_board": "MSBSHSE", "ssc_seat_number": f"D{self.ssc_serial:07d}", "ssc_passing_year": intake_year,
            "ssc_percentage": round(RNG.uniform(45, 92), 2), "highest_qualification": "SSC",
            "is_demo": 1, "created_by": iti["principal"]})
        mob = self.mobile()
        self.ins("student_contacts", {"student_id": sid, "contact_type": "MOBILE", "value": mob, "value_normalized": mob,
                                      "is_primary": 1, "created_by": iti["principal"]})
        self.ins("student_contacts", {"student_id": sid, "contact_type": "ADDRESS", "value": f"At post {RNG.choice(['Wadi', 'Nagar', 'Peth', 'Colony'])}, {iti['name'].split(', ')[-1]}",
                                      "district_id": self.district_ids[iti["district"]], "is_primary": 1, "created_by": iti["principal"]})
        self.ins("consents", {"student_id": sid, "purpose": "DATA_PROCESSING", "granted": 1,
                              "given_by": "GUARDIAN" if (date(intake_year, 8, 1) - dob).days < 18 * 365 else "STUDENT",
                              "policy_version": "v1.0", "channel": "PAPER_FORM", "recorded_by": iti["principal"]})
        self.counts["students"] += 1
        return sid

    def admit(self, iti, sid, year, trade, unit=1, admission_type="REGULAR", previous=None, adm_date=None):
        adm_date = adm_date or (self.ay[year]["start"] + timedelta(days=RNG.randint(0, 20)))
        aid = self.ins("admissions", {
            "student_id": sid, "iti_id": iti["id"], "trade_id": self.trade_ids[trade], "academic_year_id": self.ay[year]["id"],
            "batch_id": iti["batches"].get((year, trade, unit)), "admission_type": admission_type,
            "previous_admission_id": previous, "admission_date": adm_date, "duration_months": self.trade_duration[trade],
            "status": "ACTIVE", "created_by": iti["principal"]})
        expected_end = date(year + self.trade_duration[trade] // 12, 7, 31)
        trid = self.ins("training_records", {"student_id": sid, "admission_id": aid, "iti_id": iti["id"], "start_date": adm_date,
                                             "expected_end_date": expected_end})
        return aid, trid, expected_end

    def assign_teacher(self, iti, sid, aid, trade, start, end=None):
        self.ins("teacher_assignments", {"student_id": sid, "teacher_user_id": iti["teachers"][trade], "iti_id": iti["id"],
                                         "admission_id": aid, "start_date": start, "end_date": end,
                                         "end_reason": "Course completed" if end else None, "created_by": iti["principal"]})

    def module_progress(self, iti, sid, trid, trade, fraction):
        mods = self.module_ids[trade]
        done = int(len(mods) * fraction)
        for i, mid in enumerate(mods):
            if i < done:
                self.ins("module_progress", {"training_record_id": trid, "student_id": sid, "iti_id": iti["id"], "module_id": mid,
                                             "progress_percent": 100, "status": "COMPLETED",
                                             "completed_on": TODAY - timedelta(days=30 * (done - i))})
            elif i == done and fraction < 1:
                self.ins("module_progress", {"training_record_id": trid, "student_id": sid, "iti_id": iti["id"], "module_id": mid,
                                             "progress_percent": RNG.choice([20, 40, 60, 80]), "status": "IN_PROGRESS"})

    def status(self, sid, status, start, source="STAFF", link: dict | None = None, until=None, verified=False):
        row = {"student_id": sid, "status": status, "effective_from": start, "effective_to": until, "source": source,
               "verification_status": "VERIFIED" if verified else "PENDING"}
        row.update(link or {})
        self.ins("career_status_history", row)

    def verify(self, iti, sid, field, rec_id, company=None):
        verifier = iti["placement"] or iti["principal"]
        self.ins("verification_records", {"student_id": sid, field: rec_id, "verifier_user_id": verifier,
                                          "method": "PHONE_CALL" if not company else "EMPLOYER_CONFIRMATION",
                                          "verifier_company_id": company, "decision": "VERIFIED",
                                          "evidence_reference": f"DEMO call log {rec_id}"})

    def career(self, iti, sid, trade, passed_on: date, failed: bool):
        """Build a realistic post-ITI journey. Each job is its own row."""
        timeline: list[tuple] = []  # (status, date, link)
        roll = RNG.random()
        t = min(passed_on + timedelta(days=RNG.randint(20, 75)), TODAY)
        rec_iti = iti["id"]
        if failed and roll < 0.6:
            timeline.append(("SEEKING_EMPLOYMENT", t, {}))
        elif roll < 0.30:   # apprenticeship (maybe followed by a job)
            comp = RNG.choice(self.companies)
            end = t + timedelta(days=365)
            done = end <= TODAY
            ap = self.ins("apprenticeships", {"student_id": sid, "company_id": comp["id"], "branch_id": comp["branches"][0],
                                              "trade_id": self.trade_ids[trade], "scheme": RNG.choice(["APPRENTICES_ACT", "NAPS"]),
                                              "start_date": t, "planned_end_date": end, "actual_end_date": end if done else None,
                                              "status": "COMPLETED" if done else "ACTIVE", "stipend_amount": RNG.choice([8000, 9000, 10000, 11000]),
                                              "stipend_period": "MONTHLY", "source": "STAFF", "recorded_by_iti_id": rec_iti,
                                              "created_by": iti["placement"] or iti["principal"]})
            self.counts["apprenticeships"] += 1
            if RNG.random() < 0.6:
                self.verify(iti, sid, "apprenticeship_id", ap)
            timeline.append(("APPRENTICE", t, {"apprenticeship_id": ap}))
            if done and RNG.random() < 0.65:
                j = end + timedelta(days=RNG.randint(1, 40))
                if j <= TODAY:
                    emp = self.job(iti, sid, comp, j, None, from_app=ap)
                    timeline.append(("EMPLOYED", j, {"employment_id": emp}))
        elif roll < 0.72:   # direct job, maybe a job change
            comp = RNG.choice(self.companies)
            if RNG.random() < 0.35 and t + timedelta(days=400) < TODAY:
                leave = t + timedelta(days=RNG.randint(250, 400))
                e1 = self.job(iti, sid, comp, t, leave)
                timeline.append(("EMPLOYED", t, {"employment_id": e1}))
                comp2 = RNG.choice([c for c in self.companies if c["id"] != comp["id"]])
                j2 = min(leave + timedelta(days=RNG.randint(1, 30)), TODAY)
                e2 = self.job(iti, sid, comp2, j2, None, raise_pct=0.25)
                timeline.append(("EMPLOYED", j2, {"employment_id": e2}))
            else:
                e = self.job(iti, sid, comp, t, None)
                timeline.append(("EMPLOYED", t, {"employment_id": e}))
        elif roll < 0.78:
            b = self.ins("business_records", {"student_id": sid, "business_name": f"{RNG.choice(SURNAMES)} {RNG.choice(['Electricals', 'Engineering Works', 'Auto Garage', 'Computer Services', 'Fabrication'])}",
                                              "business_type": RNG.choice(["Repair shop", "Fabrication unit", "Service centre"]),
                                              "registration_type": RNG.choice(["UDYAM", "SHOP_ACT", "UNREGISTERED"]),
                                              "location": iti["name"].split(", ")[-1], "district_id": self.district_ids[iti["district"]],
                                              "start_date": t, "employees_count": RNG.randint(0, 4), "source": "STAFF",
                                              "recorded_by_iti_id": rec_iti, "created_by": iti["principal"]})
            timeline.append(("SELF_EMPLOYED", t, {"business_record_id": b}))
        elif roll < 0.86:
            h = self.ins("higher_education_records", {"student_id": sid, "college_id": RNG.choice(self.college_ids),
                                                      "course_name": f"Diploma in {'Mechanical' if trade in ('FIT', 'TUR', 'MMV', 'WEL') else 'Electrical'} Engineering",
                                                      "course_level": "DIPLOMA", "study_mode": "FULL_TIME", "admission_year": t.year,
                                                      "status": "ENROLLED", "source": "STAFF", "recorded_by_iti_id": rec_iti,
                                                      "created_by": iti["principal"]})
            timeline.append(("HIGHER_EDUCATION", t, {"higher_education_record_id": h}))
        elif roll < 0.92:
            timeline.append(("SEEKING_EMPLOYMENT", t, {}))
        else:
            timeline.append(("NOT_UPDATED" if RNG.random() < 0.7 else "NOT_CONTACTABLE", t, {}))

        timeline = [x for x in timeline if x[1] <= TODAY]
        if not timeline:
            timeline = [("NOT_UPDATED", passed_on + timedelta(days=1), {})]
        for i, (st, d, link) in enumerate(timeline):
            until = timeline[i + 1][1] if i + 1 < len(timeline) else None
            if until is not None and until <= d:
                until = d + timedelta(days=1)
            self.status(sid, st, d, link=link, until=until, verified=bool(link) and RNG.random() < 0.6)
        return timeline

    def job(self, iti, sid, comp, joined, left, from_app=None, raise_pct=0.0):
        base = RNG.choice([12000, 14000, 15000, 16500, 18000, 18500, 20000, 22000])
        e = self.ins("employments", {"student_id": sid, "company_id": comp["id"], "branch_id": RNG.choice(comp["branches"]),
                                     "from_apprenticeship_id": from_app,
                                     "designation": RNG.choice(["Technician", "Junior Technician", "Operator", "Maintenance Technician", "Assistant Electrician", "Fitter"]),
                                     "employment_type": RNG.choice(["FULL_TIME", "FULL_TIME", "CONTRACT"]), "employer_sector": "PRIVATE",
                                     "work_location": comp["city"], "joining_date": joined, "leaving_date": left,
                                     "leaving_reason": "Better opportunity" if left else None,
                                     "salary_amount": round(base * (1 + raise_pct), -2), "salary_period": "MONTHLY",
                                     "status": "ENDED" if left else "ACTIVE", "source": "STAFF", "recorded_by_iti_id": iti["id"],
                                     "created_by": iti["placement"] or iti["principal"]})
        self.counts["employments"] += 1
        if RNG.random() < 0.6:
            self.verify(iti, sid, "employment_id", e, company=comp["id"] if RNG.random() < 0.3 else None)
        return e

    def exams(self, iti, sid, aid, end_year) -> tuple[date | None, bool]:
        """Returns (date passed or final attempt, failed_overall). None if results not yet declared."""
        main_decl = date(end_year, 8, 20)
        if main_decl > TODAY:
            return None, False
        roll = f"{iti['id']:03d}{sid:06d}"
        if RNG.random() < 0.86:
            self.ins("exam_results", {"student_id": sid, "admission_id": aid, "iti_id": iti["id"], "exam_session_id": self.sessions[("MAIN", end_year)],
                                      "attempt_no": 1, "roll_number": roll, "result": "PASS", "marks_obtained": RNG.randint(380, 560),
                                      "max_marks": 600, "result_declared_on": main_decl, "certificate_number": f"DEMO-NTC-{sid:07d}",
                                      "verification_status": "VERIFIED"})
            return main_decl, False
        self.ins("exam_results", {"student_id": sid, "admission_id": aid, "iti_id": iti["id"], "exam_session_id": self.sessions[("MAIN", end_year)],
                                  "attempt_no": 1, "roll_number": roll, "result": "FAIL", "marks_obtained": RNG.randint(150, 230),
                                  "max_marks": 600, "result_declared_on": main_decl, "verification_status": "VERIFIED"})
        supp_decl = date(end_year + 1, 2, 20)
        if supp_decl > TODAY:
            return main_decl, True
        passed = RNG.random() < 0.7
        self.ins("exam_results", {"student_id": sid, "admission_id": aid, "iti_id": iti["id"], "exam_session_id": self.sessions[("SUPP", end_year)],
                                  "attempt_no": 2, "roll_number": roll, "result": "PASS" if passed else "FAIL",
                                  "marks_obtained": RNG.randint(360, 450) if passed else RNG.randint(150, 230), "max_marks": 600,
                                  "result_declared_on": supp_decl, "certificate_number": f"DEMO-NTC-{sid:07d}" if passed else None,
                                  "verification_status": "VERIFIED"})
        return supp_decl, not passed

    def cohort_student(self, iti, year, trade, unit, **student_kw):
        sid = self.new_student(iti, intake_year=year, **student_kw)
        aid, trid, expected_end = self.admit(iti, sid, year, trade, unit)
        adm_start = self.ay[year]["start"]
        if expected_end < TODAY:
            # course finished
            if RNG.random() < 0.04:
                stop = adm_start + timedelta(days=RNG.randint(60, 300))
                self.cur.execute("UPDATE training_records SET completion_status='DISCONTINUED', actual_end_date=%s WHERE id=%s", (stop, trid))
                self.cur.execute("UPDATE admissions SET status='DISCONTINUED', end_date=%s, end_reason='DEMO: left course' WHERE id=%s", (stop, aid))
                if iti["slug"] == "pune":
                    self.assign_teacher(iti, sid, aid, trade, adm_start, stop)
                self.status(sid, "IN_TRAINING", adm_start, until=stop, source="SYSTEM", verified=True)
                self.status(sid, "NOT_UPDATED", stop, source="SYSTEM")
                return sid, None
            if iti["slug"] == "pune":
                self.assign_teacher(iti, sid, aid, trade, adm_start, expected_end)
            self.module_progress(iti, sid, trid, trade, 1.0)
            self.cur.execute("UPDATE training_records SET completion_status='COMPLETED', actual_end_date=%s WHERE id=%s", (expected_end, trid))
            self.status(sid, "IN_TRAINING", adm_start, until=expected_end + timedelta(days=1), source="SYSTEM", verified=True)
            decl, failed = self.exams(iti, sid, aid, expected_end.year)
            end_date = decl or expected_end
            self.cur.execute("UPDATE admissions SET status='COMPLETED', end_date=%s, end_reason='Course completed' WHERE id=%s", (end_date, aid))
            if decl:
                self.career(iti, sid, trade, decl, failed)
            else:
                self.status(sid, "NOT_UPDATED", expected_end + timedelta(days=1), source="SYSTEM")
            return sid, aid
        # still studying
        self.assign_teacher(iti, sid, aid, trade, adm_start)
        months_done = max(0, (TODAY - adm_start).days / 30)
        self.module_progress(iti, sid, trid, trade, min(0.95, months_done / self.trade_duration[trade]))
        self.status(sid, "IN_TRAINING", adm_start, source="SYSTEM", verified=True)
        for k in range(RNG.randint(1, 3)):
            ad = adm_start + timedelta(days=30 * (k + 1))
            if ad < TODAY:
                self.ins("assessments", {"student_id": sid, "training_record_id": trid, "iti_id": iti["id"],
                                         "module_id": self.module_ids[trade][min(k, 5)], "assessment_type": RNG.choice(["FORMATIVE", "INTERNAL_PRACTICAL", "INTERNAL_THEORY"]),
                                         "assessment_date": ad, "max_marks": 50, "marks_obtained": RNG.randint(22, 49),
                                         "assessor_user_id": iti["teachers"][trade]})
        if iti["slug"] == "pune":
            d = TODAY - timedelta(days=35)
            while d <= TODAY:
                if d.weekday() < 6 and d >= adm_start:
                    self.ins("attendance_records", {"student_id": sid, "training_record_id": trid, "iti_id": iti["id"], "attendance_date": d,
                                                    "status": "PRESENT" if RNG.random() < 0.9 else RNG.choice(["ABSENT", "LEAVE"]),
                                                    "marked_by": iti["teachers"][trade]})
                d += timedelta(days=1)
        return sid, aid

    def students(self):
        for iti in self.itis:
            for year in self.ay:
                for trade in iti["trades"]:
                    for unit in range(1, iti["units"] + 1):
                        n = iti["size"] + RNG.randint(-2, 3)
                        for _ in range(max(n, 3)):
                            self.cohort_student(iti, year, trade, unit)
            self.conn.commit()
            print(f"  seeded {iti['name']}: {self.counts['students']} students so far", flush=True)

    # ------------------------------------------------------------------ showcase records
    def showcase(self):
        pune = next(i for i in self.itis if i["slug"] == "pune")
        nashik = next(i for i in self.itis if i["slug"] == "nashik")
        satara = next(i for i in self.itis if i["slug"] == "satara")
        sahyadri = self.companies[0]

        # 1. Rohit Deshmukh — complete alumni journey + student login
        sid = self.new_student(pune, first="Rohit", last="Deshmukh", gender="MALE", dob=date(2006, 6, 14), intake_year=2022)
        self.cur.execute("UPDATE students SET middle_name='Sanjay' WHERE id=%s", (sid,))
        aid, trid, end = self.admit(pune, sid, 2022, "ELE", 1, adm_date=date(2022, 8, 3))
        self.assign_teacher(pune, sid, aid, "ELE", date(2022, 8, 3), end)
        self.module_progress(pune, sid, trid, "ELE", 1.0)
        self.cur.execute("UPDATE training_records SET completion_status='COMPLETED', actual_end_date=%s WHERE id=%s", (end, trid))
        self.ins("exam_results", {"student_id": sid, "admission_id": aid, "iti_id": pune["id"], "exam_session_id": self.sessions[("MAIN", 2024)],
                                  "attempt_no": 1, "roll_number": "DEMO240567", "result": "PASS", "marks_obtained": 512, "max_marks": 600,
                                  "result_declared_on": date(2024, 8, 20), "certificate_number": "DEMO-NTC-ROHIT01", "verification_status": "VERIFIED"})
        self.cur.execute("UPDATE admissions SET status='COMPLETED', end_date='2024-08-20', end_reason='Course completed' WHERE id=%s", (aid,))
        ap = self.ins("apprenticeships", {"student_id": sid, "company_id": sahyadri["id"], "branch_id": sahyadri["branches"][0],
                                          "trade_id": self.trade_ids["ELE"], "scheme": "APPRENTICES_ACT", "contract_number": "DEMO-APP-2024-118",
                                          "start_date": date(2024, 9, 2), "planned_end_date": date(2025, 8, 31), "actual_end_date": date(2025, 8, 31),
                                          "status": "COMPLETED", "stipend_amount": 9500, "stipend_period": "MONTHLY", "source": "STAFF",
                                          "recorded_by_iti_id": pune["id"], "created_by": pune["placement"]})
        self.verify(pune, sid, "apprenticeship_id", ap, company=sahyadri["id"])
        emp = self.ins("employments", {"student_id": sid, "company_id": sahyadri["id"], "branch_id": sahyadri["branches"][0], "from_apprenticeship_id": ap,
                                       "designation": "Technician", "employment_type": "FULL_TIME", "employer_sector": "PRIVATE",
                                       "work_location": "Pune", "joining_date": date(2025, 9, 15), "salary_amount": 18500, "salary_period": "MONTHLY",
                                       "status": "ACTIVE", "source": "EMPLOYER", "recorded_by_iti_id": pune["id"], "created_by": pune["placement"]})
        self.verify(pune, sid, "employment_id", emp, company=sahyadri["id"])
        self.status(sid, "IN_TRAINING", date(2022, 8, 3), source="SYSTEM", until=date(2024, 9, 2), verified=True)
        self.status(sid, "APPRENTICE", date(2024, 9, 2), link={"apprenticeship_id": ap}, until=date(2025, 9, 15), verified=True)
        self.status(sid, "EMPLOYED", date(2025, 9, 15), source="EMPLOYER", link={"employment_id": emp}, verified=True)
        self.ins("consents", {"student_id": sid, "purpose": "SHARE_PROFILE_WITH_EMPLOYERS", "granted": 1, "given_by": "STUDENT",
                              "policy_version": "v1.0", "channel": "PORTAL"})
        self.user("Rohit Deshmukh", "rohit.deshmukh@demo.local", "STUDENT", student_id=sid)

        # 2. Completed historical transfer Satara -> Pune
        sid = self.new_student(satara, first="Sagar", last="Bhosale", gender="MALE", intake_year=2025)
        a1, t1, _ = self.admit(satara, sid, 2025, "WEL", 1, adm_date=date(2025, 8, 4))
        self.assign_teacher(satara, sid, a1, "WEL", date(2025, 8, 4), date(2025, 11, 17))
        eff = date(2025, 11, 17)
        self.cur.execute("UPDATE training_records SET completion_status='TRANSFERRED_OUT', actual_end_date=%s WHERE id=%s", (eff, t1))
        self.cur.execute("UPDATE admissions SET status='TRANSFERRED_OUT', end_date=%s, end_reason='DEMO transfer' WHERE id=%s", (eff, a1))
        a2, t2, _ = self.admit(pune, sid, 2025, "WEL", 1, admission_type="TRANSFER_IN", previous=a1, adm_date=eff)
        self.assign_teacher(pune, sid, a2, "WEL", eff, date(2026, 7, 31))
        self.module_progress(pune, sid, t2, "WEL", 1.0)
        self.cur.execute("UPDATE training_records SET completion_status='COMPLETED', actual_end_date='2026-07-31' WHERE id=%s", (t2,))
        self.ins("exam_results", {"student_id": sid, "admission_id": a2, "iti_id": pune["id"], "exam_session_id": self.sessions[("MAIN", 2026)],
                                  "attempt_no": 1, "result": "PASS", "marks_obtained": 455, "max_marks": 600,
                                  "result_declared_on": date(2026, 8, 20), "certificate_number": "DEMO-NTC-SAGAR01", "verification_status": "VERIFIED"})
        self.cur.execute("UPDATE admissions SET status='COMPLETED', end_date='2026-08-20', end_reason='Course completed' WHERE id=%s", (a2,))
        self.ins("transfer_requests", {"student_id": sid, "from_iti_id": satara["id"], "from_admission_id": a1, "to_iti_id": pune["id"],
                                       "to_trade_id": self.trade_ids["WEL"], "to_academic_year_id": self.ay[2025]["id"],
                                       "requested_effective_date": eff, "reason": "Change of residence (family moved to Pune)",
                                       "status": "ACCEPTED", "requested_by": satara["principal"], "requested_at": "2025-11-10 11:00:00",
                                       "decided_by": pune["principal"], "decided_at": "2025-11-14 15:30:00", "new_admission_id": a2})
        self.status(sid, "IN_TRAINING", date(2025, 8, 4), source="SYSTEM", until=date(2026, 9, 10), verified=True)
        self.status(sid, "SEEKING_EMPLOYMENT", date(2026, 9, 10), source="STAFF")

        # 3. Pending inbound transfer Nashik -> Pune (for the transfer page)
        cur_year = max(self.ay)
        for first, last in (("Ajay", "Patil"), ("Pooja", "Kale")):
            sid = self.new_student(nashik, first=first, last=last, gender="FEMALE" if first == "Pooja" else "MALE", intake_year=cur_year)
            a, _, _ = self.admit(nashik, sid, cur_year, "FIT" if first == "Ajay" else "ELE", 1)
            self.assign_teacher(nashik, sid, a, "FIT" if first == "Ajay" else "ELE", self.ay[cur_year]["start"])
            self.status(sid, "IN_TRAINING", self.ay[cur_year]["start"], source="SYSTEM", verified=True)
            if first == "Ajay":
                self.ins("transfer_requests", {"student_id": sid, "from_iti_id": nashik["id"], "from_admission_id": a, "to_iti_id": pune["id"],
                                               "to_trade_id": self.trade_ids["FIT"], "to_academic_year_id": self.ay[cur_year]["id"],
                                               "requested_effective_date": TODAY + timedelta(days=7),
                                               "reason": "Change of residence (father transferred to Pune)", "status": "REQUESTED",
                                               "requested_by": nashik["principal"]})

        # 4. Employer: HR login, vacancies, applications
        hr = self.user("Neha Joshi (HR)", "hr.sahyadri@demo.local", "EMPLOYER", company_id=sahyadri["id"])
        vacancies = []
        for title, trade, vtype, smin, smax in [("Maintenance Electrician", "ELE", "JOB", 16000, 21000),
                                                ("CNC Fitter", "FIT", "JOB", 15000, 19000),
                                                ("Welding Apprentice", "WEL", "APPRENTICESHIP", 9000, 10000),
                                                ("Apprentice - Electrical Assembly", "ELE", "APPRENTICESHIP", 9000, 10500)]:
            vacancies.append(self.ins("vacancies", {"company_id": sahyadri["id"], "branch_id": sahyadri["branches"][0],
                                                    "trade_id": self.trade_ids[trade], "vacancy_type": vtype, "title": title,
                                                    "description": "DEMO vacancy. Shift work, PF/ESI as per rules.",
                                                    "openings": RNG.randint(2, 8), "salary_min": smin, "salary_max": smax,
                                                    "salary_period": "MONTHLY", "location": "Pune (Chakan)",
                                                    "opens_on": TODAY - timedelta(days=RNG.randint(5, 40)),
                                                    "closes_on": TODAY + timedelta(days=RNG.randint(10, 40)), "status": "OPEN", "posted_by": hr}))
        self.cur.execute("""SELECT s.id FROM students s JOIN v_student_current_status cs ON cs.student_id = s.id
                            JOIN institute_affiliations ia ON ia.student_id = s.id AND ia.iti_id = %s
                            WHERE cs.status IN ('SEEKING_EMPLOYMENT','IN_TRAINING') ORDER BY s.id DESC LIMIT 40""", (pune["id"],))
        cands = [r["id"] for r in self.cur.fetchall()]
        for i, csid in enumerate(cands):
            self.ins("job_applications", {"vacancy_id": vacancies[i % len(vacancies)], "student_id": csid,
                                          "status": RNG.choice(["APPLIED", "APPLIED", "SHORTLISTED", "INTERVIEW", "REJECTED", "HIRED"]),
                                          "consent_to_share_profile": 1, "referred_by_user_id": pune["placement"], "referred_by_iti_id": pune["id"]})
            self.ins("consents", {"student_id": csid, "purpose": "SHARE_PROFILE_WITH_EMPLOYERS", "granted": 1, "given_by": "STUDENT",
                                  "policy_version": "v1.0", "channel": "STAFF_ASSISTED", "recorded_by": pune["placement"]})

        # 5. Follow-ups for Pune alumni whose status is stale / not updated
        self.cur.execute("""SELECT cs.student_id FROM v_student_current_status cs
                            JOIN institute_affiliations ia ON ia.student_id = cs.student_id AND ia.iti_id = %s AND ia.status = 'COMPLETED'
                            WHERE cs.status IN ('NOT_UPDATED','NOT_CONTACTABLE','SEEKING_EMPLOYMENT') LIMIT 30""", (pune["id"],))
        for i, r in enumerate(self.cur.fetchall()):
            done = i % 4 == 0
            self.ins("follow_ups", {"student_id": r["student_id"], "iti_id": pune["id"],
                                    "assigned_to_user_id": pune["staff"] if i % 2 else pune["placement"],
                                    "reason": "NO_RECENT_UPDATE", "due_date": TODAY + timedelta(days=(i % 10) - 3),
                                    "channel": RNG.choice(["PHONE", "WHATSAPP", "SMS"]), "status": "COMPLETED" if done else "PENDING",
                                    "outcome": "NO_CHANGE" if done else None, "completed_at": f"{TODAY} 10:00:00" if done else None,
                                    "outcome_notes": "DEMO: spoke to student" if done else None, "created_by": pune["principal"]})

        # 6. Timetable for the Fitter teacher (Teacher dashboard "Today's class")
        cur_batch = pune["batches"][(cur_year, "FIT", 1)]
        prev_batch = pune["batches"][(cur_year - 1, "FIT", 1)]
        for wd in range(1, 7):
            self.ins("timetable_slots", {"batch_id": cur_batch, "teacher_user_id": pune["teachers"]["FIT"], "weekday": wd,
                                         "start_time": "10:00", "end_time": "12:00", "subject": "Fitting - Practical"})
            self.ins("timetable_slots", {"batch_id": prev_batch, "teacher_user_id": pune["teachers"]["FIT"], "weekday": wd,
                                         "start_time": "13:00", "end_time": "15:00", "subject": "Machine Drawing" if wd % 2 else "Workshop Calculation"})
        self.conn.commit()

    def run(self):
        print("Seeding master data ...")
        self.masters()
        self.create_itis()
        self.batches()
        self.conn.commit()
        print(f"  {len(self.itis)} ITIs, {len(TRADES)} trades, {len(self.companies)} companies")
        print("Seeding students (PCIDs are issued by the database trigger) ...")
        self.students()
        print("Seeding showcase journeys, transfers, employer data, follow-ups ...")
        self.showcase()
        print(f"Done: {self.counts}")
        print(f"Demo password for all accounts: {DEMO_PASSWORD}")


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--force", action="store_true", help="seed even if students already exist")
    args = ap.parse_args()
    conn = connect()
    with conn.cursor() as cur:
        cur.execute("SELECT COUNT(*) AS n FROM students")
        if cur.fetchone()["n"] and not args.force:
            sys.exit("Database already has students. Re-install the schema first (database/mysql/install.sh).")
    Seeder(conn).run()


if __name__ == "__main__":
    main()
