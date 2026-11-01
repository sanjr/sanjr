# ITI CareerTrack

> ## ⬇️ Download the website
> **[dist/ITI-CareerTrack-Website.zip](dist/ITI-CareerTrack-Website.zip)** — the complete, ready-to-install website (PHP + MySQL).
> Extract it into `C:\xampp\htdocs\`, open **http://localhost/ITI-CareerTrack/** and the installer does the rest.
> Step-by-step guide (Marathi + English): **[INSTALL.md](INSTALL.md)** — XAMPP and web hosting (cPanel / Hostinger, MySQL 8 or MariaDB).
>
> Tested before release: web installer (Demo and Empty), Apache sub-folder install from the ZIP,
> a real-browser walkthrough of every menu of all 10 roles (233 checks) and 109 end-to-end tests — all passing.


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
| **`html-demo/`** | **Plain HTML + CSS + JavaScript version of all 137 pages. Double-click `html-demo/index.html` — no server needed** (view only, saving is disabled) |
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
| `python/tests/test_e2e.py` | **109 automated end-to-end acceptance and security tests** |
| `python/tests/browser_walkthrough.js` | Real-browser walkthrough: every menu of every role + key tasks (233 checks) |
| `app/public/install.php` | One-time web installer (Demo or Empty system); disabled automatically after installation |
| `docs/` | Architecture review, ER diagrams, API design, implementation notes, **screenshots** |
| `database/schema.sql` | The original PostgreSQL design reviewed in phase 1 (kept for reference) |

## Pages (as in the approved screen designs)

| # | Page | Who | Screenshot |
|---|---|---|---|
| 1 | Home page (public website) | Everyone | [view](docs/screenshots/01-home-public-website.png) |
| 2 | Login / role selection | Everyone | [view](docs/screenshots/02-login-role-selection.png) |
| 3 | State Admin Dashboard | State Admin | [view](docs/screenshots/03-state-admin-dashboard.png) |
| 4 | RO / DVTO / Apprentice Advisor Dashboards | RO, DVTO, Advisor | [RO](docs/screenshots/04a-ro-admin-dashboard.png) · [DVTO](docs/screenshots/04-dvto-dashboard.png) · [Advisor](docs/screenshots/04b-apprentice-advisor-dashboard.png) |
| 5 | Principal Dashboard | Principal | [view](docs/screenshots/05-principal-dashboard.png) |
| 6 | Teacher Dashboard | Teacher | [view](docs/screenshots/06-teacher-dashboard.png) |
| 7 | Placement Officer Dashboard | Placement Officer | [view](docs/screenshots/07-placement-officer-dashboard.png) |
| 8 | Student / Alumni Dashboard | Student | [view](docs/screenshots/08-student-alumni-dashboard.png) |
| 9 | Employer / HR Dashboard | Employer | [view](docs/screenshots/09-employer-dashboard.png) |
| 10 | Student Admission (5-step form) | Principal | [view](docs/screenshots/10-student-admission-form.png) |
| 11 | Student Career Profile & Timeline | Staff in scope, the student | [view](docs/screenshots/11-student-career-profile.png) |
| 12 | Student Transfer | Principals | [view](docs/screenshots/12-student-transfer.png) |
| 13 | Reports & Analytics (+ CSV) | State / District / ITI | [view](docs/screenshots/13-reports-analytics.png) |
| 14 | Company & Vacancy Management | Placement Officer | [view](docs/screenshots/14-company-vacancy-management.png) |
| 15 | Follow-up & Task Management | Principal / Placement / Staff | [view](docs/screenshots/15-follow-up-task-management.png) |
| 16 | Permanent Career ID (PCID) card with QR | Student, staff | [view](docs/screenshots/16-pcid-card.png) |
| 17 | Public PCID verification (QR target) | Everyone | [view](docs/screenshots/17-public-pcid-verification.png) |
| 18 | User management (State Admin creates user IDs) | State Admin | [view](docs/screenshots/18-user-management-state-admin.png) |
| 19 | Employer: student's full history (view only) | Employer | [view](docs/screenshots/19-employer-student-full-history.png) |
| 20 | Instructor: all students of own trade | Instructor | [view](docs/screenshots/20-instructor-trade-students.png) |

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

## Roles (who sees what)

| Role | Sees | Can change |
|---|---|---|
| **State Admin (DVET)** | All of Maharashtra | Creates all user IDs, ITIs, master data; resets passwords |
| **RO Admin** (6 Regional Offices: Mumbai, Pune, Nashik, Chh. Sambhajinagar, Amravati, Nagpur) | Full data of all ITIs in the region | Verification decisions (view + reports) |
| **DVTO Admin** | Full data of all ITIs in the district | Verification decisions (view + reports) |
| **Apprentice Advisor** | Full data of the ITIs assigned to them | Verification, company approval |
| **Principal** | Full data of own ITI | Admissions, transfers, results, career records, staff passwords |
| **Instructor** | All students (current + alumni) of **their trade(s)** at their ITI | Attendance, module progress, assessments |
| **Student / Alumni** | Own Career Passport | Adds new job / company / course (pending verification) |
| **Employer / HR** | Full career history (view only) of students who work, train or applied with consent | Adds new job / apprenticeship records **only for own company** (needs PCID + date of birth); cannot edit or delete |
| Placement Officer, ITI Staff | Own ITI / assigned follow-ups | Placements, vacancies, follow-ups |

**User IDs:** only the State Admin creates them (Users page). A Principal's User ID is the ITI's **NCVT MIS code**;
the *Create ITI logins* button creates one for every ITI at once. New accounts get the default password **`Test@12345`**
and must change it at first login. Everyone can change their password under **Settings**. Login works with User ID, e-mail or mobile.

## Demo accounts (password `Demo@1234`)

On the login page you can also just click a role on the right — in DEMO mode it fills that role's demo account.

| Role | User ID / e-mail |
|---|---|
| State Admin (DVET) | `DVET-ADMIN` |
| RO Admin | `RO-PUN` (also `RO-MUM`, `RO-NSK`, `RO-CSN`, `RO-AMR`, `RO-NGP`) |
| DVTO Admin | `DVTO-PUNE`, `DVTO-SATARA`, `DVTO-SANGLI`, `DVTO-SOLAPUR`, `DVTO-KOLHAPUR`, `DVTO-NASHIK` |
| Principal — real Pune-region ITIs (61) | the ITI's NCVT MIS code, e.g. `GU27000009` (list: `database/data/pune_region_itis.csv`) |
| Apprentice Advisor | `AA-PUNE` (9 ITIs of the Pune region) |
| Principal — Govt ITI Pune | `DEMO0001` (the ITI code; every ITI's Principal uses its code) |
| Principal — Govt ITI Nashik | `DEMO0002` |
| Instructor (Fitter, Pune) | `teacher.pune@demo.local` (Electrician: `teacher.pune.ele@demo.local`) |
| Placement Officer (Pune) | `placement.pune@demo.local` |
| ITI Staff (Pune) | `staff.pune@demo.local` |
| Employer / HR | `hr.sahyadri@demo.local` |
| Student / Alumni | `rohit.deshmukh@demo.local` |

## Upgrading an existing database

If you installed an earlier version, run the new migration once (it keeps all data):

```bash
mysql -uroot -p careertrack < database/mysql/05_regions_roles.sql
```

## Run the tests

```bash
CT_DB_USER=root CT_DB_PASS=yourpass python3 python/tests/test_e2e.py --base http://127.0.0.1:8080
```

Expected result: `109 passed, 0 failed`.

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
