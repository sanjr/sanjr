<?php
/* Media Library: every image and PDF in uploads/. Upload many at once, copy the link, delete. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

$files = [];
foreach (glob(UPLOAD_DIR . '/*.{jpg,jpeg,png,webp,gif,pdf}', GLOB_BRACE) ?: [] as $path) {
    $n = basename($path);
    $files[] = ['name' => $n, 'url' => upload_url($n), 'size' => filesize($path), 'time' => filemtime($path), 'pdf' => str_ends_with($n, '.pdf')];
}
usort($files, fn($a, $b) => $b['time'] <=> $a['time']);

/* JSON list for the editor's image picker */
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode(array_values(array_map(fn($f) => ['url' => $f['url'], 'name' => $f['name']], array_filter($files, fn($f) => !$f['pdf']))));
    exit;
}

if (is_post()) {
    csrf_check();
    $ajax = isset($_POST['ajax']);
    if (($_POST['action'] ?? '') === 'delete') {
        $n = basename((string)$_POST['name']);
        delete_upload($n);
        flash('Deleted ' . $n . '.');
        redirect('/admin/media.php');
    }
    // Upload (one or many)
    $saved = []; $errors = [];
    $up = $_FILES['files'] ?? null;
    if ($up && is_array($up['name'])) {
        foreach ($up['name'] as $i => $orig) {
            if ($up['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            try { $saved[] = save_upload(['name' => $orig, 'type' => $up['type'][$i], 'tmp_name' => $up['tmp_name'][$i], 'error' => $up['error'][$i], 'size' => $up['size'][$i]], true); }
            catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
        }
    }
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode($saved ? ['url' => upload_url($saved[0])] : ['error' => $errors[0] ?? 'Choose a file to upload.']);
        exit;
    }
    if ($saved) flash(count($saved) . ' file(s) uploaded.');
    foreach ($errors as $er) flash($er, 'err');
    redirect('/admin/media.php');
}

$type = in_array($_GET['type'] ?? '', ['img', 'pdf'], true) ? $_GET['type'] : '';
$q = trim($_GET['q'] ?? '');
$list = array_filter($files, fn($f) => (!$type || ($type === 'pdf') === $f['pdf']) && ($q === '' || stripos($f['name'], $q) !== false));
$size = fn($b) => $b > 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB';

admin_header('Media Library', 'media');
?>
    <form method="post" enctype="multipart/form-data" class="box uploader" id="drop">
      <?= csrf_field() ?>
      <span class="up-ic"><?= icon('download') ?></span>
      <div><b>Upload photos or PDFs</b><small class="muted">JPG, PNG, WEBP, GIF (max <?= MAX_UPLOAD_BYTES >> 20 ?> MB) or PDF (max <?= MAX_PDF_BYTES >> 20 ?> MB). You can choose many files together.</small></div>
      <input type="file" name="files[]" multiple accept="image/*,application/pdf" required>
      <button class="btn">Upload</button>
    </form>

    <div class="bar">
      <form class="inline-filter" method="get">
        <input name="q" value="<?= e($q) ?>" placeholder="Search file name…">
        <select name="type"><option value="">All files</option><option value="img"<?= $type === 'img' ? ' selected' : '' ?>>Images</option><option value="pdf"<?= $type === 'pdf' ? ' selected' : '' ?>>PDFs</option></select>
        <button class="btn alt">Filter</button>
      </form>
      <span class="muted"><?= count($list) ?> file<?= count($list) === 1 ? '' : 's' ?></span>
    </div>

    <div class="media-grid">
<?php foreach ($list as $f): $used = upload_usage($f['name']); ?>
      <figure class="mcard">
        <a href="<?= e($f['url']) ?>" target="_blank" class="mthumb"><?php if ($f['pdf']): ?><span class="pdf"><?= icon('pdf') ?>PDF</span><?php else: ?><img src="<?= e($f['url']) ?>" alt="" loading="lazy"><?php endif; ?></a>
        <figcaption>
          <code title="<?= e($f['name']) ?>"><?= e($f['name']) ?></code>
          <small class="muted"><?= $size($f['size']) ?> · <?= date('d M Y', $f['time']) ?><?= $used ? ' · <b class="inuse">In use: ' . e(implode(', ', $used)) . '</b>' : '' ?></small>
          <span class="acts">
            <button type="button" class="btn sm alt" data-copy="<?= e($f['url']) ?>">Copy link</button>
            <form method="post" data-confirm="<?= $used ? 'This file is used in: ' . e(implode(', ', $used)) . '. Those places will show a broken image. Delete anyway?' : 'Delete this file?' ?>"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="name" value="<?= e($f['name']) ?>"><button class="btn sm danger">Delete</button></form>
          </span>
        </figcaption>
      </figure>
<?php endforeach; ?>
<?php if (!$list): ?><p class="muted">No files yet. Upload your first photo or PDF above.</p><?php endif; ?>
    </div>
    <script>
      document.querySelectorAll('[data-copy]').forEach(function (b) {
        b.addEventListener('click', function () {
          var url = b.getAttribute('data-copy');
          (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(function () { b.textContent = 'Copied ✓'; },
            function () { prompt('Copy this link:', url); });
          setTimeout(function () { b.textContent = 'Copy link'; }, 1600);
        });
      });
    </script>
<?php admin_footer();
