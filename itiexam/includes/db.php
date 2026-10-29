<?php
require_once __DIR__ . '/config.php';

const SCHEMA_VERSION = '21';

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (DB_DRIVER === 'mysql') {
        try {
            $pdo = mysql_connect_db(DB_HOST, DB_NAME, DB_USER, DB_PASS);
        } catch (PDOException $e) {
            // Never show the error (it can name the database user); tell the visitor to come back
            error_log('ITIExam: MySQL connection failed: ' . $e->getMessage());
            http_response_code(503);
            header('Retry-After: 120');
            exit('<!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Back soon</title>'
                . '<div style="font:16px/1.6 system-ui,sans-serif;text-align:center;padding:60px 20px"><h1 style="font-size:22px">We will be back in a minute</h1>'
                . '<p>The website cannot reach its database right now. Please try again shortly.</p></div>');
        }
    } else {
        $dir = dirname(DB_SQLITE_PATH);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $pdo = new PDO('sqlite:' . DB_SQLITE_PATH);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    db_migrate($pdo);
    return $pdo;
}

/** A MySQL connection set up like the SQLite one (also used by Admin → Database to fill a new MySQL database). */
function mysql_connect_db(string $host, string $name, string $user, string $pass): PDO
{
    $port = '';
    if (preg_match('/^(.+):(\d+)$/', $host, $m)) { $host = $m[1]; $port = ';port=' . $m[2]; }
    $pdo = new PDO("mysql:host=$host$port;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_EMULATE_PREPARES => false,              // real prepared statements: LIMIT ? and numbers work as on SQLite
    ]);
    // Same forgiving rules as SQLite (no ONLY_FULL_GROUP_BY / strict errors on long text), Indian time for NOW()
    $pdo->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION', time_zone = '+05:30'");
    return $pdo;
}

