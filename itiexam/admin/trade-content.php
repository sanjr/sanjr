<?php
/* Manage the content sections of one trade page (mock tests, syllabus, notes, papers…). */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM trades WHERE id = ?');
$st->execute([$id]);
$t = $st->fetch();
if (!$t) redirect('/admin/trades.php');

$sections = trade_sections();
$sec = array_key_exists($_GET['section'] ?? '', $sections) ? $_GET['section'] : 'mock_tests';
$cfg = $sections[$sec];
$self = "/admin/trade-content.php?id=$id&section=$sec";
$touch = fn() => db()->prepare('UPDATE trades SET updated_at = ? WHERE id = ?')->execute([now(), $id]);
$isVideo = $sec === 'videos';
if ($isVideo) require_once __DIR__ . '/inc/cbt-import-lib.php';      // LEVELS, level_options()

$edit = ['id' => 0, 'title' => '', 'subtitle' => '', 'url' => '', 'label' => '', 'icon' => '', 'questions' => 0, 'minutes' => 0, 'is_new' => 0, 'sort_order' => 0, 'level' => '', 'image' => ''];
if (!empty($_GET['item'])) {
    $q = db()->prepare('SELECT * FROM trade_items WHERE id = ? AND trade_id = ?');
    $q->execute([(int)$_GET['item'], $id]);
    $edit = $q->fetch() ?: $edit;
}

