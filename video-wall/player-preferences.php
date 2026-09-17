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
    'debugEnabled' => !empty($payload['debugEnabled']),
];
$ffmpegPath = trim((string)($payload['ffmpegPath'] ?? ''));
if ($ffmpegPath !== '' && !is_file($ffmpegPath)) preferencesError('The FFmpeg path does not point to an existing file.');

function updateCollageWatcher(bool $enabled): void {
    $scriptPath = 'B:\htdocs\ollamavisioncollage.ps1';
    $pidFile = 'B:\htdocs\video-wall\data\collage-watcher.pid';
    if (!$enabled) {
        $pid = is_file($pidFile) ? (int)trim((string)file_get_contents($pidFile)) : 0;
        if ($pid > 0) @exec('taskkill /PID ' . $pid . ' /T /F 2>NUL');
        @unlink($pidFile);
        return;
    }
    if (!is_file($scriptPath)) throw new RuntimeException('Debug collage script was not found at ' . $scriptPath);
    if (is_file($pidFile)) return;
    $command = "powershell.exe -NoProfile -ExecutionPolicy Bypass -Command \"Start-Process powershell.exe -ArgumentList '-NoProfile','-ExecutionPolicy','Bypass','-File','$scriptPath' -WindowStyle Hidden\"";
    @pclose(@popen($command, 'r'));
}

try {
    $oldDebug = db()->prepare('SELECT value FROM settings WHERE key=?');
    $oldDebug->execute(['debugEnabled']);
    $wasDebugEnabled = $oldDebug->fetchColumn() === '1';
    $statement = db()->prepare('INSERT OR REPLACE INTO settings(key,value) VALUES(?,?)');
    foreach ($preferences as $key => $enabled) $statement->execute([$key, $enabled ? '1' : '0']);
    if (array_key_exists('ffmpegPath', $payload)) $statement->execute(['ffmpegPath', $ffmpegPath]);
    if ($wasDebugEnabled !== $preferences['debugEnabled']) updateCollageWatcher($preferences['debugEnabled']);
    echo json_encode(['success' => true, 'preferences' => $preferences, 'ffmpegPath' => $ffmpegPath]);
} catch (Throwable $error) {
    preferencesError('Unable to save playback preferences: ' . $error->getMessage(), 500);
}
