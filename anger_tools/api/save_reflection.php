<?php
// Standalone endpoint: do not load the HTML header before this file.
ob_start();
require_once __DIR__ . '/../extra/liteConnect.php';
require_once __DIR__ . '/../extra/functions.php';
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function reflection_reply($status, $message, $success = false) {
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') reflection_reply(405, 'POST required.');
if (empty($_SESSION['user'])) reflection_reply(401, 'Please sign in again. Your answers have not been saved.');
$csrf = $_POST['csrf'] ?? null;
if (!is_string($csrf) || empty($_SESSION['reflection_csrf']) || !hash_equals($_SESSION['reflection_csrf'], $csrf)) reflection_reply(403, 'Session verification failed. Reload the page before trying again.');
$values = [];
foreach (['madAbout', 'express', 'thoughts', 'feelings', 'act', 'better'] as $field) {
    $value = $_POST[$field] ?? null;
    if (!is_string($value) || preg_match('//u', $value) !== 1 || preg_match('/\S/u', $value) !== 1 || strlen($value) > 16000) reflection_reply(422, 'Every written answer is required and must be no longer than 4,000 characters.');
    $length = preg_match_all('/./us', $value);
    if ($length > 4000) reflection_reply(422, 'Please shorten your answer to 4,000 characters.');
    $values[$field] = trim($value);
}
$scale = $_POST['angerScale'] ?? null;
$public = $_POST['public'] ?? null;
if (!is_string($scale) || !preg_match('/^option(?:[1-9]|10)$/D', $scale)) reflection_reply(422, 'Please select an anger level from 1 to 10.');
if (!is_string($public) || !in_array($public, ['0', '1'], true)) reflection_reply(422, 'Please choose whether to keep your log private or make it public.');
try {
    $uid = getUserID($_SESSION['user']);
    if (!$uid) reflection_reply(401, 'Please sign in again.');
    // Plain UTF-8 preserves punctuation and non-English text. This is NOT encryption.
    // Existing readers skip legacy binary decoding when keycode differs from user salt.
    $marker = 'plain:v1';
    if ((string)getCode($uid) === $marker) $marker = 'plain:v1:alternate';
    $values += ['angerScale' => $scale, 'month' => date('m'), 'day' => date('d'), 'year' => date('Y'), 'username' => $uid, 'keycode' => $marker, 'public' => (int)$public];
    $columns = array_keys($values);
    $query = 'INSERT INTO anger (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')';
    $stmt = $db->prepare($query);
    if (!$stmt->execute($values)) throw new RuntimeException('Insert failed');
    reflection_reply(200, 'Your reflection has been saved.', true);
} catch (Throwable $e) {
    error_log('Reflection save failed: ' . get_class($e));
    reflection_reply(500, 'We could not save your reflection. Your answers are still here; please try again.');
}
