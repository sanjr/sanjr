<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
require_once __DIR__ . '/inc/seed.php';
if (is_post() && ($_POST['action'] ?? '') === 'starter') {
    csrf_check();
    seed_starter();
    flash('Starter content added: menus, trades, pages and homepage lists. Edit or delete anything you do not need.');
    redirect('/admin/');
}
$pdo = db();
$c = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$liveNow = $c('SELECT COUNT(*) FROM visitors_live WHERE last_seen >= ' . (time() - 300));
$visToday = $c("SELECT COUNT(DISTINCT vid) FROM visits WHERE day = '" . date('Y-m-d') . "'");
$stats = [
    ['Live visitors now', $liveNow, '/admin/visitors.php', 'globe', 'c-green'],
    ['Visitors today', $visToday, '/admin/visitors.php', 'users', 'c-orange'],
    ['Trades', $c('SELECT COUNT(*) FROM trades'), '/admin/trades.php', 'tool', 'c-blue'],
    ['Trade content items', $c('SELECT COUNT(*) FROM trade_items'), '/admin/trades.php', 'list', 'c-green'],
    ['Open jobs', $c('SELECT COUNT(*) FROM jobs j WHERE ' . job_active_sql()), '/admin/jobs.php?show=open', 'brief', 'c-orange'],
    ['Pages & articles', $c('SELECT COUNT(*) FROM pages'), '/admin/pages.php', 'doc', 'c-purple'],
    ['Students', $c('SELECT COUNT(*) FROM students'), '/admin/students.php', 'users', 'c-blue'],
    ['Exams taken', $c('SELECT COUNT(*) FROM attempts'), '/admin/students.php', 'clip', 'c-green'],
    ['Tests solved today', $c("SELECT COUNT(*) FROM attempts WHERE created_at >= '" . date('Y-m-d') . "'"), '/admin/students.php', 'clip', 'c-orange'],
];
$recent = $pdo->query('SELECT id, name, slug, updated_at FROM trades ORDER BY updated_at DESC LIMIT 5')->fetchAll();
$sampleIds = array_filter(array_map('intval', explode(',', setting('sample_job_ids'))));
$samples = $sampleIds ? (int)$pdo->query('SELECT COUNT(*) FROM jobs WHERE updated_at = posted_at AND id IN (' . implode(',', $sampleIds) . ')')->fetchColumn() : 0;
/** PHP versions with the PHP2Shell fix (released 21 Oct 2026); older branches got no official release. */
function php2shell_patched(): bool
{
    $fixed = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];
    $branch = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    if (version_compare($branch, '8.5', '>')) return true;
    return isset($fixed[$branch]) && version_compare(PHP_VERSION, $fixed[$branch], '>=');
}

