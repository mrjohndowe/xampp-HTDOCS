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
    if ($action === 'create') {
        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '' || strlen($name) > 80) throw new RuntimeException('Enter a category name.');
        $statement = db()->prepare('INSERT INTO categories(name) VALUES(?)');
        $statement->execute([$name]);
        echo json_encode(['success' => true, 'id' => (int)db()->lastInsertId(), 'name' => $name]);
        exit;
    }
    if ($action === 'delete') {
        $id = (int)($payload['id'] ?? 0);
        $statement = db()->prepare('DELETE FROM categories WHERE id=?');
        $statement->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
    throw new RuntimeException('Invalid category action.');
} catch (PDOException $error) {
    http_response_code(409);
    echo json_encode(['error' => 'That category already exists.']);
} catch (Throwable $error) {
    http_response_code(422);
    echo json_encode(['error' => $error->getMessage()]);
}
