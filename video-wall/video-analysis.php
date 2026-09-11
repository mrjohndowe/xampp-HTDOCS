<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');

function analysisError(string $message, int $status = 422): never {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function removeAnalysisFrames(array $frames): void {
    foreach ($frames as $frame) @unlink($frame);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') analysisError('POST is required.', 405);
$payload = json_decode((string) file_get_contents('php://input'), true);
$id = trim((string) ($payload['id'] ?? ''));
$model = trim((string) getenv('OLLAMA_VIDEO_ANALYSIS_MODEL'));
if ($model === '') analysisError('Local AI analysis is not configured. Set OLLAMA_VIDEO_ANALYSIS_MODEL to an installed vision-capable Ollama model, then restart Apache.', 503);
if (!function_exists('curl_init')) analysisError('PHP cURL is required for local AI analysis.', 503);

$host = rtrim((string) (getenv('OLLAMA_HOST') ?: 'http://127.0.0.1:11434'), '/');
$hostParts = parse_url($host);
$localHosts = ['127.0.0.1', 'localhost', '::1'];
if (!$hostParts || !isset($hostParts['host']) || !in_array(strtolower((string) $hostParts['host']), $localHosts, true)) analysisError('OLLAMA_HOST must point to a local Ollama service.', 503);

$statement = db()->prepare('SELECT id,path,file,original_name,created,publish_date FROM videos WHERE id=?');
$statement->execute([$id]);
$video = $statement->fetch();
if (!$video || !is_file((string) $video['path'])) analysisError('The requested video is not available.');
$ffmpeg = findFfmpeg();
if ($ffmpeg === null) analysisError('FFmpeg is required to extract temporary analysis frames. Set its path in Folders.', 503);

$temporary = DATA_DIR . DIRECTORY_SEPARATOR . 'analysis-frames';
if (!is_dir($temporary) && !mkdir($temporary, 0775, true) && !is_dir($temporary)) analysisError('Unable to create temporary analysis frames.');

$frames = [];
$suggestion = null;
$failure = null;
try {
    foreach ([2, 20, 60] as $offset) {
        $frame = $temporary . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12)) . '.jpg';
        $command = escapeshellarg($ffmpeg) . ' -hide_banner -loglevel error -y -ss ' . $offset . ' -i ' . escapeshellarg((string) $video['path']) . ' -frames:v 1 -vf ' . escapeshellarg('scale=768:-2') . ' ' . escapeshellarg($frame);
        @exec($command, $unused, $code);
        if ($code === 0 && is_file($frame) && filesize($frame) > 0) $frames[] = $frame;
        else @unlink($frame);
    }
    if (!$frames) throw new RuntimeException('Could not extract frames from this video.');

    $prompt = 'Analyze these still frames and the local file context. Return ONLY a JSON object with name (string), actors (array of strings), characters (array of strings), productions (array of strings), categories (array of strings), summary (string). Be conservative: never identify a real person by name unless the filename clearly supplies it; use empty arrays when unsure. Do not include explicit sexual detail. Create a concise, neutral library title. File name: ' . $video['file'] . '. Original name: ' . $video['original_name'] . '. Creation-derived published date: ' . ($video['publish_date'] ?: 'unknown') . '.';
    $images = array_map(static fn(string $frame): string => base64_encode((string) file_get_contents($frame)), $frames);
    $request = ['model' => $model, 'prompt' => $prompt, 'images' => $images, 'stream' => false, 'format' => 'json', 'options' => ['temperature' => 0.2]];
    $curl = curl_init($host . '/api/generate');
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($request, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90]);
    $raw = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    if (!is_string($raw) || $status < 200 || $status >= 300) throw new RuntimeException('Ollama analysis failed' . ($curlError ? ': ' . $curlError : '.'));

    $response = json_decode($raw, true);
    $text = trim((string) ($response['response'] ?? ''));
    $text = (string) preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
    $suggestion = json_decode($text, true);
    if (!is_array($suggestion)) throw new RuntimeException('Ollama returned suggestions in an unexpected format.');
    foreach (['actors', 'characters', 'productions', 'categories'] as $key) $suggestion[$key] = array_values(array_filter(array_map('strval', (array) ($suggestion[$key] ?? []))));
    $suggestion['name'] = trim((string) ($suggestion['name'] ?? $video['original_name']));
    $suggestion['summary'] = trim((string) ($suggestion['summary'] ?? ''));
} catch (Throwable $error) {
    $failure = $error->getMessage();
} finally {
    removeAnalysisFrames($frames);
}
if ($failure !== null) analysisError('Analysis could not be completed: ' . $failure, 502);
echo json_encode(['success' => true, 'suggestion' => $suggestion], JSON_UNESCAPED_UNICODE);
