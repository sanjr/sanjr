-- =============================================================================
-- ITI CareerTrack — database-level acceptance tests (DEMO data only)
-- Proves the schema design satisfies the mandatory tests in section 15 of the
-- V1.0 design document. Run against an EMPTY database after schema.sql:
--     psql -v ON_ERROR_STOP=1 -f schema.sql -f tests/acceptance_tests.sql
-- Every statement that is expected to be blocked is wrapped in test.fails().
-- =============================================================================
\set ON_ERROR_STOP 1
SET client_min_messages = notice;

CREATE SCHEMA test;
CREATE FUNCTION test.act_as(p_user uuid, p_ra uuid) RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    PERFORM set_config('app.user_id', coalesce(p_user::text, ''), false);
    PERFORM set_config('app.role_assignment_id', coalesce(p_ra::text, ''), false);
END $$;
CREATE FUNCTION test.ok(cond boolean, msg text) RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    IF cond IS NOT TRUE THEN RAISE EXCEPTION 'FAIL: %', msg; END IF;
    RAISE NOTICE 'PASS: %', msg;
END $$;
CREATE FUNCTION test.fails(p_sql text, msg text) RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    BEGIN
        EXECUTE p_sql;
    EXCEPTION WHEN OTHERS THEN
        RAISE NOTICE 'PASS: % (blocked: %)', msg, SQLERRM;
        RETURN;
    END;
    RAISE EXCEPTION 'FAIL: % — statement was NOT blocked', msg;
END $$;
GRANT USAGE ON SCHEMA test TO careertrack_app;
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA test TO careertrack_app;

-- -----------------------------------------------------------------------------
-- DEMO master data (loaded by the migration/owner role, like real master data)
-- -----------------------------------------------------------------------------
INSERT INTO districts (id, code, name) VALUES
 ('10000000-0000-0000-0000-000000000001', 'DEMO-D1', 'DEMO District One'),
 ('10000000-0000-0000-0000-000000000002', 'DEMO-D2', 'DEMO District Two');
INSERT INTO itis (id, iti_code, name, ownership_type, district_id, is_demo) VALUES
 ('20000000-0000-0000-0000-000000000001', 'DEMO-ITI-A', 'DEMO Government ITI A', 'GOVERNMENT', '10000000-0000-0000-0000-000000000001', true),
 ('20000000-0000-0000-0000-000000000002', 'DEMO-ITI-B', 'DEMO Private ITI B',    'PRIVATE',    '10000000-0000-0000-0000-000000000002', true);
INSERT INTO trades (id, trade_code, name, scheme, duration_months) VALUES
 ('30000000-0000-0000-0000-000000000001', 'DEMO-ELEC', 'DEMO Electrician', 'NCVT', 24);
INSERT INTO trade_modules (id, trade_id, module_code, name, sequence_no) VALUES
 ('31000000-0000-0000-0000-000000000001', '30000000-0000-0000-0000-000000000001', 'M01', 'DEMO Safety & Basics', 1);
INSERT INTO iti_trades (iti_id, trade_id) VALUES
 ('20000000-0000-0000-0000-000000000001', '30000000-0000-0000-0000-000000000001'),
 ('20000000-0000-0000-0000-000000000002', '30000000-0000-0000-0000-000000000001');
INSERT INTO academic_years (id, label, start_date, end_date, status) VALUES
 ('40000000-0000-0000-0000-000000000001', '2026-27', '2026-08-01', '2027-07-31', 'OPEN');
INSERT INTO exam_sessions (id, name, exam_body, exam_type, session_month) VALUES
 ('80000000-0000-0000-0000-000000000001', 'DEMO AITT Main Jul-2028', 'NCVT', 'MAIN',          '2028-07-01'),
 ('80000000-0000-0000-0000-000000000002', 'DEMO AITT Supp Jan-2029', 'NCVT', 'SUPPLEMENTARY', '2029-01-01'),
 ('80000000-0000-0000-0000-000000000003', 'DEMO AITT Supp Jul-2029', 'NCVT', 'SUPPLEMENTARY', '2029-07-01');

