-- Least-privilege account for the website: it can read, insert and update,
-- but it has NO DELETE right — lifecycle records can never be hard-deleted.
-- CHANGE THE PASSWORD before any non-local use.
CREATE USER IF NOT EXISTS 'careertrack_app'@'localhost' IDENTIFIED BY 'change_me_app_pass';
GRANT SELECT, INSERT, UPDATE, EXECUTE ON careertrack.* TO 'careertrack_app'@'localhost';
FLUSH PRIVILEGES;
