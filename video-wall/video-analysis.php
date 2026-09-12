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
        return '';
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
        return '';
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
        return [2.0, 10.0, 20.0];
    }

    $fractions = [0.10, 0.50, 0.90];
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
// $model    = trim((string) getenv('OLLAMA_VIDEO_ANALYSIS_MODEL')) ?: 'junquan2k/qwen2.5-vl-final-q4-20260608';

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
        $command = escapeshellarg($ffmpeg) . ' -hide_banner -loglevel error -y -ss ' . escapeshellarg((string) $offset) . ' -i ' . escapeshellarg((string) $video['path']) . ' -frames:v 1 -vf ' . escapeshellarg('scale=768:-2') . ' ' . escapeshellarg($frame);
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

    $prompt = 'Analyze all provided video still frames as samples from different timestamps across the same video. Compare ALL frames together and determine the overall content from the combined evidence instead of judging the video from one frame. Return ONLY valid JSON with exactly these fields: name (string), actors (array of strings), characters (array of strings), studios (array of strings), productions (array of strings), categories (array of strings), genres (array of strings), summary (string). Do not return Markdown, code fences, explanations, commentary, or any text before or after the JSON object. The content may be gameplay, movies or television, animation, personal footage, sports, music or performance, tutorials, screen recordings, social-media clips, adult/pornographic material, or other general video. Do not assume gameplay, adult content, or any other content type unless the combined frames reasonably support that conclusion. Base conclusions primarily on the supplied frames and use the filename, original name, and date only as supporting context. The name MUST be a newly generated, unique, creative, natural library title inspired only by what actually happens across the sampled parts of THIS specific video. The name MUST be 2-to-6 words. Generate the title independently for every analysis. Do not copy, reuse, paraphrase, slightly modify, or imitate a title from previous analyses, prompt examples, filenames, metadata, productions, studios, games, franchises, categories, or genres. Do not use generic media labels, stock phrases, repeated title templates, fallback-style titles, or a title that merely states the type of media. NEVER return or reformat the filename, original filename, file extension, game title, franchise name, production name, studio name, map name, mode name, category name, or genre name as the title by itself. Generate the title from the strongest recognizable activity, interaction, setting, mood, objective, surprise, or memorable moment supported by the combined frames. The title should describe or creatively evoke something specific about THIS video rather than something that could apply equally well to many unrelated videos. For adult content, create a unique playful or suggestive but non-graphic title derived from the specific visible activity, interaction, setting, clothing, mood, or other distinguishing visual details. Do not automatically use romantic, bedroom, nighttime, or generic suggestive wording unless those ideas are actually supported by the frames. For gameplay or action footage, create a unique energetic hook-style title derived from the specific action, objective, environment, event, or memorable moment. For movies, television, animation, personal footage, sports, music, tutorials, screen recordings, social-media clips, and other ordinary videos, create a unique natural descriptive or catchy title based on the most distinctive visible activity, subject, setting, or event. Every generated title should be noticeably different from titles that could reasonably describe unrelated videos. The summary MUST describe what appears to happen across the sampled parts of the video in natural language rather than describing each frame separately. Summarize the overall activity, setting, progression, and notable visible details supported by the combined evidence. Do not invent events that are not reasonably supported by the frames. The summary may describe obvious non-sensitive visual characteristics when reasonably apparent but must never identify an unknown real person from appearance alone. For adult content, the summary may accurately describe the overall adult nature of the footage and may be playful or suggestive, but it must remain non-graphic. Actors MUST contain only real performer names explicitly supported by supplied metadata or file context. Never identify a real person based only on their appearance in the frames. NEVER use generic values such as "Person", "Man", "Woman", "Male", "Female", "Unknown", "Unknown Person", or similar descriptions as actor names. If no supported actor name is available, return an empty array for actors. Characters MUST contain only identifiable named fictional or otherwise explicitly named characters when reasonably supported by the combined visual evidence or supplied context. NEVER use generic descriptions such as "Person", "Man", "Woman", "Male", "Female", "Player", "Unknown", or similar descriptions as character names. If no named character can reasonably be identified, return an empty array for characters. Studios MAY contain studios, developers, publishers, networks, labels, or other appropriate organizations when reasonably supported by the combined visual evidence and supplied context. Productions MAY contain identifiable games, movies, television series, franchises, shows, productions, or other relevant works when reasonably supported by the combined visual evidence and supplied context. Do not invent a studio or production merely to fill the arrays. Categories SHOULD contain useful, specific library classifications inferred from the combined video evidence. Categories may describe the content type, activity, setting, subject, style, or other useful organizational characteristics. Genres SHOULD contain useful broader genre classifications supported by the content. For adult content, categories and genres may accurately classify clearly visible adult content at a useful level while keeping the generated title and summary non-graphic. Do not force adult classifications when the evidence is ambiguous. For gameplay, use useful classifications based on the visible game style, activity, environment, or gameplay type when supported. For all arrays, use [] when there is insufficient evidence for a useful value rather than inventing information. Avoid duplicate, near-duplicate, meaningless, or excessively broad values in arrays. Never identify an unknown real person from appearance alone. Return ONLY one valid JSON object containing exactly the requested fields and no additional fields. File name: ' . $video['file'] . '. Original name: ' . $video['original_name'] . '. Creation-derived published date: ' . ($video['publish_date'] ?: 'unknown') . '.';


    $images      = array_map(static fn(string $frame): string => base64_encode((string) file_get_contents($frame)), $frames);
    //$request     = ['model' => $model, 'prompt' => $prompt, 'images' => $images, 'stream' => false, 'think' => false, 'format' => 'json', 'options' => ['temperature' => 0.2]];
    $request = [
        'model'   => $model,
        'prompt'  => $prompt,
        'images'  => $images,
        'stream'  => false,
        'think'   => false,
        'format'  => 'json',
        'options' => [
            'temperature' => 0.2,
            'num_ctx'     => 8192,
        ],
    ];

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
    if (! is_string($raw)) {
        throw new RuntimeException('Ollama analysis failed' . ($transportError ? ': ' . $transportError : '.'));
    }
    if ($status < 200 || $status >= 300) {
        $errorMessage = '';
        $errorJson    = json_decode($raw, true);
        if (is_array($errorJson)) {
            $errorMessage = trim((string) ($errorJson['error'] ?? $errorJson['message'] ?? ''));
        }
        if ($errorMessage === '') {
            $errorMessage = trim($raw);
        }

        if ($errorMessage === '') {
            $errorMessage = 'HTTP ' . $status;
        }

        throw new RuntimeException('Ollama analysis failed: ' . $errorMessage);
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
        $values = array_values(array_filter(array_map(static fn($value): string => trim((string) $value), (array) ($suggestion[$key] ?? [])), static fn(string $value): bool => $value !== ''));
        if (in_array($key, ['actors', 'characters'], true)) {
            $blocked = ['person', 'man', 'woman', 'male', 'female', 'unknown', 'unknown person', 'unidentified person'];
            $values  = array_values(array_filter($values, static fn(string $value): bool => ! in_array(strtolower($value), $blocked, true)));
        }
        $suggestion[$key] = array_values(array_unique($values));
    }

    $title = improveAnalysisTitle((string) ($suggestion['name'] ?? ''), (string) $video['original_name']);
    if ($title === '') {
        $title = 'Untitled Scene';
    }
    $suggestion['name']    = $title;
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