INSERT INTO users (id, full_name, email, status, is_demo) VALUES
 ('50000000-0000-0000-0000-000000000001', 'DEMO State Admin',   'state@demo.invalid',  'ACTIVE', true),
 ('50000000-0000-0000-0000-000000000002', 'DEMO Principal A',   'prin.a@demo.invalid', 'ACTIVE', true),
 ('50000000-0000-0000-0000-000000000003', 'DEMO Principal B',   'prin.b@demo.invalid', 'ACTIVE', true),
 ('50000000-0000-0000-0000-000000000004', 'DEMO Teacher A1',    't.a1@demo.invalid',   'ACTIVE', true),
 ('50000000-0000-0000-0000-000000000005', 'DEMO Teacher A2',    't.a2@demo.invalid',   'ACTIVE', true),
 ('50000000-0000-0000-0000-000000000006', 'DEMO Teacher B1',    't.b1@demo.invalid',   'ACTIVE', true),
 ('50000000-0000-0000-0000-000000000007', 'DEMO Placement B',   'po.b@demo.invalid',   'ACTIVE', true),
 ('50000000-0000-0000-0000-000000000008', 'DEMO Employer HR C3','hr.c3@demo.invalid',  'ACTIVE', true),
 ('50000000-0000-0000-0000-000000000009', 'DEMO Staff B',       'staff.b@demo.invalid','ACTIVE', true);

INSERT INTO companies (id, legal_name, is_demo) VALUES
 ('70000000-0000-0000-0000-000000000001', 'DEMO Company One Pvt Ltd',   true),
 ('70000000-0000-0000-0000-000000000002', 'DEMO Company Two Pvt Ltd',   true),
 ('70000000-0000-0000-0000-000000000003', 'DEMO Company Three Pvt Ltd', true);
INSERT INTO company_branches (id, company_id, branch_name, city, district_id) VALUES
 ('71000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000001', 'DEMO Plant 1', 'DEMO City', '10000000-0000-0000-0000-000000000001'),
 ('71000000-0000-0000-0000-000000000002', '70000000-0000-0000-0000-000000000002', 'DEMO Plant 2', 'DEMO City', '10000000-0000-0000-0000-000000000002'),
 ('71000000-0000-0000-0000-000000000003', '70000000-0000-0000-0000-000000000003', 'DEMO Plant 3', 'DEMO City', NULL);
INSERT INTO colleges (id, name) VALUES ('d0000000-0000-0000-0000-000000000001', 'DEMO Polytechnic');

INSERT INTO user_role_assignments (id, user_id, role_id, scope_type, iti_id, company_id, valid_from)
SELECT v.id::uuid, v.user_id::uuid, r.id, r.scope_type, v.iti_id::uuid, v.company_id::uuid, DATE '2026-01-01'
FROM (VALUES
 ('60000000-0000-0000-0000-000000000001','50000000-0000-0000-0000-000000000001','STATE_ADMIN',      NULL, NULL),
 ('60000000-0000-0000-0000-000000000002','50000000-0000-0000-0000-000000000002','PRINCIPAL',        '20000000-0000-0000-0000-000000000001', NULL),
 ('60000000-0000-0000-0000-000000000003','50000000-0000-0000-0000-000000000003','PRINCIPAL',        '20000000-0000-0000-0000-000000000002', NULL),
 ('60000000-0000-0000-0000-000000000004','50000000-0000-0000-0000-000000000004','TEACHER',          '20000000-0000-0000-0000-000000000001', NULL),
 ('60000000-0000-0000-0000-000000000005','50000000-0000-0000-0000-000000000005','TEACHER',          '20000000-0000-0000-0000-000000000001', NULL),
 ('60000000-0000-0000-0000-000000000006','50000000-0000-0000-0000-000000000006','TEACHER',          '20000000-0000-0000-0000-000000000002', NULL),
 ('60000000-0000-0000-0000-000000000007','50000000-0000-0000-0000-000000000007','PLACEMENT_OFFICER','20000000-0000-0000-0000-000000000002', NULL),
 ('60000000-0000-0000-0000-000000000008','50000000-0000-0000-0000-000000000008','EMPLOYER',         NULL, '70000000-0000-0000-0000-000000000003'),
 ('60000000-0000-0000-0000-000000000009','50000000-0000-0000-0000-000000000009','ITI_STAFF',        '20000000-0000-0000-0000-000000000002', NULL)
) AS v(id, user_id, role_code, iti_id, company_id)
JOIN roles r ON r.code = v.role_code;

