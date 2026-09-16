<?php

declare(strict_types=1);

return [
    'conversation' => [
        'title' => 'Messages',

        'participants' => [
            'me' => [
                'id' => 'me',
                'name' => 'Me',
                'avatar' =>'assets\images\avatars\default-avatar.png',
                'side' => 'outgoing'
            ],

            'other' => [
                'id' => 'other',
                'name' => 'Alex',
                'avatar' => 'assets\images\avatars\default-avatar.png',
                'side' => 'incoming'
            ],
        ],

        'messages' => [
            [
                'sender' => 'other',

                'text' => 'hey...',

                // Delay before they begin typing.
                'delay' => 2500,

                // Show the three-dot typing indicator.
                'typing' => 1800,
            ],
            [
                'sender' => 'me',
                'text' => 'hey yourself :rofl:',
                'delay' => rand(1800, 2500),
                'typing' => rand(1800, 2500),
            ],

            [
                'sender' => 'other',

                'text' => 'you used to not like being touched in an intimate way...',

                'delay' => 4500,
                'typing' => 3000,
            ],

            [
                'sender' => 'other',

                'text' => 'but I guess now you do...',

                'delay' => 2800,
                'typing' => 1900,
            ],

            [
                'sender' => 'other',

                'text' => 'just not with me, right?',

                'delay' => 1800,
                'typing' => 1700,
            ],

            [
                'sender' => 'me',

                'text' => 'NVM on the flirting & roleplaying with me 😌',

                // Wait before beginning our response.
                'delay' => 3500,

                // Type the message in the input first.
                'compose' => true,

                // Milliseconds per character.
                'typingSpeed' => 65,

                // Pause after typing before Send.
                'sendDelay' => 1100,
            ],

            [
                'sender' => 'me',

                'text' => 'I got the memo.',

                'delay' => 1800,
                'compose' => true,
                'typingSpeed' => 80,
                'sendDelay' => 900,
            ],
        ],
    ],
];
