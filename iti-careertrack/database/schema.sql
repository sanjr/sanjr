-- =============================================================================
-- ITI CareerTrack — PostgreSQL schema  (DESIGN DRAFT v1.0 — FOR REVIEW)
-- -----------------------------------------------------------------------------
-- Target: PostgreSQL 16+
-- Status: architecture review artefact. Not yet a production migration.
--         After owner approval this file is split into numbered migrations.
--
-- Conventions
--   * Primary keys: UUID (gen_random_uuid) for business entities — safe to
--     expose in API URLs, not guessable. High-volume logs use BIGINT identity.
--   * PCID is a unique BUSINESS identifier stored in pcid_registry. It is never
--     used as a foreign key (internal ids are).
--   * Status columns use TEXT + CHECK (easy to change by migration) rather than
--     PostgreSQL ENUM types (hard to change).
--   * Every important table has created_at/by, updated_at/by and a version
--     number (optimistic locking). created_by/updated_by are filled from the
--     session variable app.user_id that the API sets per request.
--   * Lifecycle records are never hard-deleted by the application. Mistakes are
--     "voided" with a reason. The application database role has no DELETE right.
--   * Date ranges are half-open: start_date is the first day, end_date is the
--     first day the record no longer applies (so a transfer on day X ends the
--     old episode on X and starts the new one on X, with no overlap or gap).
--   * Row-Level Security (RLS) is the second line of defence behind API checks:
--     even a buggy query cannot read another institute's students.
-- =============================================================================

BEGIN;

CREATE EXTENSION IF NOT EXISTS pgcrypto;    -- gen_random_uuid(), digest()
CREATE EXTENSION IF NOT EXISTS pg_trgm;     -- fuzzy name matching (duplicates)
CREATE EXTENSION IF NOT EXISTS btree_gist;  -- "no overlapping date ranges" rules
CREATE EXTENSION IF NOT EXISTS citext;      -- case-insensitive e-mail

CREATE SCHEMA IF NOT EXISTS app;            -- helper functions live here

-- -----------------------------------------------------------------------------
-- 0. Request context helpers (set by the API at the start of every request)
--    SET LOCAL app.user_id = '<uuid>'; SET LOCAL app.role_assignment_id = '<uuid>';
-- -----------------------------------------------------------------------------
CREATE FUNCTION app.current_user_id() RETURNS uuid
LANGUAGE sql STABLE AS
$$ SELECT nullif(current_setting('app.user_id', true), '')::uuid $$;

CREATE FUNCTION app.current_role_assignment_id() RETURNS uuid
LANGUAGE sql STABLE AS
$$ SELECT nullif(current_setting('app.role_assignment_id', true), '')::uuid $$;

CREATE FUNCTION app.normalize_name(t text) RETURNS text
LANGUAGE sql IMMUTABLE PARALLEL SAFE AS
$$ SELECT btrim(regexp_replace(regexp_replace(lower(coalesce(t, '')), '[^a-z ]', '', 'g'), '\s+', ' ', 'g')) $$;

CREATE FUNCTION app.normalize_mobile(t text) RETURNS text
LANGUAGE sql IMMUTABLE PARALLEL SAFE AS
$$ SELECT nullif(right(regexp_replace(coalesce(t, ''), '\D', '', 'g'), 10), '') $$;

-- =============================================================================
-- 1. USERS (created first because every table records created_by/updated_by)
-- =============================================================================
CREATE TABLE users (
    id                    uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    full_name             text NOT NULL,
    email                 citext UNIQUE,
    mobile                text UNIQUE CHECK (mobile ~ '^[6-9][0-9]{9}$'),
    password_hash         text,                 -- Argon2id hash; never plaintext
    mfa_enabled           boolean NOT NULL DEFAULT false,
    mfa_secret_encrypted  bytea,
    status                text NOT NULL DEFAULT 'INVITED'
                          CHECK (status IN ('INVITED','ACTIVE','LOCKED','DISABLED')),
    student_id            uuid UNIQUE,          -- set for Student/Alumni logins (FK added later)
    failed_login_count    integer NOT NULL DEFAULT 0,
    last_login_at         timestamptz,
    is_demo               boolean NOT NULL DEFAULT false,
    created_at            timestamptz NOT NULL DEFAULT now(),
    created_by            uuid REFERENCES users(id),
    updated_at            timestamptz NOT NULL DEFAULT now(),
    updated_by            uuid REFERENCES users(id),
    version               integer NOT NULL DEFAULT 1,
    CHECK (email IS NOT NULL OR mobile IS NOT NULL)
);

-- =============================================================================
-- 2. GEOGRAPHY & INSTITUTES (the tenant boundary)
-- =============================================================================
CREATE TABLE districts (
    id          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    code        text NOT NULL UNIQUE,
    name        text NOT NULL UNIQUE,
    name_mr     text,
    division    text,
    state_code  char(2) NOT NULL DEFAULT 'MH',
    created_at  timestamptz NOT NULL DEFAULT now(),
    created_by  uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at  timestamptz NOT NULL DEFAULT now(),
    updated_by  uuid REFERENCES users(id),
    version     integer NOT NULL DEFAULT 1
);

CREATE TABLE talukas (
    id          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    district_id uuid NOT NULL REFERENCES districts(id),
    code        text NOT NULL UNIQUE,
    name        text NOT NULL,
    name_mr     text,
    created_at  timestamptz NOT NULL DEFAULT now(),
    created_by  uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at  timestamptz NOT NULL DEFAULT now(),
    updated_by  uuid REFERENCES users(id),
    version     integer NOT NULL DEFAULT 1,
    UNIQUE (district_id, name),
    UNIQUE (id, district_id)
);

CREATE TABLE itis (
    id              uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    iti_code        text NOT NULL UNIQUE,      -- official institute code (source TBC)
    name            text NOT NULL,
    name_mr         text,
    ownership_type  text NOT NULL CHECK (ownership_type IN ('GOVERNMENT','PRIVATE')),
    district_id     uuid NOT NULL REFERENCES districts(id),
    taluka_id       uuid,
    address_line    text,
    pincode         text CHECK (pincode ~ '^[1-9][0-9]{5}$'),
    phone           text,
    email           citext,
    status          text NOT NULL DEFAULT 'ACTIVE'
                    CHECK (status IN ('ACTIVE','INACTIVE','SUSPENDED','CLOSED')),
    is_demo         boolean NOT NULL DEFAULT false,
    created_at      timestamptz NOT NULL DEFAULT now(),
    created_by      uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    updated_by      uuid REFERENCES users(id),
    version         integer NOT NULL DEFAULT 1,
    -- the taluka must be inside the ITI's district
    FOREIGN KEY (taluka_id, district_id) REFERENCES talukas(id, district_id)
);
CREATE INDEX itis_district_idx ON itis(district_id);

-- =============================================================================
-- 3. TRADES, MODULES, ACADEMIC YEARS, BATCHES (unit + shift)
-- =============================================================================
CREATE TABLE trades (
    id               uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    trade_code       text NOT NULL UNIQUE,
    name             text NOT NULL,
    name_mr          text,
    sector           text,
    scheme           text NOT NULL CHECK (scheme IN ('NCVT','SCVT')),
    duration_months  smallint NOT NULL CHECK (duration_months > 0),
    status           text NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','RETIRED')),
    created_at       timestamptz NOT NULL DEFAULT now(),
    created_by       uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at       timestamptz NOT NULL DEFAULT now(),
    updated_by       uuid REFERENCES users(id),
    version          integer NOT NULL DEFAULT 1
);

CREATE TABLE trade_modules (
    id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    trade_id     uuid NOT NULL REFERENCES trades(id),
    module_code  text NOT NULL,
    name         text NOT NULL,
    sequence_no  smallint NOT NULL,
    created_at   timestamptz NOT NULL DEFAULT now(),
    created_by   uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at   timestamptz NOT NULL DEFAULT now(),
    updated_by   uuid REFERENCES users(id),
    version      integer NOT NULL DEFAULT 1,
    UNIQUE (trade_id, module_code)
);

-- Which ITI offers which trade (many-to-many)
CREATE TABLE iti_trades (
    id                 uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    iti_id             uuid NOT NULL REFERENCES itis(id),
    trade_id           uuid NOT NULL REFERENCES trades(id),
    sanctioned_units   smallint NOT NULL DEFAULT 1 CHECK (sanctioned_units > 0),
    seats_per_unit     smallint CHECK (seats_per_unit > 0),
    status             text NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','SUSPENDED','WITHDRAWN')),
    valid_from         date,
    valid_to           date,
    created_at         timestamptz NOT NULL DEFAULT now(),
    created_by         uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at         timestamptz NOT NULL DEFAULT now(),
    updated_by         uuid REFERENCES users(id),
    version            integer NOT NULL DEFAULT 1,
    UNIQUE (iti_id, trade_id),
    CHECK (valid_to IS NULL OR valid_from IS NULL OR valid_to >= valid_from)
);

CREATE TABLE academic_years (
    id          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    label       text NOT NULL UNIQUE,                       -- e.g. '2027-28'
    start_date  date NOT NULL,
    end_date    date NOT NULL,
    status      text NOT NULL DEFAULT 'PLANNED' CHECK (status IN ('PLANNED','OPEN','CLOSED')),
    created_at  timestamptz NOT NULL DEFAULT now(),
    created_by  uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at  timestamptz NOT NULL DEFAULT now(),
    updated_by  uuid REFERENCES users(id),
    version     integer NOT NULL DEFAULT 1,
    CHECK (end_date > start_date),
    EXCLUDE USING gist (daterange(start_date, end_date, '[]') WITH &&)   -- years never overlap
);

-- A batch = one unit of one trade, in one shift, for one intake year
CREATE TABLE batches (
    id                uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    iti_id            uuid NOT NULL,
    trade_id          uuid NOT NULL,
    academic_year_id  uuid NOT NULL REFERENCES academic_years(id),
    unit_no           smallint NOT NULL CHECK (unit_no > 0),
    shift             text NOT NULL CHECK (shift IN ('GENERAL','FIRST','SECOND','THIRD')),
    capacity          smallint CHECK (capacity > 0),
    created_at        timestamptz NOT NULL DEFAULT now(),
    created_by        uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at        timestamptz NOT NULL DEFAULT now(),
    updated_by        uuid REFERENCES users(id),
    version           integer NOT NULL DEFAULT 1,
    FOREIGN KEY (iti_id, trade_id) REFERENCES iti_trades(iti_id, trade_id),
    UNIQUE (iti_id, trade_id, academic_year_id, unit_no, shift),
    UNIQUE (id, iti_id, trade_id)
);

-- =============================================================================
-- 4. EMPLOYERS & COLLEGES (needed before role scoping and career records)
-- =============================================================================
CREATE TABLE companies (
    id               uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    legal_name       text NOT NULL,
    display_name     text,
    cin              text UNIQUE,
    gstin            text UNIQUE CHECK (gstin ~ '^[0-9]{2}[A-Z0-9]{13}$'),
    industry_sector  text,
    company_size     text CHECK (company_size IN ('MICRO','SMALL','MEDIUM','LARGE')),
    website          text,
    status           text NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('PENDING_APPROVAL','ACTIVE','SUSPENDED')),
    is_demo          boolean NOT NULL DEFAULT false,
    created_at       timestamptz NOT NULL DEFAULT now(),
    created_by       uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at       timestamptz NOT NULL DEFAULT now(),
    updated_by       uuid REFERENCES users(id),
    version          integer NOT NULL DEFAULT 1
);
CREATE INDEX companies_name_trgm ON companies USING gin (app.normalize_name(legal_name) gin_trgm_ops);

CREATE TABLE company_branches (
    id            uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id    uuid NOT NULL REFERENCES companies(id),
    branch_name   text NOT NULL,
    address_line  text,
    city          text,
    district_id   uuid REFERENCES districts(id),   -- NULL when outside Maharashtra
    state_code    char(2) NOT NULL DEFAULT 'MH',
    country_code  char(2) NOT NULL DEFAULT 'IN',
    pincode       text,
    status        text NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','CLOSED')),
    created_at    timestamptz NOT NULL DEFAULT now(),
    created_by    uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at    timestamptz NOT NULL DEFAULT now(),
    updated_by    uuid REFERENCES users(id),
    version       integer NOT NULL DEFAULT 1,
    UNIQUE (company_id, branch_name),
    UNIQUE (id, company_id)                        -- lets children prove "branch belongs to company"
);

CREATE TABLE colleges (
    id            uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    name          text NOT NULL,
    university    text,
    college_code  text UNIQUE,
    district_id   uuid REFERENCES districts(id),
    state_code    char(2) NOT NULL DEFAULT 'MH',
    address_line  text,
    created_at    timestamptz NOT NULL DEFAULT now(),
    created_by    uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at    timestamptz NOT NULL DEFAULT now(),
    updated_by    uuid REFERENCES users(id),
    version       integer NOT NULL DEFAULT 1
);

-- =============================================================================
-- 5. ROLES, PERMISSIONS, SCOPED ROLE ASSIGNMENTS
-- =============================================================================
CREATE TABLE roles (
    id          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    code        text NOT NULL UNIQUE,
    name        text NOT NULL,
    scope_type  text NOT NULL CHECK (scope_type IN ('STATE','DISTRICT','ITI','COMPANY','SELF')),
    UNIQUE (id, scope_type)
);