-- From here on we act exactly like the API: as the non-owner application role, with RLS.
SET ROLE careertrack_app;

-- =============================================================================
\echo '--- T0: requests without an authenticated context see nothing'
SELECT test.act_as(NULL, NULL);
SELECT test.ok((SELECT count(*) FROM students) = 0, 'No context => zero students visible (default deny)');

-- =============================================================================
\echo '--- T1: Principal A admits a new student; PCID issued server-side'
SELECT test.act_as('50000000-0000-0000-0000-000000000002', '60000000-0000-0000-0000-000000000002');
INSERT INTO students (id, first_name, middle_name, last_name, gender, date_of_birth, ssc_board, ssc_seat_number, ssc_passing_year, is_demo)
VALUES ('90000000-0000-0000-0000-000000000001', 'Demo', 'Ramesh', 'Patil', 'MALE', '2009-05-14', 'MSBSHSE', 'D123456', 2025, true);
INSERT INTO admissions (id, student_id, iti_id, trade_id, academic_year_id, admission_date, duration_months, status)
VALUES ('a0000000-0000-0000-0000-000000000001', '90000000-0000-0000-0000-000000000001', '20000000-0000-0000-0000-000000000001',
        '30000000-0000-0000-0000-000000000001', '40000000-0000-0000-0000-000000000001', '2026-08-01', 24, 'ACTIVE');
-- (the API does student -> admission -> contacts in ONE transaction; contacts need the ITI link)
INSERT INTO student_contacts (student_id, contact_type, value, value_normalized, is_primary)
VALUES ('90000000-0000-0000-0000-000000000001', 'MOBILE', '+91 98765 43210', app.normalize_mobile('+91 98765 43210'), true);
SELECT pcid AS s1_pcid FROM v_student_identity WHERE student_id = '90000000-0000-0000-0000-000000000001' \gset
\echo 'Issued PCID:' :s1_pcid
SELECT test.ok(:'s1_pcid' = 'ITIC-MH-2026-000001-' || app.luhn_check_digit('2026000001'), 'First PCID of 2026 has serial 000001 + check digit');
SELECT test.ok(app.pcid_is_valid(:'s1_pcid'), 'PCID passes format + check-digit validation');
SELECT test.ok(NOT app.pcid_is_valid(replace(:'s1_pcid', '000001', '000007')), 'A mistyped PCID is rejected by the check digit');
SELECT test.ok((SELECT count(*) FROM institute_affiliations WHERE student_id = '90000000-0000-0000-0000-000000000001' AND status = 'ACTIVE') = 1,
               'Institute affiliation created automatically');
SELECT test.fails($$INSERT INTO pcid_registry (pcid, student_id, issue_year, serial) VALUES ('ITIC-MH-2026-999999-0', '90000000-0000-0000-0000-000000000001', 2026, 999999)$$,
                  'Application cannot write PCIDs directly');
SELECT test.fails($$INSERT INTO admissions (student_id, iti_id, trade_id, academic_year_id, admission_date, duration_months, status)
                    VALUES ('90000000-0000-0000-0000-000000000001', '20000000-0000-0000-0000-000000000001', '30000000-0000-0000-0000-000000000001',
                            '40000000-0000-0000-0000-000000000001', '2026-08-02', 24, 'ACTIVE')$$,
                  'Second live admission for the same student is blocked');

\echo '--- T2: duplicate prevention'
SELECT test.fails($$INSERT INTO students (first_name, last_name, gender, date_of_birth, ssc_board, ssc_seat_number, ssc_passing_year)
                    VALUES ('Another', 'Person', 'MALE', '2009-01-01', 'MSBSHSE', 'D123456', 2025)$$,
                  'Same SSC board + seat number + year cannot create a second student');
SELECT test.ok((SELECT max(score) FROM app.find_duplicate_candidates('Demo', 'Ramesh', 'Patil', '2009-05-14', '9876543210')) >= 70,
               'Same name + DOB + mobile scores as a STRONG duplicate (>=70)');