$err = '';
if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $old = db()->prepare('SELECT url, image FROM trade_items WHERE id = ? AND trade_id = ?');
        $old->execute([(int)$_POST['item_id'], $id]);
        $old = $old->fetch();
        db()->prepare('DELETE FROM trade_items WHERE id = ? AND trade_id = ?')->execute([(int)$_POST['item_id'], $id]);
        if ($old) video_cleanup($old['url'], $old['image']);           // an uploaded video no other trade uses
        $touch(); flash('Item deleted.'); redirect($self);
    }
    if ($action === 'bulk_yt' && $isVideo) {
        // Many YouTube links at once (one per line); titles come from YouTube
        $lvl = in_array(post('level'), LEVELS, true) ? post('level') : '';
        $have = db()->prepare("SELECT url FROM trade_items WHERE trade_id = ? AND section = 'videos'");
        $have->execute([$id]);
        $haveIds = array_filter(array_map('youtube_id', $have->fetchAll(PDO::FETCH_COLUMN)));
        $m = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM trade_items WHERE trade_id = ? AND section = ?');
        $m->execute([$id, $sec]);
        $order = (int)$m->fetchColumn();
        $ins = db()->prepare("INSERT INTO trade_items (trade_id, section, title, url, level, sort_order, created_at) VALUES (?, 'videos', ?, ?, ?, ?, ?)");
        $added = $skipped = 0;
        foreach (preg_split('/[\r\n,]+/', (string)($_POST['links'] ?? '')) as $line) {
            if (($line = trim($line)) === '') continue;
            $yt = youtube_id($line);
            if ($yt === '' || in_array($yt, $haveIds, true)) { $skipped++; continue; }
            $haveIds[] = $yt;
            $ins->execute([$id, youtube_title($yt) ?: $t['name'] . ' video ' . ($order + 1), 'https://www.youtube.com/watch?v=' . $yt, $lvl, ++$order, now()]);
            $added++;
        }
        $touch();
        flash("$added videos added." . ($skipped ? " $skipped links skipped (not a YouTube video link, or already added)." : ''), $added ? 'ok' : 'err');
        redirect($self);
    }
    if ($action === 'copy') {
        // Copy one section from another trade as a starting point
        $from = (int)$_POST['from_trade'];
        $rows = db()->prepare('SELECT * FROM trade_items WHERE trade_id = ? AND section = ? ORDER BY sort_order, id');
        $rows->execute([$from, $sec]);
        $ins = db()->prepare('INSERT INTO trade_items (trade_id, section, title, subtitle, url, label, icon, questions, minutes, is_new, sort_order, level, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $n = 0;
        foreach ($rows as $r) { $ins->execute([$id, $sec, $r['title'], $r['subtitle'], $r['url'], $r['label'], $r['icon'], $r['questions'], $r['minutes'], $r['is_new'], $r['sort_order'], $r['level'], $r['image'], now()]); $n++; }
        $touch(); flash("$n items copied. Edit them for this trade."); redirect($self);
    }
    if ($action === 'save') {
        $item = [
            'title' => post('title'), 'subtitle' => post('subtitle'), 'url' => post('url'), 'label' => post('label'),
            'icon' => in_array(post('icon'), ICONS, true) ? post('icon') : '', 'questions' => (int)post('questions'),
            'minutes' => (int)post('minutes'), 'is_new' => isset($_POST['is_new']) ? 1 : 0, 'sort_order' => (int)post('sort_order'),
        ];
        $itemId = (int)post('item_id');
        $prev = ['url' => '', 'image' => ''];
        if ($itemId) { $pq = db()->prepare('SELECT url, image FROM trade_items WHERE id = ? AND trade_id = ?'); $pq->execute([$itemId, $id]); $prev = $pq->fetch() ?: $prev; }
        if ($isVideo) {
            $item['level'] = in_array(post('level'), LEVELS, true) ? post('level') : '';
            $item['label'] = mb_substr(post('label'), 0, 12);                              // duration, e.g. 12:30
            $item['image'] = $prev['image'];
            if (post('source') === 'file') {
                $v = post('video_url') ?: $prev['url'];
                if (!str_starts_with($v, UPLOAD_URL . '/videos/') || !is_file(UPLOAD_DIR . '/videos/' . basename(rawurldecode($v)))) $err = 'Upload the video file first (wait for 100%).';
                $item['url'] = $v;
            } else {
                $yt = youtube_id(post('yt_url'));
                if ($yt === '') $err = 'Paste a YouTube video link, e.g. https://youtu.be/… or https://www.youtube.com/watch?v=…';
                else {
                    $item['url'] = 'https://www.youtube.com/watch?v=' . $yt;
                    if ($item['title'] === '') $item['title'] = youtube_title($yt) ?: $t['name'] . ' video';
                }
            }
            try {
                if ($img = handle_upload('thumb')) $item['image'] = $img;
                elseif (isset($_POST['no_thumb'])) $item['image'] = '';
            } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
            if (!$itemId) $item['created_at'] = now();
        } else {
            if ($item['url'] !== '' && !preg_match('~^(https?://|/|#)~i', $item['url'])) $err = 'Link must start with https://, / or #.';
            try { if ($file = handle_upload('file', true)) $item['url'] = upload_url($file); } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
        }
        if ($item['title'] === '') $err = $err ?: 'Title is required.';
        if (!$err) {
            if ($itemId) {
                $sql = 'UPDATE trade_items SET ' . implode(', ', array_map(fn($c) => "$c = ?", array_keys($item))) . ' WHERE id = ? AND trade_id = ?';
                db()->prepare($sql)->execute([...array_values($item), $itemId, $id]);
                flash('Item updated.');
            } else {
                if (!$item['sort_order']) {
                    $m = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM trade_items WHERE trade_id = ? AND section = ?');
                    $m->execute([$id, $sec]);
                    $item['sort_order'] = (int)$m->fetchColumn();
                }
                $sql = 'INSERT INTO trade_items (trade_id, section, ' . implode(', ', array_keys($item)) . ') VALUES (?, ?' . str_repeat(', ?', count($item)) . ')';
                db()->prepare($sql)->execute([$id, $sec, ...array_values($item)]);
                flash('Item added.');
            }
            // a replaced uploaded video / thumbnail is removed once nothing uses it
            if ($isVideo && $itemId) video_cleanup($prev['url'] !== $item['url'] ? $prev['url'] : '', $prev['image'] !== $item['image'] ? $prev['image'] : '');
            $touch(); redirect($self);
        }
        $edit = array_merge($edit, $item, ['id' => $itemId]);
    }
}

$items = trade_items($id);
$others = db()->prepare('SELECT t.id, t.name FROM trades t WHERE t.id <> ? AND EXISTS (SELECT 1 FROM trade_items i WHERE i.trade_id = t.id AND i.section = ?) ORDER BY t.name');
$others->execute([$id, $sec]);
$others = $others->fetchAll();
$f = $cfg['fields'];

