-- =============================================================================
-- ITI CareerTrack — migration 09: one "User ID" for every login
--   Principal            -> NCVT MIS code (already)
--   Instructor, ITI Staff -> Sevaarth ID
--   Student              -> PCID
-- Only DEMO accounts (e-mail @demo.local) get generated IDs here; real Instructor / Staff
-- accounts are created by the State Admin with their real Sevaarth ID.
-- =============================================================================
UPDATE users u
  JOIN user_role_assignments ura ON ura.user_id = u.id AND ura.status = 'ACTIVE'
  JOIN roles r ON r.id = ura.role_id AND r.code IN ('TEACHER', 'ITI_STAFF')
   SET u.username = CONCAT('DEMOSEV', LPAD(u.id, 5, '0'))
 WHERE u.username IS NULL AND u.email LIKE '%@demo.local';

UPDATE users u
  JOIN pcid_registry p ON p.student_id = u.student_id AND p.status = 'ACTIVE'
   SET u.username = p.pcid
 WHERE u.username IS NULL AND u.student_id IS NOT NULL AND u.email LIKE '%@demo.local';

UPDATE users u
  JOIN user_role_assignments ura ON ura.user_id = u.id AND ura.status = 'ACTIVE'
  JOIN roles r ON r.id = ura.role_id AND r.code = 'PLACEMENT_OFFICER'
  JOIN itis i ON i.id = ura.iti_id
   SET u.username = CONCAT('PO-', i.iti_code)
 WHERE u.username IS NULL AND u.email LIKE '%@demo.local';

UPDATE users u
  JOIN user_role_assignments ura ON ura.user_id = u.id AND ura.status = 'ACTIVE'
  JOIN roles r ON r.id = ura.role_id AND r.code = 'EMPLOYER'
   SET u.username = CONCAT('EMP-', LPAD(ura.company_id, 4, '0'))
 WHERE u.username IS NULL AND u.email LIKE '%@demo.local';
