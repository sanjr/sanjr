-- =============================================================================
-- ITI CareerTrack — migration 10: roles as decided by DVET (Nov 2026)
--  * Placement Officer and ITI Staff logins are no longer used (their work moves to the
--    Principal / Instructor); their role assignments are revoked, history is kept.
--  * State Admin: whole state, add / edit / delete.   RO Admin: own region, add / edit / delete,
--    creates Apprentice Advisors for ITIs of the region.   DVTO: own district, view.
--  * Principal: own ITI, add / edit / delete, creates Instructors and assigns trades.
--  * Instructor: final ITI result and what each passed student does next.
-- =============================================================================
INSERT IGNORE INTO permissions (code, description) VALUES
 ('record.delete', 'Delete (void) student records — kept in the audit log'),
 ('outcome.record', 'Record the final ITI result and the student''s next step');

-- Follow-up tasks of Placement Officers / ITI Staff go to the Principal of the same ITI
UPDATE follow_ups fu
  JOIN user_role_assignments ura ON ura.user_id = fu.assigned_to_user_id AND ura.status = 'ACTIVE'
  JOIN roles r ON r.id = ura.role_id AND r.code IN ('PLACEMENT_OFFICER', 'ITI_STAFF')
  JOIN user_role_assignments pa ON pa.iti_id = fu.iti_id AND pa.status = 'ACTIVE'
  JOIN roles pr ON pr.id = pa.role_id AND pr.code = 'PRINCIPAL'
   SET fu.assigned_to_user_id = pa.user_id
 WHERE fu.status IN ('PENDING', 'IN_PROGRESS');

UPDATE user_role_assignments ura JOIN roles r ON r.id = ura.role_id AND r.code IN ('PLACEMENT_OFFICER', 'ITI_STAFF')
   SET ura.status = 'REVOKED', ura.valid_to = GREATEST(ura.valid_from, CURDATE())
 WHERE ura.status = 'ACTIVE';

-- New rights per role
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON
   (r.code = 'STATE_ADMIN' AND p.code IN ('record.delete', 'outcome.record', 'career.record', 'exam_result.record', 'student.update', 'admission.manage'))
OR (r.code = 'RO_ADMIN'    AND p.code IN ('record.delete', 'outcome.record', 'career.record', 'exam_result.record', 'student.update', 'user.manage',
                                          'company.manage', 'transfer.approve'))
OR (r.code = 'PRINCIPAL'   AND p.code IN ('record.delete', 'outcome.record', 'vacancy.manage'))
OR (r.code = 'TEACHER'     AND p.code IN ('outcome.record', 'career.record', 'exam_result.record', 'career.salary.view'));