admin_header('Content: ' . $t['name'], 'trades');
?>
    <div class="bar"><span><a class="btn alt" href="/admin/trades.php">← Trades</a> <a class="btn alt" href="/admin/trade-edit.php?id=<?= $id ?>">Edit trade details</a></span><a class="btn" href="/trades/<?= e($t['slug']) ?>/" target="_blank">View Page ↗</a></div>
    <nav class="stabs">
<?php foreach ($sections as $k => $s): ?>
      <a href="/admin/trade-content.php?id=<?= $id ?>&amp;section=<?= $k ?>"<?= $k === $sec ? ' class="on"' : '' ?>><?= icon($s['icon']) ?><?= e($s['label']) ?> <em><?= count($items[$k]) ?></em></a>
<?php endforeach; ?>
    </nav>

    <div class="grid2 wide-left">
      <section class="box tablebox">
        <h2><?= e($cfg['label']) ?></h2>
<?php if ($items[$sec]): ?>
        <table>
          <thead><tr><th>#</th><th>Title</th><th><?= $isVideo ? 'Video' : 'Link' ?></th><th></th></tr></thead>
          <tbody>
<?php foreach ($items[$sec] as $it): ?>
            <tr<?= $it['id'] == $edit['id'] ? ' class="editing"' : '' ?>>
              <td><?= (int)$it['sort_order'] ?></td>
              <td><b><?= e($it['title']) ?></b><?= $it['label'] ? ' <span class="pill">' . e($it['label']) . '</span>' : '' ?><?= $it['is_new'] ? ' <span class="pill ok">New</span>' : '' ?><br><small class="muted"><?= e(mb_strimwidth($it['subtitle'], 0, 90, '…')) ?><?= $it['questions'] ? (int)$it['questions'] . ' Q · ' . (int)$it['minutes'] . ' min' : '' ?></small></td>
<?php if ($isVideo): $v = video_info($it); ?>
              <td class="url"><a href="<?= e($v['watch'] ?: $v['src']) ?>" target="_blank"><?= $v['thumb'] ? '<img class="vthumb" src="' . e($v['thumb']) . '" alt="" loading="lazy">' : '<span class="vthumb ph">▶</span>' ?></a><small class="muted"><?= $v['type'] === 'youtube' ? 'YouTube' : 'Uploaded' ?><?= $it['level'] ? ' · ' . e($it['level']) : '' ?></small></td>
<?php else: ?>
              <td class="url"><?= $it['url'] ? '<a href="' . e($it['url']) . '" target="_blank">' . e(mb_strimwidth($it['url'], 0, 40, '…')) . '</a>' : '<span class="muted">—</span>' ?></td>
<?php endif; ?>
              <td class="acts">
                <a class="btn sm alt" href="<?= $self ?>&amp;item=<?= $it['id'] ?>#form">Edit</a>
                <form method="post" action="<?= $self ?>" data-confirm="Delete this item?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="item_id" value="<?= $it['id'] ?>"><button class="btn sm danger">Delete</button></form>
              </td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
<?php else: ?>
        <p class="muted">No items yet. This section stays hidden on the trade page until you add one.</p>
<?php endif; ?>
<?php if ($others): ?>
        <form method="post" action="<?= $self ?>" class="copy"><?= csrf_field() ?><input type="hidden" name="action" value="copy">
          <label>Copy this section from <select name="from_trade"><?php foreach ($others as $o): ?><option value="<?= $o['id'] ?>"><?= e($o['name']) ?></option><?php endforeach; ?></select></label>
          <button class="btn sm alt">Copy</button>
        </form>
<?php endif; ?>
      </section>

<?php if ($isVideo):
    $vi = $edit['url'] !== '' ? video_info($edit) : ['type' => 'youtube', 'thumb' => '', 'src' => ''];
    $src = $vi['type'] === 'file' ? 'file' : 'youtube';
    // Size of one upload piece: what the hosting accepts in one request, minus room for the form fields
    $iniBytes = function (string $v): int { $n = (int)$v; return match (strtolower(substr(trim($v), -1))) { 'g' => $n << 30, 'm' => $n << 20, 'k' => $n << 10, default => $n }; };
    $limit = min(array_filter([$iniBytes((string)ini_get('upload_max_filesize')), $iniBytes((string)ini_get('post_max_size'))]) ?: [8 << 20]);
    $chunk = max(512 << 10, min(8 << 20, $limit - (256 << 10)));
