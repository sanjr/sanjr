# ITI CareerTrack

**ITI Student Lifecycle & Career Tracking System — Maharashtra**
*One Student. One Permanent Career ID. One Complete Career Journey.*

Built with **HTML, CSS, JavaScript, PHP 8 and MySQL 8**, plus **Python** for background jobs, report exports, demo data and automated tests.
No framework, no CDN and no `composer`/`npm` install is needed, so it runs on a plain XAMPP / WAMP / LAMP server.

> ⚠️ **DEMO build.** All institutes, companies, people and numbers are synthetic and clearly labelled DEMO.
> This is not an official government system, and it is not connected to any government API.

---

## What is inside

| Folder | Contents |
|---|---|
| **`html-demo/`** | **Plain HTML + CSS + JavaScript version of all 90 pages. Double-click `html-demo/index.html` — no server needed** (view only, saving is disabled) |
| `app/` | The real working website (PHP + MySQL). `public/` is the web root |
| `app/views/` | **HTML** of every page (`.php` files = HTML with small PHP parts that fill in data) |
| `app/public/assets/css/app.css` | **CSS** (all styling) |
| `app/public/assets/js/app.js` | **JavaScript** (charts, admission wizard, menus) |
| `app/src/Core/` | Security core: login and sessions, CSRF protection, **access control (`Access.php`)**, audit log, router |
| `app/src/Services/` | Business rules: admission + PCID, duplicate matching, transfers, career records, statistics |
| `app/src/Controllers/`, `app/views/` | Pages for each role |
| `database/mysql/` | MySQL schema (50 tables), triggers and stored procedures, reference data, least-privilege DB user |
| `python/seed_demo.py` | Creates DEMO data (36 districts, 46 ITIs, about 6,500 students with full career histories) |
| `python/jobs/followup_job.py` | Nightly job that creates follow-up tasks for alumni who have not updated (it never changes their status) |
| `python/reports/export_report.py` | Offline Excel/CSV report for the State or District office |
| `python/tests/test_e2e.py` | **59 automated end-to-end acceptance and security tests** |
| `docs/` | Architecture review, ER diagrams, API design, implementation notes, **screenshots** |
| `database/schema.sql` | The original PostgreSQL design reviewed in phase 1 (kept for reference) |

## Pages (as in the approved screen designs)

| # | Page | Who | Screenshot |
|---|---|---|---|
| 1 | State Admin Dashboard | State Admin / District Admin | [01](docs/screenshots/01-state-admin-dashboard.png) |
| 2 | Principal Dashboard | Principal | [02](docs/screenshots/02-principal-dashboard.png) |
| 3 | Teacher Dashboard | Teacher | [03](docs/screenshots/03-teacher-dashboard.png) |
| 4 | Placement Officer Dashboard | Placement Officer | [04](docs/screenshots/04-placement-officer-dashboard.png) |
| 5 | Student / Alumni Dashboard | Student | [05](docs/screenshots/05-student-alumni-dashboard.png) |
| 6 | Employer / HR Dashboard | Employer | [06](docs/screenshots/06-employer-dashboard.png) |
| 7 | Student Admission (5-step form) | Principal | [07](docs/screenshots/07-admission-form.png) |
| 8 | Student Career Profile / Timeline | Staff in scope, the student themself | [08](docs/screenshots/08-student-career-profile.png) |
| 9 | Student Transfer | Principals (origin requests, destination accepts) | [09](docs/screenshots/09-student-transfer.png) |
| 10 | Reports & Analytics (+ CSV download) | State / District / ITI | [10](docs/screenshots/10-reports-analytics.png) |

Every sidebar item for every role is a working page too: ITIs, users and roles, audit logs, trades and batches, attendance, module progress, assessments, results, companies, vacancies, apprenticeships, follow-ups, job search, update status, employer confirmations, and more.

---

## Quick start — Windows (XAMPP)

1. Install **XAMPP with PHP 8.1 or newer** and **MySQL 8.0.16 or newer**.
   XAMPP ships MariaDB; MySQL 8 is recommended (see [notes](docs/03-IMPLEMENTATION-NOTES.md#mariadb)).
2. Copy the `iti-careertrack` folder to `C:\xampp\htdocs\iti-careertrack`.
3. Open phpMyAdmin, create the database `careertrack` (collation `utf8mb4_unicode_ci`), then **Import** these files in order:
   `database/mysql/01_schema.sql`, `02_routines.sql`, `03_reference_data.sql`, `04_app_user.sql`.
4. Copy `app/config/config.example.php` to `app/config/config.php` and set the database password and a long random `secret`.
5. Load the demo data. Install Python 3.10+, then run these in a terminal from the `iti-careertrack` folder:

```bash
pip install -r python/requirements.txt
```

```bash
set CT_DB_USER=root
```

```bash
set CT_DB_PASS=
```

```bash
python python/seed_demo.py
```

   (`php` must be on PATH; it is used to hash the demo passwords.)
6. Open <http://localhost/iti-careertrack/app/public/> and log in with a demo account (below).

## Quick start — Linux

```bash
cd iti-careertrack && MYSQL_ROOT="mysql -uroot -p" database/mysql/install.sh
```

```bash
cp app/config/config.example.php app/config/config.php
```

```bash
pip install -r python/requirements.txt && CT_DB_USER=root CT_DB_PASS=yourpass python3 python/seed_demo.py
```

```bash
php -S 127.0.0.1:8080 -t app/public app/public/index.php
```

For production, point Apache or Nginx to `app/public` (an `.htaccess` file is included), use HTTPS, and set `CT_ENV=production`.

## Demo accounts (password `Demo@1234`)

| Role | E-mail |
|---|---|
| State Admin | `state@demo.local` |
| District Admin (Pune) | `district.pune@demo.local` |
| Principal — Govt ITI Pune | `principal.pune@demo.local` |
| Principal — Govt ITI Nashik | `principal.nashik@demo.local` |
| Teacher (Fitter, Pune) | `teacher.pune@demo.local` |
| Placement Officer (Pune) | `placement.pune@demo.local` |
| ITI Staff (Pune) | `staff.pune@demo.local` |
| Employer / HR | `hr.sahyadri@demo.local` |
| Student / Alumni | `rohit.deshmukh@demo.local` |

## Run the tests

```bash
CT_DB_USER=root CT_DB_PASS=yourpass python3 python/tests/test_e2e.py --base http://127.0.0.1:8080
```

Expected result: `59 passed, 0 failed`.

## Nightly follow-up job (cron)

```bash
30 1 * * * cd /path/to/iti-careertrack && python3 python/jobs/followup_job.py >> app/storage/logs/followup_job.log 2>&1
```

On Windows, use Task Scheduler to run `python python\jobs\followup_job.py` daily.

---

## Before going live (not done in this build)

- Official ITI, trade and district master data (replace the DEMO data).
- MFA for privileged roles, and OTP login for students (needs an approved SMS provider).
- SMS / WhatsApp / e-mail sending (the queue table exists; no provider is connected).
- Legal approval of: the PCID format, Aadhaar collection, guardian consent for minors, and data retention.
- HTTPS, encrypted and tested backups, a security audit (e.g. a CERT-In empanelled auditor), and load testing.
- Official branding (state emblem etc.) only with authorisation.

See [docs/03-IMPLEMENTATION-NOTES.md](docs/03-IMPLEMENTATION-NOTES.md) for the decisions taken during the build.
