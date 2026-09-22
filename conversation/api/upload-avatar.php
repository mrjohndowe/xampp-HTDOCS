<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/helpers/helpers.php';
$config = require dirname(__DIR__) . '/app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$conversationId = trim((string) ($_POST['conversation_id'] ?? ''));

if ($conversationId === '' || !preg_match('/^[a-z0-9_-]+$/i', $conversationId)) {
    jsonResponse(['error' => 'Invalid conversation ID.'], 400);
}

if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
    jsonResponse(['error' => 'No avatar uploaded.'], 400);
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'Upload failed.'], 400);
}

$maxSize = $config['uploads']['max_avatar_size'] ?? 5 * 1024 * 1024;

if ($file['size'] > $maxSize) {
    jsonResponse(['error' => 'Avatar file is too large.'], 413);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

$allowed = $config['uploads']['allowed_types'] ?? [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
];

if (!in_array($mime, $allowed, true)) {
    jsonResponse(['error' => 'Unsupported image type.'], 415);
}

$extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

$extension = $extensions[$mime] ?? null;

if ($extension === null) {
    jsonResponse(['error' => 'Unable to determine image extension.'], 415);
}

$uploadDir = dirname(__DIR__) . '/public/assets/images/avatars';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
    jsonResponse(['error' => 'Unable to create avatar directory.'], 500);
}

$fileName = $conversationId . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
$destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['error' => 'Unable to save avatar.'], 500);
}

$avatarUrl = '/assets/images/avatars/' . $fileName;

$storageDir = dirname(__DIR__) . '/storage';

if (!is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

$overrideFile = $storageDir . '/avatar-overrides.json';

$overrides = [];

if (is_file($overrideFile)) {
    $decoded = json_decode((string) file_get_contents($overrideFile), true);

    if (is_array($decoded)) {
        $overrides = $decoded;
    }
}

$overrides[$conversationId] = $avatarUrl;

file_put_contents(
    $overrideFile,
    json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    LOCK_EX
);

jsonResponse([
    'success' => true,
    'url' => $avatarUrl,
]);