/** True for a MySQL connection. */
function is_mysql(PDO $pdo): bool { return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'; }

/** Creates tables on first run (works for SQLite and MySQL). */
function db_migrate(PDO $pdo): void
{
    try {   // skip when the schema is already current
        if ($pdo->query("SELECT svalue FROM settings WHERE skey = 'schema_version'")->fetchColumn() === SCHEMA_VERSION) return;
    } catch (PDOException $e) { /* first run: settings table does not exist yet */ }
    $mysql = is_mysql($pdo);
    $id = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $txt = $mysql ? 'MEDIUMTEXT' : 'TEXT';
    $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id $id, username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
        created_at VARCHAR(20) NOT NULL)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS trades (
        id $id, slug VARCHAR(120) NOT NULL UNIQUE, name VARCHAR(120) NOT NULL, full_name VARCHAR(255) NOT NULL DEFAULT '',
        short_desc VARCHAR(255) NOT NULL DEFAULT '', description $txt, content $txt,
        duration VARCHAR(60) NOT NULL DEFAULT '', nsqf_level VARCHAR(60) NOT NULL DEFAULT '',
        sector VARCHAR(120) NOT NULL DEFAULT '', exam_pattern VARCHAR(120) NOT NULL DEFAULT '',
        emoji VARCHAR(16) NOT NULL DEFAULT '', image VARCHAR(255) NOT NULL DEFAULT '', card_color VARCHAR(20) NOT NULL DEFAULT '#eef4ff',
        meta_title VARCHAR(255) NOT NULL DEFAULT '', meta_description VARCHAR(400) NOT NULL DEFAULT '', meta_keywords VARCHAR(500) NOT NULL DEFAULT '',
        is_popular INT NOT NULL DEFAULT 1, status VARCHAR(20) NOT NULL DEFAULT 'published', sort_order INT NOT NULL DEFAULT 0,
        created_at VARCHAR(20) NOT NULL, updated_at VARCHAR(20) NOT NULL)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS trade_items (
        id $id, trade_id INT NOT NULL, section VARCHAR(40) NOT NULL, title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(1000) NOT NULL DEFAULT '', url VARCHAR(500) NOT NULL DEFAULT '', label VARCHAR(40) NOT NULL DEFAULT '',
        icon VARCHAR(40) NOT NULL DEFAULT '', questions INT NOT NULL DEFAULT 0, minutes INT NOT NULL DEFAULT 0,
        is_new INT NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0,
        FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id $id, slug VARCHAR(160) NOT NULL UNIQUE, title VARCHAR(255) NOT NULL, content $txt,
        meta_title VARCHAR(255) NOT NULL DEFAULT '', meta_description VARCHAR(400) NOT NULL DEFAULT '', meta_keywords VARCHAR(500) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'published', created_at VARCHAR(20) NOT NULL, updated_at VARCHAR(20) NOT NULL)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_items (
        id $id, location VARCHAR(30) NOT NULL, parent_id INT NOT NULL DEFAULT 0, label VARCHAR(120) NOT NULL,
        url VARCHAR(500) NOT NULL, sort_order INT NOT NULL DEFAULT 0)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        skey VARCHAR(80) NOT NULL PRIMARY KEY, svalue $txt)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS employers (
        id $id, name VARCHAR(160) NOT NULL, short_name VARCHAR(20) NOT NULL DEFAULT '', logo VARCHAR(255) NOT NULL DEFAULT '',
        color VARCHAR(20) NOT NULL DEFAULT '#2563eb', website VARCHAR(500) NOT NULL DEFAULT '', is_featured INT NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0)$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
        id $id, slug VARCHAR(180) NOT NULL UNIQUE, title VARCHAR(255) NOT NULL, employer_id INT NOT NULL DEFAULT 0,
        company VARCHAR(160) NOT NULL DEFAULT '', job_type VARCHAR(30) NOT NULL DEFAULT 'private',
        trades VARCHAR(500) NOT NULL DEFAULT '', qualification VARCHAR(255) NOT NULL DEFAULT '', experience VARCHAR(20) NOT NULL DEFAULT 'fresher',
        location VARCHAR(160) NOT NULL DEFAULT '', vacancies VARCHAR(60) NOT NULL DEFAULT '', salary_min INT NOT NULL DEFAULT 0, salary_max INT NOT NULL DEFAULT 0,
        salary_text VARCHAR(160) NOT NULL DEFAULT '', age_limit VARCHAR(120) NOT NULL DEFAULT '', last_date VARCHAR(10) NOT NULL DEFAULT '',
        apply_url VARCHAR(500) NOT NULL DEFAULT '', notification_url VARCHAR(500) NOT NULL DEFAULT '', summary VARCHAR(400) NOT NULL DEFAULT '',
        description $txt, is_featured INT NOT NULL DEFAULT 0, mark_new INT NOT NULL DEFAULT 1,
        meta_title VARCHAR(255) NOT NULL DEFAULT '', meta_description VARCHAR(400) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'published', posted_at VARCHAR(20) NOT NULL, updated_at VARCHAR(20) NOT NULL)$engine");

    // Columns added after the first release
    add_column($pdo, 'pages', 'category', "VARCHAR(30) NOT NULL DEFAULT ''");
    add_column($pdo, 'pages', 'excerpt', "VARCHAR(400) NOT NULL DEFAULT ''");
    add_column($pdo, 'pages', 'image', "VARCHAR(255) NOT NULL DEFAULT ''");
    add_column($pdo, 'menu_items', 'subtitle', "VARCHAR(255) NOT NULL DEFAULT ''");
    add_column($pdo, 'menu_items', 'icon', "VARCHAR(40) NOT NULL DEFAULT ''");
    // v12: year/semester of a mock test kept apart from its name ("1st Year", "Sem 3")
    add_column($pdo, 'trade_items', 'level', "VARCHAR(20) NOT NULL DEFAULT ''");
    // v20: a "trade" can also be a common subject (Employability Skills …) – same tools, own address, not in trade lists
    add_column($pdo, 'trades', 'kind', "VARCHAR(20) NOT NULL DEFAULT 'trade'");
    // v18: Video Learning – a thumbnail for uploaded videos, and when an item was added
    add_column($pdo, 'trade_items', 'image', "VARCHAR(255) NOT NULL DEFAULT ''");
    add_column($pdo, 'trade_items', 'created_at', "VARCHAR(20) NOT NULL DEFAULT ''");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cbt_tests (
        id $id, folder VARCHAR(60) NOT NULL UNIQUE, title VARCHAR(255) NOT NULL, trade_id INT NOT NULL DEFAULT 0,
        entry VARCHAR(255) NOT NULL DEFAULT 'index.html', questions INT NOT NULL DEFAULT 0, minutes INT NOT NULL DEFAULT 0,
        description VARCHAR(500) NOT NULL DEFAULT '', status VARCHAR(20) NOT NULL DEFAULT 'published',
        created_at VARCHAR(20) NOT NULL, updated_at VARCHAR(20) NOT NULL)$engine");
    // v12 (after the table exists, so a brand-new install works too)
    add_column($pdo, 'cbt_tests', 'level', "VARCHAR(20) NOT NULL DEFAULT ''");
    foreach (['trade_items', 'cbt_tests'] as $tb) {
        foreach ($pdo->query("SELECT id, title FROM $tb WHERE level = ''")->fetchAll() as $r) {
            if (preg_match('/^\s*(1st Year|2nd Year|Sem [1-4])\s*[–:-]\s*(.+)$/u', $r['title'], $m))
                $pdo->prepare("UPDATE $tb SET level = ?, title = ? WHERE id = ?")->execute([$m[1], trim($m[2]), $r['id']]);
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS not_found_log (
        id $id, path VARCHAR(300) NOT NULL UNIQUE, hits INT NOT NULL DEFAULT 1, referrer VARCHAR(300) NOT NULL DEFAULT '',
        last_seen VARCHAR(20) NOT NULL)$engine");

    // Students sign in with Google; every finished CBT test is one attempt
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id $id, google_id VARCHAR(64) NOT NULL UNIQUE, email VARCHAR(190) NOT NULL DEFAULT '', name VARCHAR(160) NOT NULL DEFAULT '',
        picture VARCHAR(500) NOT NULL DEFAULT '', trade_id INT NOT NULL DEFAULT 0, created_at VARCHAR(20) NOT NULL, last_login VARCHAR(20) NOT NULL)$engine");
    $pdo->exec("CREATE TABLE IF NOT EXISTS attempts (
        id $id, student_id INT NOT NULL, test_id INT NOT NULL DEFAULT 0, trade_id INT NOT NULL DEFAULT 0,
        title VARCHAR(255) NOT NULL DEFAULT '', level VARCHAR(20) NOT NULL DEFAULT '', score INT NOT NULL DEFAULT 0, total INT NOT NULL DEFAULT 0,
        pct INT NOT NULL DEFAULT 0, passed INT NOT NULL DEFAULT 0, time_sec INT NOT NULL DEFAULT 0, lang VARCHAR(20) NOT NULL DEFAULT '',
        created_at VARCHAR(20) NOT NULL)$engine");
    try { $pdo->exec('CREATE INDEX attempts_student ON attempts (student_id)'); } catch (PDOException $e) { /* already there */ }
    // v19: failed admin logins / reset codes, counted per IP address (brute-force protection)
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id $id, kind VARCHAR(20) NOT NULL, ip VARCHAR(64) NOT NULL, username VARCHAR(80) NOT NULL DEFAULT '', at_time INT NOT NULL)$engine");
    try { $pdo->exec('CREATE INDEX login_attempts_ip ON login_attempts (kind, ip, at_time)'); } catch (PDOException $e) { /* already there */ }
    // v17: one-time codes that hand a Google login from the phone's browser back to the Android app
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_codes (
        code VARCHAR(64) NOT NULL PRIMARY KEY, student_id INT NOT NULL, challenge VARCHAR(64) NOT NULL,
        next_url VARCHAR(300) NOT NULL DEFAULT '', created_at INT NOT NULL)$engine");
    // v14: where the student took the test ("City, State (Country)", found by the test itself)
    add_column($pdo, 'attempts', 'location', "VARCHAR(160) NOT NULL DEFAULT ''");
    add_column($pdo, 'students', 'location', "VARCHAR(160) NOT NULL DEFAULT ''");
    // v15: new wide ITIExam logo is the default; an old uploaded square logo would hide it
    if ((int)$pdo->query("SELECT svalue FROM settings WHERE skey = 'schema_version'")->fetchColumn() < 15) {
        $pdo->exec("DELETE FROM settings WHERE skey = 'logo_file'");
    }
    // v16: new round ITIExam favicon is the default; an old uploaded favicon would hide it
    if ((int)$pdo->query("SELECT svalue FROM settings WHERE skey = 'schema_version'")->fetchColumn() < 16) {
        $pdo->exec("DELETE FROM settings WHERE skey = 'favicon_file'");
    }

    // v21: website visitors – one row per page view (no cookies; a visitor is a daily hash), and who is on the site right now
    $pdo->exec("CREATE TABLE IF NOT EXISTS visits (
        id $id, day VARCHAR(10) NOT NULL, at_time INT NOT NULL, vid VARCHAR(16) NOT NULL, path VARCHAR(255) NOT NULL DEFAULT '',
        title VARCHAR(160) NOT NULL DEFAULT '', source VARCHAR(40) NOT NULL DEFAULT '', ref_host VARCHAR(120) NOT NULL DEFAULT '',
        device VARCHAR(10) NOT NULL DEFAULT '', ip VARCHAR(64) NOT NULL DEFAULT '', country VARCHAR(60) NOT NULL DEFAULT '',
        region VARCHAR(80) NOT NULL DEFAULT '', city VARCHAR(80) NOT NULL DEFAULT '')$engine");
    try { $pdo->exec('CREATE INDEX visits_day ON visits (day, vid)'); } catch (PDOException $e) { /* already there */ }
    try { $pdo->exec('CREATE INDEX visits_ip ON visits (ip)'); } catch (PDOException $e) { /* already there */ }
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitors_live (
        vid VARCHAR(16) NOT NULL PRIMARY KEY, last_seen INT NOT NULL, path VARCHAR(255) NOT NULL DEFAULT '', title VARCHAR(160) NOT NULL DEFAULT '',
        source VARCHAR(40) NOT NULL DEFAULT '', device VARCHAR(10) NOT NULL DEFAULT '', ip VARCHAR(64) NOT NULL DEFAULT '',
        country VARCHAR(60) NOT NULL DEFAULT '', region VARCHAR(80) NOT NULL DEFAULT '', city VARCHAR(80) NOT NULL DEFAULT '')$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS syllabi (
        id $id, trade_name VARCHAR(160) NOT NULL, trade_id INT NOT NULL DEFAULT 0, sector VARCHAR(120) NOT NULL DEFAULT '',
        duration VARCHAR(60) NOT NULL DEFAULT '', nsqf VARCHAR(20) NOT NULL DEFAULT '', details VARCHAR(500) NOT NULL DEFAULT '',
        pdf_url VARCHAR(500) NOT NULL DEFAULT '', hindi_url VARCHAR(500) NOT NULL DEFAULT '', source VARCHAR(80) NOT NULL DEFAULT '',
        is_featured INT NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'published', sort_order INT NOT NULL DEFAULT 0,
        updated_at VARCHAR(20) NOT NULL)$engine");

    require_once __DIR__ . '/../admin/inc/seed.php';
    if ((int)$pdo->query('SELECT COUNT(*) FROM syllabi')->fetchColumn() === 0 && !setting('syllabi_seeded')) { seed_syllabi(); setting_set('syllabi_seeded', '1'); }
    if (!setting('syllabi_more_seeded')) { seed_syllabi_more(); setting_set('syllabi_more_seeded', '1'); }
    // A brand-new (or emptied) website gets the starter menus, trades, pages and settings right away,
    // even before the admin account is created. Sample jobs are only added by the admin setup.
    if ((int)$pdo->query('SELECT COUNT(*) FROM trades')->fetchColumn() === 0 && (int)$pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn() === 0) {
        seed_starter();
    }
    if (!setting('es_subject_done')) { make_es_subject($pdo); setting_set('es_subject_done', '1'); }
    // One-time fixes for sites installed with an older version (missing starter pages, old menu links)
    if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) upgrade_content();
    if (setting('footer_credit', "\0") === "\0") setting_set('footer_credit', 'Created & Developed by – Sanjay L. Rathod');

    $pdo->prepare('DELETE FROM settings WHERE skey = ?')->execute(['schema_version']);
    $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)')->execute(['schema_version', SCHEMA_VERSION]);
}

