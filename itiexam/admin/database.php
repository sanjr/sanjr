<?php
/* Admin → Database: move the website from SQLite to MySQL (or back). The data is copied and counted first;
   the website switches only when every table arrived complete. MySQL details are kept in data/db-config.php,
   which website updates never contain. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
@set_time_limit(600);

const DB_CONFIG = __DIR__ . '/../data/db-config.php';
const DB_CONFIG_OFF = __DIR__ . '/../data/db-config.off.php';

/** Tables of a connection (without SQLite's own). */
function dbx_tables(PDO $pdo): array
{
    return is_mysql($pdo)
        ? $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)
        : $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
}

function dbx_columns(PDO $pdo, string $table): array
{
    return is_mysql($pdo)
        ? $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN)
        : array_column($pdo->query('PRAGMA table_info("' . $table . '")')->fetchAll(PDO::FETCH_ASSOC), 'name');
}

function dbx_q(PDO $pdo, string $name): string { return is_mysql($pdo) ? '`' . $name . '`' : '"' . $name . '"'; }

/** Row count of every table. */
function dbx_counts(PDO $pdo): array
{
    $out = [];
    foreach (dbx_tables($pdo) as $t) $out[$t] = (int)$pdo->query('SELECT COUNT(*) FROM ' . dbx_q($pdo, $t))->fetchColumn();
    return $out;
}

/** Copies every table of $src into $dst (its rows are replaced) and returns [table => [rows in src, rows in dst]]. */
function dbx_copy(PDO $src, PDO $dst): array
{
    db_migrate($dst);                                   // same tables and columns as the website expects
    $have = dbx_tables($dst);
    $tables = dbx_tables($src);
    foreach ($tables as $t) if (!in_array($t, $have, true)) throw new RuntimeException("Table $t is missing in the new database.");
    $my = is_mysql($dst);
    $dst->exec($my ? 'SET FOREIGN_KEY_CHECKS = 0' : 'PRAGMA foreign_keys = OFF');
    $dst->beginTransaction();
    try {
        foreach ($tables as $t) {
            $dst->exec('DELETE FROM ' . dbx_q($dst, $t));
            $cols = array_values(array_intersect(dbx_columns($src, $t), dbx_columns($dst, $t)));
            if (!$cols) continue;
            $list = implode(', ', array_map(fn($c) => dbx_q($dst, $c), $cols));
            $one = '(' . implode(', ', array_fill(0, count($cols), '?')) . ')';
            $per = max(1, intdiv(900, count($cols)));          // stays under every database's limit of values per statement
            $rows = $src->query('SELECT ' . implode(', ', array_map(fn($c) => dbx_q($src, $c), $cols)) . ' FROM ' . dbx_q($src, $t));
            $batch = [];
            $flush = function () use (&$batch, $dst, $t, $list, $one) {
                if (!$batch) return;
                $dst->prepare('INSERT INTO ' . dbx_q($dst, $t) . " ($list) VALUES " . implode(', ', array_fill(0, count($batch), $one)))
                    ->execute(array_merge(...$batch));
                $batch = [];
            };
            foreach ($rows as $r) { $batch[] = array_values($r); if (count($batch) >= $per) $flush(); }
            $flush();
        }
        $dst->commit();
    } catch (Throwable $e) {
        $dst->rollBack();
        throw $e;
    } finally {
        $dst->exec($my ? 'SET FOREIGN_KEY_CHECKS = 1' : 'PRAGMA foreign_keys = ON');
    }
    $a = dbx_counts($src); $b = dbx_counts($dst);
    $out = [];
    foreach ($tables as $t) $out[$t] = [$a[$t], $b[$t] ?? -1];
    return $out;
}

/** Writes data/db-config.php (only ever replaced as a whole, so a half-written file is never read). */
function dbx_save_config(array $c): void
{
    $tmp = DB_CONFIG . '.tmp';
    $php = "<?php\n// MySQL details for the website (written by Admin → Database). Keep this file private.\nreturn " . var_export($c, true) . ";\n";
    if (file_put_contents($tmp, $php, LOCK_EX) === false || !rename($tmp, DB_CONFIG)) throw new RuntimeException('Could not write data/db-config.php – check that the data folder is writable.');
    @chmod(DB_CONFIG, 0640);
    if (function_exists('opcache_invalidate')) @opcache_invalidate(DB_CONFIG, true);
}

