<?php if (($meta['robots'] ?? '') === '' || !str_contains($meta['robots'] ?? '', 'noindex')): ?>  <div class="container"><?= ad('footer_top', 'wide') ?></div>
<?php endif; ?>
  <footer class="site-footer">
    <div class="container foot-grid">
      <div class="foot-brand">
        <a href="/" class="logo light" aria-label="<?= e(SITE_NAME) ?> home">
          <svg class="logo-mark" viewBox="0 0 48 40" aria-hidden="true"><path d="M24 4 2 14l22 10 22-10z" fill="#fff"/><path d="M11 19v9c7 6 19 6 26 0v-9l-13 6z" fill="#fff"/><path d="M44 15v10" stroke="#fff" stroke-width="2.5"/><circle cx="44" cy="27" r="2.5" fill="#60a5fa"/></svg>
          <span class="logo-text"><strong>ITI<span>Exam</span>.com</strong><small>Your ITI Exam Preparation Partner</small></span>
        </a>
        <p><?= e(setting('footer_about', 'Online Mock Tests, Previous Year Questions, Study Material, Jobs, Apprenticeship and Expert Content for all ITI Trades.')) ?></p>
        <div class="social">
<?php foreach (['youtube' => ['yt', 'YouTube'], 'facebook' => ['fb', 'Facebook'], 'telegram' => ['tg', 'Telegram'], 'whatsapp' => ['wa', 'WhatsApp']] as $k => [$cls, $name]):
    if ($u = setting('social_' . $k)): ?>
          <a href="<?= e($u) ?>" class="<?= $cls ?>" aria-label="<?= $name ?>" rel="noopener" target="_blank"><?= icon($cls) ?></a>
<?php endif; endforeach; ?>
        </div>
      </div>
      <nav aria-label="Quick links"><h3>Quick Links</h3><ul>
<?php foreach (menu('footer_quick') as $m): ?>        <li><a href="<?= e($m['url']) ?>"<?= link_attrs($m['url']) ?>><?= e($m['label']) ?></a></li>
<?php endforeach; ?>      </ul></nav>
      <nav aria-label="Popular trades"><h3>Popular Trades</h3><ul>
<?php foreach (array_slice(trades(), 0, 10) as $t): ?>        <li><a href="<?= e(trade_url($t)) ?>"><?= e($t['name']) ?></a></li>
<?php endforeach; ?>      </ul></nav>
      <nav aria-label="Useful resources"><h3>Useful Resources</h3><ul>
<?php foreach (menu('footer_resources') as $m): ?>        <li><a href="<?= e($m['url']) ?>"<?= link_attrs($m['url']) ?>><?= e($m['label']) ?></a></li>
<?php endforeach; ?>      </ul></nav>
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

  <!-- Phone bottom navigation -->
  <nav class="bnav" aria-label="Quick navigation">
<?php
$cp = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
foreach ([['/', 'Home', 'home'], ['/trades/', 'Trades', 'grid'], ['/mock-test/', 'Tests', 'clip'], ['/jobs/', 'Jobs', 'brief'], ['/search/', 'Search', 'search']] as [$u, $l, $ic]):
    $on = $u === '/' ? $cp === '/' : str_starts_with($cp, $u); ?>
    <a href="<?= $u ?>"<?= $on ? ' class="on" aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= $l ?></span></a>
<?php endforeach; ?>
  </nav>
  <div class="nav-overlay" hidden></div>

  <!-- Faster page changes: the browser fetches a page as soon as a finger or mouse heads to its link -->
  <script type="speculationrules">{"prefetch":[{"where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/admin/*","/search/*","/*\\?*","/uploads/*"]}}]},"eagerness":"moderate"}]}</script>
</body>
</html>
