-- =============================================================================
-- ITI CareerTrack — MySQL stored functions, procedures and triggers
-- Rules that must hold no matter which program writes to the database
-- (PHP website, Python jobs, manual SQL).
-- =============================================================================

DELIMITER $$

-- -----------------------------------------------------------------------------
-- PCID helpers
-- -----------------------------------------------------------------------------
-- Luhn check digit (same algorithm as bank cards) — catches typing mistakes
CREATE FUNCTION fn_luhn_check_digit(p_digits VARCHAR(20)) RETURNS CHAR(1)
DETERMINISTIC NO SQL
BEGIN
    DECLARE v_sum INT DEFAULT 0;
    DECLARE v_i INT;
    DECLARE v_d INT;
    DECLARE v_double BOOLEAN DEFAULT TRUE;
    SET v_i = CHAR_LENGTH(p_digits);
    WHILE v_i > 0 DO
        SET v_d = CAST(SUBSTRING(p_digits, v_i, 1) AS UNSIGNED);
        IF v_double THEN
            SET v_d = v_d * 2;
            IF v_d > 9 THEN SET v_d = v_d - 9; END IF;
        END IF;
        SET v_sum = v_sum + v_d;
        SET v_double = NOT v_double;
        SET v_i = v_i - 1;
    END WHILE;
    RETURN CAST((10 - (v_sum MOD 10)) MOD 10 AS CHAR);
END$$

-- Issues the permanent PCID. Idempotent: a student who already has one keeps it.
-- Collision-free: the per-year counter row is locked until the transaction ends,
-- and a rollback also rolls back the counter (no gaps).
CREATE PROCEDURE sp_issue_pcid(IN p_student_id BIGINT UNSIGNED, IN p_admission_id BIGINT UNSIGNED,
                               IN p_academic_year_id BIGINT UNSIGNED, IN p_actor BIGINT UNSIGNED)
BEGIN
    DECLARE v_existing VARCHAR(32);
    DECLARE v_year SMALLINT;
    DECLARE v_serial INT;
    DECLARE v_prefix VARCHAR(10);
    DECLARE v_state VARCHAR(4);
    DECLARE v_status VARCHAR(10);

    SET v_status = (SELECT profile_status FROM students WHERE id = p_student_id);
    IF v_status IS NULL OR v_status <> 'ACTIVE' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PCID: student not found or not active';
    END IF;

    SET v_existing = (SELECT pcid FROM pcid_registry WHERE student_id = p_student_id AND status = 'ACTIVE' LIMIT 1);
    IF v_existing IS NULL THEN
        SET v_year = (SELECT YEAR(start_date) FROM academic_years WHERE id = p_academic_year_id);
        IF v_year IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PCID: academic year not found';
        END IF;
        SET v_prefix = COALESCE((SELECT setting_value FROM settings WHERE setting_key = 'pcid_prefix'), 'ITIC');
        SET v_state  = COALESCE((SELECT setting_value FROM settings WHERE setting_key = 'pcid_state'), 'MH');

        INSERT INTO pcid_year_counters (issue_year, last_serial) VALUES (v_year, 1)
            ON DUPLICATE KEY UPDATE last_serial = last_serial + 1;
        SET v_serial = (SELECT last_serial FROM pcid_year_counters WHERE issue_year = v_year);

        INSERT INTO pcid_registry (pcid, student_id, issue_year, serial, issued_for_admission_id, issued_by)
        VALUES (CONCAT(v_prefix, '-', v_state, '-', v_year, '-', LPAD(v_serial, 6, '0'), '-',
                       fn_luhn_check_digit(CONCAT(v_year, LPAD(v_serial, 6, '0')))),
                p_student_id, v_year, v_serial, p_admission_id, p_actor);
    END IF;
END$$

-- -----------------------------------------------------------------------------
-- Admission lifecycle: PCID + institute affiliation are automatic
-- -----------------------------------------------------------------------------
CREATE TRIGGER trg_admissions_ai AFTER INSERT ON admissions FOR EACH ROW
BEGIN
    IF NEW.status IN ('ACCEPTED','ACTIVE') THEN
        CALL sp_issue_pcid(NEW.student_id, NEW.id, NEW.academic_year_id, NEW.created_by);
        INSERT INTO institute_affiliations (student_id, iti_id, admission_id, start_date, status)
        VALUES (NEW.student_id, NEW.iti_id, NEW.id, NEW.admission_date, 'ACTIVE');
    END IF;