$form = ['host' => trim($_POST['host'] ?? 'localhost'), 'name' => trim($_POST['name'] ?? ''), 'user' => trim($_POST['user'] ?? '')];
$report = null; $err = '';
if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'test' || $action === 'move') {
            if (DB_DRIVER === 'mysql') throw new RuntimeException('The website already uses MySQL.');
            if ($form['name'] === '' || $form['user'] === '') throw new RuntimeException('Fill in the database name and the username.');
            try {
                $my = mysql_connect_db($form['host'] ?: 'localhost', $form['name'], $form['user'], (string)($_POST['pass'] ?? ''));
            } catch (PDOException $e) {
                throw new RuntimeException('Could not connect to MySQL: ' . preg_replace('/^SQLSTATE\[[^\]]*\]\s*(\[\d+\]\s*)?/', '', $e->getMessage()) . ' Check the name, username and password in hPanel → Databases.');
            }
            $filled = array_sum(dbx_counts($my));
            if ($action === 'test') {
                flash('✔ Connected to MySQL ' . $my->query('SELECT VERSION()')->fetchColumn() . '. ' . ($filled ? "This database already holds $filled rows – they will be replaced." : 'The database is empty and ready.'));
            } else {
                if ($filled && empty($_POST['replace'])) throw new RuntimeException("This MySQL database already holds $filled rows. Tick “Replace what is in this MySQL database” to overwrite them.");
                $report = dbx_copy(db(), $my);
                $bad = array_filter($report, fn($c) => $c[0] !== $c[1]);
                if ($bad) throw new RuntimeException('Not all rows were copied (' . implode(', ', array_keys($bad)) . '). The website stays on SQLite; nothing was changed.');
                dbx_save_config(['driver' => 'mysql', 'host' => $form['host'] ?: 'localhost', 'name' => $form['name'], 'user' => $form['user'], 'pass' => (string)($_POST['pass'] ?? '')]);
                @unlink(DB_CONFIG_OFF);
                $_SESSION['dbx_report'] = $report;
                flash('✔ The website now runs on MySQL. ' . count($report) . ' tables and ' . array_sum(array_column($report, 0)) . ' rows were copied and checked. The old SQLite file is kept as a backup.');
                redirect('/admin/database.php');
            }
        } elseif ($action === 'back') {
            if (DB_DRIVER !== 'mysql') throw new RuntimeException('The website already uses SQLite.');
            // Keep the old SQLite file, then fill it with the current MySQL data, so nothing done since the move is lost
            if (is_file(DB_SQLITE_PATH)) copy(DB_SQLITE_PATH, dirname(DB_SQLITE_PATH) . '/itiexam-before-switch-back-' . date('Ymd-His') . '.sqlite');
            $lite = new PDO('sqlite:' . DB_SQLITE_PATH);
            $lite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $lite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $report = dbx_copy(db(), $lite);
            $bad = array_filter($report, fn($c) => $c[0] !== $c[1]);
            if ($bad) throw new RuntimeException('Not all rows were copied (' . implode(', ', array_keys($bad)) . '). The website stays on MySQL.');
            if (!rename(DB_CONFIG, DB_CONFIG_OFF)) throw new RuntimeException('Could not rename data/db-config.php.');
            if (function_exists('opcache_invalidate')) @opcache_invalidate(DB_CONFIG, true);
            $_SESSION['dbx_report'] = $report;
            flash('✔ The website is back on SQLite with all the MySQL data. The MySQL database itself was not changed.');
            redirect('/admin/database.php');
        }
    } catch (Throwable $e) {
        $err = $e instanceof RuntimeException ? $e->getMessage() : 'Error: ' . $e->getMessage();
    }
    if (!$err && $action === 'test') redirect('/admin/database.php?' . http_build_query($form));
}
if (!is_post()) foreach (['host', 'name', 'user'] as $k) if (isset($_GET[$k])) $form[$k] = trim((string)$_GET[$k]);
$report = $report ?? ($_SESSION['dbx_report'] ?? null);
unset($_SESSION['dbx_report']);

