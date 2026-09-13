<?php
declare (strict_types = 1);
set_time_limit(600);
ini_set('max_execution_time', '600');



require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    analysisError('POST is required.', 405);
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$id      = trim((string) ($payload['id'] ?? ''));

if ($id === '') {
    analysisError('A video id is required.', 400);
}

$model = trim((string) getenv('OLLAMA_VIDEO_ANALYSIS_MODEL')) ?: 'qwen3-vl:2b';

$host = trim((string) (getenv('OLLAMA_HOST') ?: 'http://127.0.0.1:11434'));
if (! str_contains($host, '://')) {
    $host = 'http://' . $host;
}

$host       = rtrim($host, '/');
$hostParts  = parse_url($host);
$localHosts = ['127.0.0.1', 'localhost', '::1', '0.0.0.0'];

if (
    ! $hostParts
    || ! isset($hostParts['host'])
    || ! in_array(strtolower((string) $hostParts['host']), $localHosts, true)
) {
    analysisError('OLLAMA_HOST must point to a local Ollama service.', 503);
}

if (strtolower((string) $hostParts['host']) === '0.0.0.0') {
    $port = isset($hostParts['port']) ? ':' . (int) $hostParts['port'] : '';
    $host = 'http://127.0.0.1' . $port;
}

$statement = db()->prepare(
    'SELECT id, path, file, original_name, created, publish_date
     FROM videos
     WHERE id = ?'
);
$statement->execute([$id]);
$video = $statement->fetch(PDO::FETCH_ASSOC);

if (! $video || ! is_file((string) $video['path'])) {
    analysisError('The requested video is not available.');
}

$titleStatement = db()->prepare(
    "SELECT COALESCE(NULLIF(TRIM(display_name), ''), TRIM(original_name)) AS title
     FROM videos
     WHERE id <> ?
       AND COALESCE(NULLIF(TRIM(display_name), ''), TRIM(original_name)) <> ''"
);
$titleStatement->execute([$id]);

$existingTitles = array_values(array_filter(
    array_map(
        static fn(array $row): string => trim((string) ($row['title'] ?? '')),
        $titleStatement->fetchAll(PDO::FETCH_ASSOC)
    ),
    static fn(string $title): bool => $title !== ''
));

$normalizedExistingTitles = array_values(array_unique(array_map(
    static fn(string $title): string => normalizeVideoTitle($title),
    $existingTitles
)));

$existingTitleList = $existingTitles
    ? "\n\nTITLES ALREADY USED IN THIS VIDEO LIBRARY:\n- " . implode("\n- ", $existingTitles)
    : "\n\nTITLES ALREADY USED IN THIS VIDEO LIBRARY:\n- None";

$ffmpeg = findFfmpeg();
if ($ffmpeg === null) {
    analysisError(
        'FFmpeg is required to extract temporary analysis frames. Set its path in Folders.',
        503
    );
}

$temporary = DATA_DIR . DIRECTORY_SEPARATOR . 'analysis-frames';
if (
    ! is_dir($temporary)
    && ! mkdir($temporary, 0775, true)
    && ! is_dir($temporary)
) {
    analysisError('Unable to create temporary analysis frames.');
}

$frames     = [];
$suggestion = null;
$failure    = null;

