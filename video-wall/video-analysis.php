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

    $prompt = 'Analyze the provided video still frames together with the available local file context. Return ONLY a valid JSON object with exactly these fields: name (string), actors (array of strings), characters (array of strings), studios (array of strings), productions (array of strings), categories (array of strings), genres (array of strings), and summary (string). Output valid JSON only with no Markdown, code fences, commentary, explanations, or additional fields. Determine the type of content first and adapt the metadata appropriately; the video may contain gameplay, movies, television, animation, personal footage, music, sports, adult/pornographic material, or other content. Base the analysis primarily on visible evidence while using the filename, original name, date, and supplied local context as supporting evidence. Never identify a real person by name from appearance alone; actors may be named only when their identity is clearly supplied by the filename or other provided context. You MAY intelligently suggest likely fictional characters, studios, developers, publishers, productions, franchises, categories, and genres when the available evidence reasonably supports the inference, but do not present weak guesses as facts. Use [] when there is insufficient evidence. Choose metadata appropriate to the detected content type: for gameplay, studios may contain developers or publishers, characters may contain recognizable fictional characters, productions may contain the game or franchise, categories may describe visible gameplay subjects or activities, and genres may contain game genres; for movies, television, animation, or similar media, studios may contain likely production companies, characters may contain recognizable fictional characters, productions may contain the title or franchise, categories may describe themes or content, and genres may contain appropriate media genres; for adult or pornographic material, accurately classify the content as Adult or Pornographic where appropriate and suggest useful high-level adult genres and categories supported by the visible content or supplied metadata, while keeping wording concise, neutral, non-graphic, and non-vulgar and never inferring a performer identity from appearance alone. The summary must adapt to the content type and briefly describe the visible scene or event using neutral language; for explicit adult material, summarize it at a high level without graphic descriptions of sexual acts or anatomy. The name MUST be an original, natural, curiosity-driven library title based on the most distinctive visible scene, action, setting, objective, encounter, mood, or memorable moment rather than merely identifying the production. For gameplay or general videos, titles may resemble strong TikTok, YouTube Short, or highlight hooks such as "I Had No Idea What Was About to Hit This Objective", "Everything Hit the Objective at Once", "I Thought This Objective Was Already Lost", or "This Push Got Out of Control Fast". For adult material, create a concise, distinctive, non-graphic title based on the visible setting, scenario, mood, wardrobe, or supplied context rather than generating sexually explicit prose. NEVER use only a game, franchise, production, studio, actor, character, filename, map, mode, or generic label such as "Adult Video" as the title and NEVER simply clean up or copy the filename. Put identifiable production information in productions, studio information in studios, and descriptive classification information in categories and genres rather than relying on those values as the title. If the exact event or production is unclear, create the best truthful descriptive title possible from what is actually visible instead of guessing. File name: ' . $video['file'] . '. Original name: ' . $video['original_name'] . '. Creation-derived published date: ' . ($video['publish_date'] ?: 'unknown') . '.';
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
    if (!is_array($suggestion)) {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) $suggestion = json_decode(substr($text, $start, $end - $start + 1), true);
    }
    if (!is_array($suggestion)) throw new RuntimeException('Ollama returned suggestions in an unexpected format.');
    foreach (['actors', 'characters', 'studios', 'productions', 'categories', 'genres'] as $key) $suggestion[$key] = array_values(array_filter(array_map('strval', (array) ($suggestion[$key] ?? []))));
    $suggestion['name'] = improveAnalysisTitle((string) ($suggestion['name'] ?? $video['original_name']), (string) $video['original_name']);
    $suggestion['summary'] = trim((string) ($suggestion['summary'] ?? ''));
} catch (Throwable $error) {
    $failure = $error->getMessage();
} finally {
    removeAnalysisFrames($frames);
}
if ($failure !== null) analysisError('Analysis could not be completed: ' . $failure, 502);
echo json_encode(['success' => true, 'suggestion' => $suggestion], JSON_UNESCAPED_UNICODE);