$counts = dbx_counts(db());
$mysql = DB_DRIVER === 'mysql';
admin_header('Database', 'database');
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <div class="grid2 wide-left">
      <section class="box">
        <h2>Database in use</h2>
        <div class="dbx-now">
          <span class="chip <?= $mysql ? 'c-green' : 'c-blue' ?>"><?= icon('db') ?></span>
          <div><b><?= $mysql ? 'MySQL' : 'SQLite' ?></b>
            <small><?= $mysql ? e(DB_NAME) . ' on ' . e(DB_HOST) : 'File data/itiexam.sqlite · ' . number_format((is_file(DB_SQLITE_PATH) ? filesize(DB_SQLITE_PATH) : 0) / 1048576, 1) . ' MB' ?></small></div>
        </div>
        <ul class="dbx-stats">
          <li><b><?= number_format($counts['trades'] ?? 0) ?></b><small>Trades &amp; subjects</small></li>
          <li><b><?= number_format($counts['cbt_tests'] ?? 0) ?></b><small>CBT tests</small></li>
          <li><b><?= number_format($counts['students'] ?? 0) ?></b><small>Students</small></li>
          <li><b><?= number_format($counts['attempts'] ?? 0) ?></b><small>Test results</small></li>
          <li><b><?= number_format($counts['jobs'] ?? 0) ?></b><small>Jobs</small></li>
          <li><b><?= number_format(array_sum($counts)) ?></b><small>Rows in all</small></li>
        </ul>
<?php if ($report): ?>
        <h3>Last copy – rows per table</h3>
        <div class="tablebox"><table>
          <thead><tr><th>Table</th><th>Old database</th><th>New database</th><th></th></tr></thead>
          <tbody>
<?php foreach ($report as $t => [$a, $b]): ?>
            <tr><td><code><?= e($t) ?></code></td><td><?= number_format($a) ?></td><td><?= number_format($b) ?></td><td><?= $a === $b ? '✔' : '✖' ?></td></tr>
<?php endforeach; ?>
          </tbody>
        </table></div>
<?php endif; ?>
      </section>

<?php if (!$mysql): ?>
      <section class="box">
        <h2>Move to MySQL</h2>
        <p class="muted">MySQL handles many students saving results at the same time, and Hostinger backs it up for you. Create the database in <b>hPanel → Websites → Databases</b>, then enter its details here. Names on Hostinger start with your account code, e.g. <code>u123456789_newmcq</code>.</p>
        <form method="post" class="form" autocomplete="off">
          <?= csrf_field() ?>
          <label>Host <input name="host" value="<?= e($form['host'] ?: 'localhost') ?>" placeholder="localhost"></label>
          <label>MySQL database name <input name="name" value="<?= e($form['name']) ?>" placeholder="u123456789_newmcq" required></label>
          <label>MySQL username <input name="user" value="<?= e($form['user']) ?>" placeholder="u123456789_newmcq" required></label>
          <label>Password <input type="password" name="pass" autocomplete="new-password"></label>
          <label class="check"><input type="checkbox" name="replace"> Replace what is in this MySQL database (only needed if it is not empty)</label>
          <p class="muted">Copying takes a few seconds. All data is copied, then every table is counted; the website switches only if everything arrived. The SQLite file stays as a backup.</p>
          <div class="bar"><button class="btn alt" name="action" value="test">Test connection</button>
            <button class="btn" name="action" value="move" data-confirm="Copy all data to MySQL and switch the website to it?">Copy data &amp; switch to MySQL</button></div>
        </form>
      </section>
<?php else: ?>
      <section class="box">
        <h2>MySQL is active</h2>
        <p>The website reads and saves everything in MySQL. Its details are kept in <code>data/db-config.php</code>, which is not part of any website update, so uploading new files never switches it back.</p>
        <p class="muted">Back up the database in <b>hPanel → Databases → phpMyAdmin → Export</b>, or use Hostinger's automatic backups.</p>
        <h3>Switch back to SQLite</h3>
        <p class="muted">Only if MySQL gives trouble. The current MySQL data is copied into the SQLite file first (the old file is kept), so nothing is lost.</p>
        <form method="post" data-confirm="Copy the MySQL data into SQLite and switch the website back?"><?= csrf_field() ?><input type="hidden" name="action" value="back">
          <button class="btn alt">Copy data &amp; switch back to SQLite</button></form>
      </section>
<?php endif; ?>
    </div>
    <script>
      // Copying can take a while: show it, and stop a second click
      document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;
        var b = e.submitter;
        if (b && (b.value === 'move' || e.target.querySelector('[name=action][value=back]'))) {
          setTimeout(function () { b.disabled = true; b.textContent = 'Copying data… please wait'; }, 0);
        }
      });
    </script>
<?php admin_footer();
