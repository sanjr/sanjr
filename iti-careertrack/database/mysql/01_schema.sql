-- =============================================================================
-- ITI CareerTrack — MySQL 8.0 schema (production build of the approved design)
-- -----------------------------------------------------------------------------
-- Requires MySQL 8.0.16+ (CHECK constraints, expression defaults, generated cols)
-- Charset utf8mb4 (Marathi names supported).
--
-- MySQL differences vs the PostgreSQL review design (see docs/03-MYSQL-NOTES.md):
--  * BIGINT AUTO_INCREMENT primary keys (idiomatic for PHP/MySQL; URLs are still
--    protected because every lookup goes through the access-control layer).
--  * "Only one ACTIVE row" rules use a generated *_flag column (1 or NULL) inside
--    a UNIQUE key — MySQL allows many NULLs but only one 1.
--  * MySQL has no Row-Level Security: tenant isolation is enforced centrally in
--    the PHP Access class (every student query goes through it) and is covered by
--    the automated HTTP security tests.
--  * Business-critical rules that must never be bypassed (PCID issuance,
--    affiliation tracking, immutable columns, append-only audit) are triggers /
--    stored procedures — see 02_routines.sql.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- System settings (PCID prefix etc.)
-- -----------------------------------------------------------------------------
CREATE TABLE settings (
    setting_key    VARCHAR(64)  NOT NULL PRIMARY KEY,
    setting_value  VARCHAR(255) NOT NULL,
    description    VARCHAR(255) NULL,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Users
-- -----------------------------------------------------------------------------
CREATE TABLE users (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name           VARCHAR(150) NOT NULL,
    email               VARCHAR(190) NULL,
    mobile              VARCHAR(10)  NULL,
    password_hash       VARCHAR(255) NULL,
    status              VARCHAR(12)  NOT NULL DEFAULT 'ACTIVE',
    student_id          BIGINT UNSIGNED NULL,
    failed_login_count  INT NOT NULL DEFAULT 0,
    locked_until        DATETIME NULL,
    last_login_at       DATETIME NULL,
    is_demo             TINYINT(1) NOT NULL DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by          BIGINT UNSIGNED NULL,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by          BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_mobile (mobile),
    UNIQUE KEY uq_users_student (student_id),
    CONSTRAINT ck_users_status CHECK (status IN ('INVITED','ACTIVE','LOCKED','DISABLED')),
    CONSTRAINT ck_users_mobile CHECK (mobile IS NULL OR mobile REGEXP '^[6-9][0-9]{9}$'),
    CONSTRAINT ck_users_login  CHECK (email IS NOT NULL OR mobile IS NOT NULL)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Geography & institutes
-- -----------------------------------------------------------------------------
CREATE TABLE districts (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20)  NOT NULL,
    name        VARCHAR(100) NOT NULL,
    name_mr     VARCHAR(100) NULL,
    division    VARCHAR(50)  NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_districts_code (code),
    UNIQUE KEY uq_districts_name (name)
) ENGINE=InnoDB;

CREATE TABLE talukas (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    district_id  BIGINT UNSIGNED NOT NULL,
    code         VARCHAR(20)  NOT NULL,
    name         VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_talukas_code (code),
    UNIQUE KEY uq_talukas_name (district_id, name),
    UNIQUE KEY uq_talukas_id_district (id, district_id),
    CONSTRAINT fk_talukas_district FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB;

CREATE TABLE itis (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    iti_code        VARCHAR(20)  NOT NULL,
    name            VARCHAR(200) NOT NULL,
    name_mr         VARCHAR(200) NULL,
    ownership_type  VARCHAR(12)  NOT NULL,
    district_id     BIGINT UNSIGNED NOT NULL,
    taluka_id       BIGINT UNSIGNED NULL,
    address_line    VARCHAR(255) NULL,
    pincode         CHAR(6) NULL,
    phone           VARCHAR(20) NULL,
    email           VARCHAR(190) NULL,
    status          VARCHAR(12) NOT NULL DEFAULT 'ACTIVE',
    is_demo         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by      BIGINT UNSIGNED NULL,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by      BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_itis_code (iti_code),
    KEY ix_itis_district (district_id),
    CONSTRAINT fk_itis_district FOREIGN KEY (district_id) REFERENCES districts(id),
    CONSTRAINT fk_itis_taluka FOREIGN KEY (taluka_id, district_id) REFERENCES talukas(id, district_id),
    CONSTRAINT ck_itis_owner CHECK (ownership_type IN ('GOVERNMENT','PRIVATE')),
    CONSTRAINT ck_itis_status CHECK (status IN ('ACTIVE','INACTIVE','SUSPENDED','CLOSED')),
    CONSTRAINT ck_itis_pin CHECK (pincode IS NULL OR pincode REGEXP '^[1-9][0-9]{5}$')
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Trades, modules, academic years, batches, timetable
-- -----------------------------------------------------------------------------
CREATE TABLE trades (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trade_code       VARCHAR(20)  NOT NULL,
    name             VARCHAR(120) NOT NULL,
    name_mr          VARCHAR(120) NULL,
    sector           VARCHAR(80)  NULL,
    scheme           VARCHAR(4)   NOT NULL DEFAULT 'NCVT',
    duration_months  SMALLINT NOT NULL,
    status           VARCHAR(10) NOT NULL DEFAULT 'ACTIVE',
    UNIQUE KEY uq_trades_code (trade_code),
    CONSTRAINT ck_trades_scheme CHECK (scheme IN ('NCVT','SCVT')),
    CONSTRAINT ck_trades_duration CHECK (duration_months > 0)
) ENGINE=InnoDB;

CREATE TABLE trade_modules (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trade_id     BIGINT UNSIGNED NOT NULL,
    module_code  VARCHAR(20) NOT NULL,
    name         VARCHAR(150) NOT NULL,
    sequence_no  SMALLINT NOT NULL,
    UNIQUE KEY uq_modules (trade_id, module_code),
    CONSTRAINT fk_modules_trade FOREIGN KEY (trade_id) REFERENCES trades(id)
) ENGINE=InnoDB;

CREATE TABLE iti_trades (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    iti_id            BIGINT UNSIGNED NOT NULL,
    trade_id          BIGINT UNSIGNED NOT NULL,
    sanctioned_units  SMALLINT NOT NULL DEFAULT 1,
    seats_per_unit    SMALLINT NULL,
    status            VARCHAR(10) NOT NULL DEFAULT 'ACTIVE',
    UNIQUE KEY uq_iti_trades (iti_id, trade_id),
    CONSTRAINT fk_it_iti FOREIGN KEY (iti_id) REFERENCES itis(id),
    CONSTRAINT fk_it_trade FOREIGN KEY (trade_id) REFERENCES trades(id),
    CONSTRAINT ck_it_units CHECK (sanctioned_units > 0),
    CONSTRAINT ck_it_status CHECK (status IN ('ACTIVE','SUSPENDED','WITHDRAWN'))
) ENGINE=InnoDB;

CREATE TABLE academic_years (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label       VARCHAR(9) NOT NULL,
    start_date  DATE NOT NULL,
    end_date    DATE NOT NULL,
    status      VARCHAR(8) NOT NULL DEFAULT 'PLANNED',
    UNIQUE KEY uq_ay_label (label),
    CONSTRAINT ck_ay_dates CHECK (end_date > start_date),
    CONSTRAINT ck_ay_status CHECK (status IN ('PLANNED','OPEN','CLOSED'))
) ENGINE=InnoDB;

CREATE TABLE batches (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    iti_id            BIGINT UNSIGNED NOT NULL,
    trade_id          BIGINT UNSIGNED NOT NULL,
    academic_year_id  BIGINT UNSIGNED NOT NULL,
    unit_no           SMALLINT NOT NULL,
    shift             VARCHAR(8) NOT NULL DEFAULT 'GENERAL',
    batch_code        VARCHAR(40) NOT NULL,
    capacity          SMALLINT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_batches (iti_id, trade_id, academic_year_id, unit_no, shift),
    UNIQUE KEY uq_batches_link (id, iti_id, trade_id),
    CONSTRAINT fk_batches_it FOREIGN KEY (iti_id, trade_id) REFERENCES iti_trades(iti_id, trade_id),
    CONSTRAINT fk_batches_ay FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    CONSTRAINT ck_batches_shift CHECK (shift IN ('GENERAL','FIRST','SECOND','THIRD')),
    CONSTRAINT ck_batches_unit CHECK (unit_no > 0)
) ENGINE=InnoDB;

CREATE TABLE timetable_slots (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id         BIGINT UNSIGNED NOT NULL,
    teacher_user_id  BIGINT UNSIGNED NOT NULL,
    weekday          TINYINT NOT NULL,           -- 1 = Monday ... 7 = Sunday
    start_time       TIME NOT NULL,
    end_time         TIME NOT NULL,
    subject          VARCHAR(120) NOT NULL,
    KEY ix_tt_teacher (teacher_user_id, weekday),
    CONSTRAINT fk_tt_batch FOREIGN KEY (batch_id) REFERENCES batches(id),
    CONSTRAINT fk_tt_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id),
    CONSTRAINT ck_tt_day CHECK (weekday BETWEEN 1 AND 7),
    CONSTRAINT ck_tt_time CHECK (end_time > start_time)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Employers & colleges
-- -----------------------------------------------------------------------------
CREATE TABLE companies (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    legal_name       VARCHAR(200) NOT NULL,
    display_name     VARCHAR(200) NULL,
    gstin            VARCHAR(15) NULL,
    industry_sector  VARCHAR(80) NULL,
    company_size     VARCHAR(8) NULL,
    website          VARCHAR(200) NULL,
    hq_city          VARCHAR(80) NULL,
    status           VARCHAR(16) NOT NULL DEFAULT 'ACTIVE',
    is_demo          TINYINT(1) NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by       BIGINT UNSIGNED NULL,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_companies_gstin (gstin),
    KEY ix_companies_name (legal_name),
    CONSTRAINT ck_comp_status CHECK (status IN ('PENDING_APPROVAL','ACTIVE','SUSPENDED')),
    CONSTRAINT ck_comp_size CHECK (company_size IS NULL OR company_size IN ('MICRO','SMALL','MEDIUM','LARGE'))
) ENGINE=InnoDB;

CREATE TABLE company_branches (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id    BIGINT UNSIGNED NOT NULL,
    branch_name   VARCHAR(150) NOT NULL,
    address_line  VARCHAR(255) NULL,
    city          VARCHAR(80) NULL,
    district_id   BIGINT UNSIGNED NULL,          -- NULL when outside Maharashtra
    state_code    CHAR(2) NOT NULL DEFAULT 'MH',
    pincode       CHAR(6) NULL,
    status        VARCHAR(8) NOT NULL DEFAULT 'ACTIVE',
    UNIQUE KEY uq_branches (company_id, branch_name),
    UNIQUE KEY uq_branches_link (id, company_id),
    CONSTRAINT fk_branches_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_branches_district FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB;

CREATE TABLE colleges (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(200) NOT NULL,
    university    VARCHAR(200) NULL,
    district_id   BIGINT UNSIGNED NULL,
    CONSTRAINT fk_colleges_district FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Roles, permissions, scoped assignments
-- -----------------------------------------------------------------------------
CREATE TABLE roles (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30) NOT NULL,
    name        VARCHAR(60) NOT NULL,
    scope_type  VARCHAR(10) NOT NULL,
    UNIQUE KEY uq_roles_code (code),
    UNIQUE KEY uq_roles_scope (id, scope_type),
    CONSTRAINT ck_roles_scope CHECK (scope_type IN ('STATE','DISTRICT','ITI','COMPANY','SELF'))
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(60) NOT NULL,
    description  VARCHAR(200) NOT NULL,
    UNIQUE KEY uq_permissions_code (code)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id        BIGINT UNSIGNED NOT NULL,
    permission_id  BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id)
) ENGINE=InnoDB;

CREATE TABLE user_role_assignments (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    role_id      BIGINT UNSIGNED NOT NULL,
    scope_type   VARCHAR(10) NOT NULL,
    district_id  BIGINT UNSIGNED NULL,
    iti_id       BIGINT UNSIGNED NULL,
    company_id   BIGINT UNSIGNED NULL,
    valid_from   DATE NOT NULL,
    valid_to     DATE NULL,
    status       VARCHAR(10) NOT NULL DEFAULT 'ACTIVE',
    scope_key    BIGINT UNSIGNED AS (COALESCE(iti_id, district_id, company_id, 0)) STORED,
    active_flag  TINYINT AS (IF(status = 'ACTIVE', 1, NULL)) STORED,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by   BIGINT UNSIGNED NULL,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by   BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_ura_active (user_id, role_id, scope_key, active_flag),
    KEY ix_ura_iti (iti_id),
    CONSTRAINT fk_ura_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_ura_role FOREIGN KEY (role_id, scope_type) REFERENCES roles(id, scope_type),
    CONSTRAINT fk_ura_district FOREIGN KEY (district_id) REFERENCES districts(id),
    CONSTRAINT fk_ura_iti FOREIGN KEY (iti_id) REFERENCES itis(id),
    CONSTRAINT fk_ura_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT ck_ura_status CHECK (status IN ('ACTIVE','SUSPENDED','REVOKED')),
    CONSTRAINT ck_ura_dates CHECK (valid_to IS NULL OR valid_to >= valid_from),
    CONSTRAINT ck_ura_scope CHECK (
        (scope_type IN ('STATE','SELF') AND district_id IS NULL AND iti_id IS NULL AND company_id IS NULL) OR
        (scope_type = 'DISTRICT' AND district_id IS NOT NULL AND iti_id IS NULL AND company_id IS NULL) OR
        (scope_type = 'ITI'      AND iti_id IS NOT NULL AND district_id IS NULL AND company_id IS NULL) OR
        (scope_type = 'COMPANY'  AND company_id IS NOT NULL AND district_id IS NULL AND iti_id IS NULL))
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Students, contacts, PCID, duplicates
-- -----------------------------------------------------------------------------
CREATE TABLE students (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name              VARCHAR(80) NOT NULL,
    middle_name             VARCHAR(80) NULL,
    last_name               VARCHAR(80) NULL,
    full_name_mr            VARCHAR(200) NULL,
    full_name_normalized    VARCHAR(250) AS (LOWER(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
                                CONCAT_WS(' ', first_name, middle_name, last_name), '[^A-Za-z ]', ''), ' +', ' ')))) STORED,
    gender                  VARCHAR(12) NOT NULL,
    date_of_birth           DATE NOT NULL,
    guardian_name           VARCHAR(150) NULL,
    social_category         VARCHAR(10) NULL,
    is_pwd                  TINYINT(1) NOT NULL DEFAULT 0,
    ssc_board               VARCHAR(40) NULL,
    ssc_seat_number         VARCHAR(20) NULL,
    ssc_passing_year        SMALLINT NULL,
    ssc_percentage          DECIMAL(5,2) NULL,
    highest_qualification   VARCHAR(40) NULL,
    aadhaar_hash            BINARY(32) NULL,      -- HMAC-SHA256 only; raw Aadhaar is NEVER stored
    aadhaar_last4           CHAR(4) NULL,
    photo_document_id       BIGINT UNSIGNED NULL,
    profile_status          VARCHAR(10) NOT NULL DEFAULT 'ACTIVE',
    merged_into_student_id  BIGINT UNSIGNED NULL,
    is_demo                 TINYINT(1) NOT NULL DEFAULT 0,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by              BIGINT UNSIGNED NULL,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by              BIGINT UNSIGNED NULL,
    version                 INT NOT NULL DEFAULT 1,
    UNIQUE KEY uq_students_ssc (ssc_board, ssc_seat_number, ssc_passing_year),
    UNIQUE KEY uq_students_aadhaar (aadhaar_hash),
    KEY ix_students_dob (date_of_birth),
    KEY ix_students_name (full_name_normalized),
    KEY ix_students_last (last_name),
    CONSTRAINT fk_students_merged FOREIGN KEY (merged_into_student_id) REFERENCES students(id),
    CONSTRAINT ck_students_gender CHECK (gender IN ('MALE','FEMALE','TRANSGENDER','UNDISCLOSED')),
    CONSTRAINT ck_students_dob CHECK (date_of_birth > '1940-01-01'),
    CONSTRAINT ck_students_status CHECK (profile_status IN ('ACTIVE','MERGED','DECEASED','INACTIVE')),
    CONSTRAINT ck_students_merge CHECK ((profile_status = 'MERGED') = (merged_into_student_id IS NOT NULL)),
    CONSTRAINT ck_students_ssc CHECK ((ssc_board IS NULL) = (ssc_seat_number IS NULL) AND (ssc_board IS NULL) = (ssc_passing_year IS NULL)),
    CONSTRAINT ck_students_cat CHECK (social_category IS NULL OR social_category IN ('OPEN','OBC','SC','ST','VJNT','SBC','EWS','OTHER'))
) ENGINE=InnoDB;

ALTER TABLE users ADD CONSTRAINT fk_users_student FOREIGN KEY (student_id) REFERENCES students(id);

CREATE TABLE student_contacts (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id        BIGINT UNSIGNED NOT NULL,
    contact_type      VARCHAR(16) NOT NULL,
    value             VARCHAR(255) NOT NULL,
    value_normalized  VARCHAR(190) NULL,
    district_id       BIGINT UNSIGNED NULL,
    pincode           CHAR(6) NULL,
    is_primary        TINYINT(1) NOT NULL DEFAULT 0,
    valid_from        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    valid_to          DATETIME NULL,
    primary_flag      TINYINT AS (IF(is_primary = 1 AND valid_to IS NULL, 1, NULL)) STORED,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by        BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_contacts_primary (student_id, contact_type, primary_flag),
    KEY ix_contacts_value (value_normalized),
    CONSTRAINT fk_contacts_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_contacts_district FOREIGN KEY (district_id) REFERENCES districts(id),
    CONSTRAINT ck_contacts_type CHECK (contact_type IN ('MOBILE','WHATSAPP','EMAIL','GUARDIAN_MOBILE','ADDRESS'))
) ENGINE=InnoDB;

CREATE TABLE pcid_year_counters (
    issue_year   SMALLINT NOT NULL PRIMARY KEY,
    last_serial  INT NOT NULL,
    CONSTRAINT ck_pcid_counter CHECK (last_serial BETWEEN 0 AND 999999)
) ENGINE=InnoDB;

CREATE TABLE pcid_registry (
    id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pcid                     VARCHAR(32) NOT NULL,
    student_id               BIGINT UNSIGNED NOT NULL,
    issue_year               SMALLINT NOT NULL,
    serial                   INT NOT NULL,
    status                   VARCHAR(16) NOT NULL DEFAULT 'ACTIVE',
    issued_for_admission_id  BIGINT UNSIGNED NULL,
    issued_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    issued_by                BIGINT UNSIGNED NULL,
    retired_at               DATETIME NULL,
    retired_reason           VARCHAR(255) NULL,
    redirect_to_pcid_id      BIGINT UNSIGNED NULL,
    active_flag              TINYINT AS (IF(status = 'ACTIVE', 1, NULL)) STORED,
    UNIQUE KEY uq_pcid (pcid),
    UNIQUE KEY uq_pcid_serial (issue_year, serial),
    UNIQUE KEY uq_pcid_one_active (student_id, active_flag),
    CONSTRAINT fk_pcid_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_pcid_redirect FOREIGN KEY (redirect_to_pcid_id) REFERENCES pcid_registry(id),
    CONSTRAINT ck_pcid_status CHECK (status IN ('ACTIVE','RETIRED_MERGED','VOID'))
) ENGINE=InnoDB;

CREATE TABLE duplicate_candidates (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id_a   BIGINT UNSIGNED NOT NULL,
    student_id_b   BIGINT UNSIGNED NOT NULL,
    match_score    SMALLINT NOT NULL,
    match_reasons  VARCHAR(255) NOT NULL,
    status         VARCHAR(20) NOT NULL DEFAULT 'OPEN',
    override_reason VARCHAR(255) NULL,
    reviewed_by    BIGINT UNSIGNED NULL,
    reviewed_at    DATETIME NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by     BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_dup_pair (student_id_a, student_id_b),
    CONSTRAINT fk_dup_a FOREIGN KEY (student_id_a) REFERENCES students(id),
    CONSTRAINT fk_dup_b FOREIGN KEY (student_id_b) REFERENCES students(id),
    CONSTRAINT ck_dup_order CHECK (student_id_a < student_id_b),
    CONSTRAINT ck_dup_status CHECK (status IN ('OPEN','CONFIRMED_DUPLICATE','NOT_DUPLICATE'))
) ENGINE=InnoDB;

CREATE TABLE student_merges (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survivor_student_id  BIGINT UNSIGNED NOT NULL,
    merged_student_id    BIGINT UNSIGNED NOT NULL,
    reason               VARCHAR(255) NOT NULL,
    approved_by          BIGINT UNSIGNED NOT NULL,
    merged_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_merge (merged_student_id),
    CONSTRAINT fk_merge_survivor FOREIGN KEY (survivor_student_id) REFERENCES students(id),
    CONSTRAINT fk_merge_merged FOREIGN KEY (merged_student_id) REFERENCES students(id),
    CONSTRAINT fk_merge_user FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Admissions, affiliations, teacher assignments, transfers
-- -----------------------------------------------------------------------------
CREATE TABLE admissions (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id              BIGINT UNSIGNED NOT NULL,
    iti_id                  BIGINT UNSIGNED NOT NULL,
    trade_id                BIGINT UNSIGNED NOT NULL,
    academic_year_id        BIGINT UNSIGNED NOT NULL,
    batch_id                BIGINT UNSIGNED NULL,
    admission_type          VARCHAR(14) NOT NULL DEFAULT 'REGULAR',
    previous_admission_id   BIGINT UNSIGNED NULL,
    admission_date          DATE NOT NULL,
    duration_months         SMALLINT NOT NULL,
    external_admission_ref  VARCHAR(40) NULL,
    status                  VARCHAR(16) NOT NULL DEFAULT 'ACCEPTED',
    end_date                DATE NULL,
    end_reason              VARCHAR(255) NULL,
    live_flag               TINYINT AS (IF(status IN ('ACCEPTED','ACTIVE'), 1, NULL)) STORED,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by              BIGINT UNSIGNED NULL,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by              BIGINT UNSIGNED NULL,
    version                 INT NOT NULL DEFAULT 1,
    UNIQUE KEY uq_adm_one_live (student_id, live_flag),
    UNIQUE KEY uq_adm_ext (academic_year_id, external_admission_ref),
    UNIQUE KEY uq_adm_iti (id, iti_id),
    UNIQUE KEY uq_adm_student (id, student_id),
    KEY ix_adm_iti_year (iti_id, academic_year_id, status),
    CONSTRAINT fk_adm_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_adm_it FOREIGN KEY (iti_id, trade_id) REFERENCES iti_trades(iti_id, trade_id),
    CONSTRAINT fk_adm_ay FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    CONSTRAINT fk_adm_batch FOREIGN KEY (batch_id, iti_id, trade_id) REFERENCES batches(id, iti_id, trade_id),
    CONSTRAINT fk_adm_prev FOREIGN KEY (previous_admission_id) REFERENCES admissions(id),
    CONSTRAINT ck_adm_type CHECK (admission_type IN ('REGULAR','TRANSFER_IN','RE_ADMISSION')),
    CONSTRAINT ck_adm_status CHECK (status IN ('ACCEPTED','ACTIVE','COMPLETED','TRANSFERRED_OUT','DISCONTINUED','CANCELLED')),
    CONSTRAINT ck_adm_transfer CHECK (admission_type <> 'TRANSFER_IN' OR previous_admission_id IS NOT NULL),
    CONSTRAINT ck_adm_end CHECK ((status IN ('ACCEPTED','ACTIVE')) = (end_date IS NULL)),
    CONSTRAINT ck_adm_end_after CHECK (end_date IS NULL OR end_date >= admission_date),
    CONSTRAINT ck_adm_duration CHECK (duration_months > 0)
) ENGINE=InnoDB;

ALTER TABLE pcid_registry ADD CONSTRAINT fk_pcid_admission FOREIGN KEY (issued_for_admission_id) REFERENCES admissions(id);

CREATE TABLE institute_affiliations (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id    BIGINT UNSIGNED NOT NULL,
    iti_id        BIGINT UNSIGNED NOT NULL,
    admission_id  BIGINT UNSIGNED NOT NULL,
    start_date    DATE NOT NULL,
    end_date      DATE NULL,
    status        VARCHAR(16) NOT NULL,
    active_flag   TINYINT AS (IF(status = 'ACTIVE', 1, NULL)) STORED,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aff_admission (admission_id),
    UNIQUE KEY uq_aff_one_active (student_id, active_flag),
    KEY ix_aff_iti (iti_id, status),
    CONSTRAINT fk_aff_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_aff_adm_iti FOREIGN KEY (admission_id, iti_id) REFERENCES admissions(id, iti_id),
    CONSTRAINT fk_aff_adm_student FOREIGN KEY (admission_id, student_id) REFERENCES admissions(id, student_id),
    CONSTRAINT ck_aff_status CHECK (status IN ('ACTIVE','COMPLETED','TRANSFERRED_OUT','DISCONTINUED')),
    CONSTRAINT ck_aff_end CHECK ((status = 'ACTIVE') = (end_date IS NULL)),
    CONSTRAINT ck_aff_dates CHECK (end_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB;

CREATE TABLE teacher_assignments (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id       BIGINT UNSIGNED NOT NULL,
    teacher_user_id  BIGINT UNSIGNED NOT NULL,
    iti_id           BIGINT UNSIGNED NOT NULL,
    admission_id     BIGINT UNSIGNED NOT NULL,
    assignment_role  VARCHAR(24) NOT NULL DEFAULT 'TRADE_INSTRUCTOR',
    start_date       DATE NOT NULL,
    end_date         DATE NULL,                    -- first day WITHOUT access
    end_reason       VARCHAR(255) NULL,
    open_flag        TINYINT AS (IF(end_date IS NULL, 1, NULL)) STORED,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by       BIGINT UNSIGNED NULL,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ta_open (student_id, teacher_user_id, assignment_role, open_flag),
    KEY ix_ta_teacher (teacher_user_id, iti_id, end_date),
    CONSTRAINT fk_ta_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_ta_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id),
    CONSTRAINT fk_ta_adm_iti FOREIGN KEY (admission_id, iti_id) REFERENCES admissions(id, iti_id),
    CONSTRAINT fk_ta_adm_student FOREIGN KEY (admission_id, student_id) REFERENCES admissions(id, student_id),
    CONSTRAINT ck_ta_role CHECK (assignment_role IN ('TRADE_INSTRUCTOR','EMPLOYABILITY_SKILLS','WORKSHOP_CALC_SCIENCE','ENGINEERING_DRAWING','CLASS_TEACHER')),
    CONSTRAINT ck_ta_dates CHECK (end_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB;

CREATE TABLE transfer_requests (
    id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id                BIGINT UNSIGNED NOT NULL,
    from_iti_id               BIGINT UNSIGNED NOT NULL,
    from_admission_id         BIGINT UNSIGNED NOT NULL,
    to_iti_id                 BIGINT UNSIGNED NOT NULL,
    to_trade_id               BIGINT UNSIGNED NOT NULL,
    to_academic_year_id       BIGINT UNSIGNED NOT NULL,
    requested_effective_date  DATE NOT NULL,
    reason                    VARCHAR(500) NOT NULL,
    status                    VARCHAR(10) NOT NULL DEFAULT 'REQUESTED',
    requested_by              BIGINT UNSIGNED NOT NULL,
    requested_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decided_by                BIGINT UNSIGNED NULL,
    decided_at                DATETIME NULL,
    decision_remarks          VARCHAR(500) NULL,
    new_admission_id          BIGINT UNSIGNED NULL,
    open_flag                 TINYINT AS (IF(status = 'REQUESTED', 1, NULL)) STORED,
    UNIQUE KEY uq_tr_one_open (student_id, open_flag),
    KEY ix_tr_to (to_iti_id, status),
    KEY ix_tr_from (from_iti_id, status),
    CONSTRAINT fk_tr_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_tr_from_adm FOREIGN KEY (from_admission_id, from_iti_id) REFERENCES admissions(id, iti_id),
    CONSTRAINT fk_tr_from_adm_st FOREIGN KEY (from_admission_id, student_id) REFERENCES admissions(id, student_id),
    CONSTRAINT fk_tr_to FOREIGN KEY (to_iti_id, to_trade_id) REFERENCES iti_trades(iti_id, trade_id),
    CONSTRAINT fk_tr_ay FOREIGN KEY (to_academic_year_id) REFERENCES academic_years(id),
    CONSTRAINT fk_tr_req_by FOREIGN KEY (requested_by) REFERENCES users(id),
    CONSTRAINT fk_tr_dec_by FOREIGN KEY (decided_by) REFERENCES users(id),
    CONSTRAINT fk_tr_new_adm FOREIGN KEY (new_admission_id) REFERENCES admissions(id),
    CONSTRAINT ck_tr_diff CHECK (from_iti_id <> to_iti_id),
    CONSTRAINT ck_tr_status CHECK (status IN ('REQUESTED','ACCEPTED','REJECTED','CANCELLED')),
    CONSTRAINT ck_tr_decided CHECK ((status IN ('ACCEPTED','REJECTED')) = (decided_at IS NOT NULL)),
    CONSTRAINT ck_tr_new CHECK ((status = 'ACCEPTED') = (new_admission_id IS NOT NULL)),
    CONSTRAINT ck_tr_reason CHECK (CHAR_LENGTH(TRIM(reason)) >= 5)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Training, attendance, modules, assessments, exams
-- -----------------------------------------------------------------------------
CREATE TABLE training_records (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id         BIGINT UNSIGNED NOT NULL,
    admission_id       BIGINT UNSIGNED NOT NULL,
    iti_id             BIGINT UNSIGNED NOT NULL,
    start_date         DATE NOT NULL,
    expected_end_date  DATE NULL,
    actual_end_date    DATE NULL,
    completion_status  VARCHAR(16) NOT NULL DEFAULT 'IN_PROGRESS',
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tr_iti (id, iti_id),
    UNIQUE KEY uq_tr_student (id, student_id),
    KEY ix_training_student (student_id),
    KEY ix_training_adm (admission_id),
    CONSTRAINT fk_trn_adm_iti FOREIGN KEY (admission_id, iti_id) REFERENCES admissions(id, iti_id),
    CONSTRAINT fk_trn_adm_student FOREIGN KEY (admission_id, student_id) REFERENCES admissions(id, student_id),
    CONSTRAINT ck_trn_status CHECK (completion_status IN ('IN_PROGRESS','COMPLETED','TRANSFERRED_OUT','DISCONTINUED')),
    CONSTRAINT ck_trn_dates CHECK (actual_end_date IS NULL OR actual_end_date >= start_date)
) ENGINE=InnoDB;

-- High volume table. (MySQL cannot partition tables that have foreign keys, so it
-- is indexed instead; archive old years to attendance_archive_YYYY if needed.)
CREATE TABLE attendance_records (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id          BIGINT UNSIGNED NOT NULL,
    training_record_id  BIGINT UNSIGNED NOT NULL,
    iti_id              BIGINT UNSIGNED NOT NULL,
    attendance_date     DATE NOT NULL,
    session             VARCHAR(10) NOT NULL DEFAULT 'FULL_DAY',
    status              VARCHAR(8) NOT NULL,
    marked_by           BIGINT UNSIGNED NOT NULL,
    marked_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_att (training_record_id, attendance_date, session),
    KEY ix_att_student (student_id, attendance_date),
    KEY ix_att_iti_date (iti_id, attendance_date),
    CONSTRAINT fk_att_tr_iti FOREIGN KEY (training_record_id, iti_id) REFERENCES training_records(id, iti_id),
    CONSTRAINT fk_att_tr_student FOREIGN KEY (training_record_id, student_id) REFERENCES training_records(id, student_id),
    CONSTRAINT fk_att_user FOREIGN KEY (marked_by) REFERENCES users(id),
    CONSTRAINT ck_att_session CHECK (session IN ('FULL_DAY','THEORY','PRACTICAL')),
    CONSTRAINT ck_att_status CHECK (status IN ('PRESENT','ABSENT','LEAVE','HOLIDAY','LATE'))
) ENGINE=InnoDB;

CREATE TABLE module_progress (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    training_record_id  BIGINT UNSIGNED NOT NULL,
    student_id          BIGINT UNSIGNED NOT NULL,
    iti_id              BIGINT UNSIGNED NOT NULL,
    module_id           BIGINT UNSIGNED NOT NULL,
    progress_percent    DECIMAL(5,2) NOT NULL DEFAULT 0,
    status              VARCHAR(12) NOT NULL DEFAULT 'NOT_STARTED',
    completed_on        DATE NULL,
    updated_by          BIGINT UNSIGNED NULL,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mp (training_record_id, module_id),
    CONSTRAINT fk_mp_tr_iti FOREIGN KEY (training_record_id, iti_id) REFERENCES training_records(id, iti_id),
    CONSTRAINT fk_mp_tr_student FOREIGN KEY (training_record_id, student_id) REFERENCES training_records(id, student_id),
    CONSTRAINT fk_mp_module FOREIGN KEY (module_id) REFERENCES trade_modules(id),
    CONSTRAINT ck_mp_pct CHECK (progress_percent BETWEEN 0 AND 100),
    CONSTRAINT ck_mp_status CHECK (status IN ('NOT_STARTED','IN_PROGRESS','COMPLETED')),
    CONSTRAINT ck_mp_done CHECK ((status = 'COMPLETED') = (completed_on IS NOT NULL))
) ENGINE=InnoDB;

CREATE TABLE assessments (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id          BIGINT UNSIGNED NOT NULL,
    training_record_id  BIGINT UNSIGNED NOT NULL,
    iti_id              BIGINT UNSIGNED NOT NULL,
    module_id           BIGINT UNSIGNED NULL,
    assessment_type     VARCHAR(20) NOT NULL,
    assessment_date     DATE NOT NULL,
    max_marks           DECIMAL(6,2) NOT NULL,
    marks_obtained      DECIMAL(6,2) NULL,
    assessor_user_id    BIGINT UNSIGNED NOT NULL,
    remarks             VARCHAR(255) NULL,
    voided_at           DATETIME NULL,
    void_reason         VARCHAR(255) NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_ass_student (student_id),
    CONSTRAINT fk_ass_tr_iti FOREIGN KEY (training_record_id, iti_id) REFERENCES training_records(id, iti_id),
    CONSTRAINT fk_ass_tr_student FOREIGN KEY (training_record_id, student_id) REFERENCES training_records(id, student_id),
    CONSTRAINT fk_ass_module FOREIGN KEY (module_id) REFERENCES trade_modules(id),
    CONSTRAINT fk_ass_user FOREIGN KEY (assessor_user_id) REFERENCES users(id),
    CONSTRAINT ck_ass_type CHECK (assessment_type IN ('FORMATIVE','INTERNAL_THEORY','INTERNAL_PRACTICAL','PROJECT','OTHER')),
    CONSTRAINT ck_ass_marks CHECK (max_marks > 0 AND (marks_obtained IS NULL OR (marks_obtained >= 0 AND marks_obtained <= max_marks))),
    CONSTRAINT ck_ass_void CHECK ((voided_at IS NULL) = (void_reason IS NULL))
) ENGINE=InnoDB;

CREATE TABLE exam_sessions (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    exam_body      VARCHAR(4) NOT NULL,
    exam_type      VARCHAR(14) NOT NULL,
    session_month  DATE NOT NULL,
    UNIQUE KEY uq_es (exam_body, exam_type, session_month),
    CONSTRAINT ck_es_body CHECK (exam_body IN ('NCVT','SCVT')),
    CONSTRAINT ck_es_type CHECK (exam_type IN ('MAIN','SUPPLEMENTARY')),
    CONSTRAINT ck_es_month CHECK (DAY(session_month) = 1)
) ENGINE=InnoDB;

CREATE TABLE exam_results (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id           BIGINT UNSIGNED NOT NULL,
    admission_id         BIGINT UNSIGNED NOT NULL,
    iti_id               BIGINT UNSIGNED NOT NULL,
    exam_session_id      BIGINT UNSIGNED NOT NULL,
    attempt_no           SMALLINT NOT NULL,
    roll_number          VARCHAR(30) NULL,
    result               VARCHAR(14) NOT NULL,
    marks_obtained       DECIMAL(7,2) NULL,
    max_marks            DECIMAL(7,2) NULL,
    result_declared_on   DATE NULL,
    certificate_number   VARCHAR(40) NULL,
    verification_status  VARCHAR(10) NOT NULL DEFAULT 'PENDING',
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           BIGINT UNSIGNED NULL,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_er_attempt (admission_id, attempt_no),
    UNIQUE KEY uq_er_session (admission_id, exam_session_id),
    UNIQUE KEY uq_er_cert (certificate_number),
    UNIQUE KEY uq_er_student (id, student_id),
    KEY ix_er_student (student_id),
    CONSTRAINT fk_er_adm_iti FOREIGN KEY (admission_id, iti_id) REFERENCES admissions(id, iti_id),
    CONSTRAINT fk_er_adm_student FOREIGN KEY (admission_id, student_id) REFERENCES admissions(id, student_id),
    CONSTRAINT fk_er_session FOREIGN KEY (exam_session_id) REFERENCES exam_sessions(id),
    CONSTRAINT ck_er_attempt CHECK (attempt_no >= 1),
    CONSTRAINT ck_er_result CHECK (result IN ('PASS','FAIL','SUPPLEMENTARY','ABSENT','WITHHELD','AWAITED')),
    CONSTRAINT ck_er_marks CHECK (marks_obtained IS NULL OR max_marks IS NULL OR marks_obtained <= max_marks),
    CONSTRAINT ck_er_pass CHECK (result <> 'PASS' OR result_declared_on IS NOT NULL),
    CONSTRAINT ck_er_ver CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED'))
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Documents (metadata; files in a private folder outside the web root)
-- -----------------------------------------------------------------------------
CREATE TABLE student_documents (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id           BIGINT UNSIGNED NOT NULL,
    document_type        VARCHAR(30) NOT NULL,
    storage_key          VARCHAR(255) NOT NULL,
    original_filename    VARCHAR(255) NOT NULL,
    mime_type            VARCHAR(40) NOT NULL,
    size_bytes           INT NOT NULL,
    sha256               CHAR(64) NOT NULL,
    verification_status  VARCHAR(10) NOT NULL DEFAULT 'PENDING',
    uploaded_by          BIGINT UNSIGNED NOT NULL,
    voided_at            DATETIME NULL,
    void_reason          VARCHAR(255) NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_doc_key (storage_key),
    UNIQUE KEY uq_doc_student (id, student_id),
    KEY ix_doc_student (student_id),
    CONSTRAINT fk_doc_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_doc_user FOREIGN KEY (uploaded_by) REFERENCES users(id),
    CONSTRAINT ck_doc_type CHECK (document_type IN ('PHOTO','MARKSHEET','CERTIFICATE','ADMISSION_LETTER','APPRENTICESHIP_CONTRACT',
        'OFFER_LETTER','APPOINTMENT_LETTER','EXPERIENCE_LETTER','SALARY_SLIP','BUSINESS_PROOF','COLLEGE_ADMISSION_PROOF','ID_PROOF','OTHER')),
    CONSTRAINT ck_doc_mime CHECK (mime_type IN ('application/pdf','image/jpeg','image/png')),
    CONSTRAINT ck_doc_size CHECK (size_bytes BETWEEN 1 AND 5242880),
    CONSTRAINT ck_doc_ver CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED'))
) ENGINE=InnoDB;

ALTER TABLE students ADD CONSTRAINT fk_students_photo FOREIGN KEY (photo_document_id) REFERENCES student_documents(id);

-- -----------------------------------------------------------------------------
-- Career pathways
-- -----------------------------------------------------------------------------
CREATE TABLE apprenticeships (
    id                         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id                 BIGINT UNSIGNED NOT NULL,
    company_id                 BIGINT UNSIGNED NOT NULL,
    branch_id                  BIGINT UNSIGNED NULL,
    trade_id                   BIGINT UNSIGNED NULL,
    scheme                     VARCHAR(16) NOT NULL DEFAULT 'OTHER',
    contract_number            VARCHAR(40) NULL,
    start_date                 DATE NOT NULL,
    planned_end_date           DATE NULL,
    actual_end_date            DATE NULL,
    stipend_amount             DECIMAL(12,2) NULL,
    stipend_period             VARCHAR(8) NULL,
    status                     VARCHAR(14) NOT NULL DEFAULT 'ACTIVE',
    verification_status        VARCHAR(10) NOT NULL DEFAULT 'PENDING',
    source                     VARCHAR(10) NOT NULL,
    recorded_by_iti_id         BIGINT UNSIGNED NULL,
    voided_at                  DATETIME NULL,
    void_reason                VARCHAR(255) NULL,
    created_at                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by                 BIGINT UNSIGNED NULL,
    updated_at                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by                 BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_app_student (id, student_id),
    KEY ix_app_student (student_id),
    KEY ix_app_company (company_id),
    CONSTRAINT fk_app_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_app_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_app_branch FOREIGN KEY (branch_id, company_id) REFERENCES company_branches(id, company_id),
    CONSTRAINT fk_app_trade FOREIGN KEY (trade_id) REFERENCES trades(id),
    CONSTRAINT fk_app_iti FOREIGN KEY (recorded_by_iti_id) REFERENCES itis(id),
    CONSTRAINT ck_app_scheme CHECK (scheme IN ('APPRENTICES_ACT','NAPS','DUAL_SYSTEM','OTHER')),
    CONSTRAINT ck_app_status CHECK (status IN ('PLANNED','ACTIVE','COMPLETED','TERMINATED','DISCONTINUED')),
    CONSTRAINT ck_app_end CHECK ((status IN ('COMPLETED','TERMINATED','DISCONTINUED')) = (actual_end_date IS NOT NULL)),
    CONSTRAINT ck_app_dates CHECK ((actual_end_date IS NULL OR actual_end_date >= start_date) AND (planned_end_date IS NULL OR planned_end_date >= start_date)),
    CONSTRAINT ck_app_stipend CHECK (stipend_amount IS NULL OR (stipend_amount >= 0 AND stipend_period IN ('MONTHLY','ANNUAL','DAILY'))),
    CONSTRAINT ck_app_ver CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    CONSTRAINT ck_app_source CHECK (source IN ('STAFF','STUDENT','EMPLOYER','IMPORT'))
) ENGINE=InnoDB;

CREATE TABLE employments (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id              BIGINT UNSIGNED NOT NULL,
    company_id              BIGINT UNSIGNED NOT NULL,
    branch_id               BIGINT UNSIGNED NULL,
    from_apprenticeship_id  BIGINT UNSIGNED NULL,
    designation             VARCHAR(120) NOT NULL,
    employment_type         VARCHAR(10) NOT NULL,
    employer_sector         VARCHAR(10) NULL,
    work_location           VARCHAR(150) NULL,
    work_district_id        BIGINT UNSIGNED NULL,
    work_state_code         CHAR(2) NULL,
    joining_date            DATE NOT NULL,
    leaving_date            DATE NULL,
    leaving_reason          VARCHAR(255) NULL,
    salary_amount           DECIMAL(12,2) NULL,
    salary_period           VARCHAR(8) NULL,
    status                  VARCHAR(6) NOT NULL DEFAULT 'ACTIVE',
    verification_status     VARCHAR(10) NOT NULL DEFAULT 'PENDING',
    source                  VARCHAR(10) NOT NULL,
    recorded_by_iti_id      BIGINT UNSIGNED NULL,
    voided_at               DATETIME NULL,
    void_reason             VARCHAR(255) NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by              BIGINT UNSIGNED NULL,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by              BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_emp_student (id, student_id),
    KEY ix_emp_student (student_id),
    KEY ix_emp_company (company_id),
    KEY ix_emp_joining (joining_date),
    CONSTRAINT fk_emp_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_emp_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_emp_branch FOREIGN KEY (branch_id, company_id) REFERENCES company_branches(id, company_id),
    CONSTRAINT fk_emp_app FOREIGN KEY (from_apprenticeship_id, student_id) REFERENCES apprenticeships(id, student_id),
    CONSTRAINT fk_emp_district FOREIGN KEY (work_district_id) REFERENCES districts(id),
    CONSTRAINT fk_emp_iti FOREIGN KEY (recorded_by_iti_id) REFERENCES itis(id),
    CONSTRAINT ck_emp_type CHECK (employment_type IN ('FULL_TIME','PART_TIME','CONTRACT','TEMPORARY','GIG')),
    CONSTRAINT ck_emp_sector CHECK (employer_sector IS NULL OR employer_sector IN ('PRIVATE','GOVERNMENT','PSU','NGO','OTHER')),
    CONSTRAINT ck_emp_status CHECK (status IN ('ACTIVE','ENDED')),
    CONSTRAINT ck_emp_end CHECK ((status = 'ENDED') = (leaving_date IS NOT NULL)),
    CONSTRAINT ck_emp_dates CHECK (leaving_date IS NULL OR leaving_date >= joining_date),
    CONSTRAINT ck_emp_salary CHECK (salary_amount IS NULL OR (salary_amount >= 0 AND salary_period IN ('MONTHLY','ANNUAL','DAILY','HOURLY'))),
    CONSTRAINT ck_emp_ver CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    CONSTRAINT ck_emp_source CHECK (source IN ('STAFF','STUDENT','EMPLOYER','IMPORT'))
) ENGINE=InnoDB;

CREATE TABLE business_records (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id           BIGINT UNSIGNED NOT NULL,
    business_name        VARCHAR(150) NOT NULL,
    business_type        VARCHAR(100) NOT NULL,
    registration_type    VARCHAR(12) NULL,
    registration_number  VARCHAR(40) NULL,
    location             VARCHAR(150) NULL,
    district_id          BIGINT UNSIGNED NULL,
    start_date           DATE NOT NULL,
    end_date             DATE NULL,
    employees_count      INT NULL,
    status               VARCHAR(8) NOT NULL DEFAULT 'ACTIVE',
    verification_status  VARCHAR(10) NOT NULL DEFAULT 'PENDING',
    source               VARCHAR(10) NOT NULL,
    recorded_by_iti_id   BIGINT UNSIGNED NULL,
    voided_at            DATETIME NULL,
    void_reason          VARCHAR(255) NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_bus_student (id, student_id),
    KEY ix_bus_student (student_id),
    CONSTRAINT fk_bus_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_bus_district FOREIGN KEY (district_id) REFERENCES districts(id),
    CONSTRAINT fk_bus_iti FOREIGN KEY (recorded_by_iti_id) REFERENCES itis(id),
    CONSTRAINT ck_bus_reg CHECK (registration_type IS NULL OR registration_type IN ('UDYAM','GST','SHOP_ACT','UNREGISTERED','OTHER')),
    CONSTRAINT ck_bus_status CHECK (status IN ('ACTIVE','PAUSED','CLOSED')),
    CONSTRAINT ck_bus_end CHECK ((status = 'CLOSED') = (end_date IS NOT NULL)),
    CONSTRAINT ck_bus_ver CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    CONSTRAINT ck_bus_source CHECK (source IN ('STAFF','STUDENT','IMPORT'))
) ENGINE=InnoDB;

CREATE TABLE higher_education_records (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id           BIGINT UNSIGNED NOT NULL,
    college_id           BIGINT UNSIGNED NOT NULL,
    course_name          VARCHAR(150) NOT NULL,
    course_level         VARCHAR(14) NOT NULL,
    study_mode           VARCHAR(10) NOT NULL DEFAULT 'FULL_TIME',
    admission_year       SMALLINT NOT NULL,
    status               VARCHAR(12) NOT NULL DEFAULT 'ENROLLED',
    verification_status  VARCHAR(10) NOT NULL DEFAULT 'PENDING',
    source               VARCHAR(10) NOT NULL,
    recorded_by_iti_id   BIGINT UNSIGNED NULL,
    voided_at            DATETIME NULL,
    void_reason          VARCHAR(255) NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_he_student (id, student_id),
    KEY ix_he_student (student_id),
    CONSTRAINT fk_he_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_he_college FOREIGN KEY (college_id) REFERENCES colleges(id),
    CONSTRAINT fk_he_iti FOREIGN KEY (recorded_by_iti_id) REFERENCES itis(id),
    CONSTRAINT ck_he_level CHECK (course_level IN ('CERTIFICATE','ADVANCED_ITI','DIPLOMA','DEGREE','POSTGRADUATE','OTHER')),
    CONSTRAINT ck_he_mode CHECK (study_mode IN ('FULL_TIME','PART_TIME','DISTANCE')),
    CONSTRAINT ck_he_status CHECK (status IN ('ENROLLED','COMPLETED','DISCONTINUED')),
    CONSTRAINT ck_he_ver CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    CONSTRAINT ck_he_source CHECK (source IN ('STAFF','STUDENT','IMPORT'))
) ENGINE=InnoDB;

-- One primary status per period; NOT_UPDATED / NOT_CONTACTABLE are never "unemployed"
CREATE TABLE career_status_history (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id                  BIGINT UNSIGNED NOT NULL,
    status                      VARCHAR(20) NOT NULL,
    effective_from              DATE NOT NULL,
    effective_to                DATE NULL,
    source                      VARCHAR(10) NOT NULL,
    verification_status         VARCHAR(10) NOT NULL DEFAULT 'PENDING',
    apprenticeship_id           BIGINT UNSIGNED NULL,
    employment_id               BIGINT UNSIGNED NULL,
    business_record_id          BIGINT UNSIGNED NULL,
    higher_education_record_id  BIGINT UNSIGNED NULL,
    note                        VARCHAR(255) NULL,
    current_flag                TINYINT AS (IF(effective_to IS NULL, 1, NULL)) STORED,
    created_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by                  BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_csh_current (student_id, current_flag),
    KEY ix_csh_status (status, current_flag),
    CONSTRAINT fk_csh_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_csh_app FOREIGN KEY (apprenticeship_id, student_id) REFERENCES apprenticeships(id, student_id),
    CONSTRAINT fk_csh_emp FOREIGN KEY (employment_id, student_id) REFERENCES employments(id, student_id),
    CONSTRAINT fk_csh_bus FOREIGN KEY (business_record_id, student_id) REFERENCES business_records(id, student_id),
    CONSTRAINT fk_csh_he FOREIGN KEY (higher_education_record_id, student_id) REFERENCES higher_education_records(id, student_id),
    CONSTRAINT ck_csh_status CHECK (status IN ('IN_TRAINING','APPRENTICE','EMPLOYED','SELF_EMPLOYED','HIGHER_EDUCATION',
        'SEEKING_EMPLOYMENT','NOT_SEEKING','NOT_UPDATED','NOT_CONTACTABLE','OTHER')),
    CONSTRAINT ck_csh_source CHECK (source IN ('STAFF','STUDENT','EMPLOYER','SYSTEM','FOLLOW_UP')),
    CONSTRAINT ck_csh_ver CHECK (verification_status IN ('PENDING','VERIFIED','REJECTED')),
    CONSTRAINT ck_csh_dates CHECK (effective_to IS NULL OR effective_to > effective_from),
    CONSTRAINT ck_csh_link CHECK ((apprenticeship_id IS NOT NULL) + (employment_id IS NOT NULL)
        + (business_record_id IS NOT NULL) + (higher_education_record_id IS NOT NULL) <= 1)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Follow-ups, verification, corrections, consent
-- -----------------------------------------------------------------------------
CREATE TABLE follow_ups (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id           BIGINT UNSIGNED NOT NULL,
    iti_id               BIGINT UNSIGNED NOT NULL,
    assigned_to_user_id  BIGINT UNSIGNED NOT NULL,
    reason               VARCHAR(20) NOT NULL,
    due_date             DATE NOT NULL,
    channel              VARCHAR(10) NULL,
    status               VARCHAR(12) NOT NULL DEFAULT 'PENDING',
    outcome              VARCHAR(14) NULL,
    outcome_notes        VARCHAR(500) NULL,
    completed_at         DATETIME NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           BIGINT UNSIGNED NULL,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_fu_queue (iti_id, status, due_date),
    KEY ix_fu_assignee (assigned_to_user_id, status),
    KEY ix_fu_student (student_id),
    CONSTRAINT fk_fu_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_fu_iti FOREIGN KEY (iti_id) REFERENCES itis(id),
    CONSTRAINT fk_fu_user FOREIGN KEY (assigned_to_user_id) REFERENCES users(id),
    CONSTRAINT ck_fu_reason CHECK (reason IN ('NO_RECENT_UPDATE','POST_COMPLETION','APPRENTICESHIP_ENDING','VERIFICATION','JOB_CHANGE','OTHER')),
    CONSTRAINT ck_fu_channel CHECK (channel IS NULL OR channel IN ('PHONE','SMS','WHATSAPP','EMAIL','VISIT','IN_PERSON')),
    CONSTRAINT ck_fu_status CHECK (status IN ('PENDING','IN_PROGRESS','COMPLETED','CANCELLED')),
    CONSTRAINT ck_fu_outcome CHECK (outcome IS NULL OR outcome IN ('UPDATED','NO_CHANGE','NOT_REACHABLE','WRONG_NUMBER','REFUSED')),
    CONSTRAINT ck_fu_done CHECK ((status = 'COMPLETED') = (outcome IS NOT NULL AND completed_at IS NOT NULL))
) ENGINE=InnoDB;

CREATE TABLE verification_records (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id                  BIGINT UNSIGNED NOT NULL,
    exam_result_id              BIGINT UNSIGNED NULL,
    apprenticeship_id           BIGINT UNSIGNED NULL,
    employment_id               BIGINT UNSIGNED NULL,
    business_record_id          BIGINT UNSIGNED NULL,
    higher_education_record_id  BIGINT UNSIGNED NULL,
    student_document_id         BIGINT UNSIGNED NULL,
    verifier_user_id            BIGINT UNSIGNED NOT NULL,
    verifier_company_id         BIGINT UNSIGNED NULL,
    method                      VARCHAR(24) NOT NULL,
    decision                    VARCHAR(10) NOT NULL,
    evidence_document_id        BIGINT UNSIGNED NULL,
    evidence_reference          VARCHAR(255) NULL,
    remarks                     VARCHAR(500) NULL,
    decided_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_vr_student (student_id),
    CONSTRAINT fk_vr_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_vr_er FOREIGN KEY (exam_result_id, student_id) REFERENCES exam_results(id, student_id),
    CONSTRAINT fk_vr_app FOREIGN KEY (apprenticeship_id, student_id) REFERENCES apprenticeships(id, student_id),
    CONSTRAINT fk_vr_emp FOREIGN KEY (employment_id, student_id) REFERENCES employments(id, student_id),
    CONSTRAINT fk_vr_bus FOREIGN KEY (business_record_id, student_id) REFERENCES business_records(id, student_id),
    CONSTRAINT fk_vr_he FOREIGN KEY (higher_education_record_id, student_id) REFERENCES higher_education_records(id, student_id),
    CONSTRAINT fk_vr_doc FOREIGN KEY (student_document_id, student_id) REFERENCES student_documents(id, student_id),
    CONSTRAINT fk_vr_evidence FOREIGN KEY (evidence_document_id, student_id) REFERENCES student_documents(id, student_id),
    CONSTRAINT fk_vr_user FOREIGN KEY (verifier_user_id) REFERENCES users(id),
    CONSTRAINT fk_vr_company FOREIGN KEY (verifier_company_id) REFERENCES companies(id),
    CONSTRAINT ck_vr_one_target CHECK ((exam_result_id IS NOT NULL) + (apprenticeship_id IS NOT NULL) + (employment_id IS NOT NULL)
        + (business_record_id IS NOT NULL) + (higher_education_record_id IS NOT NULL) + (student_document_id IS NOT NULL) = 1),
    CONSTRAINT ck_vr_method CHECK (method IN ('DOCUMENT','EMPLOYER_CONFIRMATION','PHONE_CALL','OFFICIAL_RECORD','SITE_VISIT')),
    CONSTRAINT ck_vr_decision CHECK (decision IN ('VERIFIED','REJECTED','NEEDS_INFO')),
    CONSTRAINT ck_vr_employer CHECK (method <> 'EMPLOYER_CONFIRMATION' OR verifier_company_id IS NOT NULL),
    CONSTRAINT ck_vr_evidence CHECK (decision <> 'VERIFIED' OR evidence_document_id IS NOT NULL OR evidence_reference IS NOT NULL)
) ENGINE=InnoDB;

CREATE TABLE correction_requests (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id        BIGINT UNSIGNED NOT NULL,
    target_table      VARCHAR(40) NOT NULL,
    target_id         BIGINT UNSIGNED NULL,
    proposed_changes  JSON NOT NULL,
    reason            VARCHAR(500) NOT NULL,
    status            VARCHAR(10) NOT NULL DEFAULT 'SUBMITTED',
    submitted_by      BIGINT UNSIGNED NOT NULL,
    reviewed_by       BIGINT UNSIGNED NULL,
    reviewed_at       DATETIME NULL,
    review_remarks    VARCHAR(500) NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cr_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_cr_user FOREIGN KEY (submitted_by) REFERENCES users(id),
    CONSTRAINT ck_cr_status CHECK (status IN ('SUBMITTED','APPROVED','REJECTED','WITHDRAWN'))
) ENGINE=InnoDB;

CREATE TABLE consents (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      BIGINT UNSIGNED NOT NULL,
    purpose         VARCHAR(30) NOT NULL,
    granted         TINYINT(1) NOT NULL,
    given_by        VARCHAR(10) NOT NULL DEFAULT 'STUDENT',     -- GUARDIAN for minors
    policy_version  VARCHAR(20) NOT NULL,
    channel         VARCHAR(16) NULL,
    recorded_by     BIGINT UNSIGNED NULL,
    recorded_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_consent (student_id, purpose, recorded_at),
    CONSTRAINT fk_consent_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT ck_consent_purpose CHECK (purpose IN ('SHARE_PROFILE_WITH_EMPLOYERS','SMS','WHATSAPP','EMAIL','DATA_PROCESSING')),
    CONSTRAINT ck_consent_by CHECK (given_by IN ('STUDENT','GUARDIAN'))
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Vacancies & applications
-- -----------------------------------------------------------------------------
CREATE TABLE vacancies (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id     BIGINT UNSIGNED NOT NULL,
    branch_id      BIGINT UNSIGNED NULL,
    trade_id       BIGINT UNSIGNED NULL,
    vacancy_type   VARCHAR(14) NOT NULL,
    title          VARCHAR(150) NOT NULL,
    description    TEXT NULL,
    openings       SMALLINT NOT NULL DEFAULT 1,
    salary_min     DECIMAL(12,2) NULL,
    salary_max     DECIMAL(12,2) NULL,
    salary_period  VARCHAR(8) NULL,
    location       VARCHAR(150) NULL,
    opens_on       DATE NOT NULL,
    closes_on      DATE NULL,
    status         VARCHAR(8) NOT NULL DEFAULT 'OPEN',
    posted_by      BIGINT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_vac_company (company_id, status),
    CONSTRAINT fk_vac_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_vac_branch FOREIGN KEY (branch_id, company_id) REFERENCES company_branches(id, company_id),
    CONSTRAINT fk_vac_trade FOREIGN KEY (trade_id) REFERENCES trades(id),
    CONSTRAINT ck_vac_type CHECK (vacancy_type IN ('JOB','APPRENTICESHIP')),
    CONSTRAINT ck_vac_status CHECK (status IN ('DRAFT','OPEN','CLOSED','FILLED')),
    CONSTRAINT ck_vac_open CHECK (openings > 0),
    CONSTRAINT ck_vac_salary CHECK (salary_max IS NULL OR salary_min IS NULL OR salary_max >= salary_min),
    CONSTRAINT ck_vac_dates CHECK (closes_on IS NULL OR closes_on >= opens_on)
) ENGINE=InnoDB;

CREATE TABLE job_applications (
    id                           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vacancy_id                   BIGINT UNSIGNED NOT NULL,
    student_id                   BIGINT UNSIGNED NOT NULL,
    status                       VARCHAR(12) NOT NULL DEFAULT 'APPLIED',
    consent_to_share_profile     TINYINT(1) NOT NULL,
    referred_by_user_id          BIGINT UNSIGNED NULL,
    referred_by_iti_id           BIGINT UNSIGNED NULL,
    resulting_employment_id      BIGINT UNSIGNED NULL,
    resulting_apprenticeship_id  BIGINT UNSIGNED NULL,
    applied_at                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ja (vacancy_id, student_id),
    KEY ix_ja_student (student_id),
    CONSTRAINT fk_ja_vacancy FOREIGN KEY (vacancy_id) REFERENCES vacancies(id),
    CONSTRAINT fk_ja_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_ja_emp FOREIGN KEY (resulting_employment_id, student_id) REFERENCES employments(id, student_id),
    CONSTRAINT fk_ja_app FOREIGN KEY (resulting_apprenticeship_id, student_id) REFERENCES apprenticeships(id, student_id),
    CONSTRAINT ck_ja_status CHECK (status IN ('APPLIED','SHORTLISTED','INTERVIEW','OFFERED','HIRED','REJECTED','WITHDRAWN'))
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Notifications, integration logs, audit
-- -----------------------------------------------------------------------------
CREATE TABLE notifications (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_user_id    BIGINT UNSIGNED NULL,
    student_id           BIGINT UNSIGNED NULL,
    channel              VARCHAR(10) NOT NULL,
    template_code        VARCHAR(60) NOT NULL,
    payload              JSON NULL,
    status               VARCHAR(10) NOT NULL DEFAULT 'QUEUED',
    attempts             SMALLINT NOT NULL DEFAULT 0,
    last_error_code      VARCHAR(60) NULL,
    scheduled_for        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at              DATETIME NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_notif_queue (status, scheduled_for),
    CONSTRAINT fk_notif_user FOREIGN KEY (recipient_user_id) REFERENCES users(id),
    CONSTRAINT fk_notif_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT ck_notif_channel CHECK (channel IN ('EMAIL','SMS','WHATSAPP','IN_APP')),
    CONSTRAINT ck_notif_status CHECK (status IN ('QUEUED','SENT','DELIVERED','FAILED','CANCELLED'))
) ENGINE=InnoDB;

CREATE TABLE integration_logs (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integration  VARCHAR(40) NOT NULL,
    operation    VARCHAR(60) NOT NULL,
    status       VARCHAR(8) NOT NULL,
    http_status  SMALLINT NULL,
    duration_ms  INT NULL,
    error_code   VARCHAR(60) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    occurred_at               DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    actor_user_id             BIGINT UNSIGNED NULL,
    actor_role_assignment_id  BIGINT UNSIGNED NULL,
    actor_ip                  VARCHAR(45) NULL,
    action                    VARCHAR(30) NOT NULL,
    entity_table              VARCHAR(40) NOT NULL,
    entity_id                 BIGINT UNSIGNED NULL,
    student_id                BIGINT UNSIGNED NULL,
    iti_id                    BIGINT UNSIGNED NULL,
    before_data               JSON NULL,
    after_data                JSON NULL,
    reason                    VARCHAR(500) NULL,
    KEY ix_audit_student (student_id, occurred_at),
    KEY ix_audit_entity (entity_table, entity_id),
    KEY ix_audit_iti (iti_id, occurred_at),
    KEY ix_audit_actor (actor_user_id, occurred_at)
) ENGINE=InnoDB;

-- Login throttling (per IP)
CREATE TABLE login_attempts (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip          VARCHAR(45) NOT NULL,
    login       VARCHAR(190) NOT NULL,
    success     TINYINT(1) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_login_ip (ip, created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
