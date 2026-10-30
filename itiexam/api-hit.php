<?php
/* /api/hit/ – each page reports a view ("v") and, while it stays open, "still here" every minute ("p"). Counted in Admin → Visitors. */
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/visits.php';

header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
$in = json_decode((string)file_get_contents('php://input', false, null, 0, 4096), true);
if (is_array($in) && in_array($in['k'] ?? '', ['v', 'p'], true)) {
    try {
        visit_record($in['k'], (string)($in['p'] ?? '/'), (string)($in['t'] ?? ''), (string)($in['r'] ?? ''));
    } catch (Throwable $e) {
        error_log('ITIExam: visit not saved: ' . $e->getMessage());               // a counter problem never bothers the visitor
    }
}
http_response_code(204);
