<?php
require_once __DIR__ . '/../config.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verify_csrf();
$id = filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare("SELECT cover_path,file_path FROM books WHERE id=?");
$stmt->execute([$id]);
$b = $stmt->fetch();
if ($b) {
    $stmt = db()->prepare("DELETE FROM books WHERE id=?");
    $stmt->execute([$id]);
    foreach (['cover_path','file_path'] as $k) {
        if (!empty($b[$k])) {
            $real = realpath(__DIR__ . '/../' . $b[$k]);
            $base = realpath(__DIR__ . '/../uploads');
            if ($real && $base && str_starts_with($real,$base) && is_file($real)) @unlink($real);
        }
    }
}
header('Location: index.php');
