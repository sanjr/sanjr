<?php
require_once __DIR__ . '/includes/functions.php';
http_response_code(404);
$meta = ['title' => 'Page Not Found | ' . SITE_NAME, 'description' => 'The page you are looking for does not exist.', 'robots' => 'noindex, follow', 'path' => '/404/'];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim nf">
    <div class="container">
      <h1>404 <span class="grad">Page Not Found</span></h1>
      <p class="lead">The page you are looking for has moved or does not exist.</p>
      <p><a href="/" class="btn-sm big">Back to Home</a> <a href="/trades/" class="btn-sm big ghost">Browse ITI Trades</a></p>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