SELECT test.ok((SELECT max(score) FROM app.find_duplicate_candidates('Demo', 'R', 'Paatil', '2009-05-14')) >= 50,
               'Spelling variation + same DOB is still flagged as a possible duplicate');
SELECT test.ok((SELECT max(score) FROM app.find_duplicate_candidates('Sunita', 'Ramesh', 'Patil', '2009-05-14', '9876543210')) < 70,
               'Twin sibling (same DOB + family mobile, different first name) is NOT blocked');
SELECT test.ok((SELECT max(score) FROM app.find_duplicate_candidates('Demo', 'Ramesh', 'Patil', '2009-05-14', '9000000000')) >= 70,
               'Same name + DOB with a NEW mobile number is still a STRONG duplicate');
SELECT test.ok((SELECT max(score) FROM app.find_duplicate_candidates('SSC', NULL, 'Match', '2001-01-01', NULL, 'MSBSHSE', 'D123456', 2025::smallint)) = 100,
               'Same SSC seat number => certain match (100)');

-- a second student, to check serials increase
INSERT INTO students (id, first_name, last_name, gender, date_of_birth, is_demo)
VALUES ('90000000-0000-0000-0000-000000000002', 'Demo', 'Second', 'FEMALE', '2009-02-02', true);
INSERT INTO admissions (student_id, iti_id, trade_id, academic_year_id, admission_date, duration_months, status)
VALUES ('90000000-0000-0000-0000-000000000002', '20000000-0000-0000-0000-000000000001', '30000000-0000-0000-0000-000000000001',
        '40000000-0000-0000-0000-000000000001', '2026-08-01', 24, 'ACTIVE');
SELECT test.ok((SELECT pcid FROM v_student_identity WHERE student_id = '90000000-0000-0000-0000-000000000002') LIKE 'ITIC-MH-2026-000002-_',
               'Next student gets the next serial (000002)');

\echo '--- T3: training at ITI A, teacher assignment'
INSERT INTO training_records (id, student_id, admission_id, iti_id, start_date, expected_end_date)
VALUES ('b0000000-0000-0000-0000-000000000001', '90000000-0000-0000-0000-000000000001', 'a0000000-0000-0000-0000-000000000001',
        '20000000-0000-0000-0000-000000000001', '2026-08-01', '2028-07-31');
INSERT INTO teacher_assignments (student_id, teacher_user_id, iti_id, admission_id)
VALUES ('90000000-0000-0000-0000-000000000001', '50000000-0000-0000-0000-000000000004', '20000000-0000-0000-0000-000000000001', 'a0000000-0000-0000-0000-000000000001');
SELECT test.fails($$INSERT INTO teacher_assignments (student_id, teacher_user_id, iti_id, admission_id)
                    VALUES ('90000000-0000-0000-0000-000000000001', '50000000-0000-0000-0000-000000000006', '20000000-0000-0000-0000-000000000001', 'a0000000-0000-0000-0000-000000000001')$$,
                  'A teacher from ANOTHER ITI cannot be assigned');

\echo '--- T4: Principal visibility & cross-institute isolation'
SELECT test.ok((SELECT count(*) FROM students) = 2, 'Principal A sees both students of ITI A');
SELECT test.act_as('50000000-0000-0000-0000-000000000003', '60000000-0000-0000-0000-000000000003');
SELECT test.ok((SELECT count(*) FROM students) = 0, 'Principal B (unrelated ITI) sees NO students of ITI A');
SELECT test.ok((SELECT count(*) FROM admissions) = 0 AND (SELECT count(*) FROM student_contacts) = 0,
               'Principal B sees no admissions or contact details of ITI A');
-- An UPDATE on rows you cannot see silently affects 0 rows:
UPDATE students SET first_name = 'Hacked' WHERE id = '90000000-0000-0000-0000-000000000001';
RESET ROLE;
SELECT test.ok((SELECT first_name FROM students WHERE id = '90000000-0000-0000-0000-000000000001') = 'Demo',
               'Cross-institute UPDATE affected 0 rows');
SET ROLE careertrack_app;

