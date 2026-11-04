-- 13: WhatsApp bot. The Principal / Instructor sends a status question to students on WhatsApp;
-- the student answers with one number (1 job, 2 apprenticeship, 3 studies, 4 business, 5 looking
-- for a job) and one or two short replies, and the career record is updated without a portal login.

-- every message in or out (status updated from WhatsApp's delivery callbacks)
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id     BIGINT UNSIGNED NULL,
    phone          VARCHAR(15) NOT NULL,          -- 10 digits
    direction      VARCHAR(3) NOT NULL,           -- OUT, IN
    kind           VARCHAR(10) NOT NULL DEFAULT 'TEXT',   -- TEMPLATE, TEXT, BUTTON, REMINDER
    body           VARCHAR(1000) NULL,
    wa_message_id  VARCHAR(100) NULL,
    status         VARCHAR(10) NOT NULL,          -- DEMO, QUEUED, SENT, DELIVERED, READ, FAILED, RECEIVED
    error          VARCHAR(255) NULL,
    sent_by        BIGINT UNSIGNED NULL,          -- user who asked (NULL = bot / nightly job)
    iti_id         BIGINT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_wam_student (student_id, created_at),
    KEY ix_wam_phone (phone, created_at),
    KEY ix_wam_waid (wa_message_id),
    KEY ix_wam_iti (iti_id, created_at)
) ENGINE=InnoDB;

-- where each phone is in the short conversation, and opt-out ("STOP")
CREATE TABLE IF NOT EXISTS whatsapp_sessions (
    phone        VARCHAR(15) NOT NULL PRIMARY KEY,
    student_id   BIGINT UNSIGNED NULL,
    step         VARCHAR(16) NOT NULL DEFAULT 'IDLE',
    data         VARCHAR(1000) NULL,
    opted_out    TINYINT(1) NOT NULL DEFAULT 0,
    last_in_at   DATETIME NULL,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_was_student (student_id)
) ENGINE=InnoDB;
