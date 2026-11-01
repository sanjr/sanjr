<?php
use App\Controllers\OutcomeController;
use App\Core\Auth;

$s = $summary;
$q = static fn (array $extra) => url('/outcomes', array_filter(array_merge(array_diff_key($_GET, ['page' => 1]), $extra), static fn ($v) => $v !== null && $v !== ''));
$cur = ['exam' => (string) input('exam', ''), 'path' => (string) input('path', '')];
$chip = static function (string $label, mixed $n, string $key, string $val, string $tone) use ($q, $cur) {
    $active = $cur[$key] === $val;
    return '<a class="oc-chip oc-' . $tone . ($active ? ' on' : '') . '" href="' . e($q([$key => $active ? null : $val])) . '"><b>' . num($n) . '</b><span>' . e($label) . '</span></a>';
};
$pathBadge = ['EMPLOYED' => 'green', 'APPRENTICE' => 'blue', 'HIGHER_EDUCATION' => 'purple', 'SELF_EMPLOYED' => 'amber', 'SEEKING_EMPLOYMENT' => 'red'];
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p>Every admitted student: exam → result → job, apprenticeship, higher education or business — and where.</p></div>
  <div class="actions"><a class="btn btn-light" href="<?= e(url('/outcomes/export', array_diff_key($_GET, ['page' => 1]))) ?>"><?= icon('download') ?> Download CSV</a></div>
</div>

<div class="oc-chips">
  <a class="oc-chip oc-all<?= !$cur['exam'] && !$cur['path'] ? ' on' : '' ?>" href="<?= e($q(['exam' => null, 'path' => null])) ?>"><b><?= num($s['total'] ?? 0) ?></b><span>Admitted</span></a>
  <?= $chip('Appeared in exam', $s['appeared'] ?? 0, 'exam', 'appeared', 'blue') ?>
  <?= $chip('Not appeared / not yet', (int) ($s['total'] ?? 0) - (int) ($s['appeared'] ?? 0), 'exam', 'not_appeared', 'grey') ?>
  <?= $chip('Passed', $s['passed'] ?? 0, 'exam', 'PASS', 'green') ?>
  <?= $chip('Failed', $s['failed'] ?? 0, 'exam', 'FAIL', 'red') ?>
</div>
<div class="oc-chips">
  <?= $chip('Job', $s['job'] ?? 0, 'path', 'EMPLOYED', 'green') ?>
  <?= $chip('Apprenticeship', $s['apprentice'] ?? 0, 'path', 'APPRENTICE', 'blue') ?>
  <?= $chip('Higher education', $s['higher_ed'] ?? 0, 'path', 'HIGHER_EDUCATION', 'purple') ?>
  <?= $chip('Business', $s['business'] ?? 0, 'path', 'SELF_EMPLOYED', 'amber') ?>
  <?= $chip('Seeking job', $s['seeking'] ?? 0, 'path', 'SEEKING_EMPLOYMENT', 'red') ?>
  <?= $chip('Not updated', $s['not_updated'] ?? 0, 'path', 'NOT_UPDATED', 'grey') ?>
</div>

<form class="card filters" method="get" action="<?= url('/outcomes') ?>">
  <input type="hidden" name="exam" value="<?= e($cur['exam']) ?>"><input type="hidden" name="path" value="<?= e($cur['path']) ?>">
  <div class="field" style="flex:1"><label>Search</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Name or PCID"></div>
  <?php if ($itis): ?><div class="field"><label>ITI</label><select name="iti"><?= options($itis, 'id', 'name', input('iti'), 'All') ?></select></div><?php endif; ?>
  <div class="field"><label>Intake year</label><select name="year"><?= options($years, 'id', 'label', input('year'), 'All') ?></select></div>
  <div class="field"><label>Trade</label><select name="trade"><?= options($trades, 'id', 'name', input('trade'), 'All') ?></select></div>
  <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
</form>

<div class="card card-tight">
  <div class="table-wrap"><table class="table oc-table">
    <tr><th>Student</th><th>Trade · Year</th><th>Exam</th><th>Result</th><th>Now</th><th>Apprenticeship (where, when)</th><th>After apprenticeship</th><th></th></tr>
    <?php foreach ($p['rows'] as $r): ?>
      <tr>
        <td><b><?= e($r['name']) ?></b><br><span class="code"><?= e($r['pcid'] ?? '—') ?></span><?php if ($itis): ?><br><small class="muted"><?= e($r['iti']) ?></small><?php endif; ?></td>
        <td><?= e($r['trade']) ?><br><small class="muted"><?= e($r['year']) ?> · <?= e(label($r['adm_status'])) ?></small></td>
        <td><?= $r['exam'] === 'Appeared' ? '<span class="badge badge-blue">Appeared</span>' . ($r['attempts'] > 1 ? '<br><small class="muted">' . (int) $r['attempts'] . ' attempts</small>' : '') : '<span class="badge badge-grey">' . e($r['exam']) . '</span>' ?></td>
        <td><?php if ($r['result']): ?><span class="badge badge-<?= $r['result'] === 'PASS' ? 'green' : 'red' ?>"><?= $r['result'] === 'PASS' ? 'Pass' : ($r['result'] === 'FAIL' ? 'Fail' : e(label($r['result']))) ?></span>
          <br><small class="muted"><?= e($r['exam_detail'] ?? '') ?></small><?php else: ?>—<?php endif; ?></td>
        <td><span class="badge badge-<?= $pathBadge[$r['path'] ?? ''] ?? 'grey' ?>"><?= e(OutcomeController::PATHS[$r['path'] ?? 'NOT_UPDATED'] ?? label((string) $r['path'])) ?></span>
          <?php if ($r['where']): ?><br><small><?= e($r['where']) ?></small><?php endif; ?></td>
        <td><?php if ($r['appr']): ?><b><?= e($r['appr']['company']) ?></b><br><small class="muted"><?= fmt_date($r['appr']['from']) ?> – <?= $r['appr']['to'] ? fmt_date($r['appr']['to']) : 'ongoing' ?> · <?= e(label($r['appr']['status'])) ?></small><?php else: ?>—<?php endif; ?></td>
        <td><?= $r['after_appr'] ? e($r['after_appr']) : '—' ?></td>
        <td class="right nowrap"><a class="btn btn-light btn-sm" href="<?= url('/students/' . $r['student_id']) ?>">View</a>
          <?php if (can('career.record')): ?><a class="btn btn-sm" href="<?= url('/students/' . $r['student_id'] . '/career') ?>">Update</a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="8" class="empty">No students match.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>
