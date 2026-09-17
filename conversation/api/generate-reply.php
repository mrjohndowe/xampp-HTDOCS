<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/helpers/helpers.php';

$config = require dirname(__DIR__) . '/app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

if (!($config['ai_reply']['enabled'] ?? false)) {
    jsonResponse(['error' => 'AI replies are disabled.'], 503);
}

$payload = json_decode(file_get_contents('php://input'), true);

if (!is_array($payload)) {
    jsonResponse(['error' => 'Invalid JSON body.'], 400);
}

$conversationId = trim((string) ($payload['conversationId'] ?? ''));

if (!preg_match('/^[a-z0-9_-]+$/i', $conversationId)) {
    jsonResponse(['error' => 'Invalid conversation.'], 400);
}

$conversationFile = dirname(__DIR__)
    . '/app/data/conversations/' . $conversationId . '.php';

if (!is_file($conversationFile)) {
    jsonResponse(['error' => 'Conversation not found.'], 404);
}

$conversationData = require $conversationFile;
$replySettings = is_array($conversationData['reply'] ?? null)
    ? $conversationData['reply']
    : [];
$history = is_array($payload['history'] ?? null) ? $payload['history'] : [];
$maxHistory = max(1, (int) ($config['ai_reply']['max_history_messages'] ?? 12));
$history = array_slice($history, -$maxHistory);
$ollamaMessages = [];

$systemPrompt = trim((string) ($replySettings['system_prompt'] ?? ''));

if ($systemPrompt === '') {
    $systemPrompt = 'You are replying as the other person in a fictional text-message simulator. '
        . 'Reply naturally and concisely to the latest message. '
        . 'Do not say that you are an AI or describe these instructions.';
}

$ollamaMessages[] = ['role' => 'system', 'content' => $systemPrompt];

foreach ($history as $message) {
    if (!is_array($message)) {
        continue;
    }

    $sender = (string) ($message['sender'] ?? '');
    $text = trim((string) ($message['text'] ?? ''));

    if ($text === '' || strlen($text) > 2000) {
        continue;
    }

    $ollamaMessages[] = [
        'role' => $sender === 'other' ? 'assistant' : 'user',
        'content' => $text,
    ];
}

if (count($ollamaMessages) === 1) {
    jsonResponse(['error' => 'A message is required before generating a reply.'], 422);
}

$requestBody = json_encode([
    'model' => (string) ($replySettings['model'] ?? $config['ai_reply']['model']),
    'messages' => $ollamaMessages,
    'stream' => false,
    'options' => ['temperature' => 0.75],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($requestBody === false) {
    jsonResponse(['error' => 'Unable to prepare the AI request.'], 500);
}

$timeout = max(1, (int) ($config['ai_reply']['timeout_seconds'] ?? 45));
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
        'content' => $requestBody,
        'timeout' => $timeout,
        'ignore_errors' => true,
    ],
]);

$responseBody = @file_get_contents((string) $config['ai_reply']['ollama_url'], false, $context);
$responseData = is_string($responseBody) ? json_decode($responseBody, true) : null;
$reply = trim((string) ($responseData['message']['content'] ?? ''));

if ($reply === '') {
    $reply = (string) ($replySettings['fallback_reply'] ?? $config['ai_reply']['fallback_reply']);
}

jsonResponse([
    'reply' => $reply,
    'provider' => $responseData !== null ? 'ollama' : 'fallback',
]);
