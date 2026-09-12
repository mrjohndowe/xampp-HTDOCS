<?php
declare (strict_types = 1);

require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');

function analysisError(string $message, int $status = 422): never
{
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function removeAnalysisFrames(array $frames): void
{
    foreach ($frames as $frame) {
        @unlink($frame);
    }

}

function normalizeAnalysisTitle(string $value): string
{
    return trim((string) preg_replace('/[^a-z0-9]+/i', ' ', strtolower($value)));
}

function improveAnalysisTitle(string $title, string $originalName): string
{
    $title              = trim($title);
    $originalNormalized = normalizeAnalysisTitle($originalName);

    if ($title === '') {
        return 'Untitled Video';
    }

    $parts = preg_split('/\s*[-–—:]\s*/', $title, 2);
    if (count($parts) === 2) {
        [$leading, $descriptive] = array_map('trim', $parts);
        $leadingNormalized       = normalizeAnalysisTitle($leading);
        if ($descriptive !== '' && $leadingNormalized !== '' && str_contains($originalNormalized, $leadingNormalized)) {
            $title = $descriptive . ' - ' . $leading;
        }
    }

    if (normalizeAnalysisTitle($title) === $originalNormalized) {
        return 'Untitled Video Scene';
    }

    return $title;
}

function probeVideoDuration(string $ffmpeg, string $videoPath): ?float
{
    $ffprobe = preg_replace('/ffmpeg(?:\.exe)?$/i', PHP_OS_FAMILY === 'Windows' ? 'ffprobe.exe' : 'ffprobe', $ffmpeg);
    if (! is_string($ffprobe) || $ffprobe === '' || ! is_file($ffprobe)) {
        return null;
    }

    $command = escapeshellarg($ffprobe) . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($videoPath);
    $output  = [];
    $code    = 1;
    @exec($command, $output, $code);
    if ($code !== 0 || ! $output) {
        return null;
    }

    $duration = (float) trim((string) $output[0]);
    return $duration > 0 ? $duration : null;
}

function buildFrameOffsets(?float $duration): array
{
    if ($duration === null || $duration <= 0) {
        return [5.0]; // Reduced to single frame for context size
    }

    // Reduced to 3 frames instead of 5 to stay within context limits
    $fractions = $duration < 8 ? [0.25, 0.50, 0.75] : [0.15, 0.40, 0.70];
    $offsets   = [];
    foreach ($fractions as $fraction) {
        $offset = max(0.0, min($duration - 0.15, $duration * $fraction));
        if ($offset >= 0) {
            $offsets[] = round($offset, 3);
        }

    }

    $offsets = array_values(array_unique($offsets, SORT_REGULAR));
    return $offsets ?: [0.0];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    analysisError('POST is required.', 405);
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$id      = trim((string) ($payload['id'] ?? ''));
$model   = trim((string) getenv('OLLAMA_VIDEO_ANALYSIS_MODEL')) ?: 'qwen3-vl:2b';

$host = trim((string) (getenv('OLLAMA_HOST') ?: 'http://127.0.0.1:11434'));
if (! str_contains($host, '://')) {
    $host = 'http://' . $host;
}

$host       = rtrim($host, '/');
$hostParts  = parse_url($host);
$localHosts = ['127.0.0.1', 'localhost', '::1', '0.0.0.0'];
if (! $hostParts || ! isset($hostParts['host']) || ! in_array(strtolower((string) $hostParts['host']), $localHosts, true)) {
    analysisError('OLLAMA_HOST must point to a local Ollama service.', 503);
}

if (strtolower((string) $hostParts['host']) === '0.0.0.0') {
    $port = isset($hostParts['port']) ? ':' . (int) $hostParts['port'] : '';
    $host = 'http://127.0.0.1' . $port;
}

$statement = db()->prepare('SELECT id,path,file,original_name,created,publish_date FROM videos WHERE id=?');
$statement->execute([$id]);
$video = $statement->fetch();
if (! $video || ! is_file((string) $video['path'])) {
    analysisError('The requested video is not available.');
}

$ffmpeg = findFfmpeg();
if ($ffmpeg === null) {
    analysisError('FFmpeg is required to extract temporary analysis frames. Set its path in Folders.', 503);
}

$temporary = DATA_DIR . DIRECTORY_SEPARATOR . 'analysis-frames';
if (! is_dir($temporary) && ! mkdir($temporary, 0775, true) && ! is_dir($temporary)) {
    analysisError('Unable to create temporary analysis frames.');
}

$frames     = [];
$suggestion = null;
$failure    = null;
try {
    $duration = probeVideoDuration($ffmpeg, (string) $video['path']);
    foreach (buildFrameOffsets($duration) as $offset) {
        $frame   = $temporary . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12)) . '.jpg';
        // Reduced image size from 768 to 512 pixels to reduce token count
        $command = escapeshellarg($ffmpeg) . ' -hide_banner -loglevel error -y -ss ' . escapeshellarg((string) $offset) . ' -i ' . escapeshellarg((string) $video['path']) . ' -frames:v 1 -vf ' . escapeshellarg('scale=512:-2') . ' ' . escapeshellarg($frame);
        $unused  = [];
        $code    = 1;
        @exec($command, $unused, $code);
        if ($code === 0 && is_file($frame) && filesize($frame) > 0) {
            $frames[] = $frame;
        } else {
            @unlink($frame);
        }

    }
    if (! $frames) {
        throw new RuntimeException('Could not extract frames from this video.');
    }

    $prompt      = 'Analyze these video frames. Return ONLY JSON with: name (string), actors (array), characters (array), studios (array), productions (array), categories (array), genres (array), summary (string). Use [] for unknown. File: ' . $video['file'] . '.';
    $images      = array_map(static fn(string $frame): string => base64_encode((string) file_get_contents($frame)), $frames);
    $request     = ['model' => $model, 'prompt' => $prompt, 'images' => $images, 'stream' => false, 'think' => false, 'format' => 'json', 'options' => ['temperature' => 0.2]];
    $requestJson = json_encode($request, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if (! is_string($requestJson)) {
        throw new RuntimeException('Unable to encode the Ollama request.');
    }

    if (function_exists('curl_init')) {
        $curl = curl_init($host . '/api/generate');
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $requestJson, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90]);
        $raw            = curl_exec($curl);
        $status         = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $transportError = curl_error($curl);
        curl_close($curl);
    } else {
        $context = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $requestJson, 'timeout' => 90, 'ignore_errors' => true]]);
        $raw     = @file_get_contents($host . '/api/generate', false, $context);
        $status  = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $header, $matches)) {$status = (int) $matches[1];
                break;}
        }
        $transportError = $raw === false ? 'The local Ollama service could not be reached.' : '';
    }
    if (! is_string($raw) || $status < 200 || $status >= 300) {
        throw new RuntimeException('Ollama analysis failed' . ($transportError ? ': ' . $transportError : '.'));
    }

    $response = json_decode($raw, true);
    if (! is_array($response)) {
        throw new RuntimeException('Ollama returned an invalid API response.');
    }

    if (isset($response['error']) && is_string($response['error']) && $response['error'] !== '') {
        throw new RuntimeException('Ollama error: ' . $response['error']);
    }

    $text = trim((string) (($response['response'] ?? '') ?: ($response['thinking'] ?? '')));
    if ($text === '') {
        throw new RuntimeException('Ollama returned an empty analysis response.');
    }

    $text       = (string) preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
    $suggestion = json_decode($text, true);
    if (! is_array($suggestion)) {
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $suggestion = json_decode(substr($text, $start, $end - $start + 1), true);
        }

    }
    if (! is_array($suggestion)) {
        throw new RuntimeException('Ollama returned suggestions in an unexpected format.');
    }

    foreach (['actors', 'characters', 'studios', 'productions', 'categories', 'genres'] as $key) {
        $suggestion[$key] = array_values(array_filter(array_map('strval', (array) ($suggestion[$key] ?? []))));
    }

    $suggestion['name']    = improveAnalysisTitle((string) ($suggestion['name'] ?? $video['original_name']), (string) $video['original_name']);
    $suggestion['summary'] = trim((string) ($suggestion['summary'] ?? ''));
} catch (Throwable $error) {
    $failure = $error->getMessage();
} finally {
    removeAnalysisFrames($frames);
}
if ($failure !== null) {
    analysisError('Analysis could not be completed: ' . $failure, 502);
}

echo json_encode(['success' => true, 'suggestion' => $suggestion], JSON_UNESCAPED_UNICODE);
