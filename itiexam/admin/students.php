<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)$_POST['id'];
    db()->prepare('DELETE FROM attempts WHERE student_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
    flash('Student and their results deleted.');
    redirect('/admin/students.php?view=list');
}

/** "Pune, Maharashtra (India)" → ['Pune', 'Maharashtra'] */
function loc_parts(string $loc): array
{
    $loc = trim($loc);
    if ($loc === '') return ['', ''];
    $main = trim(preg_replace('/\s*\(.*$/u', '', $loc));
    $bits = array_map('trim', explode(',', $main));
    return count($bits) > 1 ? [$bits[0], end($bits)] : ['', $bits[0]];
}

$pdo = db();
$view = in_array($_GET['view'] ?? '', ['list', 'student'], true) ? $_GET['view'] : 'report';
$days = in_array((int)($_GET['days'] ?? 0), [7, 30, 90], true) ? (int)$_GET['days'] : 0;
$since = $days ? date('Y-m-d H:i:s', strtotime("-$days day")) : '0000';
$period = $days ? "last $days days" : 'all time';
$pct = fn($a, $b) => $b ? (int)round($a * 100 / $b) : 0;

/* ---------- one student ---------- */
if ($view === 'student') {
    $st = $pdo->prepare('SELECT s.*, t.name AS tname FROM students s LEFT JOIN trades t ON t.id = s.trade_id WHERE s.id = ?');
    $st->execute([(int)($_GET['id'] ?? 0)]);
    $s = $st->fetch();
    if (!$s) redirect('/admin/students.php?view=list');
    $at = $pdo->prepare('SELECT a.*, t.name AS tname FROM attempts a LEFT JOIN trades t ON t.id = a.trade_id WHERE a.student_id = ? ORDER BY a.created_at DESC');
    $at->execute([$s['id']]);
    $rows = $at->fetchAll();
    $passed = count(array_filter($rows, fn($r) => $r['passed']));
    admin_header($s['name'], 'students');
?>
    <div class="bar"><p><a href="/admin/students.php?view=list">← All students</a></p></div>
    <div class="stats">
      <div class="stat"><span class="chip c-blue"><?= icon('users') ?></span><b style="font-size:18px"><?= e($s['name']) ?></b><small><?= e($s['email']) ?></small></div>
      <div class="stat"><span class="chip c-purple"><?= icon('pin') ?></span><b style="font-size:18px"><?= e($s['location'] ?: '–') ?></b><small><?= e($s['tname'] ?: 'Trade not known yet') ?></small></div>
      <div class="stat"><span class="chip c-green"><?= icon('clip') ?></span><b><?= count($rows) ?> / <?= $passed ?></b><small>Exams / passed (<?= $pct($passed, count($rows)) ?>%)</small></div>
      <div class="stat"><span class="chip c-orange"><?= icon('cal') ?></span><b style="font-size:18px"><?= e(date('d M Y', strtotime($s['created_at']))) ?></b><small>Joined · last login <?= e(date('d M Y', strtotime($s['last_login']))) ?></small></div>
    </div>
    <div class="box tablebox"><table>
      <thead><tr><th>Test</th><th>Trade</th><th>Score</th><th>Result</th><th>Time</th><th>Location</th><th>Date</th></tr></thead>
      <tbody>
<?php foreach ($rows as $r): ?>
        <tr><td><b><?= e($r['title']) ?></b><?= $r['level'] ? '<br><small class="muted">' . e($r['level']) . '</small>' : '' ?></td><td><?= e($r['tname'] ?: '–') ?></td>
          <td><?= (int)$r['score'] ?>/<?= (int)$r['total'] ?> (<?= (int)$r['pct'] ?>%)</td><td><span class="pill <?= $r['passed'] ? 'ok' : 'bad' ?>"><?= $r['passed'] ? 'Pass' : 'Fail' ?></span></td>
          <td><?= $r['time_sec'] ? sprintf('%d:%02d', intdiv($r['time_sec'], 60), $r['time_sec'] % 60) : '–' ?></td><td><?= e($r['location'] ?: '–') ?></td><td><?= e(date('d M Y, h:i A', strtotime($r['created_at']))) ?></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="7" class="muted">No tests yet.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
<?php
    admin_footer();
    exit;
}