/* Security check of the live server (the web request to /data/ is cached for 6 hours) */
// Latest PHP errors of the website (data/php-errors.log), so a "Something went wrong" page can be traced
$errLog = dirname(__DIR__) . '/data/php-errors.log';
if (is_post() && ($_POST['action'] ?? '') === 'clearlog') { csrf_check(); @file_put_contents($errLog, ''); flash('Error list cleared.'); redirect('/admin/#errors'); }
$errors = [];
if (is_file($errLog) && ($fh = @fopen($errLog, 'r'))) {
    fseek($fh, max(0, filesize($errLog) - 20000));
    $tail = (string)stream_get_contents($fh);
    fclose($fh);
    preg_match_all('/^\[([^\]]+)\] (PHP (?:Fatal|Parse)[^\n]*|Uncaught[^\n]*|ITIExam:[^\n]*)$/m', $tail, $mm, PREG_SET_ORDER);
    foreach (array_slice(array_reverse($mm), 0, 8) as $m) $errors[] = [$m[1], str_replace(dirname(__DIR__) . '/', '', $m[2])];
}
if (is_post() && ($_POST['action'] ?? '') === 'recheck') { csrf_check(); setting_set('sec_probe', ''); redirect('/admin/#security'); }
$probe = json_decode(setting('sec_probe'), true);
if (!is_array($probe) || ($probe['at'] ?? 0) < time() - 6 * 3600) {
    $code = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init(SITE_URL . '/data/itiexam.sqlite');
        curl_setopt_array($ch, [CURLOPT_NOBODY => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_FOLLOWLOCATION => false]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    $probe = ['at' => time(), 'data' => $code];
    setting_set('sec_probe', json_encode($probe));
}
// Every file from the same update? (an old file next to new ones breaks pages, e.g. "Something went wrong" on tests)
$root = dirname(__DIR__);
$stale = array_merge(
    array_filter(['tests_need_login', 'mysql_connect_db', 'trade_years', 'shown_level', 'cbt_play_url'], fn($f) => !function_exists($f)),
    array_filter(['admin/database.php', 'admin/visitors.php', 'api-hit.php', 'includes/visits.php', 'assets/css/cbt-skin.css', 'assets/js/cbt-bridge.js'], fn($f) => !is_file("$root/$f")),
    !str_contains((string)@file_get_contents("$root/assets/css/style.css"), '.cbt-gate-mr') ? ['assets/css/style.css'] : []
);
$checks = [
    [!$stale, 'All website files are from the latest update',
        'Some files on the server are old (' . implode(', ', $stale) . '). Upload the full website ZIP again and choose “Replace” for every file – your data is not touched.'],
    [is_https(), 'HTTPS (SSL) is on', 'Turn on the free SSL certificate in your hosting panel.'],
    [$probe['data'] === 0 ? null : !in_array($probe['data'], [200, 206], true), 'Database and private files cannot be downloaded',
        $probe['data'] === 0 ? 'Could not test from the server – open ' . SITE_URL . '/data/itiexam.sqlite in a browser: it must show “Forbidden” or “Not found”.'
        : 'DANGER: the data folder is public. Your hosting ignores .htaccess files – ask hosting support to block the /data/ folder.'],
    [!filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN), 'PHP errors are hidden from visitors', 'Turn off “display_errors” in the hosting PHP settings.'],
    [!is_file(dirname(DB_SQLITE_PATH) . '/reset.txt'), 'No password-reset file is left on the server', 'Delete data/reset.txt in the File Manager.'],
    [php2shell_patched(), 'PHP ' . PHP_VERSION . ' is patched against PHP2Shell (CVE-2026-109618 / 109619)',
        'URGENT: this PHP version has the PHP2Shell security hole. In the hosting panel choose PHP 8.3 or 8.4 and make sure it is 8.2.35 / 8.3.36 / 8.4.27 / 8.5.12 or newer, or ask hosting support to update PHP.'],
    [!filter_var(ini_get('session.upload_progress.enabled'), FILTER_VALIDATE_BOOLEAN), 'PHP2Shell attack path (session upload progress) is off',
        'Ask hosting support to set session.upload_progress.enabled = 0 for this site (extra protection until PHP is updated).'],
    [google_login_ready(), 'Google login for students is set up', 'Add the Google Client ID and Secret in Settings.'],
];
$empty = !$c('SELECT COUNT(*) FROM trades') || !$c('SELECT COUNT(*) FROM menu_items') || !$c('SELECT COUNT(*) FROM pages');
admin_header('Dashboard', 'dashboard');
?>
<?php if ($stale): ?>
    <div class="flash err"><b>Old files on the server</b> – some pages can show “Something went wrong” (<?= e(implode(', ', $stale)) ?>). Upload the full website ZIP again and choose “Replace” for every file; your data is not touched.</div>
<?php endif; ?>
<?php if (function_exists('tests_need_login') && !tests_need_login()): ?>
    <div class="flash err"><b>Mock tests are open to everyone</b> – <?= google_login_ready() ? 'the “Students must log in” rule is switched off' : 'Google login for students is not set up yet' ?>. <a href="/admin/settings.php">Settings → Student login (Google)</a></div>
<?php endif; ?>
<?php if ($empty): ?>
    <form method="post" class="flash err starter"><?= csrf_field() ?><input type="hidden" name="action" value="starter">
      <span><b>Your website is missing its starter content</b> (menus, trades or pages). Add the ready-made menus, trades, pages and homepage lists, then edit them as you like.</span>
      <button class="btn">Restore starter content</button>
    </form>
<?php endif; ?>
<?php if ($samples): ?>
    <div class="flash err"><b><?= $samples ?> sample job<?= $samples > 1 ? 's are' : ' is' ?> still on the website.</b> They were added during setup to show the design. <a href="/admin/jobs.php">Edit them with real details or delete them</a> before you launch.</div>
<?php endif; ?>
    <div class="stats">
<?php foreach ($stats as [$l, $n, $u, $i, $col]): ?>
      <a href="<?= $u ?>" class="stat"><span class="chip <?= $col ?>"><?= icon($i) ?></span><b><?= $n ?></b><small><?= $l ?></small></a>
<?php endforeach; ?>
    </div>
    <section class="box" id="security">
      <h2>Security check</h2>
      <ul class="secl">
