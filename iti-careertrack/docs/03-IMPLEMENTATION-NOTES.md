# ITI CareerTrack — Implementation Notes (v1.0 build)

This explains, in plain language, the decisions taken while building the website from the approved design and screen mock-ups.

## 1. Technology (as requested by the project owner)

| Layer | Choice | Why |
|---|---|---|
| Pages | HTML + CSS + vanilla JavaScript | Runs anywhere and loads nothing from the internet. Charts are drawn in-house as SVG. |
| Server | PHP 8 (no framework) | Easy to host on any XAMPP/LAMP server and easy for local developers to maintain. |
| Database | MySQL 8 | As requested. |
| Background work | Python 3 | Demo data, nightly follow-up job, offline report export, automated tests. |

## 2. What changed from the PostgreSQL design, and how safety is kept

The phase-1 design used PostgreSQL "Row-Level Security", where the database itself hides other ITIs' rows. MySQL has no such feature, so:

1. **One central gatekeeper.** `app/src/Core/Access.php` is the *only* place that decides which students a user may see. Every list, profile, report, download and update calls it. If a student is outside your scope, you get "404 not found", so the system never even reveals that the student exists elsewhere.
2. **Rules the database still enforces itself** (they cannot be bypassed by any program):
   - The PCID is issued only by a stored procedure (`sp_issue_pcid`), automatically when an admission is saved, with a locked yearly counter (no duplicates, no gaps).
   - The institute affiliation opens and closes automatically with the admission.
   - Only one live admission, one active PCID, one open transfer and one current career status per student.
   - A job or apprenticeship can never be moved to another company or student; the trigger refuses.
   - The audit log and verification records are append-only; even the database owner cannot edit or delete them.
   - A teacher can only be assigned at an ITI where they hold an active teacher role.
   - Composite foreign keys, for example: a branch must belong to the job's company, and a trade must be offered by the ITI.
3. **Least-privilege database account.** The website connects as `careertrack_app`, which has **no DELETE right at all**.
4. **59 automated tests** (`python/tests/test_e2e.py`) log in as each role and try forbidden things: other ITI's students, unassigned students, privilege escalation, CSRF, SQL injection, HTML injection, document downloads, and account lockout.

Other MySQL differences: ID numbers are BIGINT auto-increment (normal for PHP/MySQL; safe because every lookup is access-checked), and attendance is indexed rather than partitioned (MySQL cannot partition tables that have foreign keys).

## 3. Decisions taken (please confirm or change)

| Topic | What the build does | Easy to change? |
|---|---|---|
| **PCID format** | `ITIC-MH-2026-000123-7`. The last digit is a check digit that catches typing mistakes. The mock-up showed `PCID-MH-…`. | Yes: the `pcid_prefix` setting, **only before the first real PCID is issued** |
| **Aadhaar** | Optional field. The full number is **never stored**: only a one-way fingerprint (for the duplicate check) and the last 4 digits for display. | Yes |
| **Main duplicate key** | SSC board + seat number + year (the same seat number can never get a second PCID). | — |
| **Duplicate warnings** | 100 = must use the existing student; 70–95 = blocked unless the user gives a reason (audited); 50–69 = allowed but listed for review. | Yes |
| **After a transfer** | The new ITI sees the full history. The old ITI keeps only its own records plus name and PCID. The previous teacher keeps name and PCID only. | Yes (`Access.php`) |
| **Who completes the transfer** | The origin Principal requests and the destination Principal accepts (the State Admin can also accept). | Yes |
| **Alumni** | The ITI where the student *completed* owns the follow-up and placement reporting. | — |
| **Placement rate** | Placed (employed + apprentice + self-employed) ÷ alumni. "Not updated" is shown separately and **never** counted as unemployed. | — |
| **Attendance corrections** | Allowed for the last 7 days. | Yes (`TeacherController::EDIT_DAYS`) |
| **Minors** | Data-processing consent is recorded as given by the *guardian* for students under 18. | Policy needs legal sign-off |
| **Branding** | Generic CareerTrack logo; no state emblem (that needs official authorisation). | Yes |

## 4. Security features in this build

- Passwords hashed with Argon2id (bcrypt fallback). The account locks for 15 minutes after 5 wrong passwords, with a per-network limit as well.
- Session cookie is HttpOnly and SameSite, Secure on HTTPS; 30-minute idle logout; the session ID is regenerated at login.
- CSRF token on every form; Content-Security-Policy blocks inline scripts; every output is HTML-escaped.
- All SQL uses prepared statements.
- Role and permission are re-read from the database on every request, so a revoked role stops working immediately.
- Field-level permissions: phone numbers (`student.contact.view`), salaries (`career.salary.view`) and documents (`student.document.view`).
- Uploaded files are stored outside the web root, with type/size/fingerprint checks. Downloads are permission-checked and audited.
- CSV exports are protected against spreadsheet formula injection.

## 5. MariaDB

The schema uses standard MySQL 8 features (CHECK constraints, stored generated columns, `SIGNAL`, `REGEXP_REPLACE`). Most should work on MariaDB 10.6+, but **the build was tested only on MySQL 8.0**. Please use MySQL 8 (XAMPP lets you swap it in) or test on MariaDB first.

## 6. Not yet built (next phases)

- MFA, OTP login, real SMS/WhatsApp/e-mail delivery (needs approved providers).
- Duplicate-merge screen for the State Admin (the tables `duplicate_candidates` and `student_merges` are ready).
- Correction-request approval screen (the table is ready).
- Marathi user interface (the database already stores Marathi names).
- Official data import tools and production hosting, backup and monitoring.