\echo '--- T5: Teacher visibility (assigned vs unassigned)'
SELECT test.act_as('50000000-0000-0000-0000-000000000004', '60000000-0000-0000-0000-000000000004');
SELECT test.ok((SELECT count(*) FROM students) = 1, 'Assigned Teacher A1 sees exactly their 1 assigned student');
SELECT test.ok((SELECT count(*) FROM admissions) = 1, 'Assigned Teacher sees the student lifecycle (admission)');
SELECT test.ok((SELECT count(*) FROM student_contacts) = 0, 'Teacher has no contact-details permission => phone hidden');
INSERT INTO attendance_records (student_id, training_record_id, iti_id, attendance_date, status)
VALUES ('90000000-0000-0000-0000-000000000001', 'b0000000-0000-0000-0000-000000000001', '20000000-0000-0000-0000-000000000001', '2026-10-01', 'PRESENT');
INSERT INTO assessments (student_id, training_record_id, iti_id, assessment_type, assessment_date, max_marks, marks_obtained)
VALUES ('90000000-0000-0000-0000-000000000001', 'b0000000-0000-0000-0000-000000000001', '20000000-0000-0000-0000-000000000001', 'INTERNAL_PRACTICAL', '2026-10-15', 100, 78);
SELECT test.ok(true, 'Assigned Teacher recorded attendance and an assessment');
SELECT test.fails($$INSERT INTO employments (student_id, company_id, designation, employment_type, joining_date, source)
                    VALUES ('90000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000001', 'X', 'FULL_TIME', '2028-08-01', 'STAFF')$$,
                  'Teacher cannot write career/employment records');
SELECT test.act_as('50000000-0000-0000-0000-000000000005', '60000000-0000-0000-0000-000000000005');
SELECT test.ok((SELECT count(*) FROM students) = 0, 'Unassigned Teacher A2 (same ITI) sees no students');
SELECT test.fails($$INSERT INTO attendance_records (student_id, training_record_id, iti_id, attendance_date, status)
                    VALUES ('90000000-0000-0000-0000-000000000001', 'b0000000-0000-0000-0000-000000000001', '20000000-0000-0000-0000-000000000001', '2026-10-02', 'PRESENT')$$,
                  'Unassigned Teacher cannot mark attendance');

\echo '--- T6: Privilege escalation is blocked'
SELECT test.act_as('50000000-0000-0000-0000-000000000002', '60000000-0000-0000-0000-000000000002');
SELECT test.fails($$INSERT INTO user_role_assignments (user_id, role_id, scope_type)
                    SELECT '50000000-0000-0000-0000-000000000004', id, 'STATE' FROM roles WHERE code = 'STATE_ADMIN'$$,
                  'Principal cannot grant the State Admin role');
SELECT test.fails($$INSERT INTO user_role_assignments (user_id, role_id, scope_type, iti_id)
                    SELECT '50000000-0000-0000-0000-000000000005', id, 'ITI', '20000000-0000-0000-0000-000000000002' FROM roles WHERE code = 'TEACHER'$$,
                  'Principal A cannot appoint teachers at ITI B');

\echo '--- T7: Transfer A -> B without changing PCID'
INSERT INTO transfer_requests (id, student_id, from_iti_id, from_admission_id, to_iti_id, to_trade_id, to_academic_year_id,
                               requested_effective_date, reason)
VALUES ('c0000000-0000-0000-0000-000000000001', '90000000-0000-0000-0000-000000000001', '20000000-0000-0000-0000-000000000001',
        'a0000000-0000-0000-0000-000000000001', '20000000-0000-0000-0000-000000000002', '30000000-0000-0000-0000-000000000001',
        '40000000-0000-0000-0000-000000000001', current_date, 'DEMO: family relocated to District Two');
SELECT test.fails($$SELECT app.accept_transfer('c0000000-0000-0000-0000-000000000001')$$, 'Origin Principal cannot approve their own outbound transfer');

SELECT test.act_as('50000000-0000-0000-0000-000000000003', '60000000-0000-0000-0000-000000000003');
SELECT test.ok((SELECT count(*) FROM students WHERE id = '90000000-0000-0000-0000-000000000001') = 1,
               'Destination Principal sees the identity summary while reviewing the request');
SELECT test.ok((SELECT count(*) FROM student_contacts) = 0 AND (SELECT count(*) FROM admissions) = 0,
               '...but NOT contacts or academic records before accepting');
