<?php
require_once __DIR__ . '/../config.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verify_csrf_or_fail();
$id = (int)($_POST['id'] ?? 0);
delete_review($id);
header('Location: ' . app_url('admin/index.php'));
