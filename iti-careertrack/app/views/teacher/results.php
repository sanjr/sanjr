<div class="page-head"><div><h1><?= e($title) ?></h1><p>Examination results of students in your scope. Results are entered by the Principal's office; each attempt is a separate row.</p></div></div>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>Trade</th><th>Session</th><th>Attempt</th><th>Result</th><th class="num">Marks</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr><td><a href="<?= url('/students/' . $r['student_id'], ['tab' => 'results']) ?>"><?= e($r['name']) ?></a></td><td><span class="pcid"><?= e($r['pcid']) ?></span></td>
        <td><?= e($r['trade']) ?></td><td><?= e($r['session_name']) ?></td><td>#<?= (int) $r['attempt_no'] ?></td><td><?= badge($r['result']) ?></td>
        <td class="num"><?= $r['marks_obtained'] !== null ? e($r['marks_obtained'] . ' / ' . $r['max_marks']) : '—' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty">No exam results for your current students yet (results are declared after the final exam).</td></tr><?php endif; ?>
  </table></div>
</div>
