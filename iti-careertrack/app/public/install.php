<?php
declare(strict_types=1);

/**
 * ITI CareerTrack — one-time web installer (works on XAMPP / WAMP / LAMP, no command line needed).
 *  1. Connects to MySQL with the details you enter and creates the database.
 *  2. Installs the tables, rules and triggers — either an EMPTY system (only the State Admin login,
 *     36 districts and 6 DVET regions) or a DEMO system with synthetic sample data.
 *  3. Writes config/config.php. Once that file exists this installer is disabled automatically.
 */

$root = dirname(__DIR__);
$configFile = $root . '/config/config.php';
$dbDir = dirname($root) . '/database/mysql';
if (!is_dir($dbDir)) {
    $dbDir = $root . '/database/mysql';     // packaged layout: database folder inside app/
}

if (is_file($configFile)) {
    http_response_code(404);
    echo 'The system is already installed. (To reinstall, delete config/config.php first.)';
    exit;
}

session_name('CTINSTALL');
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$h = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

/** Split a .sql (or .sql.gz) file into statements; supports the DELIMITER command used for triggers. */
function ct_statements(string $file): Generator
{
    $gz = str_ends_with($file, '.gz');
    $fh = $gz ? gzopen($file, 'rb') : fopen($file, 'rb');
    if (!$fh) {
        throw new RuntimeException('Cannot read ' . basename($file));
    }
    $delim = ';';
    $buf = '';
    while (($line = $gz ? gzgets($fh) : fgets($fh)) !== false) {
        $trim = trim($line);
        if ($buf === '' && ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '#'))) {
            continue;
        }
        if (preg_match('/^DELIMITER\s+(\S+)\s*$/i', $trim, $m)) {
            $delim = $m[1];
            continue;
        }
        $buf .= $line;
        if (str_ends_with(rtrim($line), $delim)) {
            $stmt = trim(substr(rtrim($buf), 0, -strlen($delim)));
            $buf = '';
            if ($stmt !== '') {
                yield $stmt;
            }
        }
    }
    if (trim($buf) !== '') {
        yield trim($buf);
    }
    $gz ? gzclose($fh) : fclose($fh);
}

/** 'TRIGGER' | 'FUNCTION' | 'PROCEDURE' for CREATE/DROP of stored programs (also mysqldump's versioned-comment form), else null. */
function ct_routine_kind(string $stmt): ?string
{
    $head = preg_replace('~/\*!\d+\s*|\*/~', ' ', substr($stmt, 0, 300));
    if (preg_match('/^\s*(CREATE|DROP)\b[^(;]*?\b(TRIGGER|FUNCTION|PROCEDURE)\b/i', $head, $m)) {
        return strtoupper($m[2]);
    }
    return null;
}

/** MariaDB (used by many web hosts) differs from MySQL 8 in a few statements. */
function ct_compat(string $stmt, bool $maria): string
{
    if (!$maria) {
        return $stmt;
    }
    $stmt = preg_replace('/\bDROP\s+CHECK\s+/i', 'DROP CONSTRAINT ', $stmt);
    // mysqldump writes "x REGEXP 'p'" as regexp_like(`x`,_utf8mb4'p'), which MariaDB does not know
    return preg_replace('/\bregexp_like\((`?\w+`?),\s*((?:_utf8mb4)?\'(?:[^\'\\\\]|\\\\.)*\')\)/i', '($1 REGEXP $2)', $stmt);
}

