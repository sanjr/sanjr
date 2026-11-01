-- =============================================================================
-- ITI CareerTrack — migration 07: indexes for state-wide volume
-- (1.3 lakh admissions per year; lakhs of students and crores of attendance rows)
-- Safe to run once on an existing database.
-- =============================================================================
CREATE INDEX ix_er_iti ON exam_results (iti_id, exam_session_id);
CREATE INDEX ix_emp_verify ON employments (verification_status, voided_at, student_id);
CREATE INDEX ix_app_verify ON apprenticeships (verification_status, voided_at, student_id);
CREATE INDEX ix_aff_student_status ON institute_affiliations (student_id, status, iti_id);
CREATE INDEX ix_adm_student_id ON admissions (student_id, id);
CREATE INDEX ix_aff_iti_status_end ON institute_affiliations (iti_id, status, end_date, student_id);
