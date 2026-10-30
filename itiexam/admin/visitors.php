<?php
/* Admin → Visitors: who is on the website now, visitors per day, where they come from (Google, WhatsApp …),
   their state and city, the most visited pages and devices. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/../includes/visits.php';
require_login();
$pdo = db();
$today = date('Y-m-d');

/** People active in the last 5 minutes. */
function live_rows(PDO $pdo): array
{
    $st = $pdo->prepare('SELECT * FROM visitors_live WHERE last_seen >= ? ORDER BY last_seen DESC LIMIT 60');
    $st->execute([time() - 300]);
    return $st->fetchAll();
}
function place(array $r): string
{
    $p = array_filter([$r['city'], $r['region']], fn($x) => $x !== '' && $x !== null);
    $p = array_values(array_unique($p));
    return $p ? implode(', ', $p) . ($r['country'] && $r['country'] !== 'India' ? ' (' . $r['country'] . ')' : '') : ($r['country'] ?: 'Finding…');
}
function ago(int $t): string { $s = max(0, time() - $t); return $s < 60 ? 'now' : intdiv($s, 60) . ' min ago'; }

// Live list as JSON for the auto-refresh
if (isset($_GET['live'])) {
    visits_geo_fill(30);
    header('Content-Type: application/json');
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM visitors_live WHERE last_seen >= ?');
    $cnt->execute([time() - 300]);
    $rows = live_rows($pdo);
    echo json_encode(['n' => (int)$cnt->fetchColumn(), 'rows' => array_map(fn($r) => [
        'page' => $r['title'] ?: $r['path'], 'path' => $r['path'], 'place' => place($r), 'source' => $r['source'], 'device' => $r['device'], 'ago' => ago((int)$r['last_seen']),
    ], $rows)], JSON_UNESCAPED_UNICODE);
    exit;
}
if (is_post()) {
    csrf_check();
    setting_set('visitor_geo', isset($_POST['visitor_geo']) ? '1' : '0');
    if (!isset($_POST['visitor_geo'])) { $pdo->exec("UPDATE visits SET ip = '' WHERE ip <> ''"); $pdo->exec("UPDATE visitors_live SET ip = ''"); }
    flash('Saved.');
    redirect('/admin/visitors.php');
}
visits_geo_fill(100);

$ranges = ['today' => ['Today', $today], '7' => ['Last 7 days', date('Y-m-d', strtotime('-6 days'))], '30' => ['Last 30 days', date('Y-m-d', strtotime('-29 days'))],
    '90' => ['Last 90 days', date('Y-m-d', strtotime('-89 days'))], '365' => ['Last 12 months', date('Y-m-d', strtotime('-364 days'))]];
$rk = isset($ranges[$_GET['range'] ?? '']) ? $_GET['range'] : '7';
[$rLabel, $from] = $ranges[$rk];

$one = function (string $sql, array $p) use ($pdo) { $st = $pdo->prepare($sql); $st->execute($p); return (int)$st->fetchColumn(); };
$uniq = fn(string $a, string $b = '') => $one('SELECT COUNT(*) FROM (SELECT DISTINCT day, vid FROM visits WHERE day >= ? AND day <= ?) x', [$a, $b ?: $a]);
$live = live_rows($pdo);
$yday = date('Y-m-d', strtotime('-1 day'));
$cards = [
    ['Live now', $one('SELECT COUNT(*) FROM visitors_live WHERE last_seen >= ?', [time() - 300]), 'People on the website in the last 5 minutes', 'c-green', 'live'],
    ['Visitors today', $uniq($today), number_format($one('SELECT COUNT(*) FROM visits WHERE day = ?', [$today])) . ' page views', 'c-blue', ''],
    ['Yesterday', $uniq($yday), number_format($one('SELECT COUNT(*) FROM visits WHERE day = ?', [$yday])) . ' page views', 'c-purple', ''],
    ['Last 7 days', $uniq(date('Y-m-d', strtotime('-6 days')), $today), 'visitors (each day counted)', 'c-orange', ''],
    ['Last 30 days', $uniq(date('Y-m-d', strtotime('-29 days')), $today), 'visitors (each day counted)', 'c-blue', ''],
];