SELECT app.accept_transfer('c0000000-0000-0000-0000-000000000001', NULL, 'DEMO accepted') AS new_admission_id \gset
SELECT test.ok((SELECT pcid FROM v_student_identity WHERE student_id = '90000000-0000-0000-0000-000000000001') = :'s1_pcid',
               'PCID unchanged after transfer');
SELECT test.ok((SELECT count(*) FROM admissions WHERE student_id = '90000000-0000-0000-0000-000000000001') = 2,
               'Destination Principal now sees full history: both admissions');
SELECT test.ok((SELECT count(*) FROM attendance_records) = 1 AND (SELECT count(*) FROM assessments) = 1,
               'Previous attendance and assessments at ITI A are preserved and visible to new ITI');
SELECT test.ok((SELECT count(*) FROM student_contacts) = 1, 'Destination Principal now sees contact details');

SELECT test.act_as('50000000-0000-0000-0000-000000000002', '60000000-0000-0000-0000-000000000002');
SELECT test.ok((SELECT count(*) FROM admissions WHERE student_id = '90000000-0000-0000-0000-000000000001') = 1,
               'Origin Principal keeps ITS OWN admission history only (not the new ITI B admission)');
SELECT test.ok((SELECT count(*) FROM student_contacts WHERE student_id = '90000000-0000-0000-0000-000000000001') = 0,
               'Origin Principal no longer sees the current contact details');
SELECT test.act_as('50000000-0000-0000-0000-000000000004', '60000000-0000-0000-0000-000000000004');
SELECT test.ok((SELECT count(*) FROM admissions) = 0 AND (SELECT count(*) FROM students) = 1,
               'Previous teacher keeps only a summary (name/PCID), no lifecycle access');
RESET ROLE;
SELECT test.ok((SELECT status FROM admissions WHERE id = 'a0000000-0000-0000-0000-000000000001') = 'TRANSFERRED_OUT'
           AND (SELECT completion_status FROM training_records WHERE id = 'b0000000-0000-0000-0000-000000000001') = 'TRANSFERRED_OUT'
           AND (SELECT count(*) FROM teacher_assignments WHERE end_date IS NULL AND student_id = '90000000-0000-0000-0000-000000000001') = 0
           AND (SELECT count(*) FROM institute_affiliations WHERE student_id = '90000000-0000-0000-0000-000000000001') = 2,
               'Origin episode closed with dates; old teacher assignment ended; both affiliations retained');
SET ROLE careertrack_app;

\echo '--- T8: exam attempts are separate rows'
SELECT test.act_as('50000000-0000-0000-0000-000000000003', '60000000-0000-0000-0000-000000000003');
INSERT INTO exam_results (student_id, admission_id, iti_id, exam_session_id, attempt_no, result, result_declared_on) VALUES
 ('90000000-0000-0000-0000-000000000001', :'new_admission_id', '20000000-0000-0000-0000-000000000002', '80000000-0000-0000-0000-000000000001', 1, 'FAIL',          '2028-08-20'),
 ('90000000-0000-0000-0000-000000000001', :'new_admission_id', '20000000-0000-0000-0000-000000000002', '80000000-0000-0000-0000-000000000002', 2, 'SUPPLEMENTARY', '2029-02-20'),
 ('90000000-0000-0000-0000-000000000001', :'new_admission_id', '20000000-0000-0000-0000-000000000002', '80000000-0000-0000-0000-000000000003', 3, 'PASS',          '2029-08-20');
SELECT test.ok((SELECT count(*) FROM exam_results WHERE student_id = '90000000-0000-0000-0000-000000000001') = 3,
               'Fail, Supplementary and Pass stored as three separate attempts');
UPDATE admissions SET status = 'COMPLETED', end_date = current_date + 1 WHERE id = :'new_admission_id';
SELECT test.ok((SELECT count(*) FROM students WHERE id = '90000000-0000-0000-0000-000000000001') = 1,
               'After completion the student remains visible to ITI B as an alumnus');

