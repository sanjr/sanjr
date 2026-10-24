<?php
/* /student/ – the signed-in student's dashboard: totals, charts, full exam history and what to practise next. */
require __DIR__ . '/includes/functions.php';
$me = student();
if (!$me) { header('Location: ' . login_url('/student/')); exit; }
header('Cache-Control: private, no-store');

$st = db()->prepare("SELECT a.*, c.folder, c.status AS cstatus, t.name AS tname, t.duration AS tdur FROM attempts a
    LEFT JOIN cbt_tests c ON c.id = a.test_id LEFT JOIN trades t ON t.id = a.trade_id
    WHERE a.student_id = ? ORDER BY a.created_at DESC, a.id DESC LIMIT 1000");
$st->execute([$me['id']]);
$rows = $st->fetchAll();
foreach ($rows as &$r) $r['level'] = shown_level($r['level'], (string)$r['tdur']);   // one-year trades: no "1st Year"
unset($r);

$full = fn(array $a) => ($a['level'] ? $a['level'] . ' – ' : '') . $a['title'];
$total = count($rows);
$passed = count(array_filter($rows, fn($a) => (int)$a['passed'] === 1));
$failed = $total - $passed;
$avg = $total ? (int)round(array_sum(array_column($rows, 'pct')) / $total) : 0;
$best = null;
foreach ($rows as $a) if (!$best || $a['pct'] > $best['pct']) $best = $a;
$rate = $total ? (int)round($passed * 100 / $total) : 0;

// Test-wise performance: average % of the 8 most recently practised tests
$perf = [];
foreach ($rows as $a) {
    $k = $a['test_id'];
    if (!isset($perf[$k])) { if (count($perf) >= 8) continue; $perf[$k] = ['name' => $full($a), 'sum' => 0, 'n' => 0, 'best' => 0]; }
    $perf[$k]['sum'] += $a['pct']; $perf[$k]['n']++; $perf[$k]['best'] = max($perf[$k]['best'], (int)$a['pct']);
}

// Score trend: the last 10 attempts, oldest first
$trend = array_reverse(array_slice($rows, 0, 10));

// Study streak (days in a row with at least one test)
$days = array_flip(array_map(fn($a) => substr($a['created_at'], 0, 10), $rows));
$streak = 0;
$d = isset($days[date('Y-m-d')]) ? time() : strtotime('-1 day');
while (isset($days[date('Y-m-d', $d)])) { $streak++; $d = strtotime('-1 day', $d); }
$bestStreak = 0; $run = 0; $prev = null;
foreach (array_reverse(array_keys($days)) as $day) {
    $run = $prev && strtotime($day) - strtotime($prev) <= 90000 ? $run + 1 : 1;
    $bestStreak = max($bestStreak, $run); $prev = $day;
}

// Recommended next test: a test of the student's trade not tried yet, else the weakest one to retake
$tradeId = (int)($me['trade_id'] ?: ($rows[0]['trade_id'] ?? 0));
$next = null; $nextWhy = '';
if ($tradeId) {
    $q = db()->prepare("SELECT c.*, t.name AS tname, t.duration AS tdur FROM cbt_tests c LEFT JOIN trades t ON t.id = c.trade_id
        WHERE c.trade_id = ? AND c.status = 'published' AND c.id NOT IN (SELECT test_id FROM attempts WHERE student_id = ?)");
    $q->execute([$tradeId, $me['id']]);
    $todo = $q->fetchAll();
    usort($todo, fn($x, $y) => strnatcasecmp($x['level'] . ' ' . $x['title'], $y['level'] . ' ' . $y['title']));
    if ($todo) { $next = $todo[0]; $nextWhy = 'You have not tried this test yet.'; }
}
if (!$next && $perf) {
    $weak = null;
    foreach ($rows as $a) if ($a['folder'] && $a['cstatus'] === 'published' && (!$weak || $a['pct'] < $weak['pct'])) $weak = $a;
    if ($weak && $weak['pct'] < 90) { $next = ['folder' => $weak['folder'], 'title' => $weak['title'], 'level' => $weak['level'], 'tname' => $weak['tname'], 'tdur' => $weak['tdur'], 'questions' => 0, 'minutes' => 0]; $nextWhy = 'Your lowest score was ' . (int)$weak['pct'] . '% – try again to improve it.'; }
}
if (!$next && !$total) {
    $next = db()->query("SELECT c.*, t.name AS tname, t.duration AS tdur FROM cbt_tests c LEFT JOIN trades t ON t.id = c.trade_id WHERE c.status = 'published' ORDER BY c.created_at DESC LIMIT 1")->fetch() ?: null;
    $nextWhy = 'Start with a free mock test.';
}

if ($next) $next['level'] = shown_level($next['level'] ?? '', (string)($next['tdur'] ?? ''));
$myTrade = null;
if ($tradeId) { $q = db()->prepare('SELECT name, slug FROM trades WHERE id = ?'); $q->execute([$tradeId]); $myTrade = $q->fetch() ?: null; }

$badges = [
    ['🎯', 'First Step', 'Finish your first test', $total >= 1],
    ['✅', 'First Pass', 'Pass any test', $passed >= 1],
    ['📚', 'Regular', 'Finish 5 tests', $total >= 5],
    ['🏅', 'Dedicated', 'Finish 25 tests', $total >= 25],
    ['🌟', 'High Scorer', 'Score 90% or more', $best && $best['pct'] >= 90],
    ['💯', 'Perfect', 'Score 100% in a test', $best && $best['pct'] >= 100],
    ['🔥', 'On Fire', '3-day study streak', $bestStreak >= 3],
    ['🏆', 'Champion', '7-day study streak', $bestStreak >= 7],
];
$earned = count(array_filter($badges, fn($b) => $b[3]));

$trades = array_values(array_unique(array_filter(array_column($rows, 'tname'))));
$mmss = fn(int $s) => $s ? sprintf('%d:%02d', intdiv($s, 60), $s % 60) : '–';
$first = strtok($me['name'], ' ');

$meta = ['title' => 'My Dashboard | ' . SITE_NAME, 'description' => 'Your ITI mock test results and progress.', 'robots' => 'noindex, nofollow', 'path' => '/student/', 'body' => 'st-dash'];
require __DIR__ . '/includes/header.php';
?>
<main id="main" class="sd">
  <section class="sd-hero">
    <div class="container sd-hero-in">
      <?= student_avatar($me, 'sd-av') ?>
      <div class="sd-hi">
        <h1><?= isset($_GET['welcome']) ? 'Welcome' : 'Welcome back' ?>, <?= e($first) ?> 👋</h1>
        <p><?= $myTrade ? e($myTrade['name']) . ' · ' : '' ?>Member since <?= e(date('M Y', strtotime($me['created_at']))) ?><?= $streak ? ' · 🔥 ' . $streak . '-day streak' : '' ?></p>
      </div>
      <div class="sd-hero-act">
        <a class="btn-sm big" href="<?= $myTrade ? '/trades/' . e($myTrade['slug']) . '/#mock-tests' : '/mock-test/' ?>"><?= icon('play') ?> Start a Test</a>
        <a class="btn-sm big ghost-w" href="<?= e(logout_url()) ?>">Logout</a>
      </div>
    </div>
    <nav class="container sd-tabs" aria-label="Dashboard sections">
      <a href="#overview" class="on">Overview</a><a href="#history">Exam History</a><a href="#achievements">Achievements</a>
    </nav>
  </section>

  <div class="container sd-body">
<?php if (!$total): ?>
    <div class="card sd-empty">
      <div class="sd-empty-i">📝</div>
      <h2>No tests yet</h2>
      <p>Take any free CBT mock test – your score, history and progress will appear here automatically.</p>
<?php if ($next): ?>      <a class="btn-sm big" href="<?= e(cbt_url($next)) ?>"><?= icon('play') ?> Start: <?= e(($next['level'] ? $next['level'] . ' – ' : '') . $next['title']) ?></a>
<?php endif; ?>
    </div>
<?php else: ?>
    <section id="overview" class="sd-stats" aria-label="Summary">
      <div class="sd-stat"><span class="sd-si c-blue"><?= icon('clip') ?></span><b><?= $total ?></b><small>Total Exams</small></div>
      <div class="sd-stat"><span class="sd-si c-green">✓</span><b><?= $passed ?></b><small>Passed · <?= $rate ?>% success</small></div>
      <div class="sd-stat"><span class="sd-si c-orange">✗</span><b><?= $failed ?></b><small>Failed</small></div>
      <div class="sd-stat"><span class="sd-si c-purple"><?= icon('target') ?></span><b><?= $avg ?>%</b><small>Average Score</small></div>
      <div class="sd-stat"><span class="sd-si c-amber"><?= icon('star') ?></span><b><?= (int)$best['pct'] ?>%</b><small title="<?= e($full($best)) ?>">Highest · <?= e($best['title']) ?></small></div>
    </section>

    <div class="sd-grid">
      <section class="card sd-card sd-span2" aria-labelledby="perf-h">
        <h2 id="perf-h">Test-wise Performance <small>average score</small></h2>
        <ul class="sd-bars">
<?php foreach ($perf as $p): $v = (int)round($p['sum'] / $p['n']); ?>
          <li title="<?= e($p['name']) ?> – average <?= $v ?>%, best <?= $p['best'] ?>%, <?= $p['n'] ?> attempt<?= $p['n'] > 1 ? 's' : '' ?>">
            <span class="sd-bl"><?= e($p['name']) ?></span>
            <span class="sd-bt"><i style="width:<?= max(2, $v) ?>%"></i></span>
            <b><?= $v ?>%</b>
          </li>
<?php endforeach; ?>
        </ul>
      </section>

      <section class="card sd-card" aria-labelledby="pf-h">
        <h2 id="pf-h">Pass / Fail</h2>
<?php $c = 2 * M_PI * 40; $pl = $total ? $c * $passed / $total : 0; $gap = $passed && $failed ? 2 : 0; ?>
        <div class="sd-donut">
          <svg viewBox="0 0 100 100" role="img" aria-label="<?= $passed ?> passed, <?= $failed ?> failed">
            <circle cx="50" cy="50" r="40" fill="none" stroke="var(--line)" stroke-width="12"/>
<?php if ($passed): ?>            <circle cx="50" cy="50" r="40" fill="none" stroke="#1f4fd6" stroke-width="12" stroke-dasharray="<?= round(max(0, $pl - $gap), 2) ?> <?= round($c, 2) ?>" transform="rotate(-90 50 50)"><title>Passed: <?= $passed ?></title></circle>
<?php endif; if ($failed): ?>            <circle cx="50" cy="50" r="40" fill="none" stroke="#f7740f" stroke-width="12" stroke-dasharray="<?= round(max(0, $c - $pl - $gap), 2) ?> <?= round($c, 2) ?>" stroke-dashoffset="<?= round(-$pl, 2) ?>" transform="rotate(-90 50 50)"><title>Failed: <?= $failed ?></title></circle>
<?php endif; ?>
          </svg>
          <div class="sd-dc"><b><?= $rate ?>%</b><small>success</small></div>
        </div>
        <ul class="sd-legend">
          <li><i style="background:#1f4fd6"></i> Passed <b><?= $passed ?></b></li>
          <li><i style="background:#f7740f"></i> Failed <b><?= $failed ?></b></li>
        </ul>
      </section>

      <section class="card sd-card sd-span2" aria-labelledby="tr-h">
        <h2 id="tr-h">Score Trend <small>last <?= count($trend) ?> tests</small></h2>
<?php
$W = 600; $H = 210; $L = 36; $R = 14; $T = 14; $B = 26;
$n = count($trend);
$x = fn($i) => $n > 1 ? $L + ($W - $L - $R) * $i / ($n - 1) : $L + ($W - $L - $R) / 2;
$y = fn($v) => $T + ($H - $T - $B) * (1 - $v / 100);
$pts = [];
foreach ($trend as $i => $a) $pts[] = round($x($i), 1) . ',' . round($y($a['pct']), 1);
?>
        <svg class="sd-trend" viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Score percent of your last <?= $n ?> tests">
<?php foreach ([0, 25, 50, 75, 100] as $g): ?>
          <line x1="<?= $L ?>" x2="<?= $W - $R ?>" y1="<?= $y($g) ?>" y2="<?= $y($g) ?>" class="g"/><text x="<?= $L - 6 ?>" y="<?= $y($g) + 4 ?>" class="ax" text-anchor="end"><?= $g ?></text>
<?php endforeach; ?>
<?php if ($n > 1): ?>          <polygon class="ar" points="<?= round($x(0), 1) ?>,<?= $y(0) ?> <?= implode(' ', $pts) ?> <?= round($x($n - 1), 1) ?>,<?= $y(0) ?>"/>
          <polyline class="ln" points="<?= implode(' ', $pts) ?>"/>
<?php endif; foreach ($trend as $i => $a): ?>
          <g class="pt"><circle cx="<?= round($x($i), 1) ?>" cy="<?= round($y($a['pct']), 1) ?>" r="4.5"/><circle class="hit" cx="<?= round($x($i), 1) ?>" cy="<?= round($y($a['pct']), 1) ?>" r="14"><title><?= e($full($a)) ?>&#10;<?= e(date('d M Y', strtotime($a['created_at']))) ?> · <?= (int)$a['score'] ?>/<?= (int)$a['total'] ?> (<?= (int)$a['pct'] ?>%)</title></circle></g>
<?php endforeach; $la = end($trend); ?>
          <text x="<?= round($x($n - 1), 1) ?>" y="<?= round($y($la['pct']), 1) - 10 ?>" class="lv" text-anchor="<?= $n > 1 ? 'end' : 'middle' ?>"><?= (int)$la['pct'] ?>%</text>
        </svg>
      </section>

      <section class="card sd-card" aria-labelledby="sk-h">
        <h2 id="sk-h">Study Streak</h2>
        <p class="sd-big">🔥 <b><?= $streak ?></b> day<?= $streak === 1 ? '' : 's' ?></p>
        <ol class="sd-week">
<?php for ($i = 6; $i >= 0; $i--): $dd = strtotime("-$i day"); $on = isset($days[date('Y-m-d', $dd)]); ?>
          <li class="<?= $on ? 'on' : '' ?>" title="<?= date('D d M', $dd) ?><?= $on ? ' – practised' : '' ?>"><i><?= $on ? '✓' : '' ?></i><small><?= substr(date('D', $dd), 0, 2) ?></small></li>
<?php endfor; ?>
        </ol>
        <p class="sd-note">Best streak: <?= $bestStreak ?> day<?= $bestStreak === 1 ? '' : 's' ?>. Practise daily to keep it going!</p>
      </section>
    </div>

<?php if ($next): ?>
    <section class="card sd-next" aria-labelledby="nx-h">
      <span class="sd-next-i">🚀</span>
      <div><h2 id="nx-h">Recommended Next Test</h2><b><?= e(($next['tname'] ? $next['tname'] . ' · ' : '') . ($next['level'] ? $next['level'] . ' – ' : '') . $next['title']) ?></b><small><?= e($nextWhy) ?></small></div>
      <a class="btn-sm big" href="<?= e(cbt_url($next)) ?>"><?= icon('play') ?> Start Test</a>
    </section>
<?php endif; ?>

    <section class="card sd-card" id="history" aria-labelledby="hi-h">
      <h2 id="hi-h">Exam History <small><?= $total ?> test<?= $total === 1 ? '' : 's' ?></small></h2>
      <div class="sd-filters">
        <input type="search" id="hf-q" placeholder="Search test…" aria-label="Search test">
<?php if (count($trades) > 1): ?>        <select id="hf-t" aria-label="Trade"><option value="">All trades</option><?php foreach ($trades as $t): ?><option><?= e($t) ?></option><?php endforeach; ?></select>
<?php endif; ?>
        <select id="hf-s" aria-label="Result"><option value="">All results</option><option value="1">Passed</option><option value="0">Failed</option></select>
      </div>
      <div class="sd-tw">
        <table class="sd-table">
          <thead><tr><th>Test</th><th>Date</th><th>Score</th><th>Result</th><th>Time</th><th></th></tr></thead>
          <tbody>
<?php foreach ($rows as $a): ?>
            <tr data-q="<?= e(mb_strtolower($full($a) . ' ' . $a['tname'])) ?>" data-t="<?= e((string)$a['tname']) ?>" data-s="<?= (int)$a['passed'] ?>">
              <td class="sd-tn"><b><?= e($a['title']) ?></b><small><?= e(implode(' · ', array_filter([$a['tname'], $a['level']]))) ?></small></td>
              <td data-l="Date"><?= e(date('d M Y, h:i A', strtotime($a['created_at']))) ?></td>
              <td data-l="Score"><b><?= (int)$a['score'] ?></b>/<?= (int)$a['total'] ?> <span class="sd-pct">(<?= (int)$a['pct'] ?>%)</span></td>
              <td data-l="Result"><span class="sd-badge <?= $a['passed'] ? 'pass' : 'fail' ?>"><?= $a['passed'] ? '✓ Pass' : '✗ Fail' ?></span></td>
              <td data-l="Time"><?= $mmss((int)$a['time_sec']) ?></td>
              <td><?php if ($a['folder'] && $a['cstatus'] === 'published'): ?><a class="sd-retake" href="<?= e(cbt_url($a)) ?>">Retake</a><?php endif; ?></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
        <p class="sd-none" id="hf-none" hidden>No tests match these filters.</p>
      </div>
    </section>
<?php endif; ?>

    <section class="card sd-card" id="achievements" aria-labelledby="ac-h">
      <h2 id="ac-h">Achievements <small><?= $earned ?> of <?= count($badges) ?> earned</small></h2>
      <ul class="sd-badges">
<?php foreach ($badges as [$ic, $bn, $bd, $ok]): ?>
        <li class="<?= $ok ? 'got' : '' ?>"><span><?= $ic ?></span><b><?= e($bn) ?></b><small><?= e($bd) ?></small><?= $ok ? '' : '<em>🔒</em>' ?></li>
<?php endforeach; ?>
      </ul>
    </section>
  </div>
</main>
<script>
  (function () {
    var q = document.getElementById("hf-q"), t = document.getElementById("hf-t"), s = document.getElementById("hf-s");
    if (!q) return;
    var rows = [].slice.call(document.querySelectorAll(".sd-table tbody tr")), none = document.getElementById("hf-none");
    function run() {
      var w = q.value.trim().toLowerCase(), tv = t ? t.value : "", sv = s.value, shown = 0;
      rows.forEach(function (r) {
        var ok = (!w || r.dataset.q.indexOf(w) > -1) && (!tv || r.dataset.t === tv) && (!sv || r.dataset.s === sv);
        r.hidden = !ok; if (ok) shown++;
      });
      none.hidden = shown > 0;
    }
    [q, t, s].forEach(function (el) { if (el) el.addEventListener("input", run); });
  })();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
