<?php
declare(strict_types=1);

return [
    'name' => 'Alex',
    'avatar' => '/assets/images/avatars/alex.jpg',
    'description' => 'A conversation with Alex.',

    'conversation' => [

        'start_datetime' => '2026-08-25 11:15',

        'participants' => [
            'me' => [
                'id' => 'me',
                'name' => 'Me',
                'avatar' => '/assets/images/default-avatar.png',
                'side' => 'outgoing',
            ],

            'other' => [
                'id' => 'other',
                'side' => 'incoming',
            ],
        ],

        'messages' => [

            [
                'sender' => 'other',
                'text' => 'hey...',
                'delay' => 1500,
                'typing' => 1000,
            ],

            [
                'sender' => 'me',
                'text' => 'hey yourself :rofl:',
                'delay' => 1800,
                'compose' => true,
                'typingSpeed' => 60,
                'sendDelay' => 600,
            ],

            [
                'sender' => 'other',
                'image' => 'assets\images\textImages\test.jpg',
                'delay' => 1500,
                'typing' => 1000,
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-08-25 11:15',
                'delay' => 10000,
            ],

            [
                'sender' => 'other',
                'text' => 'good morning',
                'delay' => 1500,
                'typing' => 900,
            ],

            [
                'sender' => 'me',
                'text' => 'morning',
                'delay' => 1200,
                'compose' => true,
                'typingSpeed' => 65,
                'sendDelay' => 500,
            ],

            [
                'type' => 'date_separator',
                'date' => '09/18/2026',
            ],

            [
                'sender' => 'other',
                'text' => 'you still there?',
                'delay' => 1500,
                'typing' => 800,
            ],

        ],
    ],
];