CREATE TABLE permissions (
    id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    code         text NOT NULL UNIQUE,           -- e.g. 'student.contact.view'
    description  text NOT NULL
);

CREATE TABLE role_permissions (
    role_id        uuid NOT NULL REFERENCES roles(id),
    permission_id  uuid NOT NULL REFERENCES permissions(id),
    PRIMARY KEY (role_id, permission_id)
);

-- "User X holds role Y for scope Z between dates A and B"
CREATE TABLE user_role_assignments (
    id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id      uuid NOT NULL REFERENCES users(id),
    role_id      uuid NOT NULL,
    scope_type   text NOT NULL,
    district_id  uuid REFERENCES districts(id),
    iti_id       uuid REFERENCES itis(id),
    company_id   uuid REFERENCES companies(id),
    valid_from   date NOT NULL DEFAULT current_date,
    valid_to     date,
    status       text NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','SUSPENDED','REVOKED')),
    created_at   timestamptz NOT NULL DEFAULT now(),
    created_by   uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at   timestamptz NOT NULL DEFAULT now(),
    updated_by   uuid REFERENCES users(id),
    version      integer NOT NULL DEFAULT 1,
    FOREIGN KEY (role_id, scope_type) REFERENCES roles(id, scope_type),
    CHECK (valid_to IS NULL OR valid_to >= valid_from),
    -- exactly the right scope column is filled for the role's scope type
    CHECK (
        (scope_type IN ('STATE','SELF') AND num_nonnulls(district_id, iti_id, company_id) = 0) OR
        (scope_type = 'DISTRICT' AND district_id IS NOT NULL AND iti_id IS NULL AND company_id IS NULL) OR
        (scope_type = 'ITI'      AND iti_id IS NOT NULL AND district_id IS NULL AND company_id IS NULL) OR
        (scope_type = 'COMPANY'  AND company_id IS NOT NULL AND district_id IS NULL AND iti_id IS NULL)
    )
);
CREATE UNIQUE INDEX ura_no_duplicate_active
    ON user_role_assignments (user_id, role_id,
        coalesce(iti_id, district_id, company_id, '00000000-0000-0000-0000-000000000000'::uuid))
    WHERE status = 'ACTIVE';
CREATE INDEX ura_iti_idx ON user_role_assignments(iti_id) WHERE iti_id IS NOT NULL;

-- =============================================================================
-- 6. STUDENTS, CONTACTS, PCID
-- =============================================================================
CREATE TABLE students (
    id                      uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    first_name              text NOT NULL,
    middle_name             text,                     -- often father's/husband's name
    last_name               text,
    full_name_mr            text,                     -- name in Marathi (display only)
    full_name_normalized    text GENERATED ALWAYS AS
                            (app.normalize_name(first_name || ' ' || coalesce(middle_name, '') || ' ' || coalesce(last_name, ''))) STORED,
    gender                  text NOT NULL CHECK (gender IN ('MALE','FEMALE','TRANSGENDER','UNDISCLOSED')),
    date_of_birth           date NOT NULL CHECK (date_of_birth > DATE '1940-01-01'),
    guardian_name           text,
    is_pwd                  boolean NOT NULL DEFAULT false,
    -- Strong de-duplication key WITHOUT Aadhaar (SSC/10th board seat number). Confirm policy.
    ssc_board               text,
    ssc_seat_number         text,
    ssc_passing_year        smallint CHECK (ssc_passing_year BETWEEN 1960 AND 2100),
    -- Only if legally approved: keyed HMAC of a government ID. NEVER the raw number.
    national_id_hash        bytea UNIQUE,
    profile_status          text NOT NULL DEFAULT 'ACTIVE'
                            CHECK (profile_status IN ('ACTIVE','MERGED','DECEASED','INACTIVE')),
    merged_into_student_id  uuid REFERENCES students(id),
    is_demo                 boolean NOT NULL DEFAULT false,
    created_at              timestamptz NOT NULL DEFAULT now(),
    created_by              uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at              timestamptz NOT NULL DEFAULT now(),
    updated_by              uuid REFERENCES users(id),
    version                 integer NOT NULL DEFAULT 1,
    CHECK ((profile_status = 'MERGED') = (merged_into_student_id IS NOT NULL)),
    CHECK (merged_into_student_id IS NULL OR merged_into_student_id <> id),
    CHECK (num_nonnulls(ssc_board, ssc_seat_number, ssc_passing_year) IN (0, 3)),
    UNIQUE (ssc_board, ssc_seat_number, ssc_passing_year)
);
CREATE INDEX students_name_trgm ON students USING gin (full_name_normalized gin_trgm_ops);
CREATE INDEX students_dob_idx   ON students(date_of_birth);

ALTER TABLE users ADD CONSTRAINT users_student_fk FOREIGN KEY (student_id) REFERENCES students(id);

-- Contact details are separated because they are sensitive and need a separate permission.
-- History is kept: a changed mobile number closes the old row (valid_to) and adds a new one.
CREATE TABLE student_contacts (
    id                uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id        uuid NOT NULL REFERENCES students(id),
    contact_type      text NOT NULL CHECK (contact_type IN ('MOBILE','WHATSAPP','EMAIL','GUARDIAN_MOBILE','ADDRESS')),
    value             text NOT NULL,
    value_normalized  text,
    district_id       uuid REFERENCES districts(id),   -- for ADDRESS
    pincode           text,
    is_primary        boolean NOT NULL DEFAULT false,
    is_verified       boolean NOT NULL DEFAULT false,
    valid_from        timestamptz NOT NULL DEFAULT now(),
    valid_to          timestamptz,
    created_at        timestamptz NOT NULL DEFAULT now(),
    created_by        uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at        timestamptz NOT NULL DEFAULT now(),
    updated_by        uuid REFERENCES users(id),
    version           integer NOT NULL DEFAULT 1,
    CHECK (valid_to IS NULL OR valid_to >= valid_from)
);
CREATE UNIQUE INDEX student_contacts_one_primary
    ON student_contacts(student_id, contact_type) WHERE is_primary AND valid_to IS NULL;
CREATE INDEX student_contacts_value_idx ON student_contacts(value_normalized) WHERE valid_to IS NULL;
CREATE INDEX student_contacts_student_idx ON student_contacts(student_id);

-- PCID: one counter row per issue year -> gap-free, collision-free serials
CREATE TABLE pcid_year_counters (
    issue_year   smallint PRIMARY KEY CHECK (issue_year BETWEEN 2020 AND 2100),
    last_serial  integer NOT NULL CHECK (last_serial BETWEEN 0 AND 999999)
);

CREATE TABLE pcid_registry (
    id                       uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    pcid                     text NOT NULL UNIQUE CHECK (pcid ~ '^ITIC-MH-[0-9]{4}-[0-9]{6}-[0-9]$'),
    student_id               uuid NOT NULL REFERENCES students(id),
    issue_year               smallint NOT NULL,
    serial                   integer NOT NULL,
    status                   text NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','RETIRED_MERGED','VOID')),
    issued_for_admission_id  uuid,                         -- FK added after admissions
    issued_at                timestamptz NOT NULL DEFAULT now(),
    issued_by                uuid REFERENCES users(id),
    retired_at               timestamptz,
    retired_reason           text,
    redirect_to_pcid_id      uuid REFERENCES pcid_registry(id),  -- merged duplicate -> survivor's PCID
    UNIQUE (issue_year, serial),
    CHECK ((status = 'ACTIVE') = (retired_at IS NULL))
);
-- exactly one ACTIVE PCID per student (retired PCIDs stay for lookup/redirect)
CREATE UNIQUE INDEX pcid_one_active_per_student ON pcid_registry(student_id) WHERE status = 'ACTIVE';

-- Possible duplicates found by matching; reviewed by staff, never auto-merged
CREATE TABLE duplicate_candidates (
    id             uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id_a   uuid NOT NULL REFERENCES students(id),
    student_id_b   uuid NOT NULL REFERENCES students(id),
    match_score    smallint NOT NULL,
    match_reasons  text[] NOT NULL,
    status         text NOT NULL DEFAULT 'OPEN' CHECK (status IN ('OPEN','CONFIRMED_DUPLICATE','NOT_DUPLICATE')),
    reviewed_by    uuid REFERENCES users(id),
    reviewed_at    timestamptz,
    created_at     timestamptz NOT NULL DEFAULT now(),
    CHECK (student_id_a < student_id_b),
    UNIQUE (student_id_a, student_id_b)
);

CREATE TABLE student_merges (
    id                   uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    survivor_student_id  uuid NOT NULL REFERENCES students(id),
    merged_student_id    uuid NOT NULL UNIQUE REFERENCES students(id),
    reason               text NOT NULL,
    approved_by          uuid NOT NULL REFERENCES users(id),
    merged_at            timestamptz NOT NULL DEFAULT now(),
    CHECK (survivor_student_id <> merged_student_id)
);

-- =============================================================================
-- 7. ADMISSIONS, INSTITUTE AFFILIATIONS, TEACHER ASSIGNMENTS, TRANSFERS
-- =============================================================================
CREATE TABLE admissions (
    id                     uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id             uuid NOT NULL REFERENCES students(id),
    iti_id                 uuid NOT NULL,
    trade_id               uuid NOT NULL,
    academic_year_id       uuid NOT NULL REFERENCES academic_years(id),
    batch_id               uuid,
    admission_type         text NOT NULL DEFAULT 'REGULAR'
                           CHECK (admission_type IN ('REGULAR','TRANSFER_IN','RE_ADMISSION')),
    previous_admission_id  uuid REFERENCES admissions(id),
    admission_date         date NOT NULL,
    duration_months        smallint NOT NULL CHECK (duration_months > 0),
    external_admission_ref text,          -- state admission portal reference, if any
    status                 text NOT NULL DEFAULT 'ACCEPTED'
                           CHECK (status IN ('ACCEPTED','ACTIVE','COMPLETED','TRANSFERRED_OUT','DISCONTINUED','CANCELLED')),
    end_date               date,
    end_reason             text,
    created_at             timestamptz NOT NULL DEFAULT now(),
    created_by             uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at             timestamptz NOT NULL DEFAULT now(),
    updated_by             uuid REFERENCES users(id),
    version                integer NOT NULL DEFAULT 1,
    FOREIGN KEY (iti_id, trade_id) REFERENCES iti_trades(iti_id, trade_id),          -- trade must be offered by that ITI
    FOREIGN KEY (batch_id, iti_id, trade_id) REFERENCES batches(id, iti_id, trade_id), -- batch must match ITI + trade
    CHECK (admission_type <> 'TRANSFER_IN' OR previous_admission_id IS NOT NULL),
    CHECK (end_date IS NULL OR end_date >= admission_date),
    CHECK ((status IN ('ACCEPTED','ACTIVE')) = (end_date IS NULL)),
    UNIQUE (academic_year_id, external_admission_ref),
    UNIQUE (id, iti_id),
    UNIQUE (id, student_id)
);
-- a student can be "live" in only ONE admission at a time
CREATE UNIQUE INDEX admissions_one_live_per_student ON admissions(student_id) WHERE status IN ('ACCEPTED','ACTIVE');
CREATE INDEX admissions_iti_year_idx ON admissions(iti_id, academic_year_id);

ALTER TABLE pcid_registry ADD CONSTRAINT pcid_admission_fk
    FOREIGN KEY (issued_for_admission_id) REFERENCES admissions(id);

-- Which institute "owns" the student over time. Maintained automatically from admissions.
-- This is the anchor for Principal / District access.
CREATE TABLE institute_affiliations (
    id            uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id    uuid NOT NULL REFERENCES students(id),
    iti_id        uuid NOT NULL REFERENCES itis(id),
    admission_id  uuid NOT NULL UNIQUE,
    start_date    date NOT NULL,
    end_date      date,
    status        text NOT NULL CHECK (status IN ('ACTIVE','COMPLETED','TRANSFERRED_OUT','DISCONTINUED')),
    created_at    timestamptz NOT NULL DEFAULT now(),
    created_by    uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at    timestamptz NOT NULL DEFAULT now(),
    updated_by    uuid REFERENCES users(id),
    version       integer NOT NULL DEFAULT 1,
    FOREIGN KEY (admission_id, iti_id)     REFERENCES admissions(id, iti_id),
    FOREIGN KEY (admission_id, student_id) REFERENCES admissions(id, student_id),
    CHECK (end_date IS NULL OR end_date >= start_date),
    CHECK ((status = 'ACTIVE') = (end_date IS NULL)),
    -- the same student can never be enrolled at two ITIs over overlapping dates
    EXCLUDE USING gist (student_id WITH =, daterange(start_date, end_date, '[)') WITH &&)
);
CREATE INDEX affiliations_iti_idx ON institute_affiliations(iti_id, status);
CREATE INDEX affiliations_student_idx ON institute_affiliations(student_id);

