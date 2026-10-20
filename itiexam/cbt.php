<?php
/* Ready-made HTML CBT tests.
   /cbt/{folder}/              → page with the website header, the test in a sandboxed frame, and more tests
   /cbt/{folder}/play/{file}   → the uploaded test files themselves, always sent with a sandbox rule */
require __DIR__ . '/includes/functions.php';

$folder = preg_replace('/[^a-z0-9-]/', '', (string)($_GET['f'] ?? ''));
$st = db()->prepare('SELECT c.*, t.name AS tname, t.slug AS tslug FROM cbt_tests c LEFT JOIN trades t ON t.id = c.trade_id WHERE c.folder = ?');
$st->execute([$folder]);
$test = $st->fetch();
if (!$test || ($test['status'] !== 'published' && empty($_SESSION['admin'])) || !is_dir(CBT_DIR . '/' . $folder)) not_found();

/* ---------- serve one file of the test ---------- */
if (isset($_GET['p'])) {
    if (tests_locked()) {                       // opened directly without logging in
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        exit('<!DOCTYPE html><meta charset="utf-8"><meta name="robots" content="noindex"><p style="font:16px sans-serif;text-align:center;margin:40px">Please <a href="' . e(login_url(cbt_url($test))) . '" target="_top">login with Google</a> to start this test.</p>');
    }
    $rel = trim(str_replace('\\', '/', rawurldecode((string)$_GET['p'])), '/');
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    $base = realpath(CBT_DIR . '/' . $folder);
    $file = realpath($base . '/' . $rel);
    if ($rel === '' || str_contains($rel, '..') || !isset(CBT_TYPES[$ext]) || !$file || !str_starts_with($file, $base . DIRECTORY_SEPARATOR) || !is_file($file)) {
        http_response_code(404);
        exit('Not found');
    }
    header('Content-Type: ' . CBT_TYPES[$ext]);
    header('Content-Security-Policy: sandbox ' . cbt_sandbox());
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex');
    header('Cache-Control: ' . (in_array($ext, ['html', 'htm'], true) ? 'no-cache' : 'public, max-age=86400'));
    if (in_array($ext, ['html', 'htm'], true)) {
        // Tell the website page when the test is finished, so the result can go to the student's dashboard
        $html = file_get_contents($file);
        $bridge = '<script>' . file_get_contents(__DIR__ . '/assets/js/cbt-bridge.js') . '</script>';
        $pos = strripos($html, '</body>');
        $html = $pos === false ? $html . $bridge : substr($html, 0, $pos) . $bridge . substr($html, $pos);
        header('Content-Length: ' . strlen($html));
        echo $html;
        exit;
    }
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

/* ---------- the test page ---------- */
if ($test['level'] ?? '') $test['title'] = $test['level'] . ' – ' . $test['title'];   // "1st Year – Module 3" on the test page
$name = $test['tname'] ?: 'ITI';
$path = cbt_url($test);
$more = [];
if ($test['trade_id']) {
    $m = db()->prepare("SELECT * FROM cbt_tests WHERE trade_id = ? AND id <> ? AND status = 'published' ORDER BY created_at DESC LIMIT 6");
    $m->execute([$test['trade_id'], $test['id']]);
    $more = $m->fetchAll();
}
$desc = $test['description'] ?: "Free online CBT mock test: $name – {$test['title']}" . ($test['questions'] ? ", {$test['questions']} questions" : '') . ($test['minutes'] ? ", {$test['minutes']} minutes" : '') . '. Practise in the real NCVT ITI exam pattern.';
$crumbs = [['Home', '/']];
if ($test['tslug']) { $crumbs[] = ['Trades', '/trades/']; $crumbs[] = [$name, '/trades/' . $test['tslug'] . '/']; }
$crumbs[] = [$test['title'], $path];
$meta = [
    'title' => ($test['tname'] ? "ITI $name " : '') . $test['title'] . ' – Free Online CBT Mock Test | ITI Exam',
    'description' => $desc,
    'keywords' => "ITI $name mock test, ITI $name online test, {$test['title']}, ITI CBT mock test, NCVT online test",
    'path' => $path,
    'rv' => ['t' => ($test['tname'] ? $test['tname'] . ' – ' : '') . $test['title'], 's' => 'Mock Test' . ($test['questions'] ? ' · ' . $test['questions'] . ' Questions' : ''), 'i' => '📝'],
    'jsonld' => [
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($c, $i) =>
            ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => SITE_URL . $c[1]], $crumbs, array_keys($crumbs))],
        ['@context' => 'https://schema.org', '@type' => 'Quiz', 'name' => $test['title'], 'description' => $desc, 'url' => SITE_URL . $path,
         'educationalLevel' => 'ITI', 'about' => ['@type' => 'Thing', 'name' => "ITI $name"], 'isAccessibleForFree' => true,
         'provider' => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL . '/']],
    ],
];
$me = student();
$locked = tests_locked();
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim cbt-hero">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><?php foreach ($crumbs as $i => [$l, $u]): ?><?php if ($i === count($crumbs) - 1): ?><span aria-current="page"><?= e($l) ?></span><?php else: ?><a href="<?= e($u) ?>"><?= e($l) ?></a> <?= icon('chev-r') ?> <?php endif; endforeach; ?></nav>
      <h1><?= $test['tname'] ? '<span class="cbt-trade">' . e($name) . '</span> ' : '' ?><?= e($test['title']) ?></h1>
      <p class="cbt-meta">
        <?php if ($test['questions']): ?><span><?= icon('doc') ?> <?= (int)$test['questions'] ?> Questions</span><?php endif; ?>
        <?php if ($test['minutes']): ?><span><?= icon('clock') ?> <?= (int)$test['minutes'] ?> Minutes</span><?php endif; ?>
        <span><?= icon('star') ?> Free</span>
      </p>
    </div>
  </section>

  <section class="section cbt-sec">
    <div class="container">
