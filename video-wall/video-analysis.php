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

function normalizeAnalysisTitle(string $value): string {
    return trim((string) preg_replace('/[^a-z0-9]+/i', ' ', strtolower($value)));
}

function improveAnalysisTitle(string $title, string $originalName): string {
    $title = trim($title);
    $originalNormalized = normalizeAnalysisTitle($originalName);
    $parts = preg_split('/\s*[-–—:]\s*/', $title, 2);
    if (count($parts) === 2) {
        [$leading, $descriptive] = array_map('trim', $parts);
        $leadingNormalized = normalizeAnalysisTitle($leading);
        if ($descriptive !== '' && $leadingNormalized !== '' && str_contains($originalNormalized, $leadingNormalized)) {
            return $descriptive . ' - ' . $leading;
        }
    }
    if ($title !== '' && normalizeAnalysisTitle($title) === $originalNormalized) return 'Gameplay Highlight - ' . $title;
    return $title;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') analysisError('POST is required.', 405);
$payload = json_decode((string) file_get_contents('php://input'), true);
$id = trim((string) ($payload['id'] ?? ''));
$model = trim((string) getenv('OLLAMA_VIDEO_ANALYSIS_MODEL')) ?: 'qwen3-vl:2b';

$host = trim((string) (getenv('OLLAMA_HOST') ?: 'http://127.0.0.1:11434'));
if (!str_contains($host, '://')) $host = 'http://' . $host;
$host = rtrim($host, '/');
$hostParts = parse_url($host);
$localHosts = ['127.0.0.1', 'localhost', '::1', '0.0.0.0'];
if (!$hostParts || !isset($hostParts['host']) || !in_array(strtolower((string) $hostParts['host']), $localHosts, true)) analysisError('OLLAMA_HOST must point to a local Ollama service.', 503);
if (strtolower((string) $hostParts['host']) === '0.0.0.0') {
    $port = isset($hostParts['port']) ? ':' . (int) $hostParts['port'] : '';
    $host = 'http://127.0.0.1' . $port;
}

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

    $prompt = 'Analyze the provided video still frames together with the available local file context. Return ONLY a valid JSON object with exactly these fields: name (string), actors (array of strings), characters (array of strings), productions (array of strings), categories (array of strings), and summary (string). Output valid JSON only with no Markdown, code fences, commentary, explanations, or additional fields. Base all results only on information visible in the frames or explicitly provided by the file context. Be conservative when identifying people, characters, productions, games, locations, or other entities; never identify a real person by name unless their identity is clearly supplied by the filename or provided file context, and use empty arrays [] whenever actors, characters, productions, or categories cannot be determined confidently. Do not include explicit sexual detail; describe mature content only in neutral, non-graphic language. Keep the summary concise and describe only what is visibly happening without inventing unsupported details. The name MUST be a catchy, natural, curiosity-driven title or headline describing the most interesting moment in the footage, similar to a TikTok, YouTube Short, or gaming highlight hook, and should make someone curious enough to watch without being misleading. Focus on the action, surprise, tension, close call, objective, encounter, mistake, clutch, unexpected event, or memorable moment rather than simply identifying the content. Titles may use natural first-person phrasing when appropriate, such as "I Had No Idea What Was About to Hit This Objective", "Everything Hit the Objective at Once", "I Thought This Objective Was Already Lost", "This Push Got Out of Control Fast", or "I Was Not Ready for What Came Next". NEVER use only a game, franchise, production, character, person, filename, map, or mode name as the title and NEVER simply clean up or copy the filename. For gameplay, the game name should normally be omitted from the title because it belongs in productions or categories; if included, it must only provide secondary context after the actual hook. Determine what is visibly happening first and build the title around the strongest moment. If the exact event is unclear, create a compelling but truthful title from the visible action, environment, objective, equipment, encounter, or setting instead of falling back to the game name. Treat file context as supporting metadata, prefer visible evidence when describing what happens, and use file context only to resolve information it clearly supplies. File name: ' . $video['file'] . '. Original name: ' . $video['original_name'] . '. Creation-derived published date: ' . ($video['publish_date'] ?: 'unknown') . '.';
    $images = array_map(static fn(string $frame): string => base64_encode((string) file_get_contents($frame)), $frames);
    $request = ['model' => $model, 'prompt' => $prompt, 'images' => $images, 'stream' => false, 'think' => false, 'format' => 'json', 'options' => ['temperature' => 0.2]];
    $requestJson = json_encode($request, JSON_UNESCAPED_UNICODE);
    if (function_exists('curl_init')) {
        $curl = curl_init($host . '/api/generate');
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $requestJson, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $transportError = curl_error($curl);
        curl_close($curl);
    } else {
        $context = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $requestJson, 'timeout' => 90, 'ignore_errors' => true]]);
        $raw = @file_get_contents($host . '/api/generate', false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $header, $matches)) { $status = (int) $matches[1]; break; }
        }
        $transportError = $raw === false ? 'The local Ollama service could not be reached.' : '';
    }
    if (!is_string($raw) || $status < 200 || $status >= 300) throw new RuntimeException('Ollama analysis failed' . ($transportError ? ': ' . $transportError : '.'));

    $response = json_decode($raw, true);
    $text = trim((string) (($response['response'] ?? '') ?: ($response['thinking'] ?? '')));
    $text = (string) preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
    $suggestion = json_decode($text, true);
    if (!is_array($suggestion)) throw new RuntimeException('Ollama returned suggestions in an unexpected format.');
    foreach (['actors', 'characters', 'productions', 'categories'] as $key) $suggestion[$key] = array_values(array_filter(array_map('strval', (array) ($suggestion[$key] ?? []))));
    $suggestion['name'] = improveAnalysisTitle((string) ($suggestion['name'] ?? $video['original_name']), (string) $video['original_name']);
    $suggestion['summary'] = trim((string) ($suggestion['summary'] ?? ''));
} catch (Throwable $error) {
    $failure = $error->getMessage();
} finally {
    removeAnalysisFrames($frames);
}
if ($failure !== null) analysisError('Analysis could not be completed: ' . $failure, 502);
echo json_encode(['success' => true, 'suggestion' => $suggestion], JSON_UNESCAPED_UNICODE);
