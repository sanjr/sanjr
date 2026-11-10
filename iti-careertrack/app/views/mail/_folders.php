<?php /** @var string $f @var bool $compose @var array $counts */ ?>
<aside class="ml-side">
  <?php if ($compose): ?><a class="btn ml-new" href="<?= url('/mail/compose') ?>"><?= icon('plus') ?> New message</a><?php endif; ?>
  <nav class="ml-folders">
    <?php foreach (\App\Controllers\MailController::FOLDERS as $k => $v): if ($k === 'drafts' && !$compose) { continue; } ?>
      <?php $n = (int) ($k === 'inbox' ? ($counts['inbox'] ?? 0) : ($k === 'drafts' ? ($counts['drafts'] ?? 0) : ($k === 'starred' ? ($counts['starred'] ?? 0) : 0))); ?>
      <a href="<?= url('/mail', ['f' => $k]) ?>" class="<?= $f === $k ? 'on' : '' ?>"><?= icon(['inbox' => 'mail', 'starred' => 'award', 'sent' => 'arrow-right', 'drafts' => 'edit', 'archive' => 'layers'][$k]) ?>
        <span><?= e($v) ?></span><?php if ($n): ?><b class="ml-count"><?= num($n) ?></b><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
</aside>
