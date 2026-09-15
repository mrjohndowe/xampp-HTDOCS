<?php

declare (strict_types = 1);

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function emojiShortcodes(string $text): string
{
    static $emojiMap = null;

    if ($emojiMap === null) {
        $emojiMap = require dirname(__DIR__) . '/data/emojis.php';
    }

    return strtr($text, $emojiMap);
}


function formatMessage(string $text): string
{
    return nl2br(
        e(
            emojiShortcodes($text)
        )
    );
}

function jsonResponse(
    array $data,
    int $status = 200
): never {
    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT
    );

    exit;
}

function getConversationFiles(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $conversations = [];

    $files = glob(
        rtrim($directory, '/\\')
        . DIRECTORY_SEPARATOR
        . '*.php'
    );

    if ($files === false) {
        return [];
    }

    foreach ($files as $file) {
        $data = require $file;

        if (! is_array($data)) {
            continue;
        }

        $id = pathinfo(
            $file,
            PATHINFO_FILENAME
        );

        $name = trim(
            (string) ($data['name'] ?? '')
        );

        if ($name === '') {
            continue;
        }

        $conversations[$id] = [
            'id'          => $id,

            'name'        => $name,

            'avatar'      =>
            (string) (
                $data['avatar'] ?? '/assets/images/default-avatar.png'
            ),

            'description' =>
            (string) (
                $data['description'] ?? ''
            ),
        ];
    }

    uasort(
        $conversations,
        static function (
            array $a,
            array $b
        ): int {
            return strcasecmp(
                $a['name'],
                $b['name']
            );
        }
    );

    return $conversations;
}