CREATE TABLE teacher_assignments (
    id                uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id        uuid NOT NULL REFERENCES students(id),
    teacher_user_id   uuid NOT NULL REFERENCES users(id),
    iti_id            uuid NOT NULL,
    admission_id      uuid NOT NULL,
    assignment_role   text NOT NULL DEFAULT 'TRADE_INSTRUCTOR'
                      CHECK (assignment_role IN ('TRADE_INSTRUCTOR','EMPLOYABILITY_SKILLS','WORKSHOP_CALC_SCIENCE','ENGINEERING_DRAWING','CLASS_TEACHER')),
    start_date        date NOT NULL DEFAULT current_date,
    end_date          date,
    end_reason        text,
    created_at        timestamptz NOT NULL DEFAULT now(),
    created_by        uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at        timestamptz NOT NULL DEFAULT now(),
    updated_by        uuid REFERENCES users(id),
    version           integer NOT NULL DEFAULT 1,
    FOREIGN KEY (admission_id, iti_id)     REFERENCES admissions(id, iti_id),
    FOREIGN KEY (admission_id, student_id) REFERENCES admissions(id, student_id),
    CHECK (end_date IS NULL OR end_date >= start_date)
);
CREATE UNIQUE INDEX teacher_assignments_one_open
    ON teacher_assignments(student_id, teacher_user_id, assignment_role) WHERE end_date IS NULL;
CREATE INDEX teacher_assignments_teacher_idx ON teacher_assignments(teacher_user_id, iti_id);

CREATE TABLE transfer_requests (
    id                        uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id                uuid NOT NULL REFERENCES students(id),
    from_iti_id               uuid NOT NULL,
    from_admission_id         uuid NOT NULL,
    to_iti_id                 uuid NOT NULL,
    to_trade_id               uuid NOT NULL,
    to_academic_year_id       uuid NOT NULL REFERENCES academic_years(id),
    to_batch_id               uuid,
    requested_effective_date  date NOT NULL,
    reason                    text NOT NULL CHECK (length(btrim(reason)) >= 5),
    status                    text NOT NULL DEFAULT 'REQUESTED'
                              CHECK (status IN ('REQUESTED','ACCEPTED','REJECTED','CANCELLED')),
    requested_by              uuid NOT NULL REFERENCES users(id) DEFAULT app.current_user_id(),
    requested_at              timestamptz NOT NULL DEFAULT now(),
    decided_by                uuid REFERENCES users(id),
    decided_at                timestamptz,
    decision_remarks          text,
    new_admission_id          uuid REFERENCES admissions(id),
    created_at                timestamptz NOT NULL DEFAULT now(),
    created_by                uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at                timestamptz NOT NULL DEFAULT now(),
    updated_by                uuid REFERENCES users(id),
    version                   integer NOT NULL DEFAULT 1,
    FOREIGN KEY (from_admission_id, from_iti_id) REFERENCES admissions(id, iti_id),
    FOREIGN KEY (from_admission_id, student_id)  REFERENCES admissions(id, student_id),
    FOREIGN KEY (to_iti_id, to_trade_id)         REFERENCES iti_trades(iti_id, trade_id),
    FOREIGN KEY (to_batch_id, to_iti_id, to_trade_id) REFERENCES batches(id, iti_id, trade_id),
    CHECK (from_iti_id <> to_iti_id),
    CHECK ((status IN ('ACCEPTED','REJECTED')) = (decided_at IS NOT NULL)),
    CHECK ((status = 'ACCEPTED') = (new_admission_id IS NOT NULL))
);
CREATE UNIQUE INDEX transfer_one_open_per_student ON transfer_requests(student_id) WHERE status = 'REQUESTED';
CREATE INDEX transfer_to_iti_idx ON transfer_requests(to_iti_id, status);

-- =============================================================================
-- 8. TRAINING, ATTENDANCE, MODULES, ASSESSMENTS, EXAMS
-- =============================================================================
CREATE TABLE training_records (
    id                 uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id         uuid NOT NULL REFERENCES students(id),
    admission_id       uuid NOT NULL,
    iti_id             uuid NOT NULL,
    start_date         date NOT NULL,
    expected_end_date  date,
    actual_end_date    date,
    completion_status  text NOT NULL DEFAULT 'IN_PROGRESS'
                       CHECK (completion_status IN ('IN_PROGRESS','COMPLETED','TRANSFERRED_OUT','DISCONTINUED')),
    created_at         timestamptz NOT NULL DEFAULT now(),
    created_by         uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at         timestamptz NOT NULL DEFAULT now(),
    updated_by         uuid REFERENCES users(id),
    version            integer NOT NULL DEFAULT 1,
    FOREIGN KEY (admission_id, iti_id)     REFERENCES admissions(id, iti_id),
    FOREIGN KEY (admission_id, student_id) REFERENCES admissions(id, student_id),
    CHECK (actual_end_date IS NULL OR actual_end_date >= start_date),
    UNIQUE (id, iti_id),
    UNIQUE (id, student_id)
);
CREATE INDEX training_records_student_idx ON training_records(student_id);

-- Very high volume (≈ students × working days). Partitioned by year for speed & archiving.
CREATE TABLE attendance_records (
    id                  bigint GENERATED ALWAYS AS IDENTITY,
    student_id          uuid NOT NULL REFERENCES students(id),
    training_record_id  uuid NOT NULL,
    iti_id              uuid NOT NULL,
    attendance_date     date NOT NULL,
    session             text NOT NULL DEFAULT 'FULL_DAY' CHECK (session IN ('FULL_DAY','THEORY','PRACTICAL')),
    status              text NOT NULL CHECK (status IN ('PRESENT','ABSENT','LEAVE','HOLIDAY','LATE')),
    marked_by           uuid NOT NULL REFERENCES users(id) DEFAULT app.current_user_id(),
    marked_at           timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (id, attendance_date),
    FOREIGN KEY (training_record_id, iti_id)     REFERENCES training_records(id, iti_id),
    FOREIGN KEY (training_record_id, student_id) REFERENCES training_records(id, student_id),
    UNIQUE (training_record_id, attendance_date, session)
) PARTITION BY RANGE (attendance_date);
CREATE TABLE attendance_records_2026 PARTITION OF attendance_records FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');
CREATE TABLE attendance_records_2027 PARTITION OF attendance_records FOR VALUES FROM ('2027-01-01') TO ('2028-01-01');
CREATE TABLE attendance_records_2028 PARTITION OF attendance_records FOR VALUES FROM ('2028-01-01') TO ('2029-01-01');
CREATE TABLE attendance_records_default PARTITION OF attendance_records DEFAULT;
CREATE INDEX attendance_student_idx ON attendance_records(student_id, attendance_date);

CREATE TABLE module_progress (
    id                  uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    training_record_id  uuid NOT NULL,
    student_id          uuid NOT NULL,
    iti_id              uuid NOT NULL,
    module_id           uuid NOT NULL REFERENCES trade_modules(id),
    progress_percent    numeric(5,2) NOT NULL DEFAULT 0 CHECK (progress_percent BETWEEN 0 AND 100),
    status              text NOT NULL DEFAULT 'NOT_STARTED' CHECK (status IN ('NOT_STARTED','IN_PROGRESS','COMPLETED')),
    completed_on        date,
    created_at          timestamptz NOT NULL DEFAULT now(),
    created_by          uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at          timestamptz NOT NULL DEFAULT now(),
    updated_by          uuid REFERENCES users(id),
    version             integer NOT NULL DEFAULT 1,
    FOREIGN KEY (training_record_id, iti_id)     REFERENCES training_records(id, iti_id),
    FOREIGN KEY (training_record_id, student_id) REFERENCES training_records(id, student_id),
    UNIQUE (training_record_id, module_id),
    CHECK ((status = 'COMPLETED') = (completed_on IS NOT NULL))
);

CREATE TABLE assessments (
    id                  uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id          uuid NOT NULL,
    training_record_id  uuid NOT NULL,
    iti_id              uuid NOT NULL,
    module_id           uuid REFERENCES trade_modules(id),
    assessment_type     text NOT NULL CHECK (assessment_type IN ('FORMATIVE','INTERNAL_THEORY','INTERNAL_PRACTICAL','PROJECT','OTHER')),
    assessment_date     date NOT NULL,
    max_marks           numeric(6,2) NOT NULL CHECK (max_marks > 0),
    marks_obtained      numeric(6,2) CHECK (marks_obtained >= 0),
    assessor_user_id    uuid NOT NULL REFERENCES users(id) DEFAULT app.current_user_id(),
    remarks             text,
    voided_at           timestamptz,
    void_reason         text,
    created_at          timestamptz NOT NULL DEFAULT now(),
    created_by          uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at          timestamptz NOT NULL DEFAULT now(),
    updated_by          uuid REFERENCES users(id),
    version             integer NOT NULL DEFAULT 1,
    FOREIGN KEY (training_record_id, iti_id)     REFERENCES training_records(id, iti_id),
    FOREIGN KEY (training_record_id, student_id) REFERENCES training_records(id, student_id),
    CHECK (marks_obtained IS NULL OR marks_obtained <= max_marks),
    CHECK ((voided_at IS NULL) = (void_reason IS NULL))
);

CREATE TABLE exam_sessions (
    id             uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    name           text NOT NULL,                       -- e.g. 'AITT July 2028'
    exam_body      text NOT NULL CHECK (exam_body IN ('NCVT','SCVT')),
    exam_type      text NOT NULL CHECK (exam_type IN ('MAIN','SUPPLEMENTARY')),
    session_month  date NOT NULL CHECK (extract(day FROM session_month) = 1),
    created_at     timestamptz NOT NULL DEFAULT now(),
    created_by     uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at     timestamptz NOT NULL DEFAULT now(),
    updated_by     uuid REFERENCES users(id),
    version        integer NOT NULL DEFAULT 1,
    UNIQUE (exam_body, exam_type, session_month)
);

-- Each attempt is a separate row: Fail -> Supplementary -> Pass are three rows.
CREATE TABLE exam_results (
    id                   uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id           uuid NOT NULL,
    admission_id         uuid NOT NULL,
    iti_id               uuid NOT NULL,
    exam_session_id      uuid NOT NULL REFERENCES exam_sessions(id),
    attempt_no           smallint NOT NULL CHECK (attempt_no >= 1),
    roll_number          text,
    result               text NOT NULL CHECK (result IN ('PASS','FAIL','SUPPLEMENTARY','ABSENT','WITHHELD','AWAITED')),
    marks_obtained       numeric(7,2),
    max_marks            numeric(7,2),
    grade                text,
    result_declared_on   date,
    certificate_number   text UNIQUE,
    verification_status  text NOT NULL DEFAULT 'PENDING' CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    created_at           timestamptz NOT NULL DEFAULT now(),
    created_by           uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at           timestamptz NOT NULL DEFAULT now(),
    updated_by           uuid REFERENCES users(id),
    version              integer NOT NULL DEFAULT 1,
    FOREIGN KEY (admission_id, iti_id)     REFERENCES admissions(id, iti_id),
    FOREIGN KEY (admission_id, student_id) REFERENCES admissions(id, student_id),
    UNIQUE (admission_id, attempt_no),
    UNIQUE (admission_id, exam_session_id),
    UNIQUE (id, student_id),
    CHECK (marks_obtained IS NULL OR max_marks IS NULL OR marks_obtained <= max_marks),
    CHECK (result <> 'PASS' OR result_declared_on IS NOT NULL)
);
CREATE INDEX exam_results_student_idx ON exam_results(student_id);

CREATE TABLE exam_result_components (
    id              uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    exam_result_id  uuid NOT NULL REFERENCES exam_results(id),
    component       text NOT NULL,                     -- e.g. TRADE_THEORY, TRADE_PRACTICAL
    max_marks       numeric(6,2) NOT NULL CHECK (max_marks > 0),
    marks_obtained  numeric(6,2) CHECK (marks_obtained >= 0),
    result          text CHECK (result IN ('PASS','FAIL','ABSENT')),
    UNIQUE (exam_result_id, component),
    CHECK (marks_obtained IS NULL OR marks_obtained <= max_marks)
);

-- =============================================================================
-- 9. DOCUMENTS (metadata only — files live in private object storage)
-- =============================================================================
CREATE TABLE student_documents (
    id                   uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id           uuid NOT NULL REFERENCES students(id),
    document_type        text NOT NULL CHECK (document_type IN
                         ('MARKSHEET','CERTIFICATE','ADMISSION_LETTER','APPRENTICESHIP_CONTRACT','OFFER_LETTER',
                          'APPOINTMENT_LETTER','EXPERIENCE_LETTER','SALARY_SLIP','BUSINESS_PROOF',
                          'COLLEGE_ADMISSION_PROOF','ID_PROOF','OTHER')),
    storage_key          text NOT NULL UNIQUE,     -- private bucket key, never a public URL
    original_filename    text NOT NULL,
    mime_type            text NOT NULL CHECK (mime_type IN ('application/pdf','image/jpeg','image/png')),
    size_bytes           integer NOT NULL CHECK (size_bytes BETWEEN 1 AND 10485760),
    sha256               bytea NOT NULL,
    malware_scan_status  text NOT NULL DEFAULT 'PENDING' CHECK (malware_scan_status IN ('PENDING','CLEAN','INFECTED','SKIPPED')),
    verification_status  text NOT NULL DEFAULT 'PENDING' CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    uploaded_by          uuid NOT NULL REFERENCES users(id) DEFAULT app.current_user_id(),
    voided_at            timestamptz,
    void_reason          text,
    created_at           timestamptz NOT NULL DEFAULT now(),
    created_by           uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at           timestamptz NOT NULL DEFAULT now(),
    updated_by           uuid REFERENCES users(id),
    version              integer NOT NULL DEFAULT 1,
    UNIQUE (id, student_id)
);

