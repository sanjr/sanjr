<?php
/* Companies that post jobs. Logo + name are reused on every job of that company. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
$pdo = db();
$self = '/admin/employers.php';
$edit = ['id' => 0, 'name' => '', 'short_name' => '', 'logo' => '', 'color' => '#2563eb', 'website' => '', 'is_featured' => 1, 'sort_order' => 0];
if (!empty($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM employers WHERE id = ?');
    $st->execute([(int)$_GET['id']]);
    $edit = $st->fetch() ?: $edit;
}
$err = '';
if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        $eid = (int)$_POST['id'];
        $n = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE employer_id = ?'); $n->execute([$eid]);
        if ($n->fetchColumn()) { flash('This employer still has jobs. Delete or move those jobs first.', 'err'); redirect($self); }
        $l = $pdo->prepare('SELECT logo FROM employers WHERE id = ?'); $l->execute([$eid]); delete_upload((string)$l->fetchColumn());
        $pdo->prepare('DELETE FROM employers WHERE id = ?')->execute([$eid]);
        flash('Employer deleted.'); redirect($self);
    }
    $m = ['name' => post('name'), 'short_name' => mb_substr(post('short_name'), 0, 6), 'color' => preg_match('/^#[0-9a-f]{6}$/i', post('color')) ? post('color') : '#2563eb',
          'website' => post('website'), 'is_featured' => isset($_POST['is_featured']) ? 1 : 0, 'sort_order' => (int)post('sort_order'), 'logo' => $edit['logo']];
    if ($m['name'] === '') $err = 'Company name is required.';
    elseif ($m['website'] !== '' && !preg_match('~^https?://~', $m['website'])) $err = 'Website must start with https://';
    else {
        try {
            $new = handle_upload('logo');
            if ($new || isset($_POST['remove_logo'])) { delete_upload($edit['logo']); $m['logo'] = $new; }
        } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    }
    if (!$err) {
        $eid = (int)post('id');
        if ($eid) $pdo->prepare('UPDATE employers SET ' . implode(', ', array_map(fn($c) => "$c = ?", array_keys($m))) . ' WHERE id = ?')->execute([...array_values($m), $eid]);
        else $pdo->prepare('INSERT INTO employers (' . implode(', ', array_keys($m)) . ') VALUES (' . rtrim(str_repeat('?, ', count($m)), ', ') . ')')->execute(array_values($m));
        flash('Employer saved.'); redirect($self);
    }
    $edit = array_merge($edit, $m, ['id' => (int)post('id')]);
}
$rows = $pdo->query('SELECT e.*, (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = e.id) AS jobs FROM employers e ORDER BY sort_order, name')->fetchAll();
admin_header('Employers', 'employers');
?>
    <div class="grid2 wide-left">
      <section class="box tablebox">
        <h2>Companies</h2>
        <p class="muted">“Featured” companies appear in the Featured Employers row on the Jobs page.</p>
        <table><tbody>
<?php foreach ($rows as $r): ?>
          <tr<?= $r['id'] == $edit['id'] ? ' class="editing"' : '' ?>><td class="thumb"><?= employer_logo($r, 'alogo') ?></td><td><b><?= e($r['name']) ?></b><br><small class="muted"><?= (int)$r['jobs'] ?> jobs<?= $r['is_featured'] ? ' · Featured' : '' ?></small></td>
            <td class="acts"><a class="btn sm alt" href="?id=<?= $r['id'] ?>#form">Edit</a>
            <form method="post" data-confirm="Delete <?= e($r['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td class="muted">No employers yet.</td></tr><?php endif; ?>
        </tbody></table>
      </section>
      <form method="post" enctype="multipart/form-data" class="box form" id="form">
        <h2><?= $edit['id'] ? 'Edit employer' : 'Add employer' ?></h2>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <label>Company name * <input name="name" required value="<?= e($edit['name']) ?>"></label>
        <label>Logo (PNG/JPG/WEBP) <input type="file" name="logo" accept="image/*"></label>
<?php if ($edit['logo']): ?><div class="preview"><img src="<?= e(upload_url($edit['logo'])) ?>" alt=""><label class="check"><input type="checkbox" name="remove_logo"> Remove logo</label></div><?php endif; ?>
        <div class="grid2 tight">
          <label>Short name (if no logo) <input name="short_name" maxlength="6" value="<?= e($edit['short_name']) ?>" placeholder="BEL"></label>
          <label>Badge colour <input type="color" name="color" value="<?= e($edit['color']) ?>"></label>
        </div>
        <label>Website <input name="website" value="<?= e($edit['website']) ?>" placeholder="https://"></label>
        <label>Order <input type="number" name="sort_order" value="<?= (int)$edit['sort_order'] ?>"></label>
        <label class="check"><input type="checkbox" name="is_featured"<?= $edit['is_featured'] ? ' checked' : '' ?>> Featured employer</label>
        <div class="bar"><?php if ($edit['id']): ?><a href="<?= $self ?>" class="btn alt">Cancel</a><?php endif; ?><button class="btn"><?= $edit['id'] ? 'Save' : '+ Add employer' ?></button></div>
      </form>
    </div>
<?php admin_footer();
