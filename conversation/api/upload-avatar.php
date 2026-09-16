<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/helpers/helpers.php';

$config = require dirname(__DIR__) . '/app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
    jsonResponse(['error' => 'No avatar uploaded.'], 400);
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'Upload failed.'], 400);
}

if ($file['size'] > $config['uploads']['max_avatar_size']) {
    jsonResponse(['error' => 'Avatar file is too large.'], 413);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

$allowed = $config['uploads']['allowed_types'];

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

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$fileName = 'avatar_' . bin2hex(random_bytes(10)) . '.' . $extension;
$destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['error' => 'Unable to save avatar.'], 500);
}

jsonResponse([
    'success' => true,
    'url' => '/assets/images/avatars/' . $fileName,
]);
