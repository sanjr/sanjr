<?php
/* /api/attempt/ – the CBT page sends a finished test's result here for the signed-in student. */
require __DIR__ . '/includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$out = function (int $code, array $data) { http_response_code($code); echo json_encode($data); exit; };

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $out(405, ['ok' => false]);
$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) $out(400, ['ok' => false]);
if (!hash_equals(csrf_token(), (string)($in['csrf'] ?? ''))) $out(403, ['ok' => false, 'error' => 'expired']);
$me = student();
if (!$me) $out(401, ['ok' => false, 'error' => 'login']);

$st = db()->prepare("SELECT id, title, level, trade_id FROM cbt_tests WHERE folder = ? AND status = 'published'");
$st->execute([preg_replace('/[^a-z0-9-]/', '', (string)($in['folder'] ?? ''))]);
$test = $st->fetch();
if (!$test) $out(404, ['ok' => false]);

$total = max(0, min(10000, (int)($in['total'] ?? 0)));
$score = max(0, min($total ?: 10000, (int)round((float)($in['score'] ?? 0))));
$pct = $total ? (int)round($score * 100 / $total) : max(0, min(100, (int)($in['pct'] ?? 0)));
$status = strtoupper((string)($in['status'] ?? ''));
$passed = $status === 'PASS' ? 1 : ($status === 'FAIL' ? 0 : (int)($pct >= 33));
$time = max(0, min(6 * 3600, (int)($in['time'] ?? 0)));
$at = (int)(((int)($in['at'] ?? 0)) / 1000);                       // a result saved before login keeps its own time (up to 7 days)
$when = $at > time() - 7 * 86400 && $at <= time() + 60 ? date('Y-m-d H:i:s', $at) : now();

$loc = trim(preg_replace('/[\x00-\x1f<>]+/u', ' ', (string)($in['location'] ?? '')));
$loc = preg_match('/^(unknown|location denied|.*not available.*)$/i', $loc) ? '' : mb_substr($loc, 0, 160);

// The same result sent twice (e.g. a double submit) is kept once
$dup = db()->prepare('SELECT id FROM attempts WHERE student_id = ? AND test_id = ? AND score = ? AND total = ? AND (created_at >= ? OR created_at = ?)');
$dup->execute([$me['id'], $test['id'], $score, $total, date('Y-m-d H:i:s', time() - 120), $when]);
if ($id = $dup->fetchColumn()) $out(200, ['ok' => true, 'id' => (int)$id]);

db()->prepare('INSERT INTO attempts (student_id, test_id, trade_id, title, level, score, total, pct, passed, time_sec, lang, location, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$me['id'], $test['id'], $test['trade_id'], $test['title'], $test['level'], $score, $total, $pct, $passed, $time, mb_substr((string)($in['lang'] ?? ''), 0, 20), $loc, $when]);
if ($loc !== '') db()->prepare('UPDATE students SET location = ? WHERE id = ?')->execute([$loc, $me['id']]);
if (!$me['trade_id'] && $test['trade_id']) db()->prepare('UPDATE students SET trade_id = ? WHERE id = ?')->execute([$test['trade_id'], $me['id']]);
$out(200, ['ok' => true, 'id' => (int)db()->lastInsertId()]);