\echo '--- T9: multiple career pathways, never overwritten'
SELECT test.act_as('50000000-0000-0000-0000-000000000007', '60000000-0000-0000-0000-000000000007');   -- Placement Officer B
INSERT INTO apprenticeships (student_id, company_id, branch_id, start_date, actual_end_date, status, stipend_amount, stipend_period, source, recorded_by_iti_id) VALUES
 ('90000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000001', '71000000-0000-0000-0000-000000000001', current_date + 10, current_date + 200, 'COMPLETED', 9000, 'MONTHLY', 'STAFF', '20000000-0000-0000-0000-000000000002'),
 ('90000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000002', '71000000-0000-0000-0000-000000000002', current_date + 210, NULL, 'ACTIVE', 10000, 'MONTHLY', 'STAFF', '20000000-0000-0000-0000-000000000002');
INSERT INTO employments (id, student_id, company_id, branch_id, designation, employment_type, work_location, joining_date, leaving_date, status, salary_amount, salary_period, source, recorded_by_iti_id) VALUES
 ('e0000000-0000-0000-0000-000000000001', '90000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000001', '71000000-0000-0000-0000-000000000001', 'DEMO Electrician Trainee', 'FULL_TIME', 'DEMO City', current_date + 400, current_date + 700, 'ENDED', 15000, 'MONTHLY', 'STAFF', '20000000-0000-0000-0000-000000000002'),
 ('e0000000-0000-0000-0000-000000000002', '90000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000002', '71000000-0000-0000-0000-000000000002', 'DEMO Electrician',         'FULL_TIME', 'DEMO City', current_date + 701, current_date + 1000, 'ENDED', 19000, 'MONTHLY', 'STAFF', '20000000-0000-0000-0000-000000000002'),
 ('e0000000-0000-0000-0000-000000000003', '90000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000003', '71000000-0000-0000-0000-000000000003', 'DEMO Senior Electrician',  'FULL_TIME', 'Outside MH', current_date + 1001, NULL, 'ACTIVE', 300000, 'ANNUAL', 'STAFF', '20000000-0000-0000-0000-000000000002');
SELECT test.ok((SELECT count(*) FROM apprenticeships) = 2 AND (SELECT count(*) FROM employments) = 3,
               'Two apprenticeships and three jobs across three companies are all retained');
SELECT test.fails($$UPDATE employments SET company_id = '70000000-0000-0000-0000-000000000002' WHERE id = 'e0000000-0000-0000-0000-000000000001'$$,
                  'An old job row cannot be re-pointed to a different employer');
SELECT test.fails($$INSERT INTO employments (student_id, company_id, branch_id, designation, employment_type, joining_date, source)
                    VALUES ('90000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000001', '71000000-0000-0000-0000-000000000003', 'X', 'FULL_TIME', current_date, 'STAFF')$$,
                  'A branch that belongs to another company is rejected');
INSERT INTO business_records (student_id, business_name, business_type, start_date, source) VALUES
 ('90000000-0000-0000-0000-000000000001', 'DEMO Electricals Repair', 'Repair shop', current_date + 1100, 'STAFF');
INSERT INTO higher_education_records (student_id, college_id, course_name, course_level, study_mode, admission_year, source) VALUES
 ('90000000-0000-0000-0000-000000000001', 'd0000000-0000-0000-0000-000000000001', 'DEMO Diploma Electrical (part-time)', 'DIPLOMA', 'PART_TIME', 2030, 'STAFF');
SELECT test.ok(true, 'Business and higher-education records added for the same student');

\echo '--- T10: Not Updated is never Unemployed'
INSERT INTO career_status_history (student_id, status, effective_from, source, employment_id)
VALUES ('90000000-0000-0000-0000-000000000001', 'EMPLOYED', current_date + 1001, 'STAFF', 'e0000000-0000-0000-0000-000000000003');
INSERT INTO career_status_history (student_id, status, effective_from, source)
VALUES ('90000000-0000-0000-0000-000000000001', 'NOT_UPDATED', current_date + 1500, 'SYSTEM');
SELECT test.ok((SELECT count(*) FROM career_status_history WHERE effective_to IS NULL) = 1,
               'Previous status closed automatically; exactly one current status');
