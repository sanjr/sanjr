-- 12: automatic data linking. Official records (NCVT results, NAPS apprenticeship contracts,
-- EPFO employment, Udyam registrations, higher-education admissions) are imported and joined to
-- the student by an ID, so the career is recorded even when the student never updates it.

-- IDs of the student in other government systems (one value belongs to one student)
CREATE TABLE IF NOT EXISTS student_external_ids (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id  BIGINT UNSIGNED NOT NULL,
    id_type     VARCHAR(12) NOT NULL,          -- NCVT_REG, APAAR, UAN, NAPS, UDYAM, ESHRAM
    id_value    VARCHAR(40) NOT NULL,
    source      VARCHAR(10) NOT NULL DEFAULT 'STAFF',   -- STAFF, STUDENT, IMPORT
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_extid_value (id_type, id_value),
    UNIQUE KEY uq_extid_student (student_id, id_type, id_value),
    KEY ix_extid_student (student_id),
    CONSTRAINT fk_extid_student FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;

-- one uploaded / fetched file
CREATE TABLE IF NOT EXISTS data_imports (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source       VARCHAR(12) NOT NULL,          -- NCVT, NAPS, EPFO, UDYAM, HIGHER_EDU, ID_LINK
    channel      VARCHAR(8) NOT NULL DEFAULT 'UPLOAD',   -- UPLOAD, INBOX (folder), API
    file_name    VARCHAR(200) NULL,
    rows_total   INT NOT NULL DEFAULT 0,
    matched      INT NOT NULL DEFAULT 0,
    created      INT NOT NULL DEFAULT 0,        -- new records added
    updated      INT NOT NULL DEFAULT 0,        -- existing records updated (e.g. apprenticeship completed)
    unchanged    INT NOT NULL DEFAULT 0,        -- already known
    unmatched    INT NOT NULL DEFAULT 0,
    errors       INT NOT NULL DEFAULT 0,
    run_by       BIGINT UNSIGNED NULL,
    started_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at  DATETIME NULL,
    KEY ix_di_source (source, started_at)
) ENGINE=InnoDB;

-- rows that matched no student (kept, without raw Aadhaar, and tried again automatically later)
CREATE TABLE IF NOT EXISTS data_import_rows (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id      BIGINT UNSIGNED NOT NULL,
    source         VARCHAR(12) NOT NULL,
    row_no         INT NOT NULL,
    status         VARCHAR(10) NOT NULL DEFAULT 'UNMATCHED',   -- UNMATCHED, AMBIGUOUS, ERROR, MATCHED
    reason         VARCHAR(255) NULL,
    person         VARCHAR(150) NULL,
    payload        TEXT NOT NULL,                -- the row as JSON (Aadhaar replaced by its hash)
    student_id     BIGINT UNSIGNED NULL,
    tries          INT NOT NULL DEFAULT 1,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at    DATETIME NULL,
    KEY ix_dir_open (status, source),
    KEY ix_dir_import (import_id),
    CONSTRAINT fk_dir_import FOREIGN KEY (import_id) REFERENCES data_imports(id)
) ENGINE=InnoDB;

-- which import created a record (shown as "Official record — EPFO" on the profile)
ALTER TABLE exam_results ADD COLUMN data_import_id BIGINT UNSIGNED NULL;
ALTER TABLE apprenticeships ADD COLUMN data_import_id BIGINT UNSIGNED NULL;
ALTER TABLE employments ADD COLUMN data_import_id BIGINT UNSIGNED NULL;
ALTER TABLE business_records ADD COLUMN data_import_id BIGINT UNSIGNED NULL;
ALTER TABLE higher_education_records ADD COLUMN data_import_id BIGINT UNSIGNED NULL;
