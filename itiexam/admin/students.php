<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)$_POST['id'];
    db()->prepare('DELETE FROM attempts WHERE student_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
    flash('Student and their results deleted.');
    redirect('/admin/students.php');
}

$rows = db()->query('SELECT s.*, t.name AS tname, COUNT(a.id) AS tests, COALESCE(SUM(a.passed), 0) AS passed, COALESCE(ROUND(AVG(a.pct)), 0) AS avg, MAX(a.created_at) AS last_test
    FROM students s LEFT JOIN trades t ON t.id = s.trade_id LEFT JOIN attempts a ON a.student_id = s.id
    GROUP BY s.id ORDER BY s.last_login DESC LIMIT 500')->fetchAll();
$count = (int)db()->query('SELECT COUNT(*) FROM students')->fetchColumn();
$tests = (int)db()->query('SELECT COUNT(*) FROM attempts')->fetchColumn();
admin_header('Students', 'students');
?>
    <div class="bar"><p class="muted"><b><?= $count ?></b> students have logged in with Google · <b><?= $tests ?></b> test results saved.<?= google_login_ready() ? '' : ' Google login is <b>off</b> – turn it on in <a href="/admin/settings.php#google-login">Settings</a>.' ?></p></div>
    <div class="box tablebox"><table>
      <thead><tr><th>Student</th><th>Trade</th><th>Tests</th><th>Passed</th><th>Average</th><th>Last test</th><th>Joined</th><th></th></tr></thead>
      <tbody>
<?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= e($r['name']) ?></b><br><small class="muted"><?= e($r['email']) ?></small></td>
          <td><?= e($r['tname'] ?: '–') ?></td>
          <td><?= (int)$r['tests'] ?></td>
          <td><?= (int)$r['passed'] ?></td>
          <td><?= $r['tests'] ? (int)$r['avg'] . '%' : '–' ?></td>
          <td><?= $r['last_test'] ? e(date('d M Y', strtotime($r['last_test']))) : '–' ?></td>
          <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
          <td class="acts"><form method="post" data-confirm="Delete <?= e($r['name']) ?> and all their test results?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form></td>
        </tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="8" class="muted">No students yet.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
<?php admin_footer();