SELECT test.ok((SELECT status FROM v_student_current_status WHERE student_id = '90000000-0000-0000-0000-000000000001') = 'NOT_UPDATED'
           AND (SELECT count(*) FROM v_student_current_status WHERE status = 'SEEKING_EMPLOYMENT') = 0,
               'Current status is NOT_UPDATED and is not counted as unemployed');

\echo '--- T11: follow-up tasks'
SELECT test.act_as('50000000-0000-0000-0000-000000000003', '60000000-0000-0000-0000-000000000003');
INSERT INTO follow_ups (student_id, iti_id, assigned_to_user_id, reason, due_date, channel)
VALUES ('90000000-0000-0000-0000-000000000001', '20000000-0000-0000-0000-000000000002', '50000000-0000-0000-0000-000000000009', 'NO_RECENT_UPDATE', current_date + 7, 'PHONE');
SELECT test.act_as('50000000-0000-0000-0000-000000000009', '60000000-0000-0000-0000-000000000009');
SELECT test.ok((SELECT count(*) FROM follow_ups) = 1 AND (SELECT count(*) FROM student_contacts) = 1 AND (SELECT count(*) FROM employments) = 0,
               'ITI Staff sees the assigned follow-up + phone number, but NOT salary/employment details');

\echo '--- T12: Employer confirmation (verifier, time, evidence)'
SELECT test.act_as('50000000-0000-0000-0000-000000000008', '60000000-0000-0000-0000-000000000008');
SELECT test.ok((SELECT count(*) FROM employments) = 1 AND (SELECT count(*) FROM business_records) = 0 AND (SELECT count(*) FROM student_contacts) = 0,
               'Employer C3 sees only its own engagement — not other jobs, business or contacts');
INSERT INTO verification_records (student_id, employment_id, verifier_company_id, method, decision, evidence_reference)
VALUES ('90000000-0000-0000-0000-000000000001', 'e0000000-0000-0000-0000-000000000003', '70000000-0000-0000-0000-000000000003',
        'EMPLOYER_CONFIRMATION', 'VERIFIED', 'DEMO HR letter ref HR/2029/001');
SELECT test.ok((SELECT verification_status FROM employments WHERE id = 'e0000000-0000-0000-0000-000000000003') = 'VERIFIED',
               'Employer confirmation marks the job VERIFIED');
SELECT test.fails($$INSERT INTO verification_records (student_id, employment_id, verifier_company_id, method, decision, evidence_reference)
                    VALUES ('90000000-0000-0000-0000-000000000001', 'e0000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000003',
                            'EMPLOYER_CONFIRMATION', 'VERIFIED', 'fake')$$,
                  'Employer cannot verify a job at another company');
RESET ROLE;
SELECT test.ok((SELECT verifier_user_id = '50000000-0000-0000-0000-000000000008' AND decided_at IS NOT NULL AND evidence_reference IS NOT NULL
                FROM verification_records LIMIT 1), 'Verification stores verifier, timestamp and evidence reference');
SET ROLE careertrack_app;

\echo '--- T13: reports separate unique students from pathway events'
SELECT test.act_as('50000000-0000-0000-0000-000000000001', '60000000-0000-0000-0000-000000000001');   -- State Admin
SELECT test.ok((SELECT count(DISTINCT student_id) FROM employments) = 1 AND (SELECT count(*) FROM employments) = 3,
               'State view: 1 unique PCID vs 3 employment events');

\echo '--- T14: audit, no hard deletes'
SELECT test.ok((SELECT count(*) FROM audit_logs WHERE student_id = '90000000-0000-0000-0000-000000000001') >= 20,
               'Sensitive changes are in the audit log');
SELECT test.ok((SELECT count(*) FROM audit_logs WHERE entity_table = 'employments' AND actor_user_id = '50000000-0000-0000-0000-000000000007') = 3,
               'Audit log records WHO created each employment');
SELECT test.fails($$DELETE FROM employments WHERE id = 'e0000000-0000-0000-0000-000000000001'$$, 'Application cannot hard-delete lifecycle records');
SELECT test.fails($$UPDATE audit_logs SET action = 'X'$$, 'Audit log cannot be edited');
RESET ROLE;
SELECT test.fails($$DELETE FROM audit_logs$$, 'Audit log cannot be deleted, even by the owner role');

\echo '=== ALL ACCEPTANCE TESTS PASSED ==='
