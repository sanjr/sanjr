<?php
/* Header & footer links. Admin adds a link to any page, trade or external website. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

$loc = array_key_exists($_GET['loc'] ?? '', MENU_LOCATIONS) ? $_GET['loc'] : 'header';
$self = '/admin/menus.php?loc=' . $loc;
$edit = ['id' => 0, 'label' => '', 'url' => '', 'parent_id' => 0, 'sort_order' => 0];
if (!empty($_GET['item'])) {
    $q = db()->prepare('SELECT * FROM menu_items WHERE id = ?');
    $q->execute([(int)$_GET['item']]);
    $edit = $q->fetch() ?: $edit;
}
$err = '';
if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        $mid = (int)$_POST['item_id'];
        db()->prepare('DELETE FROM menu_items WHERE id = ? OR parent_id = ?')->execute([$mid, $mid]);
        flash('Link deleted.'); redirect($self);
    }
    $m = ['label' => post('label'), 'url' => post('url') ?: post('pick'), 'parent_id' => $loc === 'header' ? (int)post('parent_id') : 0, 'sort_order' => (int)post('sort_order')];
    if ($m['label'] === '') $err = 'Link text is required.';
    elseif (!preg_match('~^(https?://|/|#)~i', $m['url'])) $err = 'Link must start with / (your page), https:// (other website) or #.';
    elseif ($m['parent_id'] && $m['parent_id'] === (int)post('item_id')) $err = 'A link cannot be inside itself.';
    if (!$err) {
        if ($mid = (int)post('item_id')) {
            db()->prepare('UPDATE menu_items SET label = ?, url = ?, parent_id = ?, sort_order = ? WHERE id = ?')->execute([...array_values($m), $mid]);
        } else {
            db()->prepare('INSERT INTO menu_items (label, url, parent_id, sort_order, location) VALUES (?, ?, ?, ?, ?)')->execute([...array_values($m), $loc]);
        }
        flash('Link saved.'); redirect($self);
    }
    $edit = array_merge($edit, $m, ['id' => (int)post('item_id')]);
}

$tree = menu($loc);
$pages = db()->query('SELECT slug, title FROM pages ORDER BY title')->fetchAll();
$tradeList = db()->query('SELECT slug, name FROM trades ORDER BY name')->fetchAll();
admin_header('Menus & Links', 'menus');
?>
    <nav class="stabs">
<?php foreach (MENU_LOCATIONS as $k => $l): ?>
      <a href="/admin/menus.php?loc=<?= $k ?>"<?= $k === $loc ? ' class="on"' : '' ?>><?= e($l) ?></a>
<?php endforeach; ?>
    </nav>
    <div class="grid2 wide-left">
      <section class="box tablebox">
        <h2><?= e(MENU_LOCATIONS[$loc]) ?></h2>
<?php if ($loc === 'header'): ?><p class="muted">Links with sub-links become dropdowns. The “Popular Trades” footer column fills automatically from your trades.</p><?php endif; ?>
        <table><tbody>
<?php $row = function ($m, $child = false) use ($self) { ?>
          <tr<?= $child ? ' class="child"' : '' ?>><td><?= (int)$m['sort_order'] ?></td><td><?= $child ? '↳ ' : '' ?><b><?= e($m['label']) ?></b></td><td class="url"><a href="<?= e($m['url']) ?>" target="_blank"><?= e($m['url']) ?></a></td>
            <td class="acts"><a class="btn sm alt" href="<?= $self ?>&amp;item=<?= $m['id'] ?>#form">Edit</a>
            <form method="post" action="<?= $self ?>" data-confirm="Delete “<?= e($m['label']) ?>”<?= $child ? '' : ' and its sub-links' ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="item_id" value="<?= $m['id'] ?>"><button class="btn sm danger">Delete</button></form></td></tr>
<?php };
foreach ($tree as $m) { $row($m); foreach ($m['children'] as $c) $row($c, true); }
if (!$tree): ?><tr><td class="muted">No links yet.</td></tr><?php endif; ?>
        </tbody></table>
      </section>

      <form method="post" action="<?= $self ?>" class="box form" id="form">
        <h2><?= $edit['id'] ? 'Edit link' : 'Add link' ?></h2>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
        <?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int)$edit['id'] ?>">
        <label>Link text * <input name="label" required value="<?= e($edit['label']) ?>"></label>
        <label>Choose a page or trade
          <select name="pick" onchange="this.form.url.value=this.value">
            <option value="">— select —</option>
            <option value="/">Home</option><option value="/trades/">All Trades</option>
            <optgroup label="Trades"><?php foreach ($tradeList as $t): ?><option value="/trades/<?= e($t['slug']) ?>/"><?= e($t['name']) ?></option><?php endforeach; ?></optgroup>
            <optgroup label="Pages"><?php foreach ($pages as $pg): ?><option value="/<?= e($pg['slug']) ?>/"><?= e($pg['title']) ?></option><?php endforeach; ?></optgroup>
          </select></label>
        <label>…or type any URL <input name="url" value="<?= e($edit['url']) ?>" placeholder="/jobs/ or https://ncvtmis.gov.in/"></label>
<?php if ($loc === 'header'): ?>
        <label>Show inside dropdown of <select name="parent_id"><option value="0">— Top level —</option><?php foreach ($tree as $m): if ($m['id'] == $edit['id']) continue; ?><option value="<?= $m['id'] ?>"<?= $m['id'] == $edit['parent_id'] ? ' selected' : '' ?>><?= e($m['label']) ?></option><?php endforeach; ?></select></label>
<?php endif; ?>
        <label>Order <input type="number" name="sort_order" value="<?= (int)$edit['sort_order'] ?>"></label>
        <div class="bar"><?php if ($edit['id']): ?><a href="<?= $self ?>" class="btn alt">Cancel</a><?php endif; ?><button class="btn"><?= $edit['id'] ? 'Save link' : '+ Add link' ?></button></div>
      </form>
    </div>
<?php admin_footer();