/* ---------- student list (+ CSV) ---------- */
if ($view === 'list') {
    $q = trim((string)($_GET['q'] ?? ''));
    $tf = (int)($_GET['trade'] ?? 0);
    $sf = trim((string)($_GET['state'] ?? ''));
    $where = []; $args = [];
    if ($q !== '') { $where[] = '(s.name LIKE ? OR s.email LIKE ? OR s.location LIKE ?)'; array_push($args, "%$q%", "%$q%", "%$q%"); }
    if ($tf) { $where[] = 's.trade_id = ?'; $args[] = $tf; }
    $st = $pdo->prepare('SELECT s.*, t.name AS tname, COUNT(a.id) AS tests, COALESCE(SUM(a.passed), 0) AS passed, COALESCE(ROUND(AVG(a.pct)), 0) AS avg, MAX(a.created_at) AS last_test
        FROM students s LEFT JOIN trades t ON t.id = s.trade_id LEFT JOIN attempts a ON a.student_id = s.id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' GROUP BY s.id ORDER BY s.last_login DESC');
    $st->execute($args);
    $rows = $st->fetchAll();
    if ($sf !== '') $rows = array_values(array_filter($rows, fn($r) => strcasecmp(loc_parts($r['location'])[1], $sf) === 0));

    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="students-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Name', 'Email', 'Trade', 'Location', 'Tests', 'Passed', 'Average %', 'Last test', 'Joined']);
        foreach ($rows as $r) fputcsv($out, [$r['name'], $r['email'], $r['tname'], $r['location'], $r['tests'], $r['passed'], $r['tests'] ? $r['avg'] : '', $r['last_test'], $r['created_at']]);
        exit;
    }
    $trades = $pdo->query('SELECT DISTINCT t.id, t.name FROM students s JOIN trades t ON t.id = s.trade_id ORDER BY t.name')->fetchAll();
    $states = [];
    foreach ($pdo->query("SELECT location FROM students WHERE location <> ''")->fetchAll(PDO::FETCH_COLUMN) as $l) $states[loc_parts($l)[1]] = 1;
    ksort($states);
    admin_header('Students', 'students');
?>
    <div class="bar">
      <form class="inline-filter" method="get"><input type="hidden" name="view" value="list">
        <input name="q" value="<?= e($q) ?>" placeholder="Search name, email or city…">
        <select name="trade"><option value="">All trades</option><?php foreach ($trades as $t): ?><option value="<?= $t['id'] ?>"<?= $tf === (int)$t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select>
        <select name="state"><option value="">All states</option><?php foreach (array_keys($states) as $x): ?><option<?= $sf === $x ? ' selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?></select>
        <button class="btn alt">Filter</button>
      </form>
      <a class="btn alt" href="/admin/students.php">← Report</a>
      <a class="btn" href="?<?= e(http_build_query(['view' => 'list', 'q' => $q, 'trade' => $tf ?: '', 'state' => $sf, 'export' => 'csv'])) ?>">⬇ Download Excel (CSV)</a>
    </div>
    <p class="muted"><b><?= count($rows) ?></b> students</p>
    <div class="box tablebox"><table>
      <thead><tr><th>Student</th><th>Trade</th><th>Location</th><th>Tests</th><th>Passed</th><th>Average</th><th>Last test</th><th>Joined</th><th></th></tr></thead>
      <tbody>
<?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="?view=student&amp;id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a><br><small class="muted"><?= e($r['email']) ?></small></td>
          <td><?= e($r['tname'] ?: '–') ?></td>
          <td><?= e($r['location'] ?: '–') ?></td>
          <td><?= (int)$r['tests'] ?></td>
          <td><?= (int)$r['passed'] ?></td>
          <td><?= $r['tests'] ? (int)$r['avg'] . '%' : '–' ?></td>
          <td><?= $r['last_test'] ? e(date('d M Y', strtotime($r['last_test']))) : '–' ?></td>
          <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
          <td class="acts"><a class="btn sm alt" href="?view=student&amp;id=<?= $r['id'] ?>">View</a>
            <form method="post" data-confirm="Delete <?= e($r['name']) ?> and all their test results?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form></td>
        </tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="9" class="muted">No students found.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
<?php
    admin_footer();
    exit;
}

