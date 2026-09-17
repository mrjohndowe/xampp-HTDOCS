<?php
require_once __DIR__ . '/../config.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verify_csrf_or_fail();
$id = (string)($_POST['id'] ?? '');
$removed = delete_book_db($id);
if ($removed) {
    $pdf = __DIR__ . '/../uploads/' . basename((string)$removed['filename']);
    if (is_file($pdf)) @unlink($pdf);
    $cover = $removed['coverFilename'] ?? null;
    if ($cover) {
        $cp = __DIR__ . '/../covers/' . basename((string)$cover);
        if (is_file($cp)) @unlink($cp);
    }
}
header('Location: ' . app_url('admin/index.php'));