<?php if ($locked): ?>
      <div class="cbt-gate">
        <div class="cbt-gate-i">🔒</div>
        <h2>Login to start this mock test</h2>
        <p>Sign in with your Google account – it takes 5 seconds and is free. Your score, history and progress are saved to your own dashboard.</p>
        <a class="cbt-gate-btn" href="<?= e(login_url($path)) ?>" rel="nofollow"><svg viewBox="0 0 48 48" width="20" height="20" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.6 5.4 2.7 13.3l7.9 6.2C12.5 13.6 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.7 6c4.5-4.2 6.9-10.3 6.9-17.7z"/><path fill="#FBBC05" d="M10.6 28.5c-.5-1.4-.8-2.9-.8-4.5s.3-3.1.8-4.5l-7.9-6.2C1 16.6 0 20.2 0 24s1 7.4 2.7 10.7l7.9-6.2z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.7-6c-2.2 1.5-5 2.3-8.2 2.3-6.2 0-11.5-4.1-13.4-9.8l-7.9 6.2C6.6 42.6 14.6 48 24 48z"/></svg> Login with Google &amp; Start</a>
        <ul class="cbt-gate-pts"><li>✓ Free for all ITI students</li><li>✓ Real NCVT CBT exam pattern</li><li>✓ Track your score &amp; progress</li></ul>
      </div>
<?php else: ?>
      <div class="cbt-bar">
        <button type="button" class="btn-sm" id="cbt-full"><?= icon('layout') ?> Full screen</button>
        <a class="btn-sm ghost-b" href="<?= e(cbt_play_url($test)) ?>" target="_blank" rel="noopener"><?= icon('arrow') ?> Open in new tab</a>
<?php if ($test['tslug']): ?>        <a class="btn-sm ghost-b" href="/trades/<?= e($test['tslug']) ?>/#mock-tests"><?= icon('list') ?> All <?= e($name) ?> tests</a>
<?php endif; ?>
      </div>
      <div class="cbt-frame" id="cbt-frame">
        <button type="button" class="cbt-close" id="cbt-close" aria-label="Exit full screen">✕</button>
        <iframe id="cbt-if" src="<?= e(cbt_play_url($test)) ?>" title="<?= e($test['title']) ?>" sandbox="<?= e(cbt_sandbox()) ?>" allow="fullscreen; geolocation" loading="eager"></iframe>
        <div class="cbt-save" id="cbt-save" role="status" aria-live="polite" hidden></div>
      </div>
<?php endif; ?>
<?php if ($test['description']): ?>      <p class="cbt-desc"><?= e($test['description']) ?></p>
<?php endif; ?>
      <?= ad('trade_middle') ?>
