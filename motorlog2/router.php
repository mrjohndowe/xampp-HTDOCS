<?php
// Native PHP development/preview router. Apache on XAMPP uses .htaccess instead.
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (preg_match('~^/assets/[a-zA-Z0-9_.-]+$~D',$path) && is_file(__DIR__.$path)) return false;
if (!in_array($path,['/','/index.php'],true)) {
    http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'Not found'; return true;
}
require __DIR__.'/index.php';
