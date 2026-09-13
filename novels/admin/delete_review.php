<?php
require_once __DIR__ . '/../config.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verify_csrf();
$id = filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare("DELETE FROM reviews WHERE id=?");
$stmt->execute([$id]);
header('Location: index.php');