?>
      <div>
      <form method="post" action="<?= $self ?>" enctype="multipart/form-data" class="box form vform" id="form">
        <h2><?= $edit['id'] ? 'Edit video' : 'Add a video' ?></h2>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save"><input type="hidden" name="item_id" value="<?= (int)$edit['id'] ?>">
        <div class="vsrc">
          <label><input type="radio" name="source" value="youtube"<?= $src === 'youtube' ? ' checked' : '' ?>> ▶ YouTube link</label>
          <label><input type="radio" name="source" value="file"<?= $src === 'file' ? ' checked' : '' ?>> ⬆ Upload video</label>
        </div>
        <div class="vpane" data-src="youtube"<?= $src === 'youtube' ? '' : ' hidden' ?>>
          <label>YouTube video link * <input name="yt_url" id="yt-url" value="<?= e($vi['type'] === 'youtube' ? $edit['url'] : '') ?>" placeholder="https://youtu.be/…  or  https://www.youtube.com/watch?v=…" inputmode="url"></label>
          <div class="vprev" id="yt-prev"<?= $vi['type'] === 'youtube' && $vi['thumb'] ? '' : ' hidden' ?>><img src="<?= e($vi['type'] === 'youtube' ? $vi['thumb'] : '') ?>" alt=""><span>✓ YouTube video found</span></div>
          <p class="hint">Leave the title empty – it is filled in from YouTube.</p>
        </div>
        <div class="vpane" data-src="file"<?= $src === 'file' ? '' : ' hidden' ?>>
          <label>Video file (MP4 or WebM, up to <?= MAX_VIDEO_BYTES >> 20 ?> MB) <input type="file" id="v-file" accept="video/mp4,video/webm,video/ogg,.mp4,.webm,.m4v"></label>
          <input type="hidden" name="video_url" id="v-url" value="<?= e($vi['type'] === 'file' ? $edit['url'] : '') ?>">
          <div class="vprog" id="v-prog" hidden><i></i><span>0%</span></div>
          <p class="vstat" id="v-stat"><?= $vi['type'] === 'file' ? '✓ Current video: ' . e(basename($edit['url'])) : '' ?></p>
          <label>Thumbnail image (optional, 16:9) <input type="file" name="thumb" accept="image/*"></label>