END$$

CREATE TRIGGER trg_admissions_bu BEFORE UPDATE ON admissions FOR EACH ROW
BEGIN
    IF NEW.student_id <> OLD.student_id OR NEW.iti_id <> OLD.iti_id OR NEW.academic_year_id <> OLD.academic_year_id
       OR NEW.admission_type <> OLD.admission_type OR NOT (NEW.previous_admission_id <=> OLD.previous_admission_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Admission key fields cannot be changed; create a new admission instead';
    END IF;
    SET NEW.version = OLD.version + 1;
END$$

CREATE TRIGGER trg_admissions_au AFTER UPDATE ON admissions FOR EACH ROW
BEGIN
    IF OLD.status IN ('ACCEPTED','ACTIVE') AND NEW.status NOT IN ('ACCEPTED','ACTIVE') THEN
        UPDATE institute_affiliations
           SET status = CASE NEW.status WHEN 'COMPLETED' THEN 'COMPLETED'
                                        WHEN 'TRANSFERRED_OUT' THEN 'TRANSFERRED_OUT'
                                        ELSE 'DISCONTINUED' END,
               end_date = NEW.end_date
         WHERE admission_id = NEW.id AND status = 'ACTIVE';
    ELSEIF OLD.status NOT IN ('ACCEPTED','ACTIVE') AND NEW.status IN ('ACCEPTED','ACTIVE') THEN
        CALL sp_issue_pcid(NEW.student_id, NEW.id, NEW.academic_year_id, NEW.updated_by);
        INSERT IGNORE INTO institute_affiliations (student_id, iti_id, admission_id, start_date, status)
        VALUES (NEW.student_id, NEW.iti_id, NEW.id, NEW.admission_date, 'ACTIVE');
    END IF;
END$$

CREATE TRIGGER trg_students_bu BEFORE UPDATE ON students FOR EACH ROW
BEGIN
    SET NEW.version = OLD.version + 1;
END$$

-- -----------------------------------------------------------------------------
-- Immutable columns (history is never re-written)
-- -----------------------------------------------------------------------------
CREATE TRIGGER trg_pcid_bu BEFORE UPDATE ON pcid_registry FOR EACH ROW
BEGIN
    IF NEW.pcid <> OLD.pcid OR NEW.student_id <> OLD.student_id OR NEW.issue_year <> OLD.issue_year
       OR NEW.serial <> OLD.serial OR NEW.issued_at <> OLD.issued_at THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PCID can never be changed';
    END IF;
END$$

CREATE TRIGGER trg_employments_bu BEFORE UPDATE ON employments FOR EACH ROW
BEGIN
    IF NEW.student_id <> OLD.student_id OR NEW.company_id <> OLD.company_id OR NEW.joining_date <> OLD.joining_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A job record cannot be moved to another employer/student; add a new employment record';
    END IF;
END$$

CREATE TRIGGER trg_apprenticeships_bu BEFORE UPDATE ON apprenticeships FOR EACH ROW
BEGIN
    IF NEW.student_id <> OLD.student_id OR NEW.company_id <> OLD.company_id OR NEW.start_date <> OLD.start_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An apprenticeship cannot be moved to another company/student; add a new record';
    END IF;
END$$

CREATE TRIGGER trg_business_bu BEFORE UPDATE ON business_records FOR EACH ROW
BEGIN
    IF NEW.student_id <> OLD.student_id OR NEW.start_date <> OLD.start_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Business record key fields cannot be changed';
    END IF;
END$$

CREATE TRIGGER trg_he_bu BEFORE UPDATE ON higher_education_records FOR EACH ROW
BEGIN
    IF NEW.student_id <> OLD.student_id OR NEW.college_id <> OLD.college_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Higher education key fields cannot be changed';
    END IF;
END$$

CREATE TRIGGER trg_exam_results_bu BEFORE UPDATE ON exam_results FOR EACH ROW
BEGIN
    IF NEW.student_id <> OLD.student_id OR NEW.admission_id <> OLD.admission_id
       OR NEW.exam_session_id <> OLD.exam_session_id OR NEW.attempt_no <> OLD.attempt_no THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Exam attempt key fields cannot be changed; record a new attempt';
    END IF;
END$$

CREATE TRIGGER trg_ta_bu BEFORE UPDATE ON teacher_assignments FOR EACH ROW
BEGIN
    IF NEW.student_id <> OLD.student_id OR NEW.teacher_user_id <> OLD.teacher_user_id
       OR NEW.iti_id <> OLD.iti_id OR NEW.start_date <> OLD.start_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Teacher assignment history cannot be rewritten; end it and create a new one';
    END IF;
END$$

-- A teacher can only be assigned at an ITI where they hold an ACTIVE teacher role
CREATE TRIGGER trg_ta_bi BEFORE INSERT ON teacher_assignments FOR EACH ROW
BEGIN
    IF NOT EXISTS (SELECT 1 FROM user_role_assignments ura JOIN roles r ON r.id = ura.role_id
                   WHERE ura.user_id = NEW.teacher_user_id AND r.code = 'TEACHER' AND ura.iti_id = NEW.iti_id
                     AND ura.status = 'ACTIVE' AND CURDATE() >= ura.valid_from
                     AND (ura.valid_to IS NULL OR CURDATE() <= ura.valid_to)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User is not an active teacher at this ITI';
    END IF;
END$$

-- -----------------------------------------------------------------------------
-- Append-only tables
-- -----------------------------------------------------------------------------
CREATE TRIGGER trg_audit_bu BEFORE UPDATE ON audit_logs FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit log is append-only';
END$$
CREATE TRIGGER trg_audit_bd BEFORE DELETE ON audit_logs FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit log is append-only';
END$$
CREATE TRIGGER trg_vr_bu BEFORE UPDATE ON verification_records FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Verification records are append-only';
END$$
CREATE TRIGGER trg_vr_bd BEFORE DELETE ON verification_records FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Verification records are append-only';
END$$

-- A verification decision updates the target record's verification_status
CREATE TRIGGER trg_vr_ai AFTER INSERT ON verification_records FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(10);
    SET v_status = IF(NEW.decision = 'NEEDS_INFO', 'PENDING', NEW.decision);
    IF NEW.exam_result_id IS NOT NULL THEN UPDATE exam_results SET verification_status = v_status WHERE id = NEW.exam_result_id; END IF;
    IF NEW.apprenticeship_id IS NOT NULL THEN UPDATE apprenticeships SET verification_status = v_status WHERE id = NEW.apprenticeship_id; END IF;
    IF NEW.employment_id IS NOT NULL THEN UPDATE employments SET verification_status = v_status WHERE id = NEW.employment_id; END IF;
    IF NEW.business_record_id IS NOT NULL THEN UPDATE business_records SET verification_status = v_status WHERE id = NEW.business_record_id; END IF;
    IF NEW.higher_education_record_id IS NOT NULL THEN UPDATE higher_education_records SET verification_status = v_status WHERE id = NEW.higher_education_record_id; END IF;
    IF NEW.student_document_id IS NOT NULL THEN UPDATE student_documents SET verification_status = v_status WHERE id = NEW.student_document_id; END IF;
END$$

DELIMITER ;

-- -----------------------------------------------------------------------------
-- Views
-- -----------------------------------------------------------------------------
CREATE VIEW v_student_identity AS
SELECT s.id AS student_id, p.pcid, s.first_name, s.middle_name, s.last_name,
       CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name) AS full_name,
       s.gender, s.date_of_birth, s.profile_status, s.is_demo
FROM students s
LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE';

CREATE VIEW v_student_current_status AS
SELECT csh.student_id, csh.status, csh.effective_from, csh.verification_status, csh.source,
       DATEDIFF(CURDATE(), csh.effective_from) AS days_since_update
FROM career_status_history csh
WHERE csh.effective_to IS NULL;
