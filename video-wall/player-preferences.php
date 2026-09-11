<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

function preferencesError(string $message, int $status = 422): never {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') preferencesError('POST is required.', 405);
$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) preferencesError('Invalid settings request.');

$preferences = [
    'autoNext' => !empty($payload['autoNext']),
    'startMuted' => !empty($payload['startMuted']),
];

try {
    $statement = db()->prepare('INSERT OR REPLACE INTO settings(key,value) VALUES(?,?)');
    foreach ($preferences as $key => $enabled) $statement->execute([$key, $enabled ? '1' : '0']);
    echo json_encode(['success' => true, 'preferences' => $preferences]);
} catch (Throwable $error) {
    preferencesError('Unable to save playback preferences: ' . $error->getMessage(), 500);
}