-- =============================================================================
-- 10. CAREER PATHWAYS (each engagement = its own row; history never overwritten)
-- =============================================================================
CREATE TABLE apprenticeships (
    id                          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id                  uuid NOT NULL REFERENCES students(id),
    company_id                  uuid NOT NULL REFERENCES companies(id),
    branch_id                   uuid,
    trade_id                    uuid REFERENCES trades(id),
    related_admission_id        uuid REFERENCES admissions(id),
    scheme                      text NOT NULL DEFAULT 'OTHER' CHECK (scheme IN ('APPRENTICES_ACT','NAPS','DUAL_SYSTEM','OTHER')),
    contract_number             text,
    start_date                  date NOT NULL,
    planned_end_date            date,
    actual_end_date             date,
    stipend_amount              numeric(12,2) CHECK (stipend_amount >= 0),
    stipend_period              text CHECK (stipend_period IN ('MONTHLY','ANNUAL','DAILY')),
    status                      text NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('PLANNED','ACTIVE','COMPLETED','TERMINATED','DISCONTINUED')),
    completion_certificate_no   text,
    verification_status         text NOT NULL DEFAULT 'PENDING' CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    source                      text NOT NULL CHECK (source IN ('STAFF','STUDENT','EMPLOYER','IMPORT')),
    recorded_by_iti_id          uuid REFERENCES itis(id),
    voided_at                   timestamptz,
    void_reason                 text,
    created_at                  timestamptz NOT NULL DEFAULT now(),
    created_by                  uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at                  timestamptz NOT NULL DEFAULT now(),
    updated_by                  uuid REFERENCES users(id),
    version                     integer NOT NULL DEFAULT 1,
    FOREIGN KEY (branch_id, company_id) REFERENCES company_branches(id, company_id),   -- branch belongs to the company
    CHECK (planned_end_date IS NULL OR planned_end_date >= start_date),
    CHECK (actual_end_date  IS NULL OR actual_end_date  >= start_date),
    CHECK (stipend_amount IS NULL OR stipend_period IS NOT NULL),
    CHECK ((status IN ('COMPLETED','TERMINATED','DISCONTINUED')) = (actual_end_date IS NOT NULL)),
    UNIQUE (id, student_id)
);
CREATE INDEX apprenticeships_student_idx ON apprenticeships(student_id);
CREATE INDEX apprenticeships_company_idx ON apprenticeships(company_id);

CREATE TABLE employments (
    id                      uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id              uuid NOT NULL REFERENCES students(id),
    company_id              uuid NOT NULL REFERENCES companies(id),
    branch_id               uuid,
    from_apprenticeship_id  uuid,                 -- "absorbed after apprenticeship"
    designation             text NOT NULL,
    department              text,
    employment_type         text NOT NULL CHECK (employment_type IN ('FULL_TIME','PART_TIME','CONTRACT','TEMPORARY','GIG')),
    employer_sector         text CHECK (employer_sector IN ('PRIVATE','GOVERNMENT','PSU','NGO','OTHER')),
    work_location           text,
    work_district_id        uuid REFERENCES districts(id),
    work_state_code         char(2),
    work_country_code       char(2) NOT NULL DEFAULT 'IN',
    joining_date            date NOT NULL,
    leaving_date            date,
    leaving_reason          text,
    salary_amount           numeric(12,2) CHECK (salary_amount >= 0),
    salary_period           text CHECK (salary_period IN ('MONTHLY','ANNUAL','DAILY','HOURLY')),
    salary_currency         char(3) NOT NULL DEFAULT 'INR',
    status                  text NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','ENDED')),
    verification_status     text NOT NULL DEFAULT 'PENDING' CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    source                  text NOT NULL CHECK (source IN ('STAFF','STUDENT','EMPLOYER','IMPORT')),
    recorded_by_iti_id      uuid REFERENCES itis(id),
    voided_at               timestamptz,
    void_reason             text,
    created_at              timestamptz NOT NULL DEFAULT now(),
    created_by              uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at              timestamptz NOT NULL DEFAULT now(),
    updated_by              uuid REFERENCES users(id),
    version                 integer NOT NULL DEFAULT 1,
    FOREIGN KEY (branch_id, company_id) REFERENCES company_branches(id, company_id),
    FOREIGN KEY (from_apprenticeship_id, student_id) REFERENCES apprenticeships(id, student_id),
    CHECK (leaving_date IS NULL OR leaving_date >= joining_date),
    CHECK ((status = 'ENDED') = (leaving_date IS NOT NULL)),
    CHECK (salary_amount IS NULL OR salary_period IS NOT NULL),
    UNIQUE (id, student_id)
);
CREATE INDEX employments_student_idx ON employments(student_id);
CREATE INDEX employments_company_idx ON employments(company_id);

CREATE TABLE business_records (
    id                   uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id           uuid NOT NULL REFERENCES students(id),
    business_name        text NOT NULL,
    business_type        text NOT NULL,               -- e.g. repair shop, fabrication unit
    sector               text,
    registration_type    text CHECK (registration_type IN ('UDYAM','GST','SHOP_ACT','UNREGISTERED','OTHER')),
    registration_number  text,
    location             text,
    district_id          uuid REFERENCES districts(id),
    start_date           date NOT NULL,
    end_date             date,
    employees_count      integer CHECK (employees_count >= 0),
    status               text NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','PAUSED','CLOSED')),
    verification_status  text NOT NULL DEFAULT 'PENDING' CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    source               text NOT NULL CHECK (source IN ('STAFF','STUDENT','IMPORT')),
    recorded_by_iti_id   uuid REFERENCES itis(id),
    voided_at            timestamptz,
    void_reason          text,
    created_at           timestamptz NOT NULL DEFAULT now(),
    created_by           uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at           timestamptz NOT NULL DEFAULT now(),
    updated_by           uuid REFERENCES users(id),
    version              integer NOT NULL DEFAULT 1,
    CHECK (end_date IS NULL OR end_date >= start_date),
    CHECK ((status = 'CLOSED') = (end_date IS NOT NULL)),
    UNIQUE (id, student_id)
);
CREATE INDEX business_records_student_idx ON business_records(student_id);

CREATE TABLE higher_education_records (
    id                   uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id           uuid NOT NULL REFERENCES students(id),
    college_id           uuid NOT NULL REFERENCES colleges(id),
    course_name          text NOT NULL,
    course_level         text NOT NULL CHECK (course_level IN ('CERTIFICATE','ADVANCED_ITI','DIPLOMA','DEGREE','POSTGRADUATE','OTHER')),
    study_mode           text NOT NULL DEFAULT 'FULL_TIME' CHECK (study_mode IN ('FULL_TIME','PART_TIME','DISTANCE')),
    admission_year       smallint NOT NULL CHECK (admission_year BETWEEN 1960 AND 2100),
    start_date           date,
    end_date             date,
    status               text NOT NULL DEFAULT 'ENROLLED' CHECK (status IN ('ENROLLED','COMPLETED','DISCONTINUED')),
    verification_status  text NOT NULL DEFAULT 'PENDING' CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    source               text NOT NULL CHECK (source IN ('STAFF','STUDENT','IMPORT')),
    recorded_by_iti_id   uuid REFERENCES itis(id),
    voided_at            timestamptz,
    void_reason          text,
    created_at           timestamptz NOT NULL DEFAULT now(),
    created_by           uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at           timestamptz NOT NULL DEFAULT now(),
    updated_by           uuid REFERENCES users(id),
    version              integer NOT NULL DEFAULT 1,
    CHECK (end_date IS NULL OR start_date IS NULL OR end_date >= start_date),
    UNIQUE (id, student_id)
);
CREATE INDEX higher_education_student_idx ON higher_education_records(student_id);

-- Longitudinal "primary status" timeline. One primary status at any point in time.
-- NOT_UPDATED / NOT_CONTACTABLE are their own values and are never treated as unemployed.
CREATE TABLE career_status_history (
    id                          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id                  uuid NOT NULL REFERENCES students(id),
    status                      text NOT NULL CHECK (status IN
                                ('IN_TRAINING','APPRENTICE','EMPLOYED','SELF_EMPLOYED','HIGHER_EDUCATION',
                                 'SEEKING_EMPLOYMENT','NOT_SEEKING','NOT_UPDATED','NOT_CONTACTABLE','OTHER')),
    effective_from              date NOT NULL,
    effective_to                date,
    source                      text NOT NULL CHECK (source IN ('STAFF','STUDENT','EMPLOYER','SYSTEM','FOLLOW_UP')),
    verification_status         text NOT NULL DEFAULT 'PENDING' CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    apprenticeship_id           uuid,
    employment_id               uuid,
    business_record_id          uuid,
    higher_education_record_id  uuid,
    note                        text,
    created_at                  timestamptz NOT NULL DEFAULT now(),
    created_by                  uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at                  timestamptz NOT NULL DEFAULT now(),
    updated_by                  uuid REFERENCES users(id),
    version                     integer NOT NULL DEFAULT 1,
    FOREIGN KEY (apprenticeship_id, student_id)          REFERENCES apprenticeships(id, student_id),
    FOREIGN KEY (employment_id, student_id)              REFERENCES employments(id, student_id),
    FOREIGN KEY (business_record_id, student_id)         REFERENCES business_records(id, student_id),
    FOREIGN KEY (higher_education_record_id, student_id) REFERENCES higher_education_records(id, student_id),
    CHECK (num_nonnulls(apprenticeship_id, employment_id, business_record_id, higher_education_record_id) <= 1),
    CHECK (effective_to IS NULL OR effective_to > effective_from),
    EXCLUDE USING gist (student_id WITH =, daterange(effective_from, effective_to, '[)') WITH &&),
    UNIQUE (id, student_id)
);

-- =============================================================================
-- 11. FOLLOW-UPS, VERIFICATION, CORRECTIONS, CONSENT
-- =============================================================================
CREATE TABLE follow_ups (
    id                   uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id           uuid NOT NULL REFERENCES students(id),
    iti_id               uuid NOT NULL REFERENCES itis(id),
    assigned_to_user_id  uuid NOT NULL REFERENCES users(id),
    reason               text NOT NULL CHECK (reason IN ('NO_RECENT_UPDATE','POST_COMPLETION','APPRENTICESHIP_ENDING','VERIFICATION','JOB_CHANGE','OTHER')),
    due_date             date NOT NULL,
    channel              text CHECK (channel IN ('PHONE','SMS','WHATSAPP','EMAIL','VISIT','IN_PERSON')),
    status               text NOT NULL DEFAULT 'PENDING' CHECK (status IN ('PENDING','IN_PROGRESS','COMPLETED','CANCELLED')),
    outcome              text CHECK (outcome IN ('UPDATED','NO_CHANGE','NOT_REACHABLE','WRONG_NUMBER','REFUSED')),
    outcome_notes        text,
    completed_at         timestamptz,
    created_at           timestamptz NOT NULL DEFAULT now(),
    created_by           uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at           timestamptz NOT NULL DEFAULT now(),
    updated_by           uuid REFERENCES users(id),
    version              integer NOT NULL DEFAULT 1,
    CHECK ((status = 'COMPLETED') = (outcome IS NOT NULL AND completed_at IS NOT NULL))
);
CREATE INDEX follow_ups_queue_idx ON follow_ups(iti_id, status, due_date);
CREATE INDEX follow_ups_assignee_idx ON follow_ups(assigned_to_user_id, status);

-- Every verification decision is an event. Exactly ONE target per row, enforced by FK.
CREATE TABLE verification_records (
    id                          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id                  uuid NOT NULL REFERENCES students(id),
    exam_result_id              uuid,
    apprenticeship_id           uuid,
    employment_id               uuid,
    business_record_id          uuid,
    higher_education_record_id  uuid,
    student_document_id         uuid,
    verifier_user_id            uuid NOT NULL REFERENCES users(id) DEFAULT app.current_user_id(),
    verifier_company_id         uuid REFERENCES companies(id),       -- set for employer confirmations
    method                      text NOT NULL CHECK (method IN ('DOCUMENT','EMPLOYER_CONFIRMATION','PHONE_CALL','OFFICIAL_RECORD','SITE_VISIT')),
    decision                    text NOT NULL CHECK (decision IN ('VERIFIED','REJECTED','NEEDS_INFO')),
    evidence_document_id        uuid,
    evidence_reference          text,
    remarks                     text,
    decided_at                  timestamptz NOT NULL DEFAULT now(),
    FOREIGN KEY (exam_result_id, student_id)             REFERENCES exam_results(id, student_id),
    FOREIGN KEY (apprenticeship_id, student_id)          REFERENCES apprenticeships(id, student_id),
    FOREIGN KEY (employment_id, student_id)              REFERENCES employments(id, student_id),
    FOREIGN KEY (business_record_id, student_id)         REFERENCES business_records(id, student_id),
    FOREIGN KEY (higher_education_record_id, student_id) REFERENCES higher_education_records(id, student_id),
    FOREIGN KEY (student_document_id, student_id)        REFERENCES student_documents(id, student_id),
    FOREIGN KEY (evidence_document_id, student_id)       REFERENCES student_documents(id, student_id),
    CHECK (num_nonnulls(exam_result_id, apprenticeship_id, employment_id, business_record_id,
                        higher_education_record_id, student_document_id) = 1),
    CHECK (method <> 'EMPLOYER_CONFIRMATION' OR verifier_company_id IS NOT NULL),
    CHECK (decision <> 'VERIFIED' OR evidence_document_id IS NOT NULL OR evidence_reference IS NOT NULL)
);
CREATE INDEX verification_student_idx ON verification_records(student_id);

