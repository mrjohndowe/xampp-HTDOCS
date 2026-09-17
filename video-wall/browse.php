<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function windowsDrives(): array
{
    $drives = [];
    foreach (range('A', 'Z') as $letter) {
        $path = $letter . ':\\';
        if (@is_dir($path)) $drives[] = ['name' => $path, 'path' => $path];
    }
    return $drives;
}

$requested = trim((string) ($_GET['path'] ?? ''));
if ($requested === '') respond(['path' => '', 'parent' => null, 'folders' => windowsDrives()]);

$path = realpath($requested);
if ($path === false || !is_dir($path) || !is_readable($path)) {
    respond(['error' => 'That folder cannot be opened by Apache.'], 400);
}
$folders = [];
$entries = @scandir($path);
if ($entries === false) respond(['error' => 'Unable to read this folder.'], 403);
foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    $child = $path . DIRECTORY_SEPARATOR . $entry;
    if (@is_dir($child) && @is_readable($child)) {
        $real = realpath($child);
        if ($real !== false) $folders[] = ['name' => $entry, 'path' => $real];
    }
}
usort($folders, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
$parent = dirname($path);
if ($parent === $path) $parent = '';
respond(['path' => $path, 'parent' => $parent, 'folders' => $folders]);
