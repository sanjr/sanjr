<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$j = ['title' => '', 'slug' => '', 'employer_id' => 0, 'company' => '', 'job_type' => 'private', 'trades' => '', 'qualification' => 'ITI Pass', 'experience' => 'fresher',
      'location' => '', 'vacancies' => '', 'salary_min' => 0, 'salary_max' => 0, 'salary_text' => '', 'age_limit' => '', 'last_date' => '', 'apply_url' => '',
      'notification_url' => '', 'summary' => '', 'description' => '', 'is_featured' => 0, 'mark_new' => 1, 'meta_title' => '', 'meta_description' => '', 'status' => 'published'];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
    $st->execute([$id]);
    $j = $st->fetch() ?: redirect('/admin/jobs.php');
}
$employers = $pdo->query('SELECT id, name FROM employers ORDER BY name')->fetchAll();
$tradeNames = array_column($pdo->query('SELECT name FROM trades ORDER BY sort_order, name')->fetchAll(), 'name');

$err = '';
if (is_post()) {
    csrf_check();
    foreach (['title', 'company', 'location', 'vacancies', 'salary_text', 'age_limit', 'apply_url', 'notification_url', 'summary', 'meta_title', 'meta_description'] as $k) $j[$k] = post($k);
    $j['description'] = $_POST['description'] ?? '';
    $j['employer_id'] = (int)post('employer_id');
    $j['job_type'] = isset(JOB_TYPES[post('job_type')]) ? post('job_type') : 'private';
    $j['experience'] = isset(JOB_EXPERIENCE[post('experience')]) ? post('experience') : 'fresher';
    $picked = array_intersect((array)($_POST['trade_pick'] ?? []), [...$tradeNames, 'Multiple Trades', 'All Trades']);
    $extra = array_filter(array_map('trim', explode(',', post('trades_extra'))));
    $j['trades'] = implode(', ', array_unique([...$picked, ...$extra]));
    $j['qualification'] = implode(', ', array_intersect((array)($_POST['qual'] ?? []), JOB_QUALIFICATIONS));
    $j['salary_min'] = max(0, (int)post('salary_min'));
    $j['salary_max'] = max(0, (int)post('salary_max'));
    $j['last_date'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('last_date')) ? post('last_date') : '';
    $j['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $j['mark_new'] = isset($_POST['mark_new']) ? 1 : 0;
    $j['status'] = post('status') === 'draft' ? 'draft' : 'published';

    // New employer typed in
    if (!$j['employer_id'] && post('new_employer') !== '') {
        $pdo->prepare('INSERT INTO employers (name, color, is_featured, sort_order) VALUES (?, ?, 1, 99)')->execute([post('new_employer'), '#2563eb']);
        $j['employer_id'] = (int)$pdo->lastInsertId();
    }
    if ($j['employer_id'] && $j['company'] === '') {
        $n = $pdo->prepare('SELECT name FROM employers WHERE id = ?'); $n->execute([$j['employer_id']]); $j['company'] = (string)$n->fetchColumn();
    }
    $j['slug'] = slugify(post('slug') ?: ($j['company'] . ' ' . $j['title']));
    if (isset(JOB_TYPES[$j['slug']])) $j['slug'] .= '-job';

    try { if ($f = handle_upload('notification_file', true)) $j['notification_url'] = upload_url($f); } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    $dup = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE slug = ? AND id <> ?'); $dup->execute([$j['slug'], $id]);
    foreach (['apply_url' => 'Apply link', 'notification_url' => 'Notification link'] as $k => $l) {
        if ($j[$k] !== '' && !preg_match('~^(https?://|/)~i', $j[$k])) $err = "$l must start with https:// or /";
    }
    if ($j['title'] === '') $err = 'Job title is required.';
    elseif (!$j['employer_id'] && $j['company'] === '') $err = 'Choose an employer or type the company name.';
    elseif ($dup->fetchColumn()) $err = 'Another job already uses this URL. Change the URL slug.';

    if (!$err) {
        $cols = array_keys(array_diff_key($j, array_flip(['id', 'posted_at', 'updated_at'])));
        $vals = array_map(fn($c) => $j[$c], $cols);
        if ($id) {
            $pdo->prepare('UPDATE jobs SET ' . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ', updated_at = ? WHERE id = ?')->execute([...$vals, now(), $id]);
            flash('Job saved.');
        } else {
            $pdo->prepare('INSERT INTO jobs (' . implode(', ', $cols) . ', posted_at, updated_at) VALUES (' . rtrim(str_repeat('?, ', count($cols) + 2), ', ') . ')')->execute([...$vals, now(), now()]);
            $id = (int)$pdo->lastInsertId();
            flash('Job published. It now shows on /jobs/, the homepage and matching trade pages.');
        }
        redirect('/admin/job-edit.php?id=' . $id);
    }
}
$jTrades = array_map('trim', explode(',', $j['trades']));
$extraTrades = implode(', ', array_diff($jTrades, [...$tradeNames, 'Multiple Trades', 'All Trades', '']));
$jQual = array_map('trim', explode(',', $j['qualification']));

admin_header($id ? 'Edit Job' : 'Add New Job', 'jobs');
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?>
      <div class="grid2">
        <section class="box">
          <h2>Job details</h2>
          <label>Job title * <input id="jtitle" name="title" required value="<?= e($j['title']) ?>" placeholder="ITI Apprentice Recruitment 2026"></label>
          <label>Employer
            <select name="employer_id"><option value="0">— choose —</option><?php foreach ($employers as $em): ?><option value="<?= $em['id'] ?>"<?= $em['id'] == $j['employer_id'] ? ' selected' : '' ?>><?= e($em['name']) ?></option><?php endforeach; ?></select>
          </label>
          <label>…or add a new employer <input name="new_employer" placeholder="Company name"><span class="hint">Add a logo later in Employers</span></label>
          <div class="grid2 tight">
            <label>Job type <select name="job_type"><?php foreach (JOB_TYPES as $k => [$l]): ?><option value="<?= $k ?>"<?= $j['job_type'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
            <label>Experience <select name="experience"><?php foreach (JOB_EXPERIENCE as $k => $l): ?><option value="<?= e($k) ?>"<?= $j['experience'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
            <label>Location <input name="location" value="<?= e($j['location']) ?>" placeholder="Pune, Maharashtra / Across India"></label>
            <label>Vacancies <input name="vacancies" value="<?= e($j['vacancies']) ?>" placeholder="150 Posts"></label>
            <label>Last date <input type="date" name="last_date" value="<?= e($j['last_date']) ?>"></label>
            <label>Age limit <input name="age_limit" value="<?= e($j['age_limit']) ?>" placeholder="18 – 25 years"></label>
          </div>
          <fieldset class="picks"><legend>Trades</legend>
<?php foreach ([...$tradeNames, 'Multiple Trades', 'All Trades'] as $tn): ?>
            <label class="check"><input type="checkbox" name="trade_pick[]" value="<?= e($tn) ?>"<?= in_array($tn, $jTrades, true) ? ' checked' : '' ?>> <?= e($tn) ?></label>
<?php endforeach; ?>
          </fieldset>
          <label>Other trades (comma separated) <input name="trades_extra" value="<?= e($extraTrades) ?>" placeholder="Machinist Grinder, Painter"></label>
          <fieldset class="picks"><legend>Qualification</legend>
<?php foreach (JOB_QUALIFICATIONS as $qn): ?>
            <label class="check"><input type="checkbox" name="qual[]" value="<?= e($qn) ?>"<?= in_array($qn, $jQual, true) ? ' checked' : '' ?>> <?= e($qn) ?></label>
<?php endforeach; ?>
          </fieldset>
        </section>
        <section class="box">
          <h2>Salary, links &amp; display</h2>
          <div class="grid2 tight">
            <label>Salary min (₹/month) <input type="number" min="0" name="salary_min" value="<?= (int)$j['salary_min'] ?>"></label>
            <label>Salary max (₹/month) <input type="number" min="0" name="salary_max" value="<?= (int)$j['salary_max'] ?>"></label>
          </div>
          <label>…or salary as text <input name="salary_text" value="<?= e($j['salary_text']) ?>" placeholder="As per Apprentice Act"><span class="hint">Shown instead of min/max</span></label>
          <label>Apply online link <input name="apply_url" value="<?= e($j['apply_url']) ?>" placeholder="https://www.apprenticeshipindia.gov.in/"></label>
          <label>Official notification link <input name="notification_url" value="<?= e($j['notification_url']) ?>" placeholder="https://… .pdf"></label>
          <label>…or upload notification PDF <input type="file" name="notification_file" accept="application/pdf"></label>
          <label>Short summary (shown under title) <textarea name="summary" rows="2" maxlength="400"><?= e($j['summary']) ?></textarea></label>
          <label class="check"><input type="checkbox" name="is_featured"<?= $j['is_featured'] ? ' checked' : '' ?>> Featured (shown first)</label>
          <label class="check"><input type="checkbox" name="mark_new"<?= $j['mark_new'] ? ' checked' : '' ?>> Show “New” badge for 10 days</label>
          <label>Status <select name="status"><option value="published"<?= $j['status'] === 'published' ? ' selected' : '' ?>>Published</option><option value="draft"<?= $j['status'] === 'draft' ? ' selected' : '' ?>>Draft (hidden)</option></select></label>
          <label>URL slug <span class="hint">/jobs/<b>slug</b>/ – leave empty to create from the title</span><input name="slug" value="<?= e($j['slug']) ?>"></label>
        </section>
      </div>
      <section class="box">
        <h2>Full job details</h2>
        <p class="muted">Eligibility, important dates, selection process, how to apply. HTML allowed (&lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;&lt;li&gt;, &lt;table&gt;, &lt;a href&gt;).</p>
        <textarea name="description" rows="14" class="code"><?= e($j['description']) ?></textarea>
      </section>
      <section class="box">
        <h2>Google SEO</h2>
        <p class="muted">Leave empty to auto-generate. Job pages include Google for Jobs data automatically.</p>
        <label>Meta title <input name="meta_title" value="<?= e($j['meta_title']) ?>" data-count="60"></label>
        <label>Meta description <textarea name="meta_description" rows="2" data-count="160"><?= e($j['meta_description']) ?></textarea></label>
      </section>
      <div class="bar"><a href="/admin/jobs.php" class="btn alt">← Back</a><span><?php if ($id): ?><a class="btn alt" href="/jobs/<?= e($j['slug']) ?>/" target="_blank">View ↗</a> <?php endif; ?><button class="btn">Save Job</button></span></div>
    </form>
<?php admin_footer();