<?php if ($more): ?>
      <section class="card" aria-labelledby="more-h">
        <div class="sec-head sm"><h2 id="more-h">More <?= e($name) ?> Mock Tests</h2><a class="view-all" href="/trades/<?= e($test['tslug']) ?>/#mock-tests">View All <?= icon('arrow') ?></a></div>
        <ul class="rows">
<?php foreach ($more as $i => $o): ?>
          <li><a href="<?= e(cbt_url($o)) ?>"><span class="ri <?= chip_color($i) ?> solid"><?= icon('clip') ?></span><span class="rt"><b><?= e($o['title']) ?></b><small><?= $o['questions'] ? (int)$o['questions'] . ' Questions' : '' ?><?= $o['questions'] && $o['minutes'] ? ' · ' : '' ?><?= $o['minutes'] ? (int)$o['minutes'] . ' Minutes' : '' ?></small></span><?= start_label() ?></a></li>
<?php endforeach; ?>
        </ul>
      </section>
<?php endif; ?>
    </div>
  </section>
</main>
<?php if (!$locked): ?>
<script>
  // Full screen for the test (falls back to a fixed full-window view on phones without the Fullscreen API)
  (function () {
    var btn = document.getElementById("cbt-full"), box = document.getElementById("cbt-frame");
    btn.addEventListener("click", function () {
      if (box.requestFullscreen) { box.requestFullscreen().catch(function () { box.classList.toggle("max"); }); }
      else if (box.webkitRequestFullscreen) { box.webkitRequestFullscreen(); }
      else { box.classList.toggle("max"); }
    });
    document.getElementById("cbt-close").addEventListener("click", function () {
      if (document.fullscreenElement) document.exitFullscreen(); else box.classList.remove("max");
    });
  })();
  // Finished test → saved to the student's dashboard (guests are asked to log in; the result waits in this browser)
  (function () {
    var frame = document.getElementById("cbt-if"), box = document.getElementById("cbt-save");
    var folder = <?= json_encode($test['folder']) ?>, me = <?= json_encode($me ? ['name' => $me['name'], 'csrf' => csrf_token()] : null, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    var login = <?= json_encode(login_url($path)) ?>;
    function show(cls, html) {
      box.className = "cbt-save " + cls; box.innerHTML = html + '<button type="button" class="cbt-save-x" aria-label="Close">✕</button>'; box.hidden = false;
      box.lastChild.onclick = function () { box.hidden = true; };
    }
    window.addEventListener("message", function (e) {
      if (!frame || e.source !== frame.contentWindow || !e.data || typeof e.data !== "object") return;
      if (e.data.itx === "ready" && me) { frame.contentWindow.postMessage({ itx: "student", name: me.name }, "*"); return; }
      if (e.data.itx !== "result" || !e.data.data) return;
      var d = e.data.data, r = { folder: folder, score: +d.score || 0, total: +d.total || 0, pct: +d.pct || 0, status: String(d.status || ""), lang: String(d.lang || ""), location: String(d.location || "").slice(0, 160), time: +e.data.time || 0, at: Date.now() };
      if (!me) {
        if (!<?= google_login_ready() ? 'true' : 'false' ?>) return;        // student login is not switched on
        try { var q = JSON.parse(localStorage.getItem("itx_pending") || "[]"); q.push(r); localStorage.setItem("itx_pending", JSON.stringify(q.slice(-20))); } catch (x) {}
        show("guest", "<b>Score: " + r.score + (r.total ? " / " + r.total : "") + "</b> <span>Login with Google to save this result and track your progress.</span> <a class=\"btn-sm\" href=\"" + login + "\">Login &amp; Save</a>");
        return;
      }
      r.csrf = me.csrf;
      fetch("/api/attempt/", { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json" }, body: JSON.stringify(r) })
        .then(function (res) { return res.json(); })
        .then(function (j) {
          if (!j.ok) throw 0;
          show("ok", "<b>✓ Result saved</b> <span>" + r.score + (r.total ? " / " + r.total : "") + " added to your dashboard.</span> <a class=\"btn-sm\" href=\"/student/\">View Dashboard</a>");
        })
        .catch(function () { show("err", "<b>Could not save the result.</b> <span>Please check your internet and refresh the page.</span>"); });
    });
  })();
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