// Visitors per day (last 30 days)
$st = $pdo->prepare('SELECT day, COUNT(DISTINCT vid) v, COUNT(*) pv FROM visits WHERE day >= ? GROUP BY day');
$st->execute([date('Y-m-d', strtotime('-29 days'))]);
$byDay = [];
foreach ($st as $r) $byDay[$r['day']] = [(int)$r['v'], (int)$r['pv']];
$days = [];
for ($i = 29; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $days[$d] = $byDay[$d] ?? [0, 0]; }
$maxDay = max(1, max(array_map(fn($x) => $x[0], $days)));
$best = array_search(max($days), $days);

// Lists for the chosen range: each visitor-day counted once, from its first page view
$first = 'SELECT v.* FROM visits v JOIN (SELECT MIN(id) id FROM visits WHERE day >= ? GROUP BY day, vid) f ON f.id = v.id';
$group = function (string $col, int $limit = 10, string $where = '') use ($pdo, $first, $from) {
    $st = $pdo->prepare("SELECT $col AS k, COUNT(*) n FROM ($first) x " . ($where ? "WHERE $where " : '') . "GROUP BY $col ORDER BY n DESC LIMIT $limit");
    $st->execute([$from]);
    return $st->fetchAll();
};
$sources = $group('source');
$sites = $group('ref_host', 8, "ref_host <> '' AND source = 'Other websites'");
$states = $group("CASE WHEN region = '' THEN (CASE WHEN country = '' THEN 'Finding…' ELSE country END) ELSE region END", 12);
$cities = $group("CASE WHEN city = '' THEN '' ELSE city END", 12, "city <> ''");
$countries = $group("CASE WHEN country = '' THEN 'Finding…' ELSE country END", 8);
$devices = $group('device', 4);
$st = $pdo->prepare('SELECT path, MAX(title) title, COUNT(*) pv FROM visits WHERE day >= ? GROUP BY path ORDER BY pv DESC LIMIT 15');
$st->execute([$from]);
$pages = $st->fetchAll();
$st = $pdo->prepare('SELECT at_time FROM visits WHERE day >= ?');
$st->execute([$from]);
$hours = array_fill(0, 24, 0);
foreach ($st as $r) $hours[(int)date('G', (int)$r['at_time'])]++;
$total = array_sum(array_column($sources, 'n'));

/** One list with bars. */
function bars(array $rows, int $total, string $empty = 'No visitors yet in this period.'): string
{
    if (!$rows) return '<p class="muted">' . e($empty) . '</p>';
    $max = max(1, max(array_column($rows, 'n')));
    $h = '<ul class="vbars">';
    foreach ($rows as $r) {
        $pct = $total ? round($r['n'] * 100 / $total) : 0;
        $h .= '<li><span class="vb-l">' . e($r['k'] ?: '—') . '</span><span class="vb-n">' . number_format($r['n']) . ($total ? ' <small>' . $pct . '%</small>' : '') . '</span>'
            . '<i style="width:' . round($r['n'] * 100 / $max) . '%"></i></li>';
    }
    return $h . '</ul>';
}
$srcIcon = ['Google' => '🔎', 'WhatsApp' => '💬', 'Direct' => '⌨️', 'Facebook' => '📘', 'YouTube' => '▶️', 'Telegram' => '✈️', 'Instagram' => '📸', 'Android app' => '📱', 'Bing' => '🔍', 'Other websites' => '🔗'];
foreach ($sources as &$s) $s['k'] = ($srcIcon[$s['k']] ?? '🌐') . ' ' . $s['k']; unset($s);

