<?php
/* /logout/ – signs the student out (the short token stops other sites from logging people out). */
require __DIR__ . '/includes/functions.php';
if (hash_equals(substr(csrf_token(), 0, 16), (string)($_GET['t'] ?? ''))) {
    unset($_SESSION['student_id']);
    student_cookie(null);
    session_regenerate_id(true);
}
header('Location: /');
exit;
