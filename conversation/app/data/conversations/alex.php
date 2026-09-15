<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Conversation metadata
    |--------------------------------------------------------------------------
    */

    'name'         => 'Alex',

    'avatar' => '/assets/images/avatars/alex.jpg',

    'description'  => 'A conversation about flirting and mixed signals.',

    /*
    |--------------------------------------------------------------------------
    | Conversation
    |--------------------------------------------------------------------------
    */

    'conversation' => [
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
                'delay' => 2500,
                'typing' => 1800,
            ],

            [
                'sender' => 'me',
                'text' => 'hey yourself :rofl:',
                'delay'       => rand(35600, 4000),
                'compose'     => true,
                'typingSpeed' => rand(60, 120),
                'sendDelay'   => rand(1000, 1500),
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
                'sender'      => 'me',
                'text'        => 'yea so :rolling_eyes:',
                'delay'       => rand(35600, 4000),
                'compose'     => true,
                'typingSpeed' => rand(60, 120),
                'sendDelay'   => rand(1000, 1500),
            ],

            [
                'sender' => 'other',
                'text' => 'just not with me, right? :thinking:',
                'delay' => 1800,
                'typing' => 1700,
            ],

            [
                'sender'      => 'me',
                'text'        => 'You know what I am tire of this shit :laughing:',
                'delay'       => rand(35600, 4000),
                'compose'     => true,
                'typingSpeed' => rand(60, 120),
                'sendDelay'   => rand(1000, 1500),
            ],

            [
                'sender'      => 'me',
                'text'        => 'NVM on the flirting & roleplaying with me :rofl:',
                'delay'       => rand(35600, 4000),
                'compose'     => true,
                'typingSpeed' => rand(60, 120),
                'sendDelay'   => rand(1000, 1500),
            ],

            [
                'sender' => 'other',
                'text'   => ':thinking: find then, gn!! :angry:',
                'delay'  => 1800,
                'typing' => 1700,
            ],
        ],
    ],
];
