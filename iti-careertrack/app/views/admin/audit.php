<div class="page-head"><div><h1><?= e($title) ?></h1><p>Append-only: nobody — not even an administrator — can edit or delete these entries.</p></div></div>

<form class="card filters" method="get" action="<?= url('/audit') ?>">
  <div class="field"><label>Action</label><select name="action"><?= enum_options($actions, input('action'), 'All') ?></select></div>
  <div class="field"><label>Record type</label><select name="entity"><?= enum_options($entities, input('entity'), 'All') ?></select></div>
  <div class="field"><label>Student id</label><input type="number" name="student" value="<?= e(input('student')) ?>"></div>
  <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
</form>

<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Student</th><th>Details</th></tr>
    <?php foreach ($p['rows'] as $a): ?>
      <tr><td class="nowrap"><small><?= e(date('d M Y H:i:s', strtotime($a['occurred_at']))) ?></small></td>
        <td><?= e($a['actor'] ?? 'system') ?><br><small class="muted"><?= e($a['actor_ip'] ?? '') ?></small></td>
        <td><span class="badge badge-blue"><?= e($a['action']) ?></span></td>
        <td class="code"><?= e($a['entity_table']) ?> #<?= e($a['entity_id']) ?></td>
        <td><?= $a['student_id'] ? '<a href="' . e(url('/students/' . $a['student_id'])) . '">#' . (int) $a['student_id'] . '</a>' : '—' ?></td>
        <td style="max-width:420px"><?php if ($a['after_data'] || $a['before_data'] || $a['reason']): ?>
          <details><summary class="muted">view</summary>
            <?php if ($a['reason']): ?><p><b>Reason:</b> <?= e($a['reason']) ?></p><?php endif; ?>
            <?php if ($a['before_data']): ?><b>Before</b><pre class="json"><?= e($a['before_data']) ?></pre><?php endif; ?>
            <?php if ($a['after_data']): ?><b>After</b><pre class="json"><?= e($a['after_data']) ?></pre><?php endif; ?>
          </details><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="6" class="empty">No audit entries yet.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>
