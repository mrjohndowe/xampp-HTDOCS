<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
$id = (string) ($_GET['id'] ?? '');
$video = null;
foreach (catalog() as $item) if (hash_equals((string) ($item['id'] ?? ''), $id)) { $video = $item; break; }
if (!$video || !is_file($video['path'])) { http_response_code(404); exit; }
$directory = DATA_DIR . DIRECTORY_SEPARATOR . 'thumbnails';
if (!is_dir($directory)) @mkdir($directory, 0775, true);
$target = $directory . DIRECTORY_SEPARATOR . $id . '.jpg';
if (!is_file($target) || (int) filemtime($target) < (int) filemtime($video['path'])) {
    $ffmpeg = findFfmpeg();
    if ($ffmpeg === null) { http_response_code(404); exit; }
    $filter = 'scale=480:270:force_original_aspect_ratio=decrease,pad=480:270:(ow-iw)/2:(oh-ih)/2:black';
    $command = escapeshellarg($ffmpeg) . ' -hide_banner -loglevel error -y -ss 00:00:02 -i ' . escapeshellarg($video['path']) . ' -frames:v 1 -vf ' . escapeshellarg($filter) . ' ' . escapeshellarg($target);
    @exec($command, $unused, $exitCode);
}
if (!is_file($target) || filesize($target) === 0) { http_response_code(404); exit; }
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($target));
header('Cache-Control: private, max-age=86400');
readfile($target);