<?php foreach ($checks as [$ok, $label, $fix]): ?>
        <li class="<?= $ok === null ? 'warn' : ($ok ? 'ok' : 'bad') ?>"><b><?= $ok === null ? '?' : ($ok ? '✓' : '✕') ?></b><span><?= e($label) ?><?php if ($ok !== true): ?><small><?= e($fix) ?></small><?php endif; ?></span></li>
<?php endforeach; ?>
      </ul>
      <form method="post" class="secre"><?= csrf_field() ?><input type="hidden" name="action" value="recheck"><button class="btn sm alt">Check again</button><span class="muted">Last checked <?= e(date('d M, h:i A', $probe['at'])) ?></span></form>
    </section>
<?php if ($errors): ?>
    <section class="box" id="errors">
      <div class="bar"><h2 style="margin:0">Recent website errors</h2>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="clearlog"><button class="btn sm alt">Clear list</button></form></div>
      <p class="muted">Pages that showed “Something went wrong” to someone. “undefined function” or “not found” means an old file is on the server: upload the full website ZIP again with “Replace”.</p>
      <div class="tablebox"><table>
        <thead><tr><th>When</th><th>Error</th></tr></thead>
        <tbody>
<?php foreach ($errors as [$when, $msg]): ?>
          <tr><td class="muted" style="white-space:nowrap"><?= e($when) ?></td><td><code style="white-space:normal"><?= e(mb_strimwidth($msg, 0, 400, '…')) ?></code></td></tr>
<?php endforeach; ?>
        </tbody>
      </table></div>
    </section>
<?php endif; ?>
    <section class="box">
      <h2>Manage your website</h2>
      <div class="manage">
        <a href="/admin/home.php"><?= icon('home') ?><b>Homepage</b><small>Hero text &amp; photo, sections, FAQ</small></a>
        <a href="/admin/trades.php"><?= icon('tool') ?><b>Trades</b><small>Add trades, mock tests, notes, papers</small></a>
        <a href="/admin/cbt.php"><?= icon('clip') ?><b>CBT Tests</b><small>Upload your HTML tests</small></a>
        <a href="/admin/students.php"><?= icon('users') ?><b>Students &amp; Results</b><small>Trade-wise, location, pass rate</small></a>
        <a href="/admin/jobs.php"><?= icon('brief') ?><b>Jobs</b><small>Add, edit, copy, delete jobs</small></a>
        <a href="/admin/pages.php"><?= icon('doc') ?><b>Pages &amp; Articles</b><small>About, blog, exam updates</small></a>
        <a href="/admin/media.php"><?= icon('layout') ?><b>Media Library</b><small>Upload photos &amp; PDFs</small></a>
        <a href="/admin/menus.php"><?= icon('link') ?><b>Menus &amp; Links</b><small>Header, footer, homepage lists</small></a>
        <a href="/admin/employers.php"><?= icon('building') ?><b>Employers</b><small>Company names &amp; logos</small></a>
        <a href="/admin/ads.php"><?= icon('rupee') ?><b>Ads</b><small>Google AdSense</small></a>
        <a href="/admin/settings.php"><?= icon('gear') ?><b>Settings</b><small>Logo, WhatsApp, email, SEO</small></a>
        <a href="/admin/links.php"><?= icon('alert') ?><b>Link Checker</b><small>Find broken links</small></a>
      </div>
    </section>
    <div class="grid2">
      <section class="box">
        <h2>Quick actions</h2>
        <div class="actions">
          <a class="btn" href="/admin/trade-edit.php">+ Add New Trade</a>
          <a class="btn" href="/admin/job-edit.php">+ Add New Job</a>
          <a class="btn alt" href="/admin/page-edit.php?category=article">+ Article</a>
          <a class="btn alt" href="/admin/page-edit.php?category=exam-update">+ Exam Update</a>
          <a class="btn alt" href="/admin/page-edit.php">+ Page</a>
          <a class="btn alt" href="/admin/media.php">⬆ Upload Photos</a>
        </div>
      </section>
      <section class="box">
        <h2>Recently updated trades</h2>
        <table><tbody>
<?php foreach ($recent as $r): ?>
          <tr><td><a href="/admin/trade-content.php?id=<?= $r['id'] ?>"><?= e($r['name']) ?></a></td><td class="muted"><?= e(nice_date($r['updated_at'])) ?></td><td><a href="/trades/<?= e($r['slug']) ?>/" target="_blank">View ↗</a></td></tr>
<?php endforeach; ?>
        </tbody></table>
      </section>
    </div>
<?php admin_footer();