<?php if ($edit['image']): ?>          <label class="check"><input type="checkbox" name="no_thumb"> Remove the current thumbnail</label>
<?php endif; ?>
        </div>
        <label>Title <span class="hint"><?= $src === 'youtube' ? 'optional for YouTube' : '' ?></span><input name="title" value="<?= e($edit['title']) ?>" maxlength="255"></label>
        <label>Short description <input name="subtitle" value="<?= e($edit['subtitle']) ?>" placeholder="e.g. How to use a digital multimeter"></label>
        <div class="grid2 tight">
          <label>Year / Semester <select name="level"><?= level_options($edit['level'] ?? '', 'All years') ?></select></label>
          <label>Duration <input name="label" value="<?= e($edit['label']) ?>" placeholder="12:30" maxlength="12"></label>
        </div>
        <label>Order <input type="number" name="sort_order" value="<?= (int)$edit['sort_order'] ?>"><span class="hint">0 = add at the end</span></label>
        <div class="bar"><?php if ($edit['id']): ?><a href="<?= $self ?>" class="btn alt">Cancel</a><?php endif; ?><button class="btn" id="v-save"><?= $edit['id'] ? 'Save changes' : '+ Add video' ?></button></div>
      </form>

      <form method="post" action="<?= $self ?>" class="box form">
        <h2>Add many YouTube videos at once</h2>
        <?= csrf_field() ?><input type="hidden" name="action" value="bulk_yt">
        <label>YouTube links – one per line <textarea name="links" rows="5" placeholder="https://youtu.be/…&#10;https://www.youtube.com/watch?v=…"></textarea></label>
        <label>Year / Semester for all <select name="level"><?= level_options('', 'All years') ?></select></label>
        <div class="bar"><span class="hint">Titles are taken from YouTube</span><button class="btn">+ Add all</button></div>
      </form>
      </div>
      <style>
        .vsrc { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
        .vsrc label { display: flex; align-items: center; gap: 6px; margin: 0; padding: 9px 14px; border: 1px solid #d6deef; border-radius: 10px; cursor: pointer; font-weight: 600; }
        .vsrc label:has(input:checked) { border-color: #1f4fd6; background: #eef3ff; color: #1f4fd6; }
        .vsrc input { width: auto !important; margin: 0 !important; }
        .vpane[hidden], .vprev[hidden], .vprog[hidden] { display: none; }
        .vprev { display: flex; align-items: center; gap: 10px; margin: -4px 0 10px; color: #166534; font-weight: 600; font-size: 13px; }
        .vprev img { width: 128px; aspect-ratio: 16 / 9; object-fit: cover; border-radius: 8px; }
        .vprog { position: relative; height: 26px; border-radius: 8px; background: #eef2f9; overflow: hidden; margin: 4px 0 6px; }
        .vprog i { position: absolute; inset: 0 auto 0 0; width: 0; background: linear-gradient(90deg, #1f4fd6, #3d6ff0); transition: width .2s; }
        .vprog span { position: relative; display: block; text-align: center; line-height: 26px; font-size: 13px; font-weight: 700; color: #0b1430; }
        .vstat { font-size: 13px; color: #166534; margin: 0 0 10px; min-height: 1em; }
        .vstat.bad { color: #be123c; }
        .vthumb { width: 96px; aspect-ratio: 16 / 9; object-fit: cover; border-radius: 6px; display: block; background: #0b2a6f; }
        .vthumb.ph { display: grid; place-items: center; color: #fff; font-size: 20px; }
      </style>
      <script>
      (function () {
        var form = document.getElementById('form'), file = document.getElementById('v-file'), vurl = document.getElementById('v-url'),
            prog = document.getElementById('v-prog'), stat = document.getElementById('v-stat'), save = document.getElementById('v-save'),
            yt = document.getElementById('yt-url'), prev = document.getElementById('yt-prev'), CHUNK = <?= (int)$chunk ?>, MAX = <?= (int)MAX_VIDEO_BYTES ?>,
            csrf = form.querySelector('input[name=csrf]').value, busy = false;
        form.querySelectorAll('input[name=source]').forEach(function (r) {
          r.addEventListener('change', function () { form.querySelectorAll('.vpane').forEach(function (p) { p.hidden = p.dataset.src !== r.value; }); });
        });
        function ytId(u) {
          u = (u || '').trim();
          var m = u.match(/^https?:\/\/(?:www\.|m\.|music\.)?(?:youtube\.com|youtube-nocookie\.com|youtu\.be)\/(.*)$/i);
          if (!m) return '';
          var r = m[1].match(/^([A-Za-z0-9_-]{11})(?:[?&#]|$)/) || m[1].match(/(?:shorts|embed|live|v)\/([A-Za-z0-9_-]{11})/) || m[1].match(/[?&]v=([A-Za-z0-9_-]{11})/);
          return r ? r[1] : '';
        }
        yt.addEventListener('input', function () {
          var id = ytId(yt.value);
          prev.hidden = !id;
          if (id) prev.querySelector('img').src = 'https://i.ytimg.com/vi/' + id + '/mqdefault.jpg';
        });
        function setProg(p) { prog.hidden = false; prog.querySelector('i').style.width = p + '%'; prog.querySelector('span').textContent = p + '%'; }
        function fail(msg) { busy = false; save.disabled = false; stat.className = 'vstat bad'; stat.textContent = '✕ ' + msg; }
        file.addEventListener('change', function () {
          var f = file.files[0];
          if (!f) return;
          if (f.size > MAX) { fail('Video is larger than ' + Math.round(MAX / 1048576) + ' MB.'); return; }
          var id = Array.from(crypto.getRandomValues(new Uint8Array(16)), function (b) { return ('0' + b.toString(16)).slice(-2); }).join(''),
              total = Math.max(1, Math.ceil(f.size / CHUNK)), i = 0;
          busy = true; save.disabled = true; vurl.value = ''; stat.className = 'vstat'; stat.textContent = 'Uploading ' + f.name + ' …'; setProg(0);
          (function next() {
            var fd = new FormData();
            fd.append('csrf', csrf); fd.append('upload_id', id); fd.append('index', i); fd.append('total', total); fd.append('size', f.size);
            fd.append('chunk', f.slice(i * CHUNK, (i + 1) * CHUNK), 'chunk');
            fetch('/admin/video-upload.php', { method: 'POST', body: fd, credentials: 'same-origin' })
              .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Server error (' + r.status + ')' }; }); })
              .then(function (j) {
                if (!j.ok) return fail(j.error || 'Upload failed.');
                i++; setProg(Math.round(i * 100 / total));
                if (i < total) return next();
                busy = false; save.disabled = false; vurl.value = j.url;
                stat.className = 'vstat'; stat.textContent = '✓ Uploaded (' + j.mb + ' MB). Now add a title and save.';
                var t = form.querySelector('input[name=title]');
                if (!t.value) t.value = f.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ');
              })
              .catch(function () { fail('Network problem – check the internet and choose the file again.'); });
          })();
        });
        form.addEventListener('submit', function (e) {
          if (busy) { e.preventDefault(); alert('Please wait until the video upload reaches 100%.'); }
        });
      })();
      </script>
<?php else: ?>
      <form method="post" action="<?= $self ?>" enctype="multipart/form-data" class="box form" id="form">
        <h2><?= $edit['id'] ? 'Edit item' : 'Add item' ?></h2>
<?php if ($sec === 'mock_tests'): ?>        <p class="flash">Have a test made as an HTML page? <a href="/admin/cbt.php?trade=<?= $id ?>#form">Upload it in CBT Tests</a> – it is added to this list automatically.</p>
<?php endif; ?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save"><input type="hidden" name="item_id" value="<?= (int)$edit['id'] ?>">
        <label><?= $sec === 'faq' ? 'Question' : 'Title' ?> * <input name="title" required value="<?= e($edit['title']) ?>"></label>
<?php if (!empty($f['subtitle'])): ?>
        <label><?= $sec === 'faq' ? 'Answer' : 'Subtitle' ?> <?php if ($sec === 'faq'): ?><textarea name="subtitle" rows="4"><?= e($edit['subtitle']) ?></textarea><?php else: ?><input name="subtitle" value="<?= e($edit['subtitle']) ?>"><?php endif; ?></label>
<?php endif; ?>
<?php if (isset($f['label'])): ?>
        <label><?= e($f['label']) ?> <input name="label" value="<?= e($edit['label']) ?>" maxlength="40"></label>
<?php endif; ?>
<?php if (!empty($f['questions'])): ?>
        <div class="grid2 tight">
          <label>Questions <input type="number" min="0" name="questions" value="<?= (int)$edit['questions'] ?>"></label>
          <label>Minutes <input type="number" min="0" name="minutes" value="<?= (int)$edit['minutes'] ?>"></label>
        </div>
        <label class="check"><input type="checkbox" name="is_new"<?= $edit['is_new'] ? ' checked' : '' ?>> Show “New” badge</label>
<?php endif; ?>
<?php if ($sec !== 'faq'): ?>
        <label>Link <span class="hint">/page/ · https://… · #section</span><input name="url" value="<?= e($edit['url']) ?>" placeholder="/mock-test/copa-set-1/"></label>
        <label>…or upload a file (PDF or image) <input type="file" name="file" accept="application/pdf,image/*"></label>
<?php if ($sec !== 'syllabus' && $sec !== 'previous_papers'): ?>
        <label>Icon <select name="icon"><option value="">Default</option><?php foreach (ICONS as $ic): ?><option<?= $edit['icon'] === $ic ? ' selected' : '' ?>><?= $ic ?></option><?php endforeach; ?></select></label>
<?php endif; endif; ?>
        <label>Order <input type="number" name="sort_order" value="<?= (int)$edit['sort_order'] ?>"><span class="hint">0 = add at the end</span></label>
        <div class="bar"><?php if ($edit['id']): ?><a href="<?= $self ?>" class="btn alt">Cancel</a><?php endif; ?><button class="btn"><?= $edit['id'] ? 'Save changes' : '+ Add item' ?></button></div>
      </form>
<?php endif; ?>
    </div>
<?php admin_footer();