-- Student / staff-submitted correction requests (never edit verified data silently)
CREATE TABLE correction_requests (
    id              uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id      uuid NOT NULL REFERENCES students(id),
    target_table    text NOT NULL,
    target_id       uuid NOT NULL,
    proposed_changes jsonb NOT NULL,
    reason          text NOT NULL,
    status          text NOT NULL DEFAULT 'SUBMITTED' CHECK (status IN ('SUBMITTED','APPROVED','REJECTED','WITHDRAWN')),
    submitted_by    uuid NOT NULL REFERENCES users(id) DEFAULT app.current_user_id(),
    reviewed_by     uuid REFERENCES users(id),
    reviewed_at     timestamptz,
    review_remarks  text,
    created_at      timestamptz NOT NULL DEFAULT now(),
    created_by      uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    updated_by      uuid REFERENCES users(id),
    version         integer NOT NULL DEFAULT 1
);

CREATE TABLE consents (
    id              uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id      uuid NOT NULL REFERENCES students(id),
    purpose         text NOT NULL CHECK (purpose IN ('SHARE_PROFILE_WITH_EMPLOYERS','SMS','WHATSAPP','EMAIL','DATA_PROCESSING')),
    granted         boolean NOT NULL,
    policy_version  text NOT NULL,
    channel         text CHECK (channel IN ('PORTAL','PAPER_FORM','STAFF_ASSISTED')),
    recorded_by     uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    recorded_at     timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX consents_student_purpose_idx ON consents(student_id, purpose, recorded_at DESC);

-- =============================================================================
-- 12. VACANCIES & APPLICATIONS
-- =============================================================================
CREATE TABLE vacancies (
    id              uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id      uuid NOT NULL REFERENCES companies(id),
    branch_id       uuid,
    trade_id        uuid REFERENCES trades(id),
    vacancy_type    text NOT NULL CHECK (vacancy_type IN ('JOB','APPRENTICESHIP')),
    title           text NOT NULL,
    description     text,
    openings        smallint NOT NULL DEFAULT 1 CHECK (openings > 0),
    salary_min      numeric(12,2) CHECK (salary_min >= 0),
    salary_max      numeric(12,2),
    salary_period   text CHECK (salary_period IN ('MONTHLY','ANNUAL','DAILY','HOURLY')),
    opens_on        date NOT NULL DEFAULT current_date,
    closes_on       date,
    status          text NOT NULL DEFAULT 'DRAFT' CHECK (status IN ('DRAFT','OPEN','CLOSED','FILLED')),
    posted_by       uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    created_at      timestamptz NOT NULL DEFAULT now(),
    created_by      uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    updated_by      uuid REFERENCES users(id),
    version         integer NOT NULL DEFAULT 1,
    FOREIGN KEY (branch_id, company_id) REFERENCES company_branches(id, company_id),
    CHECK (salary_max IS NULL OR salary_min IS NULL OR salary_max >= salary_min),
    CHECK (closes_on IS NULL OR closes_on >= opens_on)
);

CREATE TABLE job_applications (
    id                         uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    vacancy_id                 uuid NOT NULL REFERENCES vacancies(id),
    student_id                 uuid NOT NULL REFERENCES students(id),
    status                     text NOT NULL DEFAULT 'APPLIED' CHECK (status IN ('APPLIED','SHORTLISTED','INTERVIEW','OFFERED','HIRED','REJECTED','WITHDRAWN')),
    consent_to_share_profile   boolean NOT NULL,
    referred_by_user_id        uuid REFERENCES users(id),
    referred_by_iti_id         uuid REFERENCES itis(id),
    resulting_employment_id    uuid,
    resulting_apprenticeship_id uuid,
    applied_at                 timestamptz NOT NULL DEFAULT now(),
    created_at                 timestamptz NOT NULL DEFAULT now(),
    created_by                 uuid REFERENCES users(id) DEFAULT app.current_user_id(),
    updated_at                 timestamptz NOT NULL DEFAULT now(),
    updated_by                 uuid REFERENCES users(id),
    version                    integer NOT NULL DEFAULT 1,
    FOREIGN KEY (resulting_employment_id, student_id)     REFERENCES employments(id, student_id),
    FOREIGN KEY (resulting_apprenticeship_id, student_id) REFERENCES apprenticeships(id, student_id),
    UNIQUE (vacancy_id, student_id)
);

-- =============================================================================
-- 13. NOTIFICATIONS, INTEGRATION LOGS, AUDIT LOGS
-- =============================================================================
CREATE TABLE notifications (
    id                   bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    recipient_user_id    uuid REFERENCES users(id),
    student_id           uuid REFERENCES students(id),
    channel              text NOT NULL CHECK (channel IN ('EMAIL','SMS','WHATSAPP','IN_APP')),
    template_code        text NOT NULL,
    payload              jsonb NOT NULL DEFAULT '{}',   -- no secrets, no OTPs
    status               text NOT NULL DEFAULT 'QUEUED' CHECK (status IN ('QUEUED','SENT','DELIVERED','FAILED','CANCELLED')),
    provider             text,
    provider_message_id  text,
    attempts             smallint NOT NULL DEFAULT 0,
    last_error_code      text,
    scheduled_for        timestamptz NOT NULL DEFAULT now(),
    sent_at              timestamptz,
    created_at           timestamptz NOT NULL DEFAULT now(),
    CHECK (recipient_user_id IS NOT NULL OR student_id IS NOT NULL)
);
CREATE INDEX notifications_queue_idx ON notifications(status, scheduled_for);

CREATE TABLE integration_logs (
    id           bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    integration  text NOT NULL,
    operation    text NOT NULL,
    request_id   text,
    status       text NOT NULL CHECK (status IN ('SUCCESS','FAILURE','TIMEOUT')),
    http_status  smallint,
    duration_ms  integer,
    error_code   text,          -- never request/response bodies with personal data or secrets
    created_at   timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE audit_logs (
    id                        bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    occurred_at               timestamptz NOT NULL DEFAULT now(),
    actor_user_id             uuid,
    actor_role_assignment_id  uuid,
    request_id                text,
    action                    text NOT NULL,   -- INSERT / UPDATE / VIEW_SENSITIVE / EXPORT / LOGIN ...
    entity_table              text NOT NULL,
    entity_id                 text,
    student_id                uuid,
    iti_id                    uuid,
    before_data               jsonb,
    after_data                jsonb,
    reason                    text
);
CREATE INDEX audit_student_idx ON audit_logs(student_id, occurred_at DESC);
CREATE INDEX audit_entity_idx  ON audit_logs(entity_table, entity_id);
CREATE INDEX audit_iti_idx     ON audit_logs(iti_id, occurred_at DESC);

-- =============================================================================
-- 14. TRIGGERS & BUSINESS FUNCTIONS
-- =============================================================================

-- 14.1 updated_at / updated_by / version on every row change
CREATE FUNCTION app.touch_row() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at := now();
    NEW.updated_by := coalesce(app.current_user_id(), NEW.updated_by);
    NEW.version    := OLD.version + 1;
    RETURN NEW;
END $$;

DO $$
DECLARE r record;
BEGIN
    FOR r IN SELECT c.table_name FROM information_schema.columns c
             JOIN information_schema.tables t ON t.table_name = c.table_name AND t.table_schema = c.table_schema
             WHERE c.table_schema = 'public' AND c.column_name = 'version' AND t.table_type = 'BASE TABLE'
    LOOP
        EXECUTE format('CREATE TRIGGER trg_touch BEFORE UPDATE ON %I FOR EACH ROW EXECUTE FUNCTION app.touch_row()', r.table_name);
    END LOOP;
END $$;

-- 14.2 Generic audit trail (append-only)
CREATE FUNCTION app.audit_row() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE
    v_new jsonb := CASE WHEN TG_OP <> 'DELETE' THEN to_jsonb(NEW) END;
    v_old jsonb := CASE WHEN TG_OP <> 'INSERT' THEN to_jsonb(OLD) END;
    v_row jsonb := coalesce(v_new, v_old);
BEGIN
    INSERT INTO audit_logs(actor_user_id, actor_role_assignment_id, request_id, action, entity_table,
                           entity_id, student_id, iti_id, before_data, after_data)
    VALUES (app.current_user_id(), app.current_role_assignment_id(),
            nullif(current_setting('app.request_id', true), ''),
            TG_OP, TG_TABLE_NAME, v_row->>'id',
            CASE WHEN TG_TABLE_NAME = 'students' THEN (v_row->>'id')::uuid ELSE (v_row->>'student_id')::uuid END,
            coalesce(v_row->>'iti_id', v_row->>'to_iti_id', v_row->>'recorded_by_iti_id')::uuid,
            v_old, v_new);
    RETURN NULL;
END $$;

DO $$
DECLARE t text;
BEGIN
    FOREACH t IN ARRAY ARRAY['students','student_contacts','pcid_registry','admissions','institute_affiliations',
        'teacher_assignments','transfer_requests','exam_results','apprenticeships','employments','business_records',
        'higher_education_records','career_status_history','verification_records','student_documents',
        'user_role_assignments','student_merges','correction_requests','consents']
    LOOP
        EXECUTE format('CREATE TRIGGER trg_audit AFTER INSERT OR UPDATE OR DELETE ON %I FOR EACH ROW EXECUTE FUNCTION app.audit_row()', t);
    END LOOP;
END $$;

CREATE FUNCTION app.block_modification() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    RAISE EXCEPTION '% on % is not allowed (append-only)', TG_OP, TG_TABLE_NAME USING ERRCODE = '42501';
END $$;
CREATE TRIGGER trg_audit_append_only BEFORE UPDATE OR DELETE ON audit_logs
    FOR EACH ROW EXECUTE FUNCTION app.block_modification();
CREATE TRIGGER trg_verification_append_only BEFORE UPDATE OR DELETE ON verification_records
    FOR EACH ROW EXECUTE FUNCTION app.block_modification();

-- 14.3 Key columns that must never change once written
--      (e.g. an old job row can never be "re-pointed" at a new employer)
CREATE FUNCTION app.protect_columns() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE c text;
BEGIN
    FOREACH c IN ARRAY TG_ARGV LOOP
        IF (to_jsonb(NEW)->c) IS DISTINCT FROM (to_jsonb(OLD)->c) THEN
            RAISE EXCEPTION 'Column %.% cannot be changed; create a new record instead', TG_TABLE_NAME, c
                USING ERRCODE = '42501';
        END IF;
    END LOOP;
    RETURN NEW;
END $$;
CREATE TRIGGER trg_protect BEFORE UPDATE ON pcid_registry      FOR EACH ROW EXECUTE FUNCTION app.protect_columns('pcid','student_id','issue_year','serial','issued_at');
CREATE TRIGGER trg_protect BEFORE UPDATE ON employments        FOR EACH ROW EXECUTE FUNCTION app.protect_columns('student_id','company_id','joining_date');
CREATE TRIGGER trg_protect BEFORE UPDATE ON apprenticeships    FOR EACH ROW EXECUTE FUNCTION app.protect_columns('student_id','company_id','start_date');
CREATE TRIGGER trg_protect BEFORE UPDATE ON business_records   FOR EACH ROW EXECUTE FUNCTION app.protect_columns('student_id','start_date');
CREATE TRIGGER trg_protect BEFORE UPDATE ON higher_education_records FOR EACH ROW EXECUTE FUNCTION app.protect_columns('student_id','college_id');
CREATE TRIGGER trg_protect BEFORE UPDATE ON admissions         FOR EACH ROW EXECUTE FUNCTION app.protect_columns('student_id','iti_id','academic_year_id','admission_type','previous_admission_id');
CREATE TRIGGER trg_protect BEFORE UPDATE ON exam_results       FOR EACH ROW EXECUTE FUNCTION app.protect_columns('student_id','admission_id','exam_session_id','attempt_no');
CREATE TRIGGER trg_protect BEFORE UPDATE ON teacher_assignments FOR EACH ROW EXECUTE FUNCTION app.protect_columns('student_id','teacher_user_id','iti_id','admission_id','start_date');

-- 14.4 PCID generation (server-side only)
CREATE FUNCTION app.luhn_check_digit(digits text) RETURNS text
LANGUAGE plpgsql IMMUTABLE AS $$
DECLARE s int := 0; d int; dbl boolean := true; i int;
BEGIN
    FOR i IN REVERSE length(digits)..1 LOOP
        d := substr(digits, i, 1)::int;
        IF dbl THEN d := d * 2; IF d > 9 THEN d := d - 9; END IF; END IF;
        s := s + d;
        dbl := NOT dbl;
    END LOOP;
    RETURN ((10 - s % 10) % 10)::text;
END $$;

-- Validates format + check digit (use in API before any lookup: catches typos)
CREATE FUNCTION app.pcid_is_valid(p text) RETURNS boolean
LANGUAGE sql IMMUTABLE AS $$
    SELECT p ~ '^ITIC-MH-[0-9]{4}-[0-9]{6}-[0-9]$'
       AND right(p, 1) = app.luhn_check_digit(substr(p, 9, 4) || substr(p, 14, 6))
$$;

CREATE FUNCTION app.issue_pcid(p_student_id uuid, p_admission_id uuid) RETURNS text
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE
    v_existing text; v_year smallint; v_serial int; v_pcid text;
BEGIN
    -- Lock the student row: two simultaneous requests for the same student queue up here.
    PERFORM 1 FROM students WHERE id = p_student_id AND profile_status = 'ACTIVE' FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Student % not found or not active', p_student_id USING ERRCODE = 'P0002';
    END IF;

    -- Idempotent: a student who already has a PCID keeps it (transfers, re-admissions).
    SELECT pcid INTO v_existing FROM pcid_registry WHERE student_id = p_student_id AND status = 'ACTIVE';
    IF FOUND THEN RETURN v_existing; END IF;

    -- Year in the PCID = start year of the academic year of the FIRST accepted admission.
    SELECT extract(year FROM ay.start_date)::smallint INTO v_year
    FROM admissions a JOIN academic_years ay ON ay.id = a.academic_year_id
    WHERE a.id = p_admission_id AND a.student_id = p_student_id AND a.status IN ('ACCEPTED','ACTIVE');
    IF NOT FOUND THEN
        RAISE EXCEPTION 'PCID requires an accepted admission for this student' USING ERRCODE = '23514';
    END IF;

    -- Per-year counter. Row lock => no two transactions can ever get the same serial.
    -- Because it is in the same transaction, a rollback also rolls back the counter (no gaps).
    INSERT INTO pcid_year_counters AS c (issue_year, last_serial) VALUES (v_year, 1)
    ON CONFLICT (issue_year) DO UPDATE SET last_serial = c.last_serial + 1
    RETURNING last_serial INTO v_serial;

    v_pcid := format('ITIC-MH-%s-%s-%s', v_year, lpad(v_serial::text, 6, '0'),
                     app.luhn_check_digit(v_year::text || lpad(v_serial::text, 6, '0')));

    INSERT INTO pcid_registry (pcid, student_id, issue_year, serial, issued_for_admission_id, issued_by)
    VALUES (v_pcid, p_student_id, v_year, v_serial, p_admission_id, app.current_user_id());
    RETURN v_pcid;
END $$;

-- 14.5 Admission lifecycle -> PCID + institute affiliation (automatic, cannot be forgotten)
CREATE FUNCTION app.on_admission_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE v_aff_status text;
BEGIN
    IF NEW.status IN ('ACCEPTED','ACTIVE') AND (TG_OP = 'INSERT' OR OLD.status NOT IN ('ACCEPTED','ACTIVE')) THEN
        PERFORM app.issue_pcid(NEW.student_id, NEW.id);
        INSERT INTO institute_affiliations (student_id, iti_id, admission_id, start_date, status)
        VALUES (NEW.student_id, NEW.iti_id, NEW.id, NEW.admission_date, 'ACTIVE')
        ON CONFLICT (admission_id) DO NOTHING;
    ELSIF TG_OP = 'UPDATE' AND OLD.status IN ('ACCEPTED','ACTIVE') AND NEW.status NOT IN ('ACCEPTED','ACTIVE') THEN
        v_aff_status := CASE NEW.status WHEN 'COMPLETED' THEN 'COMPLETED'
                                        WHEN 'TRANSFERRED_OUT' THEN 'TRANSFERRED_OUT'
                                        ELSE 'DISCONTINUED' END;
        UPDATE institute_affiliations SET status = v_aff_status, end_date = NEW.end_date
        WHERE admission_id = NEW.id AND status = 'ACTIVE';
    END IF;
    RETURN NULL;
END $$;
CREATE TRIGGER trg_admission_lifecycle AFTER INSERT OR UPDATE OF status ON admissions
    FOR EACH ROW EXECUTE FUNCTION app.on_admission_change();

-- 14.6 A teacher assignment is only valid if the user is an ACTIVE teacher at that ITI
CREATE FUNCTION app.check_teacher_assignment() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
BEGIN
    IF NEW.end_date IS NULL AND NOT EXISTS (
        SELECT 1 FROM user_role_assignments ura JOIN roles r ON r.id = ura.role_id
        WHERE ura.user_id = NEW.teacher_user_id AND r.code = 'TEACHER' AND ura.iti_id = NEW.iti_id
          AND ura.status = 'ACTIVE' AND current_date BETWEEN ura.valid_from AND coalesce(ura.valid_to, 'infinity'))
    THEN
        RAISE EXCEPTION 'User % is not an active teacher at ITI %', NEW.teacher_user_id, NEW.iti_id USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END $$;
CREATE TRIGGER trg_teacher_check BEFORE INSERT OR UPDATE ON teacher_assignments
    FOR EACH ROW EXECUTE FUNCTION app.check_teacher_assignment();

-- 14.7 Career status: inserting a new status automatically closes the previous open one
CREATE FUNCTION app.close_previous_status() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE v_open career_status_history%ROWTYPE;
BEGIN
    SELECT * INTO v_open FROM career_status_history
    WHERE student_id = NEW.student_id AND effective_to IS NULL FOR UPDATE;
    IF FOUND THEN
        IF NEW.effective_from <= v_open.effective_from THEN
            RAISE EXCEPTION 'New status must start after the current status (%). Use a correction request for back-dated changes.',
                v_open.effective_from USING ERRCODE = '23514';
        END IF;
        UPDATE career_status_history SET effective_to = NEW.effective_from WHERE id = v_open.id;
    END IF;
    RETURN NEW;
END $$;
CREATE TRIGGER trg_close_previous_status BEFORE INSERT ON career_status_history
    FOR EACH ROW EXECUTE FUNCTION app.close_previous_status();

-- 14.8 A verification decision updates the cached verification_status of its target
CREATE FUNCTION app.apply_verification() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE v_status text := CASE NEW.decision WHEN 'NEEDS_INFO' THEN 'PENDING' ELSE NEW.decision END;
BEGIN
    IF NEW.exam_result_id IS NOT NULL THEN UPDATE exam_results SET verification_status = v_status WHERE id = NEW.exam_result_id; END IF;
    IF NEW.apprenticeship_id IS NOT NULL THEN UPDATE apprenticeships SET verification_status = v_status WHERE id = NEW.apprenticeship_id; END IF;
    IF NEW.employment_id IS NOT NULL THEN UPDATE employments SET verification_status = v_status WHERE id = NEW.employment_id; END IF;
    IF NEW.business_record_id IS NOT NULL THEN UPDATE business_records SET verification_status = v_status WHERE id = NEW.business_record_id; END IF;
    IF NEW.higher_education_record_id IS NOT NULL THEN UPDATE higher_education_records SET verification_status = v_status WHERE id = NEW.higher_education_record_id; END IF;
    IF NEW.student_document_id IS NOT NULL THEN UPDATE student_documents SET verification_status = v_status WHERE id = NEW.student_document_id; END IF;
    RETURN NULL;
END $$;
CREATE TRIGGER trg_apply_verification AFTER INSERT ON verification_records
    FOR EACH ROW EXECUTE FUNCTION app.apply_verification();

-- =============================================================================
-- 15. ACCESS CONTROL FUNCTIONS (used by API and by Row-Level Security)
-- =============================================================================

-- The caller's ACTIVE role assignment (a user with two roles acts in one at a time)
CREATE FUNCTION app.ctx()
RETURNS TABLE (user_id uuid, role_assignment_id uuid, role_code text,
               district_id uuid, iti_id uuid, company_id uuid, student_id uuid)
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public, pg_temp AS $$
    SELECT ura.user_id, ura.id, r.code, ura.district_id, ura.iti_id, ura.company_id, u.student_id
    FROM user_role_assignments ura
    JOIN roles r ON r.id = ura.role_id
    JOIN users u ON u.id = ura.user_id
    WHERE ura.id = app.current_role_assignment_id()
      AND ura.user_id = app.current_user_id()
      AND ura.status = 'ACTIVE' AND u.status = 'ACTIVE'
      AND current_date >= ura.valid_from AND (ura.valid_to IS NULL OR current_date <= ura.valid_to)
$$;

CREATE FUNCTION app.current_role_code() RETURNS text
LANGUAGE sql STABLE AS $$ SELECT role_code FROM app.ctx() $$;

CREATE FUNCTION app.has_permission(p_code text) RETURNS boolean
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public, pg_temp AS $$
    SELECT EXISTS (SELECT 1 FROM app.ctx() c
                   JOIN roles r ON r.code = c.role_code
                   JOIN role_permissions rp ON rp.role_id = r.id
                   JOIN permissions p ON p.id = rp.permission_id
                   WHERE p.code = p_code)
$$;

-- Is this ITI inside the caller's administrative scope?
CREATE FUNCTION app.is_my_iti(p_iti_id uuid) RETURNS boolean
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE c record;
BEGIN
    SELECT * INTO c FROM app.ctx();
    IF NOT FOUND OR p_iti_id IS NULL THEN RETURN false; END IF;
    RETURN CASE c.role_code
        WHEN 'STATE_ADMIN' THEN true
        WHEN 'DISTRICT_ADMIN' THEN EXISTS (SELECT 1 FROM itis WHERE id = p_iti_id AND district_id = c.district_id)
        WHEN 'PRINCIPAL' THEN p_iti_id = c.iti_id
        WHEN 'PLACEMENT_OFFICER' THEN p_iti_id = c.iti_id
        ELSE false END;
END $$;

-- FULL current lifecycle access to a student
CREATE FUNCTION app.can_view_student(p_student_id uuid) RETURNS boolean
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE c record;
BEGIN
    SELECT * INTO c FROM app.ctx();
    IF NOT FOUND OR p_student_id IS NULL THEN RETURN false; END IF;
    CASE c.role_code
      WHEN 'STATE_ADMIN' THEN RETURN true;
      WHEN 'DISTRICT_ADMIN' THEN
        RETURN EXISTS (SELECT 1 FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id
                       WHERE ia.student_id = p_student_id AND i.district_id = c.district_id
                         AND ia.status IN ('ACTIVE','COMPLETED'));
      WHEN 'PRINCIPAL', 'PLACEMENT_OFFICER' THEN
        -- current students AND alumni who completed at this ITI
        RETURN EXISTS (SELECT 1 FROM institute_affiliations ia
                       WHERE ia.student_id = p_student_id AND ia.iti_id = c.iti_id
                         AND ia.status IN ('ACTIVE','COMPLETED'));
      WHEN 'TEACHER' THEN
        -- only CURRENTLY assigned students
        RETURN EXISTS (SELECT 1 FROM teacher_assignments ta
                       WHERE ta.student_id = p_student_id AND ta.teacher_user_id = c.user_id
                         AND ta.iti_id = c.iti_id AND ta.start_date <= current_date
                         AND (ta.end_date IS NULL OR ta.end_date > current_date));   -- end_date = first day WITHOUT access
      WHEN 'STUDENT' THEN RETURN p_student_id = c.student_id;
      ELSE RETURN false;      -- EMPLOYER, ITI_STAFF: no full lifecycle access
    END CASE;
END $$;

-- LIMITED access: identity summary only (name, PCID) — e.g. past teacher, origin ITI
-- after a transfer, destination ITI reviewing a pending transfer, employer with an applicant.
CREATE FUNCTION app.has_summary_access(p_student_id uuid) RETURNS boolean
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE c record;
BEGIN
    SELECT * INTO c FROM app.ctx();
    IF NOT FOUND OR p_student_id IS NULL THEN RETURN false; END IF;
    CASE c.role_code
      WHEN 'DISTRICT_ADMIN' THEN
        RETURN EXISTS (SELECT 1 FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id
                       WHERE ia.student_id = p_student_id AND i.district_id = c.district_id);
      WHEN 'PRINCIPAL', 'PLACEMENT_OFFICER' THEN
        RETURN EXISTS (SELECT 1 FROM institute_affiliations ia WHERE ia.student_id = p_student_id AND ia.iti_id = c.iti_id)
            OR EXISTS (SELECT 1 FROM transfer_requests tr WHERE tr.student_id = p_student_id
                       AND tr.to_iti_id = c.iti_id AND tr.status = 'REQUESTED');
      WHEN 'TEACHER' THEN
        RETURN EXISTS (SELECT 1 FROM teacher_assignments ta WHERE ta.student_id = p_student_id
                       AND ta.teacher_user_id = c.user_id);
      WHEN 'ITI_STAFF' THEN
        RETURN EXISTS (SELECT 1 FROM follow_ups f WHERE f.student_id = p_student_id
                       AND f.assigned_to_user_id = c.user_id AND f.status IN ('PENDING','IN_PROGRESS'));
      WHEN 'EMPLOYER' THEN
        RETURN EXISTS (SELECT 1 FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id
                       WHERE ja.student_id = p_student_id AND v.company_id = c.company_id
                         AND ja.consent_to_share_profile AND ja.status <> 'WITHDRAWN')
            OR EXISTS (SELECT 1 FROM employments e WHERE e.student_id = p_student_id AND e.company_id = c.company_id)
            OR EXISTS (SELECT 1 FROM apprenticeships a WHERE a.student_id = p_student_id AND a.company_id = c.company_id);
      ELSE RETURN false;
    END CASE;
END $$;

-- =============================================================================
-- 16. TRANSFER WORKFLOW (single atomic transaction)
-- =============================================================================
CREATE FUNCTION app.accept_transfer(p_transfer_id uuid, p_batch_id uuid DEFAULT NULL, p_remarks text DEFAULT NULL)
RETURNS uuid
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE
    c record; t transfer_requests%ROWTYPE; v_old admissions%ROWTYPE; v_old_tr training_records%ROWTYPE;
    v_new_admission uuid := gen_random_uuid(); v_eff date;
BEGIN
    SELECT * INTO c FROM app.ctx();
    SELECT * INTO t FROM transfer_requests WHERE id = p_transfer_id FOR UPDATE;
    IF NOT FOUND THEN RAISE EXCEPTION 'Transfer request not found' USING ERRCODE = 'P0002'; END IF;
    IF t.status <> 'REQUESTED' THEN RAISE EXCEPTION 'Transfer is already %', t.status USING ERRCODE = '55000'; END IF;
    IF c.role_code IS NULL OR NOT ((c.role_code = 'PRINCIPAL' AND c.iti_id = t.to_iti_id) OR c.role_code = 'STATE_ADMIN') THEN
        RAISE EXCEPTION 'Only the destination ITI Principal can accept this transfer' USING ERRCODE = '42501';
    END IF;

    SELECT * INTO v_old FROM admissions WHERE id = t.from_admission_id FOR UPDATE;
    IF v_old.status NOT IN ('ACCEPTED','ACTIVE') THEN
        RAISE EXCEPTION 'Origin admission is no longer active (%)', v_old.status USING ERRCODE = '55000';
    END IF;
    v_eff := greatest(t.requested_effective_date, v_old.admission_date);

    -- 1. Close the origin episode (history is kept, nothing deleted)
    UPDATE admissions SET status = 'TRANSFERRED_OUT', end_date = v_eff, end_reason = 'Transfer ' || t.id
     WHERE id = v_old.id;                                   -- trigger closes the affiliation
    SELECT * INTO v_old_tr FROM training_records WHERE admission_id = v_old.id AND completion_status = 'IN_PROGRESS';
    UPDATE training_records SET completion_status = 'TRANSFERRED_OUT', actual_end_date = v_eff
     WHERE admission_id = v_old.id AND completion_status = 'IN_PROGRESS';
    UPDATE teacher_assignments SET end_date = v_eff, end_reason = 'Student transferred out'
     WHERE admission_id = v_old.id AND end_date IS NULL;
    UPDATE follow_ups SET status = 'CANCELLED', outcome_notes = 'Auto-cancelled: student transferred'
     WHERE student_id = t.student_id AND iti_id = t.from_iti_id AND status IN ('PENDING','IN_PROGRESS');

    -- 2. Open the destination episode (same student_id => same PCID)
    INSERT INTO admissions (id, student_id, iti_id, trade_id, academic_year_id, batch_id, admission_type,
                            previous_admission_id, admission_date, duration_months, status)
    VALUES (v_new_admission, t.student_id, t.to_iti_id, t.to_trade_id, t.to_academic_year_id,
            coalesce(p_batch_id, t.to_batch_id), 'TRANSFER_IN', v_old.id, v_eff, v_old.duration_months, 'ACTIVE');
    INSERT INTO training_records (student_id, admission_id, iti_id, start_date, expected_end_date)
    VALUES (t.student_id, v_new_admission, t.to_iti_id, v_eff, v_old_tr.expected_end_date);

    -- 3. Close the request
    UPDATE transfer_requests SET status = 'ACCEPTED', decided_by = c.user_id, decided_at = now(),
           decision_remarks = p_remarks, new_admission_id = v_new_admission
     WHERE id = t.id;
    RETURN v_new_admission;
END $$;

-- =============================================================================
-- 17. DUPLICATE DETECTION (runs before creating a new student)
-- =============================================================================
-- Returns possible matches with a score. Bands (proposed, tunable):
--   100    : certain (same SSC seat no. / same ID hash)  -> must use the existing student
--   70..95 : strong                                     -> blocked unless override + reason
--   50..69 : possible                                   -> allowed, logged to duplicate_candidates
-- Soft signals (max 95):  first name similar +25 | surname similar +10 | same DOB +30
--                         same mobile +25 | whole name very similar +15
-- First name is scored separately because siblings/twins share surname, father's
-- name, DOB (twins) and the family mobile — but not the first name.
CREATE FUNCTION app.find_duplicate_candidates(
    p_first_name text, p_middle_name text, p_last_name text, p_dob date,
    p_mobile text DEFAULT NULL, p_ssc_board text DEFAULT NULL, p_ssc_seat text DEFAULT NULL,
    p_ssc_year smallint DEFAULT NULL, p_national_id_hash bytea DEFAULT NULL)
RETURNS TABLE (student_id uuid, score int, reasons text[])
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public, pg_temp AS $$
    WITH input AS (
        SELECT app.normalize_name(p_first_name || ' ' || coalesce(p_middle_name, '') || ' ' || coalesce(p_last_name, '')) AS nm,
               app.normalize_name(p_first_name) AS fn,
               app.normalize_name(p_last_name)  AS ln,
               app.normalize_mobile(p_mobile)   AS mob
    ), pool AS (       -- cheap indexed lookups first, then score the small candidate pool
        SELECT s.id FROM students s WHERE p_national_id_hash IS NOT NULL AND s.national_id_hash = p_national_id_hash
        UNION SELECT s.id FROM students s WHERE p_ssc_seat IS NOT NULL AND s.ssc_board = p_ssc_board
                                          AND s.ssc_seat_number = p_ssc_seat AND s.ssc_passing_year = p_ssc_year
        UNION SELECT s.id FROM students s WHERE s.date_of_birth = p_dob
        UNION SELECT s.id FROM students s, input i WHERE s.full_name_normalized % i.nm
        UNION SELECT sc.student_id FROM student_contacts sc, input i
              WHERE i.mob IS NOT NULL AND sc.value_normalized = i.mob AND sc.valid_to IS NULL
                AND sc.contact_type IN ('MOBILE','WHATSAPP','GUARDIAN_MOBILE')
    ), scored AS (
        SELECT s.id,
          ARRAY_REMOVE(ARRAY[
            CASE WHEN p_national_id_hash IS NOT NULL AND s.national_id_hash = p_national_id_hash THEN 'NATIONAL_ID' END,
            CASE WHEN p_ssc_seat IS NOT NULL AND s.ssc_seat_number = p_ssc_seat AND s.ssc_board = p_ssc_board
                      AND s.ssc_passing_year = p_ssc_year THEN 'SSC_SEAT_NUMBER' END,
            CASE WHEN similarity(app.normalize_name(s.first_name), i.fn) >= 0.6 THEN 'FIRST_NAME' END,
            CASE WHEN i.ln <> '' AND similarity(app.normalize_name(s.last_name), i.ln) >= 0.4 THEN 'SURNAME' END,
            CASE WHEN similarity(s.full_name_normalized, i.nm) >= 0.85 THEN 'FULL_NAME' END,
            CASE WHEN s.date_of_birth = p_dob THEN 'DATE_OF_BIRTH' END,
            CASE WHEN i.mob IS NOT NULL AND EXISTS (SELECT 1 FROM student_contacts sc WHERE sc.student_id = s.id
                      AND sc.value_normalized = i.mob AND sc.valid_to IS NULL) THEN 'MOBILE' END
          ], NULL) AS reasons
        FROM pool p JOIN students s ON s.id = p.id CROSS JOIN input i
        WHERE s.profile_status = 'ACTIVE'
    )
    SELECT id,
           CASE WHEN 'NATIONAL_ID' = ANY(reasons) OR 'SSC_SEAT_NUMBER' = ANY(reasons) THEN 100
           ELSE LEAST(95,
             (CASE WHEN 'FIRST_NAME'    = ANY(reasons) THEN 25 ELSE 0 END) +
             (CASE WHEN 'SURNAME'       = ANY(reasons) THEN 10 ELSE 0 END) +
             (CASE WHEN 'FULL_NAME'     = ANY(reasons) THEN 15 ELSE 0 END) +
             (CASE WHEN 'DATE_OF_BIRTH' = ANY(reasons) THEN 30 ELSE 0 END) +
             (CASE WHEN 'MOBILE'        = ANY(reasons) THEN 25 ELSE 0 END)) END AS score,
           reasons
    FROM scored
    WHERE cardinality(reasons) > 0
    ORDER BY 2 DESC
$$;

-- =============================================================================
-- 18. VIEWS (security_invoker => RLS of the caller applies)
-- =============================================================================
CREATE VIEW v_student_identity WITH (security_invoker = true) AS
SELECT s.id AS student_id, p.pcid, s.first_name, s.middle_name, s.last_name, s.full_name_mr,
       s.gender, s.date_of_birth, s.profile_status, s.is_demo
FROM students s
LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE';

CREATE VIEW v_student_current_status WITH (security_invoker = true) AS
SELECT DISTINCT ON (csh.student_id) csh.student_id, csh.status, csh.effective_from, csh.verification_status,
       csh.source, (current_date - csh.effective_from) AS days_since_update
FROM career_status_history csh
WHERE csh.effective_to IS NULL
ORDER BY csh.student_id, csh.effective_from DESC;

-- One chronological timeline across every lifecycle table (the "Career Passport")
CREATE VIEW v_student_timeline WITH (security_invoker = true) AS
SELECT student_id, admission_date AS event_date, 'ADMISSION' AS event_type, id AS record_id, iti_id,
       admission_type || ' / ' || status AS summary, NULL::text AS verification_status FROM admissions
UNION ALL
SELECT student_id, requested_effective_date, 'TRANSFER', id, to_iti_id, status, NULL FROM transfer_requests
UNION ALL
SELECT er.student_id, coalesce(er.result_declared_on, es.session_month), 'EXAM_RESULT', er.id, er.iti_id,
       es.name || ' attempt ' || er.attempt_no || ': ' || er.result, er.verification_status
FROM exam_results er JOIN exam_sessions es ON es.id = er.exam_session_id
UNION ALL
SELECT student_id, start_date, 'APPRENTICESHIP', id, recorded_by_iti_id, status, verification_status
FROM apprenticeships WHERE voided_at IS NULL
UNION ALL
SELECT student_id, joining_date, 'EMPLOYMENT', id, recorded_by_iti_id, designation || ' / ' || status, verification_status
FROM employments WHERE voided_at IS NULL
UNION ALL
SELECT student_id, start_date, 'BUSINESS', id, recorded_by_iti_id, business_name || ' / ' || status, verification_status
FROM business_records WHERE voided_at IS NULL
UNION ALL
SELECT student_id, coalesce(start_date, make_date(admission_year, 6, 1)), 'HIGHER_EDUCATION', id, recorded_by_iti_id,
       course_name || ' / ' || status, verification_status
FROM higher_education_records WHERE voided_at IS NULL
UNION ALL
SELECT student_id, effective_from, 'STATUS_CHANGE', id, NULL, status, verification_status FROM career_status_history;

-- =============================================================================
-- 19. ROW-LEVEL SECURITY
--     The API connects as role careertrack_app (NOT the table owner), so RLS applies.
--     No DELETE policies exist anywhere => the app can never hard-delete rows.
-- =============================================================================
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'careertrack_app') THEN
        CREATE ROLE careertrack_app NOLOGIN;
    END IF;
END $$;

GRANT USAGE ON SCHEMA public, app TO careertrack_app;
GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA public TO careertrack_app;
GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO careertrack_app;
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA app TO careertrack_app;
-- Tables only the database itself may write (via SECURITY DEFINER functions/triggers):
REVOKE INSERT, UPDATE ON pcid_registry, pcid_year_counters, institute_affiliations, audit_logs FROM careertrack_app;
REVOKE UPDATE ON verification_records, consents FROM careertrack_app;
REVOKE ALL ON pcid_year_counters FROM careertrack_app;

-- Helper macro: enable RLS on a list of tables
DO $$
DECLARE t text;
BEGIN
    FOREACH t IN ARRAY ARRAY['students','student_contacts','pcid_registry','admissions','institute_affiliations',
        'teacher_assignments','transfer_requests','training_records','attendance_records','module_progress',
        'assessments','exam_results','exam_result_components','student_documents','apprenticeships','employments',
        'business_records','higher_education_records','career_status_history','follow_ups','verification_records',
        'correction_requests','consents','job_applications','audit_logs','user_role_assignments']
    LOOP
        EXECUTE format('ALTER TABLE %I ENABLE ROW LEVEL SECURITY', t);
    END LOOP;
END $$;

-- 19.1 Identity
CREATE POLICY sel ON students FOR SELECT USING (app.can_view_student(id) OR app.has_summary_access(id));
CREATE POLICY ins ON students FOR INSERT WITH CHECK (app.current_role_code() IN ('PRINCIPAL','STATE_ADMIN'));
CREATE POLICY upd ON students FOR UPDATE USING (app.can_view_student(id) AND app.current_role_code() IN ('PRINCIPAL','STATE_ADMIN'));

CREATE POLICY sel ON pcid_registry FOR SELECT USING (app.can_view_student(student_id) OR app.has_summary_access(student_id));

CREATE POLICY sel ON student_contacts FOR SELECT USING (
    (app.can_view_student(student_id) AND app.has_permission('student.contact.view'))
    OR (app.current_role_code() = 'ITI_STAFF' AND app.has_summary_access(student_id)));
CREATE POLICY ins ON student_contacts FOR INSERT WITH CHECK (
    app.can_view_student(student_id) AND app.current_role_code() IN ('PRINCIPAL','PLACEMENT_OFFICER','STATE_ADMIN','STUDENT'));
CREATE POLICY upd ON student_contacts FOR UPDATE USING (
    app.can_view_student(student_id) AND app.current_role_code() IN ('PRINCIPAL','PLACEMENT_OFFICER','STATE_ADMIN','STUDENT'));

-- 19.2 Institute-owned academic records: the full-access viewer, OR the ITI that created the
--      record (so an origin ITI keeps ITS OWN history after a transfer, but sees nothing new).
DO $$
DECLARE t text;
BEGIN
    FOREACH t IN ARRAY ARRAY['admissions','institute_affiliations','teacher_assignments','training_records',
                             'attendance_records','module_progress','assessments','exam_results']
    LOOP
        EXECUTE format('CREATE POLICY sel ON %I FOR SELECT USING (app.can_view_student(student_id) OR app.is_my_iti(iti_id))', t);
    END LOOP;
    -- Principal / State Admin manage admissions, training episodes, assignments and results
    FOREACH t IN ARRAY ARRAY['admissions','teacher_assignments','training_records','exam_results']
    LOOP
        EXECUTE format($f$CREATE POLICY ins ON %I FOR INSERT WITH CHECK (app.current_role_code() IN ('PRINCIPAL','STATE_ADMIN') AND app.is_my_iti(iti_id))$f$, t);
        EXECUTE format($f$CREATE POLICY upd ON %I FOR UPDATE USING (app.current_role_code() IN ('PRINCIPAL','STATE_ADMIN') AND app.is_my_iti(iti_id))$f$, t);
    END LOOP;
    -- Teachers (for their CURRENTLY assigned students at their ITI) and Principals record training data
    FOREACH t IN ARRAY ARRAY['attendance_records','module_progress','assessments']
    LOOP
        EXECUTE format($f$CREATE POLICY ins ON %I FOR INSERT WITH CHECK (
            (app.current_role_code() = 'TEACHER' AND app.can_view_student(student_id) AND iti_id = (SELECT iti_id FROM app.ctx()))
            OR (app.current_role_code() = 'PRINCIPAL' AND app.is_my_iti(iti_id)))$f$, t);
        EXECUTE format($f$CREATE POLICY upd ON %I FOR UPDATE USING (
            (app.current_role_code() = 'TEACHER' AND app.can_view_student(student_id) AND iti_id = (SELECT iti_id FROM app.ctx()))
            OR (app.current_role_code() = 'PRINCIPAL' AND app.is_my_iti(iti_id)))$f$, t);
    END LOOP;
END $$;

CREATE POLICY sel ON exam_result_components FOR SELECT USING (
    EXISTS (SELECT 1 FROM exam_results er WHERE er.id = exam_result_id));   -- inherits exam_results RLS
CREATE POLICY ins ON exam_result_components FOR INSERT WITH CHECK (
    EXISTS (SELECT 1 FROM exam_results er WHERE er.id = exam_result_id AND app.is_my_iti(er.iti_id)
            AND app.current_role_code() IN ('PRINCIPAL','STATE_ADMIN')));

-- 19.3 Transfers: origin and destination ITIs (and anyone with full access) can see the request
CREATE POLICY sel ON transfer_requests FOR SELECT USING (
    app.is_my_iti(from_iti_id) OR app.is_my_iti(to_iti_id) OR app.can_view_student(student_id));
CREATE POLICY ins ON transfer_requests FOR INSERT WITH CHECK (
    app.current_role_code() IN ('PRINCIPAL','STATE_ADMIN') AND app.is_my_iti(from_iti_id)
    AND app.can_view_student(student_id) AND requested_by = app.current_user_id());
CREATE POLICY upd ON transfer_requests FOR UPDATE USING (     -- cancel (origin) / reject (destination)
    app.current_role_code() IN ('PRINCIPAL','STATE_ADMIN') AND (app.is_my_iti(from_iti_id) OR app.is_my_iti(to_iti_id)));

-- 19.4 Career records: full-access viewers; employers see ONLY their own company's rows
CREATE POLICY sel ON apprenticeships FOR SELECT USING (
    app.can_view_student(student_id) OR (app.current_role_code() = 'EMPLOYER' AND company_id = (SELECT company_id FROM app.ctx())));
CREATE POLICY sel ON employments FOR SELECT USING (
    app.can_view_student(student_id) OR (app.current_role_code() = 'EMPLOYER' AND company_id = (SELECT company_id FROM app.ctx())));
DO $$
DECLARE t text;
BEGIN
    FOREACH t IN ARRAY ARRAY['business_records','higher_education_records','career_status_history','student_documents','consents','correction_requests']
    LOOP
        EXECUTE format('CREATE POLICY sel ON %I FOR SELECT USING (app.can_view_student(student_id))', t);
    END LOOP;
    FOREACH t IN ARRAY ARRAY['apprenticeships','employments','business_records','higher_education_records',
                             'career_status_history','student_documents','correction_requests','consents']
    LOOP
        EXECUTE format($f$CREATE POLICY ins ON %I FOR INSERT WITH CHECK (app.can_view_student(student_id)
            AND app.current_role_code() IN ('PRINCIPAL','PLACEMENT_OFFICER','STATE_ADMIN','STUDENT'))$f$, t);
    END LOOP;
    FOREACH t IN ARRAY ARRAY['apprenticeships','employments','business_records','higher_education_records',
                             'career_status_history','student_documents','correction_requests']
    LOOP
        EXECUTE format($f$CREATE POLICY upd ON %I FOR UPDATE USING (app.can_view_student(student_id)
            AND app.current_role_code() IN ('PRINCIPAL','PLACEMENT_OFFICER','STATE_ADMIN'))$f$, t);
    END LOOP;
END $$;

-- 19.5 Follow-ups: institute staff queue + whoever the task is assigned to
CREATE POLICY sel ON follow_ups FOR SELECT USING (
    (app.is_my_iti(iti_id) AND app.can_view_student(student_id)) OR assigned_to_user_id = app.current_user_id());
CREATE POLICY ins ON follow_ups FOR INSERT WITH CHECK (
    app.current_role_code() IN ('PRINCIPAL','PLACEMENT_OFFICER') AND app.is_my_iti(iti_id) AND app.can_view_student(student_id));
CREATE POLICY upd ON follow_ups FOR UPDATE USING (
    (app.is_my_iti(iti_id) AND app.current_role_code() IN ('PRINCIPAL','PLACEMENT_OFFICER'))
    OR assigned_to_user_id = app.current_user_id());

-- 19.6 Verification: staff with full access; employer only for its own engagements
CREATE POLICY sel ON verification_records FOR SELECT USING (
    app.can_view_student(student_id) OR verifier_company_id = (SELECT company_id FROM app.ctx()));
CREATE POLICY ins ON verification_records FOR INSERT WITH CHECK (
    verifier_user_id = app.current_user_id() AND (
      (app.current_role_code() IN ('PRINCIPAL','PLACEMENT_OFFICER','STATE_ADMIN') AND app.can_view_student(student_id))
      OR (app.current_role_code() = 'EMPLOYER' AND method = 'EMPLOYER_CONFIRMATION'
          AND verifier_company_id = (SELECT company_id FROM app.ctx())
          AND (employment_id     IN (SELECT id FROM employments     WHERE company_id = verifier_company_id)
            OR apprenticeship_id IN (SELECT id FROM apprenticeships WHERE company_id = verifier_company_id)))));

-- 19.7 Applications
CREATE POLICY sel ON job_applications FOR SELECT USING (
    app.can_view_student(student_id)
    OR (app.current_role_code() = 'EMPLOYER' AND consent_to_share_profile
        AND vacancy_id IN (SELECT id FROM vacancies WHERE company_id = (SELECT company_id FROM app.ctx()))));
CREATE POLICY ins ON job_applications FOR INSERT WITH CHECK (
    app.can_view_student(student_id) AND app.current_role_code() IN ('STUDENT','PLACEMENT_OFFICER','PRINCIPAL'));
CREATE POLICY upd ON job_applications FOR UPDATE USING (
    app.can_view_student(student_id)
    OR (app.current_role_code() = 'EMPLOYER'
        AND vacancy_id IN (SELECT id FROM vacancies WHERE company_id = (SELECT company_id FROM app.ctx()))));

-- 19.8 Audit log: State Admin everything; Principal / District Admin their institutes
CREATE POLICY sel ON audit_logs FOR SELECT USING (
    app.has_permission('audit.view') AND (app.current_role_code() = 'STATE_ADMIN' OR app.is_my_iti(iti_id)));

-- 19.9 Role assignments: prevents privilege escalation (a Principal can only grant
--      Teacher / Placement Officer / ITI Staff roles, and only at their own ITI)
CREATE POLICY sel ON user_role_assignments FOR SELECT USING (
    user_id = app.current_user_id() OR app.current_role_code() = 'STATE_ADMIN' OR app.is_my_iti(iti_id));
CREATE POLICY ins ON user_role_assignments FOR INSERT WITH CHECK (
    app.current_role_code() = 'STATE_ADMIN'
    OR (app.current_role_code() = 'PRINCIPAL' AND iti_id = (SELECT iti_id FROM app.ctx())
        AND role_id IN (SELECT id FROM roles WHERE code IN ('TEACHER','PLACEMENT_OFFICER','ITI_STAFF'))));
CREATE POLICY upd ON user_role_assignments FOR UPDATE USING (
    app.current_role_code() = 'STATE_ADMIN'
    OR (app.current_role_code() = 'PRINCIPAL' AND iti_id = (SELECT iti_id FROM app.ctx())
        AND role_id IN (SELECT id FROM roles WHERE code IN ('TEACHER','PLACEMENT_OFFICER','ITI_STAFF'))));

-- =============================================================================
-- 20. REFERENCE DATA: roles & permissions (master geography/ITI/trade data is
--     loaded from the authoritative source once confirmed — see open decisions)
-- =============================================================================
INSERT INTO roles (code, name, scope_type) VALUES
 ('STATE_ADMIN',       'State Admin',        'STATE'),
 ('DISTRICT_ADMIN',    'District Admin',     'DISTRICT'),
 ('PRINCIPAL',         'ITI Principal',      'ITI'),
 ('TEACHER',           'Teacher / Instructor','ITI'),
 ('PLACEMENT_OFFICER', 'Placement Officer',  'ITI'),
 ('ITI_STAFF',         'ITI Staff (follow-up)','ITI'),
 ('EMPLOYER',          'Employer / HR',      'COMPANY'),
 ('STUDENT',           'Student / Alumni',   'SELF');

INSERT INTO permissions (code, description) VALUES
 ('student.view',               'View student lifecycle within scope'),
 ('student.create',             'Create student / admission'),
 ('student.update',             'Update student profile'),
 ('student.contact.view',       'View personal contact details'),
 ('student.document.view',      'Download student documents'),
 ('admission.manage',           'Manage admissions and batches'),
 ('transfer.request',           'Initiate an outbound transfer'),
 ('transfer.approve',           'Accept / reject an inbound transfer'),
 ('teacher_assignment.manage',  'Assign / reassign teachers'),
 ('attendance.mark',            'Mark attendance'),
 ('training.update',            'Update module progress'),
 ('assessment.record',          'Record internal assessments'),
 ('exam_result.record',         'Record exam results'),
 ('career.record',              'Record apprenticeship / job / business / higher education'),
 ('career.salary.view',         'View salary and stipend amounts'),
 ('verification.decide',        'Verify / reject records'),
 ('followup.manage',            'Create and assign follow-ups'),
 ('followup.work',              'Work on follow-ups assigned to me'),
 ('vacancy.manage',             'Manage company vacancies'),
 ('application.manage',         'Manage job applications'),
 ('employer.confirm',           'Confirm own company engagements'),
 ('report.iti',                 'Institute reports'),
 ('report.district',            'District reports'),
 ('report.state',               'State reports'),
 ('audit.view',                 'View audit logs'),
 ('user.manage',                'Manage users and role assignments'),
 ('master_data.manage',         'Manage districts, ITIs, trades, academic years'),
 ('self.view',                  'View own Career Passport'),
 ('self.update',                'Submit own updates / corrections');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON
 (r.code = 'STATE_ADMIN' AND p.code NOT IN ('self.view','self.update','employer.confirm','vacancy.manage','followup.work'))
 OR (r.code = 'DISTRICT_ADMIN' AND p.code IN ('student.view','report.iti','report.district','audit.view'))
 OR (r.code = 'PRINCIPAL' AND p.code IN ('student.view','student.create','student.update','student.contact.view',
        'student.document.view','admission.manage','transfer.request','transfer.approve','teacher_assignment.manage',
        'attendance.mark','training.update','assessment.record','exam_result.record','career.record','career.salary.view',
        'verification.decide','followup.manage','followup.work','application.manage','report.iti','audit.view','user.manage'))
 OR (r.code = 'TEACHER' AND p.code IN ('student.view','attendance.mark','training.update','assessment.record','followup.work'))
 OR (r.code = 'PLACEMENT_OFFICER' AND p.code IN ('student.view','student.contact.view','student.document.view',
        'career.record','career.salary.view','verification.decide','followup.manage','followup.work',
        'application.manage','report.iti'))
 OR (r.code = 'ITI_STAFF' AND p.code IN ('followup.work','student.contact.view'))
 OR (r.code = 'EMPLOYER' AND p.code IN ('vacancy.manage','application.manage','employer.confirm'))
 OR (r.code = 'STUDENT' AND p.code IN ('self.view','self.update','student.contact.view','student.document.view','career.salary.view'));

COMMIT;
