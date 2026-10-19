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
    <div class="container foot-search">
      <h2>Popular Searches</h2>
      <p>
<?php
$yr = date('Y');
$ps = [['ITI Mock Test ' . $yr, '/mock-test/'], ['ITI CBT Online Test', '/mock-test/'], ['ITI Question Paper PDF', '/previous-papers/'], ['NCVT ITI Exam Date ' . $yr, '/exam/ncvt-iti-exam-date/'],
    ['ITI Admit Card ' . $yr, '/exam/iti-admit-card/'], ['ITI Result ' . $yr, '/exam/maharashtra-iti-result/'], ['ITI Syllabus', '/syllabus/'], ['Employability Skills MCQ', '/employability-skills/'],
    ['ITI Study Material PDF', '/study-material/'], ['NCVT Exam Pattern', '/blog/ncvt-iti-exam-pattern-syllabus/'], ['ITI Apprenticeship ' . $yr, '/apprenticeship/'], ['ITI Jobs ' . $yr, '/jobs/']];
foreach (array_slice(trades(true), 0, 8) as $pt) { $ps[] = [$pt['name'] . ' Mock Test', trade_url($pt)]; }
foreach ($ps as [$l, $u]): ?>        <a href="<?= e($u) ?>"><?= e($l) ?></a>
<?php endforeach; ?>
      </p>
    </div>
    <div class="container foot-bottom">
      <p>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. All Rights Reserved.<?php if (($credit = setting('footer_credit', 'Created & Developed by – Sanjay L. Rathod')) !== ''): ?><span class="sep" aria-hidden="true"> | </span><span class="foot-credit"><?= preg_replace('/(by\s*[–-]?\s*)(.+)$/u', '$1<b>$2</b>', e($credit)) ?></span><?php endif; ?></p>
      <ul>
<?php foreach (menu('footer_legal') as $m): ?>        <li><a href="<?= e($m['url']) ?>"<?= link_attrs($m['url']) ?>><?= e($m['label']) ?></a></li>
<?php endforeach; ?>      </ul>
    </div>
  </footer>

  <a href="#top" class="to-top" aria-label="Back to top"><?= icon('chev') ?></a>
<?php if (($wa = whatsapp_group()) && setting('whatsapp_float', '1') === '1'): ?>
  <a class="wa-float" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="Join our WhatsApp group"><?= icon('wa') ?><span>Join Group</span></a>
<?php endif; ?>

  <!-- Live search panel (full screen on phones) -->
  <div class="sov" id="sov" role="dialog" aria-modal="true" aria-label="Search ITIExam" hidden>
    <div class="sov-box">
      <form class="sov-bar" action="/search/" method="get" role="search">
        <button type="button" class="sov-back" aria-label="Close search"><?= icon('chev-l') ?></button>
        <?= icon('search', 'ic sov-ic') ?>
        <input type="search" name="q" id="sov-q" placeholder="Search trade, test, syllabus, job…" autocomplete="off" enterkeyhint="search" aria-controls="sov-res" aria-autocomplete="list">
        <button type="button" class="sov-clear" aria-label="Clear" hidden><?= icon('close') ?></button>
      </form>
      <div class="sov-body">
        <div class="sov-start">
          <div class="sov-sec" id="sov-recent" hidden><h3>Recent searches <button type="button" class="sov-x" data-clear="itx_rs">Clear</button></h3><div class="sov-chips"></div></div>
          <div class="sov-sec" id="sov-viewed" hidden><h3>Recently viewed <button type="button" class="sov-x" data-clear="itx_rv">Clear</button></h3><ul class="sov-list"></ul></div>
          <div class="sov-sec"><h3>Popular searches</h3><div class="sov-chips">
<?php foreach (array_merge(array_map(fn($t) => $t['name'], array_slice(trades(true), 0, 6)), ['Mock Test', 'Syllabus', 'Apprentice', 'Jobs']) as $c): ?>
            <a href="/search/?q=<?= e(rawurlencode($c)) ?>" data-q="<?= e($c) ?>"><?= icon('search') ?><?= e($c) ?></a>
<?php endforeach; ?>
          </div></div>
          <div class="sov-sec"><h3>Quick links</h3><div class="sov-quick">
            <a href="/trades/"><span class="c-blue"><?= icon('grid') ?></span>All Trades</a>
            <a href="/mock-test/"><span class="c-orange"><?= icon('clip') ?></span>Mock Tests</a>
            <a href="/syllabus/"><span class="c-green"><?= icon('book') ?></span>Syllabus</a>
            <a href="/jobs/"><span class="c-purple"><?= icon('brief') ?></span>Jobs</a>
          </div></div>
        </div>
        <ul class="sov-list sov-res" id="sov-res" role="listbox" hidden></ul>
        <p class="sov-msg" hidden></p>
        <a class="sov-all" href="/search/" hidden></a>
      </div>
    </div>
  </div>

  <!-- Phone bottom navigation -->
  <nav class="bnav" aria-label="Quick navigation">
<?php
$cp = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
foreach ([['/', 'Home', 'home'], ['/trades/', 'Trades', 'grid'], ['/mock-test/', 'Tests', 'clip'], ['/jobs/', 'Jobs', 'brief'], ['/search/', 'Search', 'search']] as [$u, $l, $ic]):
    $on = $u === '/' ? $cp === '/' : str_starts_with($cp, $u); ?>
    <a href="<?= $u ?>" class="<?= $u === '/mock-test/' ? 'fab' : '' ?><?= $on ? ' on' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?><?= $u === '/search/' ? ' data-sov' : '' ?>><span class="bi"><?= icon($ic) ?></span><span class="bl"><?= $l ?></span></a>
<?php endforeach; ?>
  </nav>
  <div class="nav-overlay" hidden></div>

  <!-- Faster page changes: the browser fetches a page as soon as a finger or mouse heads to its link -->
  <script type="speculationrules">{"prefetch":[{"where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/admin/*","/search/*","/*\\?*","/uploads/*"]}}]},"eagerness":"moderate"}]}</script>
</body>
</html>
