<?php
require_once __DIR__ . '/includes/functions.php';
http_response_code(404);
log_not_found();
$meta = ['title' => 'Page Not Found | ' . SITE_NAME, 'description' => 'The page you are looking for does not exist.', 'robots' => 'noindex, follow', 'path' => '/404/'];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim nf">
    <div class="container">
      <h1>404 <span class="grad">Page Not Found</span></h1>
      <p class="lead">This page has moved or does not exist. Search for what you need, or open one of these pages.</p>
      <form class="search" role="search" action="/search/" method="get">
        <label for="q404" class="sr-only">Search</label><?= icon('search') ?>
        <input id="q404" name="q" type="search" placeholder="Search trade, topic or job (e.g. COPA, Railway)">
        <button type="submit"><?= icon('search') ?><span>Search</span></button>
      </form>
      <div class="nf-links">
        <a href="/">Home</a><a href="/trades/">All Trades</a><a href="/mock-test/">Mock Tests</a><a href="/previous-papers/">Previous Papers</a><a href="/jobs/">ITI Jobs</a><a href="/contact/">Contact</a>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
