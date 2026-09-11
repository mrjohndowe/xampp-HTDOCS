<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
$payload = json_decode((string) file_get_contents('php://input'), true);
$id = trim((string) ($payload['id'] ?? ''));
$name = trim((string) ($payload['name'] ?? ''));
$categoryIds = is_array($payload['categoryIds'] ?? null) ? $payload['categoryIds'] : (isset($payload['categoryId']) && $payload['categoryId'] !== '' ? [$payload['categoryId']] : []);
$actors = trim((string) ($payload['actors'] ?? ''));
$notes = trim((string) ($payload['notes'] ?? ''));
$database = db();
$exists = false;
foreach (catalog() as $video) if (hash_equals((string) $video['id'], $id)) {
    $exists = true;
    break;
}
if (!$exists || $name === '' || strlen($name) > 180) {
    http_response_code(422);
    echo json_encode(['error' => 'Enter a valid video name.']);
    exit;
}
$database->beginTransaction();
$statement = $database->prepare('UPDATE videos SET display_name = ?, actors = ?, characters = ?, notes = ? WHERE id = ?');
$statement->execute([$name, $actors, '', $notes, $id]);
setVideoCategories($database, $id, $categoryIds);
$database->commit();
echo json_encode(['success' => true, 'name' => $name], JSON_UNESCAPED_UNICODE);
