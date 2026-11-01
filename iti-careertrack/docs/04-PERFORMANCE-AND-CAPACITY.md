# Performance & capacity — can one database carry Maharashtra?

**Short answer: yes — one MySQL database is enough for all 430+ ITIs, *on the right server*.**
About 1.3 lakh admissions and 1.3 lakh pass-outs a year is a modest volume for MySQL 8 when the
queries use indexes and the dashboards are cached — both of which this build now does.
What does **not** fit is a cheap *shared* hosting plan for statewide production (see "Hosting" below).

---

## 1. How it was tested

| Item | Value |
|---|---|
| Test data | DEMO data multiplied 40× with `python/tools/scale_clone.py`: **2,71,215 students**, 72,160 in training, **37.5 lakh attendance rows**, admissions, PCIDs, results, jobs, apprenticeships, contacts |
| Database size | 1.9 GB (attendance 1.0 GB, everything else 0.9 GB) |
| Worst case built in | each DEMO ITI holds ~6,000–10,000 students (a real ITI has ~300–600 a year) — per-ITI pages were tested much heavier than reality |
| Server | one 4-core machine running Apache (prefork, mod_php), MySQL 8 **and** the load generator together |
| Tools | `python/tools/load_test.py` (virtual users log in and open the pages of their role), slow-query log, `EXPLAIN` |

## 2. What was slow and what was fixed

| Problem found at 2.7 lakh students | Before | After | Fix |
|---|---|---|---|
| State Admin → Students list | 35 s count + 52 s page | **0.05 s** | page starts from students in id order (`STRAIGHT_JOIN`), latest admission by `MAX(id)`, light count query |
| State / RO / DVTO dashboards | 1–4 s each | **~0.01 s** (cached) | numbers cached per data scope for 10 min (`App\Core\Cache`) |
| Principal → Examination | 5.9 s | **0.39 s** | results filtered by `iti_id` + new index |
| Placement list count | 1.4 s | 0.35 s (40× data) | count without display columns; ordered index `(iti_id, status, end_date)` |
| Public home / statewide reports | recomputed for every visitor | **1 ms** (cached) | cached statewide figures |
| "Cache stampede" (everyone arrives at 10 am) | 60 s timeouts | none | only one process computes a missing number; the others wait and reuse it |
| Login under load | 64 MB Argon2id per login | 19 MB | OWASP-recommended Argon2id cost (still strong); old hashes upgrade automatically at next login |
| Current career status | through a view | direct indexed join | `career_status_history.current_flag` unique index |

New indexes: `database/mysql/07_performance.sql` (the installer applies it; for an existing database run it once).

## 3. Load test result (steady state)

100 virtual users, each opening a new page every 1–3 seconds, for 90 seconds, on the 2.7-lakh-student database:

| Measure | Result |
|---|---|
| Throughput | **48.8 requests/second** |
| Errors | **0** |
| Median response | **23 ms** |
| 95% of requests | under **1.6 s** (the heavy per-ITI lists of the 40× inflated ITIs) |
| Dashboards, public pages, PCID card, student pages | 1–130 ms median |

A real person opens a page every 10–30 seconds, so 100 such virtual users correspond to roughly
**1,000–2,000 people logged in at the same time** — on a single 4-core box that also ran the test itself.

## 4. How big the database becomes

| Data | Per year (1.3 lakh admissions) | After 5 years |
|---|---|---|
| Students, admissions, PCID, results, career history, contacts … | ~0.4 GB | ~2 GB |
| **Daily attendance** (2.6 lakh trainees × ~250 days ≈ 6.5 crore rows) | **~18 GB** | ~90 GB |

Attendance is by far the largest part. Recommended once the system is live:
keep daily rows for the running and the last completed year, and store a monthly
summary per student for older years (archive job — to be added before year 3).
Everything else stays small for decades.

## 5. Hosting recommendation

| Use | Suitable server |
|---|---|
| Demo, training, pilot with a few ITIs | Shared hosting (Hostinger etc.) — works, tested on MariaDB 10.11 |
| **Statewide production (430+ ITIs)** | A VPS / cloud server or government data-centre VM: **8 vCPU, 16–32 GB RAM, SSD 200 GB+**, MySQL 8 with `innodb_buffer_pool_size` ≈ 60% of RAM, PHP-FPM + OPcache, HTTPS, daily backups + binlog |
| Very large later (many years of attendance, 5,000+ simultaneous users) | Separate database server + 2 web servers behind a load balancer; a MySQL read replica for reports. The code already works this way (no local state except the cache folder; point `storage/cache` to shared storage or keep per-server caches) |

Why not shared hosting for production: shared plans limit database size (usually a few GB),
simultaneous MySQL connections and CPU per account — the attendance volume alone exceeds that in year one.

## 6. Settings

`app/config/config.php` (optional; defaults shown):

```php
'cache' => ['ttl' => 600, 'count_ttl' => 60],   // dashboard numbers 10 min, list totals 1 min; 0 = off
```

## 7. Re-run the tests

```bash
CT_DB_NAME=ct_scale python3 python/tools/scale_clone.py --times 40 --attendance-days 60
```

```bash
python3 python/tools/load_test.py --base http://SERVER/ITI-CareerTrack/app/public --users 100 --seconds 90
```
