<?php if (($meta['robots'] ?? '') === '' || !str_contains($meta['robots'] ?? '', 'noindex')): ?>  <div class="container"><?= ad('footer_top', 'wide') ?></div>
<?php endif; ?>
<?php if (empty($noWaCta)): ?>  <div class="container"><?= whatsapp_cta('pre-foot') ?></div>
<?php endif; ?>
  <footer class="site-footer">
    <div class="container foot-grid">
      <div class="foot-brand">
        <?= site_logo(true) ?>
        <p><?= e(setting('footer_about') ?: 'Online Mock Tests, Previous Year Questions, Study Material, Jobs, Apprenticeship and Expert Content for all ITI Trades.') ?></p>
        <p class="foot-mail"><?= icon('chat') ?> <a href="mailto:<?= e(contact_email()) ?>"><?= e(contact_email()) ?></a></p>
<?php if ($wa = whatsapp_group()): ?>        <a class="foot-wa" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('wa') ?> Join WhatsApp Group</a>
<?php endif; ?>
        <div class="social">
<?php foreach (['youtube' => ['yt', 'YouTube'], 'facebook' => ['fb', 'Facebook'], 'telegram' => ['tg', 'Telegram'], 'whatsapp' => ['wa', 'WhatsApp']] as $k => [$cls, $name]):
    if ($u = setting('social_' . $k)): ?>
          <a href="<?= e($u) ?>" class="<?= $cls ?>" aria-label="<?= $name ?>" rel="noopener" target="_blank"><?= icon($cls) ?></a>
<?php endif; endforeach; ?>
        </div>
      </div>
<?php if ($fq = menu('footer_quick')): ?>
      <nav aria-label="Quick links"><h3>Quick Links</h3><ul>
<?php foreach ($fq as $m): ?>        <li><a href="<?= e($m['url']) ?>"<?= link_attrs($m['url']) ?>><?= e($m['label']) ?></a></li>
<?php endforeach; ?>      </ul></nav>
<?php endif; if ($ft = array_slice(trades(), 0, 10)): ?>
      <nav aria-label="Popular trades"><h3>Popular Trades</h3><ul>
<?php foreach ($ft as $t): ?>        <li><a href="<?= e(trade_url($t)) ?>"><?= e($t['name']) ?></a></li>
<?php endforeach; ?>      </ul></nav>
<?php endif; if ($fr = menu('footer_resources')): ?>
      <nav aria-label="Useful resources"><h3>Useful Resources</h3><ul>
<?php foreach ($fr as $m): ?>        <li><a href="<?= e($m['url']) ?>"<?= link_attrs($m['url']) ?>><?= e($m['label']) ?></a></li>
<?php endforeach; ?>      </ul></nav>
<?php endif; ?>
      <div class="foot-quote" aria-hidden="true">“Skilled ITI Students<br>Build a Stronger India”</div>
    </div>
    <div class="container foot-bottom">
      <p>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. All Rights Reserved.</p>
      <ul>
<?php foreach (menu('footer_legal') as $m): ?>        <li><a href="<?= e($m['url']) ?>"<?= link_attrs($m['url']) ?>><?= e($m['label']) ?></a></li>
<?php endforeach; ?>      </ul>
    </div>
  </footer>

  <a href="#top" class="to-top" aria-label="Back to top"><?= icon('chev') ?></a>
<?php if (($wa = whatsapp_group()) && setting('whatsapp_float', '1') === '1'): ?>
  <a class="wa-float" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="Join our WhatsApp group"><?= icon('wa') ?><span>Join Group</span></a>
<?php endif; ?>

  <!-- Phone bottom navigation -->
  <nav class="bnav" aria-label="Quick navigation">
<?php
$cp = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
foreach ([['/', 'Home', 'home'], ['/trades/', 'Trades', 'grid'], ['/mock-test/', 'Tests', 'clip'], ['/jobs/', 'Jobs', 'brief'], ['/search/', 'Search', 'search']] as [$u, $l, $ic]):
    $on = $u === '/' ? $cp === '/' : str_starts_with($cp, $u); ?>
    <a href="<?= $u ?>" class="<?= $u === '/mock-test/' ? 'fab' : '' ?><?= $on ? ' on' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>><span class="bi"><?= icon($ic) ?></span><span class="bl"><?= $l ?></span></a>
<?php endforeach; ?>
  </nav>
  <div class="nav-overlay" hidden></div>

  <!-- Faster page changes: the browser fetches a page as soon as a finger or mouse heads to its link -->
  <script type="speculationrules">{"prefetch":[{"where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/admin/*","/search/*","/*\\?*","/uploads/*"]}}]},"eagerness":"moderate"}]}</script>
</body>
</html>
