<?php
/* Admin → Theory Notes: per trade, modules (1st / 2nd Year for two-year trades) and the lessons inside them.
   Students read them at /trades/<slug>/notes/ and can translate every lesson into their own language. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
$pdo = db();

$tid = (int)($_GET['trade'] ?? $_POST['trade'] ?? 0);
$trade = null;
if ($tid) {
    $st = $pdo->prepare('SELECT * FROM trades WHERE id = ?');
    $st->execute([$tid]);
    $trade = $st->fetch() ?: redirect('/admin/notes.php');
}
$self = fn(array $q = []) => '/admin/notes.php?' . http_build_query(['trade' => $tid] + $q);
$err = '';

/* ---------- saving ---------- */
if ($trade && is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $years = trade_years($trade['duration']);
    if ($action === 'del_module') {
        $mid = (int)$_POST['id'];
        $pdo->prepare('DELETE FROM note_lessons WHERE module_id = ? AND trade_id = ?')->execute([$mid, $tid]);
        $pdo->prepare('DELETE FROM note_modules WHERE id = ? AND trade_id = ?')->execute([$mid, $tid]);
        flash('Module and its lessons deleted.');
        redirect($self());
    }
    if ($action === 'del_lesson') {
        $pdo->prepare('DELETE FROM note_lessons WHERE id = ? AND trade_id = ?')->execute([(int)$_POST['id'], $tid]);
        flash('Lesson deleted.');
        redirect($self(['year' => (int)post('year', '1')]));
    }
    if ($action === 'module') {
        $mid = (int)post('id');
        $m = ['year' => max(1, min(max($years, 2), (int)post('year', '1'))), 'num' => mb_substr(post('num'), 0, 10), 'title' => mb_substr(post('title'), 0, 200),
            'summary' => mb_substr(post('summary'), 0, 500), 'status' => post('status') === 'draft' ? 'draft' : 'published', 'sort_order' => (int)post('sort_order')];
        if ($m['title'] === '') $err = 'Module title is required.';
        else {
            $v = [$m['year'], $m['num'], $m['title'], $m['summary'], $m['status'], $m['sort_order'], now()];
            if ($mid) $pdo->prepare('UPDATE note_modules SET year = ?, num = ?, title = ?, summary = ?, status = ?, sort_order = ?, updated_at = ? WHERE id = ? AND trade_id = ?')->execute([...$v, $mid, $tid]);
            else $pdo->prepare('INSERT INTO note_modules (year, num, title, summary, status, sort_order, updated_at, trade_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([...$v, $tid]);
            flash($mid ? 'Module saved.' : 'Module added. Now add its lessons.');
            redirect($self(['year' => $m['year']]));
        }
    }
    if ($action === 'lesson') {
        $lid = (int)post('id');
        $L = ['module_id' => (int)post('module_id'), 'num' => mb_substr(post('num'), 0, 10), 'title' => mb_substr(post('title'), 0, 200),
            'slug' => slugify(post('slug') ?: post('title')), 'summary' => mb_substr(post('summary'), 0, 300), 'body' => (string)($_POST['body'] ?? ''),
            'image' => mb_substr(post('image'), 0, 300), 'minutes' => max(0, (int)post('minutes')), 'status' => post('status') === 'draft' ? 'draft' : 'published', 'sort_order' => (int)post('sort_order')];
        $L['slug'] = mb_substr($L['slug'], 0, 160);
        $own = $pdo->prepare('SELECT year FROM note_modules WHERE id = ? AND trade_id = ?');
        $own->execute([$L['module_id'], $tid]);
        $year = $own->fetchColumn();
        try { if ($f = handle_upload('image_file')) $L['image'] = $f; } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
        $dup = $pdo->prepare('SELECT COUNT(*) FROM note_lessons WHERE trade_id = ? AND slug = ? AND id <> ?');
        $dup->execute([$tid, $L['slug'], $lid]);
        if (!$err && $year === false) $err = 'Choose the module of this lesson.';
        elseif (!$err && $L['title'] === '') $err = 'Lesson title is required.';
        elseif (!$err && $L['slug'] === '') $err = 'Lesson address (slug) is required.';
        elseif (!$err && $dup->fetchColumn()) $err = 'Another lesson of this trade already uses the address “' . $L['slug'] . '”. Change the slug.';
        elseif (!$err && $L['image'] !== '' && str_contains($L['image'], '/') && !preg_match('~^(https?://|/)~i', $L['image'])) $err = 'Cover image link must start with https:// or /';
        if (!$err) {
            $v = [$L['module_id'], $L['num'], $L['title'], $L['slug'], $L['summary'], $L['body'], $L['image'], $L['minutes'] ?: notes_minutes($L['body']), $L['status'], $L['sort_order'], now()];
            if ($lid) $pdo->prepare('UPDATE note_lessons SET module_id = ?, num = ?, title = ?, slug = ?, summary = ?, body = ?, image = ?, minutes = ?, status = ?, sort_order = ?, updated_at = ? WHERE id = ? AND trade_id = ?')->execute([...$v, $lid, $tid]);
            else $pdo->prepare('INSERT INTO note_lessons (module_id, num, title, slug, summary, body, image, minutes, status, sort_order, updated_at, trade_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([...$v, $tid, now()]);
            flash($lid ? 'Lesson saved.' : 'Lesson added.');
            redirect(isset($_POST['stay']) ? $self(['lesson' => $lid ?: (int)$pdo->lastInsertId()]) : $self(['year' => (int)$year]));
        }
    }
}

/* ---------- no trade chosen: list of trades ---------- */
if (!$trade) {
    $rows = $pdo->query("SELECT t.id, t.name, t.slug, t.kind, t.duration, t.status,
        (SELECT COUNT(*) FROM note_modules m WHERE m.trade_id = t.id) AS mods,
        (SELECT COUNT(*) FROM note_lessons l WHERE l.trade_id = t.id) AS lessons
        FROM trades t ORDER BY (SELECT COUNT(*) FROM note_lessons l WHERE l.trade_id = t.id) > 0 DESC, t.sort_order, t.name")->fetchAll();
    admin_header('Theory Notes', 'notes');
    ?>
    <section class="box tablebox">
      <div class="bar"><h2>Theory Notes – choose a trade</h2></div>
      <p class="muted">Make notes as <b>modules</b> (cards) with <b>lessons</b> inside. Two-year trades get separate <b>1st Year</b> and <b>2nd Year</b> tabs. Students can read every lesson in Hindi, Marathi and other Indian languages using the “Read in” button.</p>
      <form class="inline-filter" onsubmit="return false"><input placeholder="Filter trades…" oninput="var v=this.value.toLowerCase();document.querySelectorAll('[data-tr]').forEach(function(r){r.hidden=r.dataset.tr.indexOf(v)<0})"></form>
      <table>
        <thead><tr><th>Trade</th><th>Duration</th><th>Notes</th><th>Actions</th></tr></thead>
        <tbody>
<?php foreach ($rows as $r): ?>
          <tr data-tr="<?= e(mb_strtolower($r['name'])) ?>">
            <td><b><?= e($r['name']) ?></b><?= $r['kind'] === 'subject' ? ' <span class="pill">subject</span>' : '' ?><?= $r['status'] !== 'published' ? ' <span class="pill off">draft</span>' : '' ?></td>
            <td><?= e($r['duration']) ?></td>
            <td><?= $r['lessons'] ? '<span class="pill ok">' . (int)$r['mods'] . ' modules · ' . (int)$r['lessons'] . ' lessons</span>' : '<span class="muted">—</span>' ?></td>
            <td class="acts"><a class="btn sm" href="?trade=<?= $r['id'] ?>">Manage notes</a><?php if ($r['lessons']): ?> <a class="btn sm alt" href="<?= e(notes_url($r)) ?>" target="_blank">View ↗</a><?php endif; ?></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </section>
<?php
    admin_footer();
    exit;
}

/* ---------- one trade ---------- */
$years = trade_years($trade['duration']);
$tree = notes_tree($tid, true);
$mods = $pdo->prepare('SELECT id, year, num, title FROM note_modules WHERE trade_id = ? ORDER BY year, sort_order, id');
$mods->execute([$tid]);
$mods = $mods->fetchAll();
$year = (int)($_GET['year'] ?? 1);
$yrs = range(1, max($years, 1));
foreach (array_keys($tree) as $y) if (!in_array($y, $yrs, true)) $yrs[] = $y;
if (!in_array($year, $yrs, true)) $year = 1;

// which form is open
$mEdit = null; $lEdit = null;
if (isset($_GET['module'])) {
    $mEdit = ['id' => 0, 'year' => $year, 'num' => '', 'title' => '', 'summary' => '', 'status' => 'published',
        'sort_order' => count($tree[$year] ?? []) + 1];
    if ((int)$_GET['module']) {
        $st = $pdo->prepare('SELECT * FROM note_modules WHERE id = ? AND trade_id = ?');
        $st->execute([(int)$_GET['module'], $tid]);
        $mEdit = $st->fetch() ?: redirect($self());
    } elseif ($mEdit['sort_order']) $mEdit['num'] = (string)(array_sum(array_map('count', $tree)) + 1);
}
if (isset($_GET['lesson']) || isset($_GET['add'])) {
    $mid = (int)($_GET['add'] ?? 0);
    $n = 0; foreach ($tree as $ms) foreach ($ms as $m) if ((int)$m['id'] === $mid) { $n = count($m['lessons']); $mnum = $m['num']; }
    $lEdit = ['id' => 0, 'module_id' => $mid, 'num' => isset($mnum) && $mnum !== '' ? $mnum . '.' . ($n + 1) : '', 'title' => '', 'slug' => '', 'summary' => '',
        'body' => '', 'image' => '', 'minutes' => 0, 'status' => 'published', 'sort_order' => $n + 1];
    if ((int)($_GET['lesson'] ?? 0)) {
        $st = $pdo->prepare('SELECT * FROM note_lessons WHERE id = ? AND trade_id = ?');
        $st->execute([(int)$_GET['lesson'], $tid]);
        $lEdit = $st->fetch() ?: redirect($self());
    }
}
// keep what was typed when saving failed
if ($err && ($_POST['action'] ?? '') === 'module') $mEdit = array_merge($mEdit ?? [], array_intersect_key($_POST, array_flip(['id', 'year', 'num', 'title', 'summary', 'status', 'sort_order'])));
if ($err && ($_POST['action'] ?? '') === 'lesson') $lEdit = array_merge($lEdit ?? ['id' => 0, 'image' => ''], array_intersect_key($_POST, array_flip(['id', 'module_id', 'num', 'title', 'slug', 'summary', 'body', 'image', 'minutes', 'status', 'sort_order'])));
$imgUrl = fn(string $i) => $i === '' ? '' : (preg_match('~^(https?://|/)~', $i) ? $i : upload_url($i));

admin_header('Theory Notes – ' . $trade['name'], 'notes');
?>
    <style>
      .nt-years { display: flex; gap: 6px; margin: 0 0 14px; flex-wrap: wrap; }
      .nt-years a { padding: 8px 16px; border-radius: 10px; border: 1px solid #dbe3f3; text-decoration: none; color: inherit; font-weight: 600; background: #fff; }
      .nt-years a.on { background: #1f4fd6; border-color: #1f4fd6; color: #fff; }
      .nt-mods { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); }
      .nt-mod { border: 1px solid #e4e9f4; border-radius: 14px; padding: 14px; background: #fff; }
      .nt-mod h3 { margin: 0 0 4px; font-size: 16px; display: flex; gap: 8px; align-items: center; }
      .nt-mod h3 b { background: #1f4fd6; color: #fff; border-radius: 8px; padding: 2px 9px; font-size: 13px; }
      .nt-mod ol { list-style: none; margin: 10px 0; padding: 0; }
      .nt-mod li { display: flex; gap: 8px; align-items: center; padding: 6px 0; border-top: 1px dashed #e4e9f4; font-size: 13.5px; }
      .nt-mod li span { flex: 1; min-width: 0; }
      .nt-mod li img { width: 36px; height: 26px; object-fit: cover; border-radius: 5px; }
      .nt-mod .acts { display: flex; gap: 6px; flex-wrap: wrap; }
      .nt-help { background: #f4f7fe; border-radius: 12px; padding: 12px 14px; font-size: 13px; }
      .nt-help code { background: #fff; padding: 1px 5px; border-radius: 5px; }
      .nt-cover { max-width: 220px; border-radius: 8px; display: block; margin-top: 6px; }
    </style>
    <div class="bar">
      <a class="btn alt" href="/admin/notes.php">← All trades</a>
      <span><a class="btn alt" href="<?= e(notes_url($trade)) ?>" target="_blank">View notes page ↗</a> <a class="btn" href="<?= e($self(['module' => 0, 'year' => $year])) ?>#form">+ Add module</a></span>
    </div>

<?php if ($mEdit !== null): ?>
    <form method="post" class="box form" id="form">
      <h2><?= $mEdit['id'] ? 'Edit module' : 'Add a module' ?></h2>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
      <?= csrf_field() ?><input type="hidden" name="action" value="module"><input type="hidden" name="trade" value="<?= $tid ?>"><input type="hidden" name="id" value="<?= (int)$mEdit['id'] ?>">
      <div class="grid2 tight">
        <label>Year <select name="year"><?php foreach (range(1, max($years, 1)) as $y): ?><option value="<?= $y ?>"<?= (int)$mEdit['year'] === $y ? ' selected' : '' ?>><?= notes_year_label($y) ?></option><?php endforeach; ?></select></label>
        <label>Module no. <input name="num" value="<?= e($mEdit['num']) ?>" placeholder="1" maxlength="10"></label>
      </div>
      <label>Module title * <input name="title" required value="<?= e($mEdit['title']) ?>" placeholder="e.g. Safety" maxlength="200"></label>
      <label>Short description <span class="hint">shown on the module card</span><textarea name="summary" rows="2" maxlength="500" placeholder="Topics covered in this module"><?= e($mEdit['summary']) ?></textarea></label>
      <div class="grid2 tight">
        <label>Order <input type="number" name="sort_order" value="<?= (int)$mEdit['sort_order'] ?>"></label>
        <label>Status <select name="status"><option value="published">Published</option><option value="draft"<?= $mEdit['status'] === 'draft' ? ' selected' : '' ?>>Draft (hidden)</option></select></label>
      </div>
      <div class="bar"><a class="btn alt" href="<?= e($self(['year' => $year])) ?>">Cancel</a><button class="btn"><?= $mEdit['id'] ? 'Save module' : '+ Add module' ?></button></div>
    </form>
<?php endif; ?>

<?php if ($lEdit !== null): ?>
    <form method="post" enctype="multipart/form-data" class="box form" id="form">
      <h2><?= $lEdit['id'] ? 'Edit lesson' : 'Add a lesson' ?></h2>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<?php if (!$mods): ?><div class="flash err">First add a module, then add lessons to it.</div><?php endif; ?>
      <?= csrf_field() ?><input type="hidden" name="action" value="lesson"><input type="hidden" name="trade" value="<?= $tid ?>"><input type="hidden" name="id" value="<?= (int)$lEdit['id'] ?>">
      <div class="grid2">
        <div>
          <label>Module * <select name="module_id" required><option value="">— choose —</option><?php foreach ($mods as $m): ?><option value="<?= $m['id'] ?>"<?= (int)$lEdit['module_id'] === (int)$m['id'] ? ' selected' : '' ?>><?= e(($years > 1 ? notes_year_label((int)$m['year']) . ' · ' : '') . 'Module ' . $m['num'] . ' – ' . $m['title']) ?></option><?php endforeach; ?></select></label>
          <div class="grid2 tight">
            <label>Lesson no. <input name="num" value="<?= e($lEdit['num']) ?>" placeholder="1.1" maxlength="10"></label>
            <label>Order <input type="number" name="sort_order" value="<?= (int)$lEdit['sort_order'] ?>"></label>
          </div>
          <label>Lesson title * <input name="title" required value="<?= e($lEdit['title']) ?>" placeholder="e.g. Personal Protective Equipment (PPE)" maxlength="200"></label>
          <label>Address (slug) <span class="hint">leave empty to make it from the title</span><input name="slug" value="<?= e($lEdit['slug']) ?>" placeholder="personal-protective-equipment-ppe" maxlength="160"></label>
          <label>Short summary <span class="hint">shown on the card and in Google</span><textarea name="summary" rows="2" maxlength="300"><?= e($lEdit['summary']) ?></textarea></label>
        </div>
        <div>
          <label>Cover picture <span class="hint">upload, or paste a link / Media Library address</span><input type="file" name="image_file" accept="image/*"></label>
          <label><input name="image" value="<?= e($lEdit['image']) ?>" placeholder="/assets/img/… or https://…"></label>
<?php if ($lEdit['image'] !== ''): ?>          <img class="nt-cover" src="<?= e($imgUrl($lEdit['image'])) ?>" alt=""><label class="check"><input type="checkbox" onchange="if(this.checked)this.form.image.value=''"> Remove picture</label>
<?php endif; ?>
          <div class="grid2 tight">
            <label>Reading time (min) <span class="hint">0 = auto</span><input type="number" name="minutes" min="0" value="<?= (int)$lEdit['minutes'] ?>"></label>
            <label>Status <select name="status"><option value="published">Published</option><option value="draft"<?= $lEdit['status'] === 'draft' ? ' selected' : '' ?>>Draft (hidden)</option></select></label>
          </div>
          <div class="nt-help"><b>Writing tips</b> – use headings (H2/H3), lists and tables. For coloured boxes, switch the editor to HTML and use:<br>
            <code>&lt;div class="nb-key"&gt;</code> key point · <code>&lt;div class="nb-tip"&gt;</code> tip · <code>&lt;div class="nb-warn"&gt;</code> warning · <code>&lt;div class="nb-formula"&gt;</code> formula<br>
            Quick revision at the end: <code>&lt;details&gt;&lt;summary&gt;Quick Revision&lt;/summary&gt;&lt;ul&gt;…&lt;/ul&gt;&lt;/details&gt;</code><br>
            Write in simple English – students translate it into their language.</div>
        </div>
      </div>
      <label>Lesson notes <textarea name="body" rows="22" class="code" data-editor><?= e($lEdit['body']) ?></textarea></label>
      <div class="bar"><a class="btn alt" href="<?= e($self(['year' => $year])) ?>">Cancel</a><span><?php if ($lEdit['id'] && $lEdit['slug'] !== ''): ?><a class="btn alt" target="_blank" href="<?= e(notes_url($trade, $lEdit['slug'])) ?>">View ↗</a> <?php endif; ?><button class="btn alt" name="stay" value="1">Save &amp; keep editing</button> <button class="btn"><?= $lEdit['id'] ? 'Save lesson' : '+ Add lesson' ?></button></span></div>
    </form>
<?php endif; ?>

    <section class="box">
      <div class="bar"><h2><?= e($trade['name']) ?> – modules &amp; lessons</h2><span class="muted"><?= e($trade['duration']) ?></span></div>
<?php if (count($yrs) > 1): ?>
      <nav class="nt-years"><?php foreach ($yrs as $y): $c = array_sum(array_map(fn($m) => count($m['lessons']), $tree[$y] ?? [])); ?><a href="<?= e($self(['year' => $y])) ?>" class="<?= $y === $year ? 'on' : '' ?>"><?= notes_year_label($y) ?> (<?= $c ?>)</a><?php endforeach; ?></nav>
<?php endif; ?>
<?php if (empty($tree[$year])): ?>
      <p class="muted">No modules for <?= notes_year_label($year) ?> yet. <a href="<?= e($self(['module' => 0, 'year' => $year])) ?>#form">Add the first module</a> (e.g. “Module 1 – Safety”), then add lessons to it.</p>
<?php else: ?>
      <div class="nt-mods">
<?php foreach ($tree[$year] as $m): ?>
        <div class="nt-mod">
          <h3><b><?= e($m['num'] ?: '–') ?></b><?= e($m['title']) ?><?= $m['status'] !== 'published' ? ' <span class="pill off">draft</span>' : '' ?></h3>
          <small class="muted"><?= e($m['summary']) ?></small>
          <ol>
<?php foreach ($m['lessons'] as $l): ?>
            <li><?php if ($l['image'] !== ''): ?><img src="<?= e($imgUrl($l['image'])) ?>" alt=""><?php endif; ?><span><b><?= e($l['num']) ?></b> <?= e($l['title']) ?><?= $l['status'] !== 'published' ? ' <span class="pill off">draft</span>' : '' ?></span>
              <a class="btn sm alt" href="<?= e($self(['lesson' => $l['id'], 'year' => $year])) ?>#form">Edit</a>
              <form method="post" data-confirm="Delete the lesson “<?= e($l['title']) ?>”?"><?= csrf_field() ?><input type="hidden" name="trade" value="<?= $tid ?>"><input type="hidden" name="year" value="<?= $year ?>"><input type="hidden" name="action" value="del_lesson"><input type="hidden" name="id" value="<?= $l['id'] ?>"><button class="btn sm danger">✕</button></form></li>
<?php endforeach; ?>
<?php if (!$m['lessons']): ?>            <li class="muted">No lessons yet – students will not see this module until it has one.</li><?php endif; ?>
          </ol>
          <div class="acts">
            <a class="btn sm" href="<?= e($self(['add' => $m['id'], 'year' => $year])) ?>#form">+ Add lesson</a>
            <a class="btn sm alt" href="<?= e($self(['module' => $m['id'], 'year' => $year])) ?>#form">Edit module</a>
            <form method="post" data-confirm="Delete module “<?= e($m['title']) ?>” and ALL its <?= count($m['lessons']) ?> lessons?"><?= csrf_field() ?><input type="hidden" name="trade" value="<?= $tid ?>"><input type="hidden" name="action" value="del_module"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn sm danger">Delete module</button></form>
          </div>
        </div>
<?php endforeach; ?>
      </div>
<?php endif; ?>
    </section>
<?php admin_footer();
