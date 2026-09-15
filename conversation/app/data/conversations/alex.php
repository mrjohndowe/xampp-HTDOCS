<?php

declare (strict_types = 1);

return [
    /*
    |--------------------------------------------------------------------------
    | Conversation metadata
    |--------------------------------------------------------------------------
    */

    'name'         => 'Alex',

    'avatar'       => '/assets/images/avatars/alex.jpg',

    'description'  => 'A conversation about flirting and mixed signals.',

    /*
    |--------------------------------------------------------------------------
    | Conversation
    |--------------------------------------------------------------------------
    */

    'conversation' => [
        'participants' => [
            'me'    => [
                'id'     => 'me',
                'name'   => 'Me',
                'avatar' => '/assets/images/default-avatar.png',
                'side'   => 'outgoing',
            ],

            'other' => [
                'id'     => 'other',
                'name'   => 'Alex',
                'avatar' => '/assets/images/avatars/alex.jpg',
                'side'   => 'incoming',
            ],
        ],

        'messages'     => [
            [
                'sender' => 'other',
                'text'   => 'hey...',
                'delay'  => 2500,
                'typing' => 1800,
            ],

            [
                'sender' => 'other',
                'text'   => 'you used to not like being touched in an intimate way...',
                'delay'  => 4500,
                'typing' => 3000,
            ],

            [
                'sender' => 'other',
                'text'   => 'but I guess now you do...',
                'delay'  => 2800,
                'typing' => 1900,
            ],

            [
                'sender' => 'other',
                'text'   => 'just not with me, right? :thinking:',
                'delay'  => 1800,
                'typing' => 1700,
            ],

            [
                'sender'      => 'me',
                'text'        => 'NVM on the flirting & roleplaying with me :rofl:',
                'delay'       => 3500,
                'compose'     => true,
                'typingSpeed' => 65,
                'sendDelay'   => 1100,
            ],
        ],
    ],
];