/** Employability Skills as a common subject at /employability-skills/: mock tests, notes, papers and videos like a trade.
    The old text page's content moves into it (the page itself is kept as a hidden draft). */
function make_es_subject(PDO $pdo): void
{
    $st = $pdo->prepare('SELECT id FROM trades WHERE slug = ?');
    $st->execute(['employability-skills']);
    if ($st->fetchColumn()) return;
    $pg = $pdo->prepare("SELECT * FROM pages WHERE slug = 'employability-skills'");
    $pg->execute();
    $page = $pg->fetch() ?: [];
    $sort = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM trades')->fetchColumn();
    $pdo->prepare("INSERT INTO trades (slug, name, full_name, short_desc, description, content, duration, nsqf_level, sector, exam_pattern, emoji, card_color,
        meta_title, meta_description, meta_keywords, is_popular, status, sort_order, kind, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'published', ?, 'subject', ?, ?)")
        ->execute(['employability-skills', 'Employability Skills', 'Common subject for all ITI trades', 'Common for every trade · 1st & 2nd Year',
            'Employability Skills (ES) is a common CBT subject in every ITI trade. Practise module-wise ES mock tests, read notes and solve previous year questions for 1st and 2nd year.',
            str_replace('<p>Use the mock tests on each trade page to practise ES questions.</p>', '', (string)($page['content'] ?? '')), '2 Years', '', 'All ITI trades', 'NCVT CBT (MCQ)', '💼', '#fff4e6',
            'ITI Employability Skills MCQ ' . date('Y') . ' – Mock Test, Notes PDF & Question Paper', (string)($page['meta_description'] ?? ''), '', $sort, now(), now()]);
    $id = (int)$pdo->lastInsertId();
    $mods = [
        ['Introduction to Employability Skills', 'Need of employability skills, job market'], ['Constitutional Values – Citizenship', 'Fundamental rights & duties, civic responsibility'],
        ['Becoming a Professional in the 21st Century', 'Self-learning, work ethics, time management'], ['Basic English Skills', 'Speaking, reading and writing at work'],
        ['Career Development & Goal Setting', 'Career planning, SMART goals'], ['Communication Skills', 'Verbal, non-verbal and written communication'],
        ['Diversity & Inclusion', 'Gender sensitivity, PwD, POSH'], ['Financial & Legal Literacy', 'Banking, digital payments, consumer rights'],
        ['Essential Digital Skills', 'Computer, internet, e-mail, cyber safety'], ['Entrepreneurship', 'Business ideas, government schemes, MSME'],
        ['Customer Service', 'Customer needs, handling complaints'], ['Getting Ready for Apprenticeship & Jobs', 'Resume, interview, apprenticeship portals'],
    ];
    $ins = $pdo->prepare("INSERT INTO trade_items (trade_id, section, title, subtitle, sort_order) VALUES (?, 'syllabus', ?, ?, ?)");
    foreach ($mods as $i => [$t, $d]) $ins->execute([$id, $t, $d, $i + 1]);
    if ($page) $pdo->prepare("UPDATE pages SET status = 'draft' WHERE id = ?")->execute([$page['id']]);
}

function add_column(PDO $pdo, string $table, string $col, string $def): void
{
    $cols = is_mysql($pdo)
        ? $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN)
        : array_column($pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array($col, $cols, true)) $pdo->exec("ALTER TABLE $table ADD COLUMN $col $def");
}
