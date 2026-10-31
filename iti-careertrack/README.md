# ITI CareerTrack — Architecture & Database Design (v1.0 review draft)

**Status: ⏳ awaiting project-owner approval. No application or frontend code yet.**

| Start here | |
|---|---|
| [docs/00-ARCHITECTURE-REVIEW.md](docs/00-ARCHITECTURE-REVIEW.md) | All decisions in plain language + decisions needed from the owner |
| [docs/01-ER-DIAGRAM.md](docs/01-ER-DIAGRAM.md) | ER diagrams (PNG copies in [docs/diagrams](docs/diagrams)) |
| [docs/02-API-ARCHITECTURE.md](docs/02-API-ARCHITECTURE.md) | API design and endpoint catalogue |
| [database/schema.sql](database/schema.sql) | Complete PostgreSQL 16 schema (48 tables, RLS, triggers, PCID) |
| [database/tests/acceptance_tests.sql](database/tests/acceptance_tests.sql) | 59 database-level acceptance checks (DEMO data) |

## Run the database checks locally

```bash
createdb careertrack_review
```

```bash
psql -d careertrack_review -v ON_ERROR_STOP=1 -f database/schema.sql -f database/tests/acceptance_tests.sql
```

Expected last line: `=== ALL ACCEPTANCE TESTS PASSED ===`. Use a superuser on a throwaway database. The schema creates the role `careertrack_app`.
