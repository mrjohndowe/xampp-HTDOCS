<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$id = (string) ($_GET['id'] ?? '');
$video = null;
foreach (catalog() as $item) {
    if (hash_equals((string) ($item['id'] ?? ''), $id)) { $video = $item; break; }
}
if (!$video || !is_file($video['path'])) { http_response_code(404); exit('Video not found.'); }

$path = $video['path'];
$size = filesize($path);
if ($size === false) { http_response_code(500); exit; }
$start = 0; $end = $size - 1;
header('Content-Type: ' . mimeFor($video['extension']));
header('Accept-Ranges: bytes');
header('Content-Disposition: inline; filename="' . addcslashes($video['file'], '"\\') . '"');
header('Cache-Control: private, max-age=3600');

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
    if ($matches[1] !== '') $start = (int) $matches[1];
    if ($matches[2] !== '') $end = min((int) $matches[2], $end);
    if ($start > $end || $start >= $size) { header("Content-Range: bytes */$size"); http_response_code(416); exit; }
    http_response_code(206); header("Content-Range: bytes $start-$end/$size");
}
header('Content-Length: ' . ($end - $start + 1));
$handle = fopen($path, 'rb');
if ($handle === false) { http_response_code(500); exit; }
fseek($handle, $start); $remaining = $end - $start + 1;
while ($remaining > 0 && !feof($handle) && connection_status() === CONNECTION_NORMAL) {
    $chunk = fread($handle, min(1024 * 1024, $remaining));
    if ($chunk === false) break;
    echo $chunk; $remaining -= strlen($chunk); flush();
}
fclose($handle);