$errors = [];
$done = null;
$in = [
    'host' => $_POST['host'] ?? '127.0.0.1', 'port' => $_POST['port'] ?? '3306', 'name' => $_POST['name'] ?? 'careertrack',
    'user' => $_POST['user'] ?? 'root', 'pass' => $_POST['pass'] ?? '', 'mode' => $_POST['mode'] ?? 'demo',
    'reset' => !empty($_POST['reset']),
    'admin_user' => strtoupper(trim((string) ($_POST['admin_user'] ?? 'DVET-ADMIN'))), 'admin_name' => $_POST['admin_name'] ?? 'State Admin (DVET)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'The form expired. Please reload the page.';
    }
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $in['name'])) {
        $errors[] = 'Database name: letters, digits and _ only.';
    }
    if (!in_array($in['mode'], ['empty', 'demo'], true)) {
        $errors[] = 'Choose Empty or Demo.';
    }
    $adminPass = (string) ($_POST['admin_pass'] ?? '');
    if ($in['mode'] === 'empty') {
        if (!preg_match('/^[A-Z0-9][A-Z0-9_.-]{2,49}$/', $in['admin_user'])) {
            $errors[] = 'State Admin User ID: 3–50 letters / digits / - _ .';
        }
        if (strlen($adminPass) < 10 || !preg_match('/[A-Za-z]/', $adminPass) || !preg_match('/\d/', $adminPass)) {
            $errors[] = 'State Admin password: at least 10 characters with letters and numbers.';
        }
    }
    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        $errors[] = 'PHP 8.1 or newer is required (you have ' . PHP_VERSION . ').';
    }
    if (!$errors) {
        try {
            @set_time_limit(600);
            $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $in['host'], (int) $in['port']), $in['user'], $in['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');   // match the tables' collation (MariaDB 11 default differs)
            $ver = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
            $maria = stripos($ver, 'mariadb') !== false;
            if (!$maria && version_compare($ver, '8.0.16', '<')) {
                throw new RuntimeException("MySQL 8.0.16 or newer is required (server is $ver).");
            }
            if ($maria && version_compare($ver, '10.5.0', '<')) {
                throw new RuntimeException("MariaDB 10.5 or newer is required (server is $ver).");
            }
            // Shared hosting (cPanel / Plesk): the database is created in the control panel and the
            // user may not be allowed to create databases. Then just use the existing one.
            try {
                $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $in['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            } catch (PDOException $ignored) {
            }
            try {
                $pdo->exec('USE `' . $in['name'] . '`');
            } catch (PDOException $e) {
                throw new RuntimeException('Database "' . $in['name'] . '" was not found, or this MySQL user has no rights on it. '
                    . 'On web hosting: cPanel → MySQL Databases → create the database and a user, then "Add User To Database" with ALL PRIVILEGES. '
                    . 'Enter the full names shown by cPanel (for example cpuser_careertrack).');
            }
            try {   // a database created in the hosting panel may default to latin1: Marathi text needs utf8mb4
                $pdo->exec('ALTER DATABASE `' . $in['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            } catch (PDOException $ignored) {
            }
            if ($in['reset']) {   // clean up an earlier, unfinished installation
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                foreach ($pdo->query("SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_type = 'VIEW' DESC")
                             ->fetchAll(PDO::FETCH_NUM) as [$t, $type]) {
                    $pdo->exec(($type === 'VIEW' ? 'DROP VIEW IF EXISTS `' : 'DROP TABLE IF EXISTS `') . str_replace('`', '', $t) . '`');
                }
                foreach ($pdo->query('SELECT routine_name, routine_type FROM information_schema.routines WHERE routine_schema = DATABASE()')->fetchAll(PDO::FETCH_NUM) as [$r, $type]) {
                    $pdo->exec('DROP ' . ($type === 'FUNCTION' ? 'FUNCTION' : 'PROCEDURE') . ' IF EXISTS `' . str_replace('`', '', $r) . '`');
                }
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            }
            if ((int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn() > 0) {
                throw new RuntimeException('Database "' . $in['name'] . '" is not empty (perhaps from an earlier attempt). '
                    . 'Tick "Delete existing tables in this database" below and press Install again, or use a new, empty database.');
            }
            // Demo: the full dump, but its procedures/triggers are skipped and created from
            // 02_routines.sql afterwards, so that hosting without the SUPER privilege also works.
            $files = $in['mode'] === 'demo'
                ? ['demo_data.sql.gz', '02_routines.sql', '07_performance.sql', '08_trade_master.sql', '09_login_ids.sql', '10_roles_v2.sql', '11_blood_group.sql']
                : ['01_schema.sql', '02_routines.sql', '03_reference_data.sql', '05_regions_roles.sql', '06_districts.sql', '07_performance.sql', '08_trade_master.sql', '09_login_ids.sql', '10_roles_v2.sql', '11_blood_group.sql'];
            $count = 0;
            $noTriggers = false;
            foreach ($files as $f) {
                $path = $dbDir . '/' . $f;
                if (!is_file($path)) {
                    throw new RuntimeException("Missing file database/mysql/$f");
                }
                foreach (ct_statements($path) as $stmt) {
                    $stmt = ct_compat($stmt, $maria);
                    $kind = ct_routine_kind($stmt);
                    if ($f === 'demo_data.sql.gz' && $kind !== null) {
                        continue;
                    }
                    if ($in['mode'] === 'demo' && $f === '02_routines.sql' && $kind === null) {
                        continue;   // views etc. are already in the dump
                    }
                    if ($kind === 'TRIGGER') {
                        if ($noTriggers) {
                            continue;
                        }
                        try {
                            $pdo->exec($stmt);
                        } catch (PDOException $e) {
                            if (!preg_match('/\[(1419|1227|1142|1044|1370)\]|SUPER/', $e->getMessage())) {
                                throw $e;
                            }
                            $noTriggers = true;   // the website enforces these rules in PHP instead
                        }
                        $count++;
                        continue;
                    }
                    $pdo->exec($stmt);
                    $count++;
                }
            }
            require_once $root . "/src/Core/Migrator.php";
            \App\Core\Migrator::setVersion($pdo, \App\Core\Migrator::latest());   // fresh install = latest database version
            if ($noTriggers) {
                foreach ($pdo->query('SELECT trigger_name FROM information_schema.triggers WHERE trigger_schema = DATABASE()')->fetchAll(PDO::FETCH_COLUMN) as $t) {
                    $pdo->exec('DROP TRIGGER `' . $t . '`');
                }
                $pdo->exec("REPLACE INTO settings (setting_key, setting_value, description)
                            VALUES ('db_triggers', '0', 'MySQL triggers not allowed by the host; rules run in PHP (DbRules service)')");
            }
            if ($in['mode'] === 'empty') {
                $y = (int) date('n') >= 8 ? (int) date('Y') : (int) date('Y') - 1;
                $pdo->prepare("INSERT INTO academic_years (label, start_date, end_date, status) VALUES (?, ?, ?, 'OPEN')")
                    ->execute([$y . '-' . sprintf('%02d', ($y + 1) % 100), "$y-08-01", ($y + 1) . '-07-31']);
                $pdo->prepare("INSERT INTO users (full_name, username, password_hash, status) VALUES (?, ?, ?, 'ACTIVE')")
                    ->execute([mb_substr((string) $in['admin_name'], 0, 150), $in['admin_user'],
                        defined('PASSWORD_ARGON2ID') ? password_hash($adminPass, PASSWORD_ARGON2ID, ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1]) : password_hash($adminPass, PASSWORD_DEFAULT)]);
                $uid = (int) $pdo->lastInsertId();
                $pdo->prepare("INSERT INTO user_role_assignments (user_id, role_id, scope_type, valid_from) SELECT ?, id, 'STATE', CURDATE() FROM roles WHERE code = 'STATE_ADMIN'")
                    ->execute([$uid]);
            }
            // Official ITI lists shipped in database/data/*_itis.csv (e.g. Pune region):
            // ITIs + Principal (User ID = NCVT MIS code), DVTO and RO logins, password Demo@1234.
            $imported = [];
            $dataDir = dirname($dbDir) . '/data';
            $csvs = glob($dataDir . '/*_itis.csv') ?: [];
            if ($csvs) {
                require_once $root . '/src/bootstrap.php';
                \App\Core\Database::usePdo($pdo);
                $actor = (int) $pdo->query("SELECT u.id FROM users u JOIN user_role_assignments ura ON ura.user_id = u.id
                                             JOIN roles r ON r.id = ura.role_id WHERE r.code = 'STATE_ADMIN' ORDER BY u.id LIMIT 1")->fetchColumn() ?: null;
                foreach ($csvs as $csv) {
                    $items = \App\Services\ItiImport::readFile($csv, basename($csv));
                    $imported[basename($csv)] = ['rows' => count($items)] + \App\Services\ItiImport::import($items, 'Demo@1234', false, $actor);
                }
            }
            $config = ['app' => ['name' => 'ITI CareerTrack', 'env' => $in['mode'] === 'demo' ? 'demo' : 'production', 'secret' => bin2hex(random_bytes(32)),
                                 'timezone' => 'Asia/Kolkata', 'session_idle_minutes' => 30, 'force_https_cookies' => false],
                       'db' => ['host' => $in['host'], 'port' => (int) $in['port'], 'name' => $in['name'], 'user' => $in['user'], 'pass' => $in['pass']],
                       'storage' => ['documents' => $root . '/storage/documents', 'max_upload_bytes' => 5 * 1024 * 1024]];
            $php = "<?php\n// Written by the ITI CareerTrack installer on " . date('Y-m-d H:i') . ". Keep this file private.\nreturn " . var_export($config, true) . ";\n";
            $written = @file_put_contents($configFile, $php) !== false;
            $done = ['statements' => $count, 'written' => $written, 'php' => $php, 'mode' => $in['mode'], 'no_triggers' => $noTriggers, 'imported' => $imported];
            session_destroy();
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '[1045]') || str_contains($msg, '[1698]')) {
                $msg = 'MySQL refused the user name / password (' . $in['user'] . '@' . $in['host'] . '). '
                    . 'On web hosting the user "root" with an empty password does not work: in cPanel → MySQL Databases create a database and a user '
                    . '(with a password), add the user to the database with ALL PRIVILEGES, and enter those full names here. Host is usually "localhost".';
            } elseif (str_contains($msg, '[2002]')) {
                $msg = 'Cannot reach the MySQL server at ' . $in['host'] . ':' . $in['port'] . '. On web hosting try host "localhost". (' . $msg . ')';
            } elseif (str_contains($msg, '[1044]') || str_contains($msg, '[1142]') || str_contains($msg, '[1227]') || str_contains($msg, '[1419]')) {
                $msg .= ' — the MySQL user needs ALL PRIVILEGES on this database (cPanel → MySQL Databases → Add User To Database → All Privileges). '
                    . 'Delete all tables in the database, then run the installer again.';
            }
            $errors[] = $msg;
        }
    }
}
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install · ITI CareerTrack</title><link rel="stylesheet" href="<?= $h($base) ?>/assets/css/app.css"></head>
<body class="auth-body">
<div class="auth-hero"><div class="auth-hero-inner"><img src="<?= $h($base) ?>/assets/img/logo.svg" alt="" width="64" height="64">
  <div><h1>ITI CareerTrack — Installation</h1><p>One-time setup. Takes about one minute.</p></div></div></div>
<main class="auth-main" style="max-width:820px">
<?php if ($done): ?>
  <div class="card">
    <h2 style="color:var(--green)">Installation complete</h2>
    <p><?= (int) $done['statements'] ?> database steps completed.</p>
    <?php foreach ($done['imported'] as $file => $im): ?>
      <div class="alert alert-success">ITI list <b><?= $h($file) ?></b>: <?= (int) $im['rows'] ?> ITIs, <?= (int) $im['principals'] ?> Principal logins
        (User ID = NCVT MIS code, password <b>Demo@1234</b>)<?= $im['dvto'] ? ', DVTO logins ' . $h(implode(', ', $im['dvto'])) : '' ?><?= $im['ro'] ? ', RO logins ' . $h(implode(', ', $im['ro'])) : '' ?>.</div>
    <?php endforeach; ?>
    <?php if ($done['no_triggers']): ?>
      <div class="alert alert-info">This MySQL server does not allow triggers for your user (binary logging without the SUPER privilege — normal on web hosting).
        That is fine: the website applies the same rules in PHP. The extra database-level protection (for example the append-only audit log)
        is active only if the host enables <code>log_bin_trust_function_creators</code> and you install again.</div>
    <?php endif; ?>
    <?php if (!$done['written']): ?>
      <div class="alert alert-warning">The installer could not write <b>app/config/config.php</b>. Create that file yourself with this content, then open the website:</div>
      <pre class="json" style="max-height:300px"><?= $h($done['php']) ?></pre>
    <?php endif; ?>
    <?php if ($done['mode'] === 'demo'): ?>
      <p>DEMO data loaded. Log in with <b>DVET-ADMIN</b> / <b>Demo@1234</b> (or click any role on the login page). All names are synthetic.</p>
    <?php else: ?>
      <p>Log in as State Admin with User ID <b><?= $h($in['admin_user']) ?></b> and the password you chose. Then:</p>
      <ol><li>Master Data → add trades (and modules) and academic years.</li><li>ITIs Management → add each ITI with its NCVT MIS code and trades.</li>
        <li>Users → <b>Create ITI logins</b> (User ID = NCVT MIS code, password Test@12345) and create RO, DVTO, Apprentice Advisor and Instructor users.</li></ol>
    <?php endif; ?>
    <a class="btn mt" href="<?= $h($base) ?>/login">Open the website</a>
  </div>
<?php else: ?>
  <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= $h($e) ?></div><?php endforeach; ?>
  <form method="post" class="card"><input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">
    <h2>1. MySQL database</h2>
    <p class="muted"><b>XAMPP (own computer):</b> host 127.0.0.1, port 3306, user root, empty password.<br>
      <b>Web hosting (cPanel):</b> first create the database and a MySQL user in cPanel → <i>MySQL Databases</i>, add the user to the database with
      <i>ALL PRIVILEGES</i>, then enter host <b>localhost</b> and the full names shown by cPanel (e.g. <code>cpuser_careertrack</code>, <code>cpuser_ctuser</code>).
      MySQL 8.0.16+ is required.</p>
    <div class="form-grid">
      <div class="field"><label>Host</label><input type="text" name="host" value="<?= $h($in['host']) ?>" required></div>
      <div class="field"><label>Port</label><input type="number" name="port" value="<?= $h($in['port']) ?>" required></div>
      <div class="field"><label>New database name</label><input type="text" name="name" value="<?= $h($in['name']) ?>" required></div>
      <div class="field"><label>MySQL user</label><input type="text" name="user" value="<?= $h($in['user']) ?>" required></div>
      <div class="field"><label>MySQL password</label><input type="password" name="pass" value="<?= $h($in['pass']) ?>"></div>
    </div>
    <h2 class="mt">2. What to install</h2>
    <div class="grid g2">
      <label class="card" style="margin:0;cursor:pointer"><input type="radio" name="mode" value="demo" <?= $in['mode'] === 'demo' ? 'checked' : '' ?>>
        <b>Demo system</b><br><small class="muted">46 ITIs, ~6,600 synthetic students and ready demo logins for every role (password Demo@1234). For training and presentation.</small></label>
      <label class="card" style="margin:0;cursor:pointer"><input type="radio" name="mode" value="empty" <?= $in['mode'] === 'empty' ? 'checked' : '' ?>>
        <b>Empty system (real use)</b><br><small class="muted">Only the State Admin login, 36 districts and the 6 DVET regions. You add ITIs, trades and users.</small></label>
    </div>
    <div class="form-grid mt">
      <div class="field"><label>State Admin User ID (empty system)</label><input type="text" name="admin_user" value="<?= $h($in['admin_user']) ?>"></div>
      <div class="field"><label>State Admin name</label><input type="text" name="admin_name" value="<?= $h($in['admin_name']) ?>"></div>
      <div class="field"><label>State Admin password (empty system)</label><input type="password" name="admin_pass" autocomplete="new-password">
        <div class="hint">At least 10 characters with letters and numbers.</div></div>
    </div>
    <label class="mt" style="display:block"><input type="checkbox" name="reset" value="1" <?= $in['reset'] ? 'checked' : '' ?>>
      Delete existing tables in this database first (use this after an unfinished installation; all data in this database is removed)</label>
    <button class="btn btn-block mt" type="submit" style="padding:12px">Install ITI CareerTrack</button>
  </form>
<?php endif; ?>
</main></body></html>