admin_header('Visitors', 'visitors');
?>
<style>
  .vcards { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 16px; }
  .vcard { background: #fff; border-radius: 14px; padding: 14px 16px; box-shadow: 0 2px 12px rgba(15, 42, 111, .06); border-top: 4px solid var(--c); }
  .vcard b { display: block; font: 700 28px/1.1 "Poppins", sans-serif; color: var(--ink); }
  .vcard span { font-weight: 600; color: var(--ink); } .vcard small { display: block; color: #64748b; font-size: 12.5px; margin-top: 2px; }
  .vcard.live b::after { content: ""; display: inline-block; width: 10px; height: 10px; margin-left: 8px; border-radius: 50%; background: #22c55e; vertical-align: middle; animation: vpulse 1.6s infinite; }
  @keyframes vpulse { 0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, .6); } 70% { box-shadow: 0 0 0 9px rgba(34, 197, 94, 0); } 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); } }
  .vchart { display: grid; grid-template-columns: repeat(30, 1fr); align-items: end; gap: 3px; height: 170px; padding-top: 18px; }
  .vchart div { position: relative; background: linear-gradient(180deg, #3d6ff0, #1f4fd6); border-radius: 4px 4px 0 0; min-height: 2px; }
  .vchart div.best { background: linear-gradient(180deg, #fbb117, #f7740f); }
  .vchart div:hover::after { content: attr(data-t); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); white-space: nowrap; background: #0b1430; color: #fff; font-size: 12px; padding: 3px 7px; border-radius: 6px; margin-bottom: 4px; z-index: 2; }
  .vaxis { display: flex; justify-content: space-between; color: #64748b; font-size: 12px; margin-top: 6px; }
  .vbars { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
  .vbars li { position: relative; display: flex; justify-content: space-between; gap: 10px; padding: 7px 10px; border-radius: 8px; background: #f5f8ff; overflow: hidden; font-size: 14px; }
  .vbars li i { position: absolute; left: 0; top: 0; bottom: 0; background: rgba(31, 79, 214, .12); border-radius: 8px; }
  .vbars .vb-l, .vbars .vb-n { position: relative; z-index: 1; } .vbars .vb-l { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .vbars .vb-n { font-weight: 600; white-space: nowrap; } .vbars small { color: #64748b; font-weight: 500; }
  .vtabs { display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 14px; }
  .vtabs a { padding: 7px 14px; border-radius: 999px; background: #fff; border: 1px solid #dbe3f3; font-weight: 600; font-size: 14px; color: #3b4763; }
  .vtabs a.on { background: #1f4fd6; border-color: #1f4fd6; color: #fff; }
  .vgrid3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
  .vlive { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; max-height: 420px; overflow: auto; }
  .vlive li { display: grid; grid-template-columns: 1fr auto; gap: 2px 10px; padding: 8px 10px; border-radius: 10px; background: #f0fdf4; font-size: 13.5px; }
  .vlive b { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--ink); } .vlive small { color: #64748b; }
  .vhours { display: grid; grid-template-columns: repeat(24, 1fr); align-items: end; gap: 2px; height: 90px; }
  .vhours div { background: #c7d6fb; border-radius: 3px 3px 0 0; min-height: 2px; } .vhours div.top { background: #f7740f; }
</style>

    <div class="vcards">
<?php foreach ($cards as [$label, $n, $sub, $c, $cls]): ?>
      <div class="vcard <?= $c ?> <?= $cls ?>"><span><?= e($label) ?></span><b<?= $cls === 'live' ? ' id="live-n"' : '' ?>><?= number_format($n) ?></b><small><?= e($sub) ?></small></div>
<?php endforeach; ?>
    </div>

    <div class="grid2 wide-left">
      <section class="box">
        <h2>Visitors per day <small class="muted" style="font-weight:500">last 30 days</small></h2>
        <div class="vchart">
<?php foreach ($days as $d => [$v, $pv]): ?>
          <div<?= $d === $best && $v ? ' class="best"' : '' ?> style="height:<?= max(1, round($v * 100 / $maxDay)) ?>%" data-t="<?= e(date('d M', strtotime($d)) . ': ' . $v . ' visitors, ' . $pv . ' views') ?>"></div>
<?php endforeach; ?>
        </div>
        <div class="vaxis"><span><?= e(date('d M', strtotime(array_key_first($days)))) ?></span><span>Best day: <?= $days[$best][0] ? e(date('d M', strtotime($best))) . ' (' . $days[$best][0] . ')' : '—' ?></span><span>Today</span></div>
      </section>
      <section class="box">
        <h2>On the website now <small class="muted" id="live-t" style="font-weight:500">updates every 15 s</small></h2>
        <ul class="vlive" id="live-list">
<?php if (!$live): ?>          <li><b>Nobody right now</b><small></small></li>
<?php endif; foreach ($live as $r): ?>
          <li><b><?= e($r['title'] ?: $r['path']) ?></b><small><?= e(ago((int)$r['last_seen'])) ?></small><small>📍 <?= e(place($r)) ?></small><small><?= e($r['source']) ?> · <?= e($r['device']) ?></small></li>
<?php endforeach; ?>
        </ul>
      </section>
    </div>

    <nav class="vtabs" aria-label="Period">
<?php foreach ($ranges as $k => [$l]): ?>
      <a href="?range=<?= $k ?>"<?= $k === $rk ? ' class="on"' : '' ?>><?= e($l) ?></a>
<?php endforeach; ?>
    </nav>

    <div class="vgrid3">
      <section class="box"><h2>Where visitors came from</h2><?= bars($sources, $total) ?>
<?php if ($sites): ?>        <h3 style="margin:14px 0 8px;font-size:14px">Other websites</h3><?= bars($sites, 0) ?><?php endif; ?></section>
      <section class="box"><h2>Top states</h2><?= bars($states, $total) ?></section>
      <section class="box"><h2>Top cities</h2><?= bars($cities, $total, 'Cities appear a few minutes after the first visits.') ?></section>
      <section class="box"><h2>Most visited pages</h2>
<?php if (!$pages): ?>        <p class="muted">No visitors yet in this period.</p>
<?php else: $mx = max(1, max(array_column($pages, 'pv'))); ?>
        <ul class="vbars">
<?php foreach ($pages as $p): ?>
          <li title="<?= e($p['path']) ?>"><span class="vb-l"><a href="<?= e($p['path']) ?>" target="_blank" rel="noopener"><?= e($p['title'] ?: $p['path']) ?></a></span><span class="vb-n"><?= number_format($p['pv']) ?> <small>views</small></span><i style="width:<?= round($p['pv'] * 100 / $mx) ?>%"></i></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </section>
      <section class="box"><h2>Devices</h2><?= bars($devices, $total) ?>
        <h3 style="margin:16px 0 8px;font-size:14px">Countries</h3><?= bars($countries, $total) ?></section>
      <section class="box"><h2>Busiest hours <small class="muted" style="font-weight:500">page views, <?= e(strtolower($rLabel)) ?></small></h2>
<?php $hm = max(1, max($hours)); $top = array_search(max($hours), $hours); ?>
        <div class="vhours"><?php foreach ($hours as $h => $n): ?><div<?= $h === $top && $n ? ' class="top"' : '' ?> style="height:<?= max(2, round($n * 100 / $hm)) ?>%" title="<?= sprintf('%02d:00', $h) ?> – <?= $n ?> views"></div><?php endforeach; ?></div>
        <div class="vaxis"><span>12 am</span><span>6 am</span><span>12 pm</span><span>6 pm</span><span>11 pm</span></div>
        <p class="muted" style="margin-top:10px"><?= max($hours) ? 'Most visitors come around <b>' . date('g A', mktime($top, 0)) . '</b> – a good time to post new tests and jobs.' : '' ?></p>
      </section>
    </div>

    <section class="box">
      <h2>Settings</h2>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <label class="check"><input type="checkbox" name="visitor_geo"<?= setting('visitor_geo', '1') === '1' ? ' checked' : '' ?>> Find the visitor's state and city (the IP address is sent once to ip-api.com and then deleted)</label>
        <p class="muted">Visitors are counted without cookies. Your own visits while logged in as admin, and search-engine robots, are not counted. Page views are kept for 12 months.</p>
        <div class="bar"><span></span><button class="btn">Save</button></div>
      </form>
    </section>
    <script>
      // Live list refresh
      (function () {
        var n = document.getElementById('live-n'), list = document.getElementById('live-list');
        function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
        setInterval(function () {
          if (document.hidden) return;
          fetch('?live=1', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
            n.textContent = d.n;
            list.innerHTML = d.rows.length ? d.rows.map(function (r) {
              return '<li><b>' + esc(r.page) + '</b><small>' + esc(r.ago) + '</small><small>📍 ' + esc(r.place) + '</small><small>' + esc(r.source) + ' · ' + esc(r.device) + '</small></li>';
            }).join('') : '<li><b>Nobody right now</b><small></small></li>';
          }).catch(function () {});
        }, 15000);
      })();
    </script>
<?php admin_footer();
