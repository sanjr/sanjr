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

$edit = ['id' => 0, 'title' => '', 'subtitle' => '', 'url' => '', 'label' => '', 'icon' => '', 'questions' => 0, 'minutes' => 0, 'is_new' => 0, 'sort_order' => 0];
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
        db()->prepare('DELETE FROM trade_items WHERE id = ? AND trade_id = ?')->execute([(int)$_POST['item_id'], $id]);
        $touch(); flash('Item deleted.'); redirect($self);
    }
    if ($action === 'copy') {
        // Copy one section from another trade as a starting point
        $from = (int)$_POST['from_trade'];
        $rows = db()->prepare('SELECT * FROM trade_items WHERE trade_id = ? AND section = ? ORDER BY sort_order, id');
        $rows->execute([$from, $sec]);
        $ins = db()->prepare('INSERT INTO trade_items (trade_id, section, title, subtitle, url, label, icon, questions, minutes, is_new, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $n = 0;
        foreach ($rows as $r) { $ins->execute([$id, $sec, $r['title'], $r['subtitle'], $r['url'], $r['label'], $r['icon'], $r['questions'], $r['minutes'], $r['is_new'], $r['sort_order']]); $n++; }
        $touch(); flash("$n items copied. Edit them for this trade."); redirect($self);
    }
    if ($action === 'save') {
        $item = [
            'title' => post('title'), 'subtitle' => post('subtitle'), 'url' => post('url'), 'label' => post('label'),
            'icon' => in_array(post('icon'), ICONS, true) ? post('icon') : '', 'questions' => (int)post('questions'),
            'minutes' => (int)post('minutes'), 'is_new' => isset($_POST['is_new']) ? 1 : 0, 'sort_order' => (int)post('sort_order'),
        ];
        if ($item['url'] !== '' && !preg_match('~^(https?://|/|#)~i', $item['url'])) $err = 'Link must start with https://, / or #.';
        try { if ($file = handle_upload('file', true)) $item['url'] = upload_url($file); } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
        if ($item['title'] === '') $err = 'Title is required.';
        if (!$err) {
            $itemId = (int)post('item_id');
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
            $touch(); redirect($self);
        }
        $edit = array_merge($edit, $item, ['id' => (int)post('item_id')]);
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
          <thead><tr><th>#</th><th>Title</th><th>Link</th><th></th></tr></thead>
          <tbody>
<?php foreach ($items[$sec] as $it): ?>
            <tr<?= $it['id'] == $edit['id'] ? ' class="editing"' : '' ?>>
              <td><?= (int)$it['sort_order'] ?></td>
              <td><b><?= e($it['title']) ?></b><?= $it['label'] ? ' <span class="pill">' . e($it['label']) . '</span>' : '' ?><?= $it['is_new'] ? ' <span class="pill ok">New</span>' : '' ?><br><small class="muted"><?= e(mb_strimwidth($it['subtitle'], 0, 90, '…')) ?><?= $it['questions'] ? (int)$it['questions'] . ' Q · ' . (int)$it['minutes'] . ' min' : '' ?></small></td>
              <td class="url"><?= $it['url'] ? '<a href="' . e($it['url']) . '" target="_blank">' . e(mb_strimwidth($it['url'], 0, 40, '…')) . '</a>' : '<span class="muted">—</span>' ?></td>
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
    </div>
<?php admin_footer();
