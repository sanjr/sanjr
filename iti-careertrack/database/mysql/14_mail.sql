-- 14: Email & circulars. State Admin writes to all ITIs of Maharashtra (or chosen regions / districts /
-- ITIs, and to RO / DVTO offices); RO Admin writes to the ITIs (and DVTO offices) of his / her region.
-- Every message is kept in the portal (Inbox / Sent / Drafts, read and reply status per ITI) and is
-- also e-mailed through SMTP to the ITI e-mail address and its Principal (queue: storage-independent).
-- Mailboxes belong to an OFFICE, not a person: STATE/0, REGION/<region id>, DISTRICT/<district id>, ITI/<iti id>.

CREATE TABLE IF NOT EXISTS mail_messages (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id       BIGINT UNSIGNED NULL,               -- reply to
    thread_id       BIGINT UNSIGNED NULL,               -- first message of the conversation
    from_user_id    BIGINT UNSIGNED NOT NULL,
    from_box_type   VARCHAR(8) NOT NULL,                -- STATE, REGION, DISTRICT, ITI
    from_box_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    subject         VARCHAR(200) NOT NULL,
    body            MEDIUMTEXT NOT NULL,
    priority        VARCHAR(8) NOT NULL DEFAULT 'NORMAL',   -- NORMAL, HIGH, URGENT
    reply_by        DATE NULL,                          -- "reply required by"
    status          VARCHAR(6) NOT NULL DEFAULT 'DRAFT',     -- DRAFT, SENT
    audience        VARCHAR(2000) NULL,                 -- JSON: how the recipients were chosen (for drafts)
    audience_label  VARCHAR(255) NULL,                  -- e.g. "All ITIs — Pune Region (70)"
    extra_emails    VARCHAR(1000) NULL,                 -- CC to other addresses (comma separated)
    recipients_count INT NOT NULL DEFAULT 0,
    sent_at         DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_mm_from (from_box_type, from_box_id, status, sent_at),
    KEY ix_mm_user (from_user_id, status),
    KEY ix_mm_thread (thread_id),
    KEY ix_mm_parent (parent_id)
) ENGINE=InnoDB;

-- one row per receiving office (ITI, RO, DVTO, State); read / reply status per office
CREATE TABLE IF NOT EXISTS mail_recipients (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id   BIGINT UNSIGNED NOT NULL,
    box_type     VARCHAR(8) NOT NULL,
    box_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    read_at      DATETIME NULL,
    read_by      BIGINT UNSIGNED NULL,
    replied_at   DATETIME NULL,
    starred      TINYINT(1) NOT NULL DEFAULT 0,
    archived     TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_mr (message_id, box_type, box_id),
    KEY ix_mr_box (box_type, box_id, archived, message_id)
) ENGINE=InnoDB;

-- one row per e-mail address; the queue for the SMTP sender
CREATE TABLE IF NOT EXISTS mail_deliveries (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id    BIGINT UNSIGNED NOT NULL,
    recipient_id  BIGINT UNSIGNED NULL,                 -- NULL = extra (CC) address
    email         VARCHAR(190) NOT NULL,
    kind          VARCHAR(8) NOT NULL DEFAULT 'NEW',    -- NEW, REMINDER
    status        VARCHAR(8) NOT NULL DEFAULT 'QUEUED', -- QUEUED, SENDING, SENT, FAILED, DEMO
    attempts      TINYINT NOT NULL DEFAULT 0,
    claim         CHAR(16) NULL,
    error         VARCHAR(255) NULL,
    sent_at       DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_md_queue (status, id),
    KEY ix_md_msg (message_id, status),
    KEY ix_md_rcpt (recipient_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mail_attachments (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id        BIGINT UNSIGNED NOT NULL,
    storage_key       VARCHAR(120) NOT NULL,
    original_filename VARCHAR(200) NOT NULL,
    mime_type         VARCHAR(120) NOT NULL,
    size_bytes        INT UNSIGNED NOT NULL,
    sha256            CHAR(64) NOT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_ma_msg (message_id)
) ENGINE=InnoDB;
