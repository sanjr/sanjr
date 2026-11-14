<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
$pdo = db();

if (is_post()) {
    csrf_check();
    $jid = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM jobs WHERE id = ?')->execute([$jid]);
        flash('Job deleted.');
    } elseif (($_POST['action'] ?? '') === 'duplicate') {
        $st = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
        $st->execute([$jid]);
        if ($j = $st->fetch()) {
            unset($j['id']);
            $j['slug'] = $j['slug'] . '-copy-' . substr(bin2hex(random_bytes(3)), 0, 4);
            $j['title'] .= ' (copy)';
            $j['status'] = 'draft';
            $j['posted_at'] = $j['updated_at'] = now();
            $pdo->prepare('INSERT INTO jobs (' . implode(', ', array_keys($j)) . ') VALUES (' . rtrim(str_repeat('?, ', count($j)), ', ') . ')')->execute(array_values($j));
            flash('Copy created as draft. Edit it and publish.');
            redirect('/admin/job-edit.php?id=' . $pdo->lastInsertId());
        }
    }
    redirect('/admin/jobs.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'type', 'show', 'page']))));
}

$q = trim($_GET['q'] ?? '');
$type = isset(JOB_TYPES[$_GET['type'] ?? '']) ? $_GET['type'] : '';
$show = in_array($_GET['show'] ?? '', ['open', 'closed', 'draft'], true) ? $_GET['show'] : '';
$where = ['1=1']; $args = [];
if ($q !== '') { $where[] = '(j.title LIKE ? OR j.company LIKE ? OR e.name LIKE ? OR j.trades LIKE ?)'; array_push($args, ...array_fill(0, 4, "%$q%")); }
if ($type) { $where[] = 'j.job_type = ?'; $args[] = $type; }
$today = date('Y-m-d');
if ($show === 'open') $where[] = job_active_sql();
if ($show === 'closed') { $where[] = "j.last_date <> '' AND j.last_date < ?"; $args[] = $today; }
if ($show === 'draft') $where[] = "j.status = 'draft'";
$from = 'FROM jobs j LEFT JOIN employers e ON e.id = j.employer_id WHERE ' . implode(' AND ', $where);
$c = $pdo->prepare("SELECT COUNT(*) $from"); $c->execute($args); $total = (int)$c->fetchColumn();
$per = 25; $pages = max(1, (int)ceil($total / $per)); $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$st = $pdo->prepare("SELECT j.*, e.name AS emp_name, e.short_name, e.logo AS emp_logo, e.color AS emp_color $from ORDER BY j.posted_at DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args);
$rows = $st->fetchAll();

admin_header('Jobs', 'jobs');
?>
    <div class="bar">
      <form class="inline-filter" method="get">
        <input name="q" value="<?= e($q) ?>" placeholder="Search title, company, trade…">
        <select name="type"><option value="">All types</option><?php foreach (JOB_TYPES as $k => [$l]): ?><option value="<?= $k ?>"<?= $type === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
        <select name="show"><option value="">All</option><option value="open"<?= $show === 'open' ? ' selected' : '' ?>>Open</option><option value="closed"<?= $show === 'closed' ? ' selected' : '' ?>>Closed (last date passed)</option><option value="draft"<?= $show === 'draft' ? ' selected' : '' ?>>Drafts</option></select>
        <button class="btn alt">Filter</button>
      </form>
      <a class="btn" href="/admin/job-edit.php">+ Add New Job</a>
    </div>
    <div class="box tablebox"><table>
      <thead><tr><th></th><th>Job</th><th>Type</th><th>Last date</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
<?php foreach ($rows as $r): $exp = job_expired($r); ?>
        <tr>
          <td class="thumb"><?= employer_logo($r, 'alogo') ?></td>
          <td><b><?= e(company_name($r)) ?></b> – <?= e($r['title']) ?><br><small class="muted"><?= e($r['trades']) ?> · <?= e($r['location']) ?></small></td>
          <td><?= e(JOB_TYPES[$r['job_type']][3] ?? $r['job_type']) ?></td>
          <td<?= $exp ? ' class="muted"' : '' ?>><?= e($r['last_date'] ? nice_date($r['last_date']) : '—') ?></td>
          <td><span class="pill <?= $r['status'] !== 'published' ? 'off' : ($exp ? 'off' : 'ok') ?>"><?= $r['status'] !== 'published' ? 'Draft' : ($exp ? 'Closed' : 'Open') ?></span><?= $r['is_featured'] ? ' <span class="pill star">Featured</span>' : '' ?></td>
          <td class="acts">
            <a class="btn sm alt" href="/admin/job-edit.php?id=<?= $r['id'] ?>">Edit</a>
            <a class="btn sm alt" href="/jobs/<?= e($r['slug']) ?>/" target="_blank">View</a>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="duplicate"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm alt" title="Make a copy">Copy</button></form>
            <form method="post" data-confirm="Delete this job?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form>
          </td>
        </tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="muted">No jobs found.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
<?php if ($pages > 1): ?>
    <p class="pager-a"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="btn sm <?= $i === $page ? '' : 'alt' ?>" href="?<?= e(http_build_query(['q' => $q, 'type' => $type, 'show' => $show, 'page' => $i])) ?>"><?= $i ?></a> <?php endfor; ?></p>
<?php endif; ?>
<?php admin_footer();
