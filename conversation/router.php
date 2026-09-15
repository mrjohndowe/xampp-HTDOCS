<?php

declare (strict_types = 1);

$root   = __DIR__;
$public = $root . '/public';

$uri = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$uri = $uri ?: '/';

/*
|--------------------------------------------------------------------------
| Installation path
|--------------------------------------------------------------------------
|
| Apache runs this project from a directory such as /conversation, while
| PHP's built-in server runs it from /. Keep one canonical, URL-safe base
| path so both environments resolve public files and API endpoints alike.
*/

$scriptDirectory = str_replace(
    '\\',
    '/',
    dirname($_SERVER['SCRIPT_NAME'] ?? '')
);

$appBasePath = $scriptDirectory === '/'
    || $scriptDirectory === '.'
    ? ''
    : rtrim($scriptDirectory, '/');

if (
    $appBasePath !== '' &&
    ($uri === $appBasePath || str_starts_with($uri, $appBasePath . '/'))
) {
    $uri = substr($uri, strlen($appBasePath)) ?: '/';
}

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
*/

if (str_starts_with($uri, '/api/')) {
    $apiPath = realpath(
        $root . $uri
    );

    $apiRoot = realpath(
        $root . '/api'
    );

    if (
        $apiPath !== false &&
        $apiRoot !== false &&
    (
        $apiPath === $apiRoot ||
        str_starts_with($apiPath, $apiRoot . DIRECTORY_SEPARATOR)
    ) &&
        is_file($apiPath)
    ) {
        require $apiPath;
        exit;
    }

    http_response_code(404);

    echo 'API endpoint not found.';
    exit;
}

/*
|--------------------------------------------------------------------------
| Public files
|--------------------------------------------------------------------------
*/

$publicPath = realpath(
    $public . $uri
);

$publicRoot = realpath(
    $public
);

if (
    $publicPath !== false &&
    $publicRoot !== false &&
    (
        $publicPath === $publicRoot ||
        str_starts_with($publicPath, $publicRoot . DIRECTORY_SEPARATOR)
    ) &&
    is_file($publicPath)
) {
    $extension =
        strtolower(
        pathinfo(
            $publicPath,
            PATHINFO_EXTENSION
        )
    );

    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
    ];

    if (isset($mimeTypes[$extension])) {
        header(
            'Content-Type: '
            . $mimeTypes[$extension]
        );
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
