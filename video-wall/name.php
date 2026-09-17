<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
$action = (string)($payload['action'] ?? '');

try {
    $pdo = db();
    if ($action === 'create') {
        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '' || strlen($name) > 120) throw new RuntimeException('Enter a name up to 120 characters.');
        $id = ensureNameExists($pdo, $name);
        echo json_encode(['success' => true, 'id' => $id, 'name' => $name], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'delete') {
        $id = (int)($payload['id'] ?? 0);
        $pdo->prepare('DELETE FROM names WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
    throw new RuntimeException('Invalid name action.');
} catch (Throwable $error) {
    http_response_code(422);
    echo json_encode(['error' => $error->getMessage()]);
}
