-- =============================================================================
-- ITI CareerTrack — migration 05: DVET regions & new roles
--   * 6 DVET Regional Offices; every district belongs to one region
--   * RO Admin (region scope), DVTO Admin (district scope — replaces District Admin),
--     Apprentice Advisor (a list of assigned ITIs), Instructor trades
--   * Login by User ID (e.g. the ITI's NCVT MIS code) + forced password change
-- Safe to run on a database created with files 01–04 (also used by install.sh).
-- =============================================================================

CREATE TABLE regions (
    id       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code     VARCHAR(10)  NOT NULL,
    name     VARCHAR(100) NOT NULL,
    hq_city  VARCHAR(80)  NULL,
    UNIQUE KEY uq_regions_code (code),
    UNIQUE KEY uq_regions_name (name)
) ENGINE=InnoDB;

INSERT INTO regions (code, name, hq_city) VALUES
 ('MUM', 'Mumbai Region', 'Mumbai'),
 ('PUN', 'Pune Region', 'Pune'),
 ('NSK', 'Nashik Region', 'Nashik'),
 ('CSN', 'Chhatrapati Sambhajinagar Region', 'Chhatrapati Sambhajinagar'),
 ('AMR', 'Amravati Region', 'Amravati'),
 ('NGP', 'Nagpur Region', 'Nagpur');

ALTER TABLE districts ADD COLUMN region_id BIGINT UNSIGNED NULL AFTER division,
    ADD KEY ix_districts_region (region_id),
    ADD CONSTRAINT fk_districts_region FOREIGN KEY (region_id) REFERENCES regions(id);

-- existing districts: map by revenue division (Konkan districts are under the Mumbai RO)
UPDATE districts d JOIN regions r ON r.code = CASE d.division
        WHEN 'Konkan' THEN 'MUM' WHEN 'Pune' THEN 'PUN' WHEN 'Nashik' THEN 'NSK'
        WHEN 'Chh. Sambhajinagar' THEN 'CSN' WHEN 'Amravati' THEN 'AMR' WHEN 'Nagpur' THEN 'NGP' END
   SET d.region_id = r.id;

-- ---------------------------------------------------------------------------
-- Roles
-- ---------------------------------------------------------------------------
ALTER TABLE roles DROP CHECK ck_roles_scope;
ALTER TABLE roles ADD CONSTRAINT ck_roles_scope CHECK (scope_type IN ('STATE','REGION','DISTRICT','ITI_GROUP','ITI','COMPANY','SELF'));

UPDATE roles SET name = 'State Admin (DVET)' WHERE code = 'STATE_ADMIN';
UPDATE roles SET code = 'DVTO', name = 'DVTO Admin' WHERE code = 'DISTRICT_ADMIN';
UPDATE roles SET name = 'Instructor' WHERE code = 'TEACHER';
INSERT INTO roles (code, name, scope_type) VALUES
 ('RO_ADMIN', 'RO Admin', 'REGION'),
 ('APPRENTICE_ADVISOR', 'Apprentice Advisor', 'ITI_GROUP');

ALTER TABLE user_role_assignments
    ADD COLUMN region_id BIGINT UNSIGNED NULL AFTER scope_type,
    ADD CONSTRAINT fk_ura_region FOREIGN KEY (region_id) REFERENCES regions(id);
ALTER TABLE user_role_assignments DROP CHECK ck_ura_scope;
ALTER TABLE user_role_assignments ADD CONSTRAINT ck_ura_scope CHECK (
    (scope_type IN ('STATE','SELF','ITI_GROUP') AND region_id IS NULL AND district_id IS NULL AND iti_id IS NULL AND company_id IS NULL) OR
    (scope_type = 'REGION'   AND region_id IS NOT NULL AND district_id IS NULL AND iti_id IS NULL AND company_id IS NULL) OR
    (scope_type = 'DISTRICT' AND district_id IS NOT NULL AND region_id IS NULL AND iti_id IS NULL AND company_id IS NULL) OR
    (scope_type = 'ITI'      AND iti_id IS NOT NULL AND region_id IS NULL AND district_id IS NULL AND company_id IS NULL) OR
    (scope_type = 'COMPANY'  AND company_id IS NOT NULL AND region_id IS NULL AND district_id IS NULL AND iti_id IS NULL));
ALTER TABLE user_role_assignments ADD KEY ix_ura_user (user_id);
ALTER TABLE user_role_assignments DROP INDEX uq_ura_active;
ALTER TABLE user_role_assignments DROP COLUMN scope_key;
ALTER TABLE user_role_assignments
    ADD COLUMN scope_key VARCHAR(30) AS (CONCAT_WS(':', scope_type, COALESCE(iti_id, district_id, region_id, company_id, 0))) STORED,
    ADD UNIQUE KEY uq_ura_active (user_id, role_id, scope_key, active_flag);

-- Apprentice Advisor: the ITIs under the advisor
CREATE TABLE role_assignment_itis (
    role_assignment_id  BIGINT UNSIGNED NOT NULL,
    iti_id              BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_assignment_id, iti_id),
    KEY ix_rai_iti (iti_id),
    CONSTRAINT fk_rai_ra FOREIGN KEY (role_assignment_id) REFERENCES user_role_assignments(id),
    CONSTRAINT fk_rai_iti FOREIGN KEY (iti_id) REFERENCES itis(id)
) ENGINE=InnoDB;

-- Instructor: the trade(s) the instructor teaches at their ITI
CREATE TABLE role_assignment_trades (
    role_assignment_id  BIGINT UNSIGNED NOT NULL,
    trade_id            BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_assignment_id, trade_id),
    CONSTRAINT fk_rat_ra FOREIGN KEY (role_assignment_id) REFERENCES user_role_assignments(id),
    CONSTRAINT fk_rat_trade FOREIGN KEY (trade_id) REFERENCES trades(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Users: User ID login + forced password change
-- ---------------------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN username VARCHAR(50) NULL AFTER full_name,
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash,
    ADD UNIQUE KEY uq_users_username (username);
ALTER TABLE users DROP CHECK ck_users_login;
ALTER TABLE users ADD CONSTRAINT ck_users_login CHECK (email IS NOT NULL OR mobile IS NOT NULL OR username IS NOT NULL);

INSERT INTO settings (setting_key, setting_value, description) VALUES
 ('default_password', 'Test@12345', 'Default password for new accounts created by the State Admin (must be changed at first login)');

-- ---------------------------------------------------------------------------
-- Permissions for the new / changed roles ("full access" = full VIEW of all data in scope)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON
    (r.code IN ('RO_ADMIN', 'DVTO') AND p.code IN ('student.view','student.contact.view','student.document.view','career.salary.view',
        'report.iti','report.district','audit.view','verification.decide'))
 OR (r.code = 'APPRENTICE_ADVISOR' AND p.code IN ('student.view','student.contact.view','student.document.view','career.salary.view',
        'report.iti','report.district','audit.view','verification.decide','company.manage'))
 OR (r.code = 'TEACHER' AND p.code IN ('student.contact.view','student.document.view'))
 OR (r.code = 'EMPLOYER' AND p.code IN ('employer.record'));
INSERT INTO permissions (code, description) VALUES ('employer.record', 'Employer: add job / apprenticeship records for own company');
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code = 'employer.record' WHERE r.code = 'EMPLOYER';
