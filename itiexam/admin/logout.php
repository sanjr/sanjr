<?php
require __DIR__ . '/inc/boot.php';
if (is_post()) { csrf_check(); $_SESSION = []; session_destroy(); }
redirect('/admin/login.php');
