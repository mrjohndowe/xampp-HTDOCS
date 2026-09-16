<?php

declare(strict_types=1);

return [
    'app_name' => 'Text Message Simulator',
    'base_path' => '/conversation',
    'paths' => [
        'root' => dirname(__DIR__, 2),
        'storage' => dirname(__DIR__, 2) . '\storage',
        'uploads' => dirname(__DIR__, 2) . '/public/assets/images/avatars',
    ],

    'conversation' => [
        'typing_speed' => 55,
        'default_typing_duration' => 1600,
        'default_message_delay' => 1200,
        'default_send_delay' => 700,
        'scroll_duration' => 500,
    ],

    'uploads' => [
        'max_avatar_size' => 5 * 1024 * 1024,
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ],
    ],
];