try {
    $duration = probeVideoDuration($ffmpeg, (string) $video['path']);

    foreach (buildFrameOffsets($duration) as $offset) {
        $frame = $temporary . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12)) . '.jpg';

        $command =
        escapeshellarg($ffmpeg)
        . ' -hide_banner -loglevel error -y'
        . ' -ss ' . escapeshellarg((string) $offset)
        . ' -i ' . escapeshellarg((string) $video['path'])
        . ' -frames:v 1'
        . ' -vf ' . escapeshellarg('scale=768:-2')
        . ' ' . escapeshellarg($frame);

        $unused = [];
        $code   = 1;

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

    $prompt = file_get_contents(__DIR__ . '/analysis-prompt.php');

    $images = array_map(
        static fn(string $frame): string =>
        base64_encode((string) file_get_contents($frame)),
        $frames
    );

    $maxAttempts    = 5;
    $duplicateTitle = null;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $attemptPrompt = $prompt;

        if ($duplicateTitle !== null) {
            $attemptPrompt .=
                "\n\nRETRY REQUIRED:"
                . "\nYour previous proposed title was: " . $duplicateTitle
                . "\nThat title already exists in the database."
                . "\nGenerate a genuinely different 2-to-6 word title based on this video's visual content."
                . "\nDo not add a number, change only punctuation, change only capitalization, or make a tiny wording change."
                . "\nThe new title must not match anything in the TITLES ALREADY USED list.";
        }

        $request = [
            'model'   => $model,
            'prompt'  => $attemptPrompt,
            'images'  => $images,
            'stream'  => false,
            'think'   => false,
            'format'  => 'json',
            'options' => [
                'temperature' => $attempt === 1 ? 0.2 : 0.4,
                'num_ctx'     => 25000,
            ],
        ];

        $requestJson = json_encode(
            $request,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        if (! is_string($requestJson)) {
            throw new RuntimeException('Unable to encode the Ollama request.');
        }

        $raw            = false;
        $status         = 0;
        $transportError = '';

        if (function_exists('curl_init')) {
            $curl = curl_init($host . '/api/generate');

            curl_setopt_array($curl, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $requestJson,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 300,
            ]);

            $raw            = curl_exec($curl);
            $status         = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $transportError = curl_error($curl);

            curl_close($curl);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        => "Content-Type: application/json\r\n",
                    'content'       => $requestJson,
                    'timeout'       => 300,
                    'ignore_errors' => true,
                ],
            ]);

            $raw = @file_get_contents(
                $host . '/api/generate',
                false,
                $context
            );

            foreach ($http_response_header ?? [] as $header) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) {
                    $status = (int) $matches[1];
                    break;
                }
            }

            if ($raw === false) {
                $transportError = 'The local Ollama service could not be reached.';
            }
        }

        if (! is_string($raw)) {
            throw new RuntimeException(
                'Ollama analysis failed'
                . ($transportError !== '' ? ': ' . $transportError : '.')
            );
        }

        if ($status < 200 || $status >= 300) {
            $errorMessage = '';
            $errorJson    = json_decode($raw, true);

            if (is_array($errorJson)) {
                $errorMessage = trim(
                    (string) ($errorJson['error'] ?? $errorJson['message'] ?? '')
                );
            }

            if ($errorMessage === '') {
                $errorMessage = trim($raw);
            }

            if ($errorMessage === '') {
                $errorMessage = 'HTTP ' . $status;
            }

            throw new RuntimeException(
                'Ollama analysis failed: ' . $errorMessage
            );
        }

        $response = json_decode($raw, true);

        if (! is_array($response)) {
            throw new RuntimeException(
                'Ollama returned an invalid API response.'
            );
        }

        if (
            isset($response['error'])
            && is_string($response['error'])
            && $response['error'] !== ''
        ) {
            throw new RuntimeException(
                'Ollama error: ' . $response['error']
            );
        }

        $text = trim(
            (string) (($response['response'] ?? '')
                    ?: ($response['thinking'] ?? ''))
        );

        if ($text === '') {
            throw new RuntimeException(
                'Ollama returned an empty analysis response.'
            );
        }

        $text = (string) preg_replace(
            '/^```(?:json)?\s*|\s*```$/',
            '',
            $text
        );

        $attemptSuggestion = json_decode($text, true);

        if (! is_array($attemptSuggestion)) {
            $start = strpos($text, '{');
            $end   = strrpos($text, '}');

            if ($start !== false && $end !== false && $end > $start) {
                $attemptSuggestion = json_decode(
                    substr($text, $start, $end - $start + 1),
                    true
                );
            }
        }

        if (! is_array($attemptSuggestion)) {
            throw new RuntimeException(
                'Ollama returned suggestions in an unexpected format.'
            );
        }

        $title = improveAnalysisTitle(
            (string) ($attemptSuggestion['name'] ?? ''),
            (string) $video['original_name']
        );

        if ($title === '') {
            $duplicateTitle = 'Untitled Scene';

            if ($attempt === $maxAttempts) {
                throw new RuntimeException(
                    'Ollama could not generate a usable title after '
                    . $maxAttempts
                    . ' attempts.'
                );
            }

            continue;
        }

        $normalizedTitle = normalizeVideoTitle($title);

        if (in_array($normalizedTitle, $normalizedExistingTitles, true)) {
            $duplicateTitle = $title;

            if ($attempt === $maxAttempts) {
                throw new RuntimeException(
                    'Ollama could not generate a unique title after '
                    . $maxAttempts
                    . ' attempts. Last duplicate: '
                    . $title
                );
            }

            continue;
        }

        $suggestion         = $attemptSuggestion;
        $suggestion['name'] = $title;
        break;
    }

    if (! is_array($suggestion)) {
        throw new RuntimeException(
            'Ollama did not return a usable unique analysis.'
        );
    }

    foreach (
        ['actors', 'characters', 'studios', 'productions', 'categories', 'genres'] as $key
    ) {
        $values = array_values(array_filter(
            array_map(
                static fn($value): string => trim((string) $value),
                (array) ($suggestion[$key] ?? [])
            ),
            static fn(string $value): bool => $value !== ''
        ));

        if (in_array($key, ['actors', 'characters'], true)) {
            $blocked = [
                'person',
                'man',
                'woman',
                'male',
                'female',
                'unknown',
                'unknown person',
                'unidentified person',
                'player',
            ];

            $values = array_values(array_filter(
                $values,
                static fn(string $value): bool =>
                ! in_array(strtolower($value), $blocked, true)
            ));
        }

        $suggestion[$key] = array_values(array_unique($values));
    }

    $suggestion['summary'] = trim(
        (string) ($suggestion['summary'] ?? '')
    );
} catch (Throwable $error) {
    $failure = $error->getMessage();
} finally {
    removeAnalysisFrames($frames);
}

if ($failure !== null) {
    analysisError(
        'Analysis could not be completed: ' . $failure,
        502
    );
}

echo json_encode(
    [
        'success'    => true,
        'suggestion' => $suggestion,
    ],
    JSON_UNESCAPED_UNICODE
);
