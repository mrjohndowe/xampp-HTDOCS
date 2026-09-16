<?php

declare(strict_types=1);

$root = __DIR__;
$public = $root . '/public';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) && $uri !== '' ? $uri : '/';

/*
|--------------------------------------------------------------------------
| Installation path
|--------------------------------------------------------------------------
*/

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

$appBasePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');

if ($appBasePath !== '' && ($uri === $appBasePath || str_starts_with($uri, $appBasePath . '/'))) {
    $uri = substr($uri, strlen($appBasePath)) ?: '/';
}

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
*/

if (str_starts_with($uri, '/api/')) {
    $apiRoot = realpath($root . '/api');
    $apiPath = realpath($root . $uri);

    if (
        $apiRoot !== false &&
        $apiPath !== false &&
        ($apiPath === $apiRoot || str_starts_with($apiPath, $apiRoot . DIRECTORY_SEPARATOR)) &&
        is_file($apiPath)
    ) {
        require $apiPath;
        exit;
    }

    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'error' => 'API endpoint not found.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    exit;
}

/*
|--------------------------------------------------------------------------
| Public files
|--------------------------------------------------------------------------
*/

$publicRoot = realpath($public);
$publicPath = realpath($public . $uri);

if (
    $publicRoot !== false &&
    $publicPath !== false &&
    ($publicPath === $publicRoot || str_starts_with($publicPath, $publicRoot . DIRECTORY_SEPARATOR)) &&
    is_file($publicPath)
) {
    $extension = strtolower(pathinfo($publicPath, PATHINFO_EXTENSION));

    $mimeTypes = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];

    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    }

    readfile($publicPath);
    exit;
}

/*
|--------------------------------------------------------------------------
| Application
|--------------------------------------------------------------------------
*/

require $public . '/index.php';
