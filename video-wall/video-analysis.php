<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');
function analysisError(string $message, int $status = 422): never { http_response_code($status); echo json_encode(['error' => $message]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') analysisError('POST is required.', 405);
$payload = json_decode((string)file_get_contents('php://input'), true);
$id = trim((string)($payload['id'] ?? ''));
$apiKey = trim((string)getenv('OPENAI_API_KEY'));
if ($apiKey === '') analysisError('AI analysis is not configured. Set OPENAI_API_KEY for the Apache/XAMPP service, then try again.', 503);
if (!function_exists('curl_init')) analysisError('PHP cURL is required for AI analysis.', 503);
$statement = db()->prepare('SELECT id,path,file,original_name,created,publish_date FROM videos WHERE id=?'); $statement->execute([$id]); $video = $statement->fetch();
if (!$video || !is_file((string)$video['path'])) analysisError('The requested video is not available.');
$ffmpeg = findFfmpeg(); if ($ffmpeg === null) analysisError('FFmpeg is required to extract temporary analysis frames. Set its path in Folders.', 503);
$temporary = DATA_DIR . DIRECTORY_SEPARATOR . 'analysis-frames'; if (!is_dir($temporary) && !mkdir($temporary, 0775, true) && !is_dir($temporary)) analysisError('Unable to create temporary analysis frames.');
$frames = [];
try {
    foreach ([2, 20, 60] as $offset) { $frame = $temporary . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12)) . '.jpg'; $command = escapeshellarg($ffmpeg) . ' -hide_banner -loglevel error -y -ss ' . $offset . ' -i ' . escapeshellarg((string)$video['path']) . ' -frames:v 1 -vf ' . escapeshellarg('scale=768:-2') . ' ' . escapeshellarg($frame); @exec($command, $unused, $code); if ($code === 0 && is_file($frame) && filesize($frame) > 0) $frames[] = $frame; else @unlink($frame); }
    if (!$frames) analysisError('Could not extract frames from this video.');
    $content = [[ 'type' => 'input_text', 'text' => 'Analyze these still frames and the local file context. Return ONLY a JSON object with name (string), actors (array of strings), characters (array of strings), productions (array of strings), categories (array of strings), summary (string). Be conservative: never identify a real person by name unless the filename clearly supplies it; use empty arrays when unsure. Do not include explicit sexual detail. Create a concise, neutral library title. File name: ' . $video['file'] . '. Original name: ' . $video['original_name'] . '. Creation-derived published date: ' . ($video['publish_date'] ?: 'unknown') . '.' ]];
    foreach ($frames as $frame) $content[] = ['type' => 'input_image', 'image_url' => 'data:image/jpeg;base64,' . base64_encode((string)file_get_contents($frame)), 'detail' => 'low'];
    $request = ['model' => getenv('OPENAI_VIDEO_ANALYSIS_MODEL') ?: 'gpt-5.6-luna', 'store' => false, 'input' => [[ 'role' => 'user', 'content' => $content ]]];
    $curl = curl_init('https://api.openai.com/v1/responses'); curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($request), CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90]); $raw = curl_exec($curl); $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $curlError = curl_error($curl); curl_close($curl);
    if (!is_string($raw) || $status < 200 || $status >= 300) analysisError('OpenAI analysis failed' . ($curlError ? ': ' . $curlError : '.') , 502);
    $response = json_decode($raw, true); $text = trim((string)($response['output_text'] ?? '')); if ($text === '') analysisError('The AI service returned no usable suggestions.', 502);
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text); $suggestion = json_decode((string)$text, true); if (!is_array($suggestion)) analysisError('The AI service returned suggestions in an unexpected format.', 502);
    foreach (['actors','characters','productions','categories'] as $key) $suggestion[$key] = array_values(array_filter(array_map('strval', (array)($suggestion[$key] ?? []))));
    $suggestion['name'] = trim((string)($suggestion['name'] ?? $video['original_name'])); $suggestion['summary'] = trim((string)($suggestion['summary'] ?? ''));
    echo json_encode(['success' => true, 'suggestion' => $suggestion], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) { analysisError('Analysis could not be completed: ' . $error->getMessage(), 500); }
finally { foreach ($frames as $frame) @unlink($frame); }