/* ---------- report ---------- */
$one = function (string $sql, array $a = []) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($a); return $s->fetch(PDO::FETCH_NUM); };
[$students] = $one('SELECT COUNT(*) FROM students');
[$newS] = $one('SELECT COUNT(*) FROM students WHERE created_at >= ?', [$since]);
[$exams, $passed, $active, $avg] = $one('SELECT COUNT(*), COALESCE(SUM(passed), 0), COUNT(DISTINCT student_id), COALESCE(ROUND(AVG(pct)), 0) FROM attempts WHERE created_at >= ?', [$since]);

$byTrade = $pdo->prepare("SELECT COALESCE(t.name, 'Other') AS name, COUNT(*) AS exams, COUNT(DISTINCT a.student_id) AS students, SUM(a.passed) AS passed, ROUND(AVG(a.pct)) AS avg
    FROM attempts a LEFT JOIN trades t ON t.id = a.trade_id WHERE a.created_at >= ? GROUP BY a.trade_id, t.name ORDER BY exams DESC");
$byTrade->execute([$since]);
$byTrade = $byTrade->fetchAll();

$byTest = $pdo->prepare("SELECT a.title, a.level, COALESCE(t.name, '') AS tname, COUNT(*) AS exams, COUNT(DISTINCT a.student_id) AS students, SUM(a.passed) AS passed, ROUND(AVG(a.pct)) AS avg
    FROM attempts a LEFT JOIN trades t ON t.id = a.trade_id WHERE a.created_at >= ? GROUP BY a.test_id, a.title, a.level, t.name ORDER BY exams DESC LIMIT 15");
$byTest->execute([$since]);
$byTest = $byTest->fetchAll();

// Location: states, then cities (each student counted once per place)
$loc = $pdo->prepare('SELECT location, student_id, COUNT(*) AS exams, SUM(passed) AS passed FROM attempts WHERE created_at >= ? GROUP BY location, student_id');
$loc->execute([$since]);
$states = []; $cities = [];
foreach ($loc->fetchAll() as $r) {
    [$city, $state] = loc_parts($r['location']);
    $state = $state ?: 'Not known';
    $states[$state] ??= ['s' => [], 'exams' => 0, 'passed' => 0];
    $states[$state]['s'][$r['student_id']] = 1; $states[$state]['exams'] += $r['exams']; $states[$state]['passed'] += $r['passed'];
    if ($city !== '') {
        $k = $city . ', ' . $state;
        $cities[$k] ??= ['s' => [], 'exams' => 0];
        $cities[$k]['s'][$r['student_id']] = 1; $cities[$k]['exams'] += $r['exams'];
    }
}
uasort($states, fn($a, $b) => $b['exams'] <=> $a['exams']);
uasort($cities, fn($a, $b) => $b['exams'] <=> $a['exams']);
$cities = array_slice($cities, 0, 12, true);

// Exams per day, last 14 days
$daily = [];
for ($i = 13; $i >= 0; $i--) $daily[date('Y-m-d', strtotime("-$i day"))] = 0;
$d = $pdo->prepare('SELECT SUBSTR(created_at, 1, 10) AS d, COUNT(*) FROM attempts WHERE created_at >= ? GROUP BY d');
$d->execute([array_key_first($daily)]);
foreach ($d->fetchAll(PDO::FETCH_KEY_PAIR) as $k => $n) if (isset($daily[$k])) $daily[$k] = (int)$n;
$max = max(1, max($daily));

admin_header('Students & Results', 'students');
?>
    <div class="bar">
      <div class="seg">
<?php foreach ([0 => 'All time', 90 => '90 days', 30 => '30 days', 7 => '7 days'] as $k => $l): ?>
        <a href="?days=<?= $k ?>" class="<?= $days === $k ? 'on' : '' ?>"><?= $l ?></a>
<?php endforeach; ?>
      </div>
      <a class="btn" href="/admin/students.php?view=list"><?= icon('users') ?> All students</a>
    </div>
<?php if (!google_login_ready()): ?>
    <div class="flash err">Google login is <b>off</b>, so students cannot sign in yet. Turn it on in <a href="/admin/settings.php#google-login">Settings</a>.</div>
<?php endif; ?>
    <div class="stats">
      <a class="stat" href="?view=list"><span class="chip c-blue"><?= icon('users') ?></span><b><?= (int)$students ?></b><small>Students (<?= (int)$newS ?> new, <?= e($period) ?>)</small></a>
      <div class="stat"><span class="chip c-purple"><?= icon('target') ?></span><b><?= (int)$active ?></b><small>Students who took tests</small></div>
      <div class="stat"><span class="chip c-orange"><?= icon('clip') ?></span><b><?= (int)$exams ?></b><small>Exams taken</small></div>
      <div class="stat"><span class="chip c-green"><?= icon('star') ?></span><b><?= (int)$passed ?></b><small>Passed · <?= $pct($passed, $exams) ?>% pass rate · avg <?= (int)$avg ?>%</small></div>
    </div>

    <section class="box">
      <h2>Exams per day <small class="muted">last 14 days</small></h2>
      <div class="dbars">
<?php foreach ($daily as $k => $n): ?>
        <div title="<?= e(date('D d M', strtotime($k))) ?>: <?= $n ?> exam<?= $n === 1 ? '' : 's' ?>"><span><?= $n ?: '' ?></span><i style="height:<?= $n ? max(4, round($n * 100 / $max)) : 0 ?>%"></i><small><?= date('d', strtotime($k)) ?></small></div>
<?php endforeach; ?>
      </div>
    </section>

    <section class="box tablebox">
      <h2>Trade-wise <small class="muted"><?= e($period) ?></small></h2>
      <table>
        <thead><tr><th>Trade</th><th>Students</th><th>Exams</th><th>Passed</th><th>Failed</th><th>Pass rate</th><th>Average</th></tr></thead>
        <tbody>
<?php foreach ($byTrade as $r): $pr = $pct($r['passed'], $r['exams']); ?>
          <tr><td><b><?= e($r['name']) ?></b></td><td><?= (int)$r['students'] ?></td><td><?= (int)$r['exams'] ?></td><td><?= (int)$r['passed'] ?></td><td><?= $r['exams'] - $r['passed'] ?></td>
            <td><span class="meter"><i style="width:<?= $pr ?>%"></i></span> <?= $pr ?>%</td><td><?= (int)$r['avg'] ?>%</td></tr>
<?php endforeach; ?>
<?php if (!$byTrade): ?><tr><td colspan="7" class="muted">No exams yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </section>

    <div class="grid2">
      <section class="box tablebox">
        <h2>Location – states</h2>
        <table>
          <thead><tr><th>State</th><th>Students</th><th>Exams</th><th>Pass rate</th></tr></thead>
          <tbody>
<?php foreach ($states as $name => $r): ?>
            <tr><td><?= $name === 'Not known' ? '<span class="muted">Not known</span>' : '<a href="?view=list&amp;state=' . e(rawurlencode($name)) . '">' . e($name) . '</a>' ?></td><td><?= count($r['s']) ?></td><td><?= $r['exams'] ?></td><td><?= $pct($r['passed'], $r['exams']) ?>%</td></tr>
<?php endforeach; ?>
<?php if (!$states): ?><tr><td colspan="4" class="muted">No exams yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </section>
      <section class="box tablebox">
        <h2>Top cities</h2>
        <table>
          <thead><tr><th>City</th><th>Students</th><th>Exams</th></tr></thead>
          <tbody>
<?php foreach ($cities as $name => $r): ?>
            <tr><td><?= e($name) ?></td><td><?= count($r['s']) ?></td><td><?= $r['exams'] ?></td></tr>
<?php endforeach; ?>
<?php if (!$cities): ?><tr><td colspan="3" class="muted">No locations yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </section>
    </div>

    <section class="box tablebox">
      <h2>Most taken tests <small class="muted"><?= e($period) ?></small></h2>
      <table>
        <thead><tr><th>Test</th><th>Trade</th><th>Students</th><th>Exams</th><th>Pass rate</th><th>Average</th></tr></thead>
        <tbody>
<?php foreach ($byTest as $r): ?>
          <tr><td><b><?= e($r['title']) ?></b><?= $r['level'] ? '<br><small class="muted">' . e($r['level']) . '</small>' : '' ?></td><td><?= e($r['tname'] ?: '–') ?></td><td><?= (int)$r['students'] ?></td><td><?= (int)$r['exams'] ?></td><td><?= $pct($r['passed'], $r['exams']) ?>%</td><td><?= (int)$r['avg'] ?>%</td></tr>
<?php endforeach; ?>
<?php if (!$byTest): ?><tr><td colspan="6" class="muted">No exams yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </section>
    <p class="muted">Location comes from the test itself (the student's internet address or phone GPS), so it is approximate. Results are saved only for students who are logged in.</p>
<?php admin_footer();
