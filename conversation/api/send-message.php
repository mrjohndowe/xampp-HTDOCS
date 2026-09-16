<?php

declare(strict_types=1);

require dirname(__DIR__)
    . '/app/helpers/helpers.php';

$config = require dirname(__DIR__)
    . '/app/config/config.php';

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {
    jsonResponse(
        [
            'error' =>
                'Method not allowed.'
        ],
        405
    );
}

$payload =
    json_decode(
        file_get_contents(
            'php://input'
        ),
        true
    );

if (!is_array($payload)) {
    jsonResponse(
        [
            'error' =>
                'Invalid JSON body.'
        ],
        400
    );
}

$sender =
    trim(
        (string)
        ($payload['sender'] ?? '')
    );

$text =
    trim(
        (string)
        ($payload['text'] ?? '')
    );

if (
    $sender === '' ||
    $text === ''
) {
    jsonResponse(
        [
            'error' =>
                'Sender and text are required.'
        ],
        422
    );
}

$storageFile =
    $config['paths']['storage']
    . '/conversations/'
    . 'conversation.json';

$directory =
    dirname($storageFile);

if (
    !is_dir($directory)
) {
    mkdir(
        $directory,
        0775,
        true
    );
}

$messages = [];

if (
    is_file($storageFile)
) {
    $existing =
        json_decode(
            file_get_contents(
                $storageFile
            ),
            true
        );

    if (is_array($existing)) {
        $messages = $existing;
    }
}

$message = [
    'id' =>
        bin2hex(
            random_bytes(8)
        ),

    'sender' =>
        $sender,

    'text' =>
        $text,

    'createdAt' =>
        date(DATE_ATOM),
];

$messages[] =
    $message;

file_put_contents(
    $storageFile,
    json_encode(
        $messages,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ),
    LOCK_EX
);

jsonResponse(
    [
        'success' => true,
        'message' => $message,
    ]
);
