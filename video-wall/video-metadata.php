<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');

function metadataError(string $message, int $status = 422): never {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') metadataError('POST is required.', 405);
$payload = json_decode((string) file_get_contents('php://input'), true);
$id = trim((string) ($payload['id'] ?? ''));
$action = (string) ($payload['action'] ?? '');
if ($id === '') metadataError('Choose a video first.');

$database = db();
$exists = $database->prepare('SELECT id FROM videos WHERE id=?');
$exists->execute([$id]);
if (!$exists->fetchColumn()) metadataError('The selected video is no longer available.', 404);

try {
    $categoryIds = array_map('intval', is_array($payload['categoryIds'] ?? null) ? $payload['categoryIds'] : []);
    $productionIds = array_map('intval', is_array($payload['productionIds'] ?? null) ? $payload['productionIds'] : []);
    $database->beginTransaction();
    if ($action === 'assignments') {
        setVideoCategories($database, $id, $categoryIds);
        setVideoProduction($database, $id, $productionIds);
    } elseif ($action === 'details') {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') throw new RuntimeException('Video name is required.');
        $actors = trim((string) ($payload['actors'] ?? ''));
        $notes = trim((string) ($payload['notes'] ?? ''));
        $update = $database->prepare('UPDATE videos SET display_name=?,actors=?,characters=?,notes=? WHERE id=?');
        $update->execute([$name, $actors, '', $notes, $id]);
        setVideoCategories($database, $id, $categoryIds);
        setVideoProduction($database, $id, $productionIds);
    } else {
        throw new RuntimeException('Invalid quick-save action.');
    }
    $database->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    if ($database->inTransaction()) $database->rollBack();
    metadataError($error->getMessage());
}
