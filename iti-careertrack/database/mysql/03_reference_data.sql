-- =============================================================================
-- ITI CareerTrack — reference data (settings, roles, permissions)
-- =============================================================================
INSERT INTO settings (setting_key, setting_value, description) VALUES
 ('pcid_prefix', 'ITIC', 'PCID prefix. Change ONLY before the first PCID is issued.'),
 ('pcid_state',  'MH',   'State code inside the PCID'),
 ('stale_update_months', '6', 'Alumni with no career update for this many months get a follow-up task'),
 ('consent_policy_version', 'v1.0', 'Current consent / privacy notice version');

INSERT INTO roles (code, name, scope_type) VALUES
 ('STATE_ADMIN',       'State Admin',          'STATE'),
 ('DISTRICT_ADMIN',    'District Admin',       'DISTRICT'),
 ('PRINCIPAL',         'Principal',            'ITI'),
 ('TEACHER',           'Teacher',              'ITI'),
 ('PLACEMENT_OFFICER', 'Placement Officer',    'ITI'),
 ('ITI_STAFF',         'ITI Staff',            'ITI'),
 ('EMPLOYER',          'Employer / HR',        'COMPANY'),
 ('STUDENT',           'Student / Alumni',     'SELF');

INSERT INTO permissions (code, description) VALUES
 ('student.view',              'View student lifecycle within scope'),
 ('student.create',            'Create student / admission'),
 ('student.update',            'Update student profile'),
 ('student.contact.view',      'View personal contact details'),
 ('student.document.view',     'Download student documents'),
 ('admission.manage',          'Manage admissions, trades and batches'),
 ('transfer.request',          'Initiate an outbound transfer'),
 ('transfer.approve',          'Accept / reject an inbound transfer'),
 ('teacher_assignment.manage', 'Assign / reassign teachers'),
 ('attendance.mark',           'Mark attendance'),
 ('training.update',           'Update module progress'),
 ('assessment.record',         'Record internal assessments'),
 ('exam_result.record',        'Record exam results'),
 ('career.record',             'Record apprenticeship / job / business / higher education'),
 ('career.salary.view',        'View salary and stipend amounts'),
 ('verification.decide',       'Verify / reject records'),
 ('followup.manage',           'Create and assign follow-ups'),
 ('followup.work',             'Work on follow-ups assigned to me'),
 ('company.manage',            'Manage companies and branches'),
 ('vacancy.manage',            'Manage vacancies'),
 ('application.manage',        'Manage job applications'),
 ('employer.confirm',          'Confirm own company engagements'),
 ('report.iti',                'Institute reports'),
 ('report.district',           'District reports'),
 ('report.state',              'State reports'),
 ('audit.view',                'View audit logs'),
 ('user.manage',               'Manage users and role assignments'),
 ('master_data.manage',        'Manage ITIs, trades, academic years'),
 ('self.view',                 'View own Career Passport'),
 ('self.update',               'Submit own updates / applications');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON
 (r.code = 'STATE_ADMIN' AND p.code IN ('student.view','student.update','student.contact.view','student.document.view',
     'transfer.approve','career.salary.view','verification.decide','company.manage','report.iti','report.district',
     'report.state','audit.view','user.manage','master_data.manage'))
 OR (r.code = 'DISTRICT_ADMIN' AND p.code IN ('student.view','report.iti','report.district','audit.view'))
 OR (r.code = 'PRINCIPAL' AND p.code IN ('student.view','student.create','student.update','student.contact.view',
     'student.document.view','admission.manage','transfer.request','transfer.approve','teacher_assignment.manage',
     'attendance.mark','training.update','assessment.record','exam_result.record','career.record','career.salary.view',
     'verification.decide','followup.manage','followup.work','company.manage','application.manage','report.iti','audit.view','user.manage'))
 OR (r.code = 'TEACHER' AND p.code IN ('student.view','attendance.mark','training.update','assessment.record','followup.work'))
 OR (r.code = 'PLACEMENT_OFFICER' AND p.code IN ('student.view','student.contact.view','student.document.view','career.record',
     'career.salary.view','verification.decide','followup.manage','followup.work','company.manage','vacancy.manage',
     'application.manage','report.iti'))
 OR (r.code = 'ITI_STAFF' AND p.code IN ('followup.work','student.contact.view'))
 OR (r.code = 'EMPLOYER' AND p.code IN ('vacancy.manage','application.manage','employer.confirm'))
 OR (r.code = 'STUDENT' AND p.code IN ('self.view','self.update','student.contact.view','student.document.view','career.salary.view'));
