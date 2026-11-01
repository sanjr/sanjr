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

$errors = [];
$done = null;
$in = [
    'host' => $_POST['host'] ?? '127.0.0.1', 'port' => $_POST['port'] ?? '3306', 'name' => $_POST['name'] ?? 'careertrack',
    'user' => $_POST['user'] ?? 'root', 'pass' => $_POST['pass'] ?? '', 'mode' => $_POST['mode'] ?? 'demo',
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
            $ver = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
            if (stripos($ver, 'mariadb') === false && version_compare($ver, '8.0.16', '<')) {
                throw new RuntimeException("MySQL 8.0.16 or newer is required (server is $ver).");
            }
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $in['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . $in['name'] . '`');
            if ((int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn() > 0) {
                throw new RuntimeException('Database "' . $in['name'] . '" is not empty. Choose a new database name.');
            }
            $files = $in['mode'] === 'demo'
                ? ['demo_data.sql.gz']
                : ['01_schema.sql', '02_routines.sql', '03_reference_data.sql', '05_regions_roles.sql', '06_districts.sql'];
            $count = 0;
            foreach ($files as $f) {
                $path = $dbDir . '/' . $f;
                if (!is_file($path)) {
                    throw new RuntimeException("Missing file database/mysql/$f");
                }
                foreach (ct_statements($path) as $stmt) {
                    $pdo->exec($stmt);
                    $count++;
                }
            }
            if ($in['mode'] === 'empty') {
                $y = (int) date('n') >= 8 ? (int) date('Y') : (int) date('Y') - 1;
                $pdo->prepare("INSERT INTO academic_years (label, start_date, end_date, status) VALUES (?, ?, ?, 'OPEN')")
                    ->execute([$y . '-' . sprintf('%02d', ($y + 1) % 100), "$y-08-01", ($y + 1) . '-07-31']);
                $pdo->prepare("INSERT INTO users (full_name, username, password_hash, status) VALUES (?, ?, ?, 'ACTIVE')")
                    ->execute([mb_substr((string) $in['admin_name'], 0, 150), $in['admin_user'],
                        password_hash($adminPass, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT)]);
                $uid = (int) $pdo->lastInsertId();
                $pdo->prepare("INSERT INTO user_role_assignments (user_id, role_id, scope_type, valid_from) SELECT ?, id, 'STATE', CURDATE() FROM roles WHERE code = 'STATE_ADMIN'")
                    ->execute([$uid]);
            }
            $config = ['app' => ['name' => 'ITI CareerTrack', 'env' => $in['mode'] === 'demo' ? 'demo' : 'production', 'secret' => bin2hex(random_bytes(32)),
                                 'timezone' => 'Asia/Kolkata', 'session_idle_minutes' => 30, 'force_https_cookies' => false],
                       'db' => ['host' => $in['host'], 'port' => (int) $in['port'], 'name' => $in['name'], 'user' => $in['user'], 'pass' => $in['pass']],
                       'storage' => ['documents' => $root . '/storage/documents', 'max_upload_bytes' => 5 * 1024 * 1024]];
            $php = "<?php\n// Written by the ITI CareerTrack installer on " . date('Y-m-d H:i') . ". Keep this file private.\nreturn " . var_export($config, true) . ";\n";
            $written = @file_put_contents($configFile, $php) !== false;
            $done = ['statements' => $count, 'written' => $written, 'php' => $php, 'mode' => $in['mode']];
            session_destroy();
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
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
    <p class="muted">On XAMPP the defaults are usually: host 127.0.0.1, port 3306, user root, empty password. MySQL 8.0.16+ is required.</p>
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
    <button class="btn btn-block mt" type="submit" style="padding:12px">Install ITI CareerTrack</button>
  </form>
<?php endif; ?>
</main></body></html>
