<?php

return [
    'participants' => [
        'me' => [
            'name'   => 'Me',
            'avatar' => '/assets/images/default-avatar.png',
            'side'   => 'outgoing',
        ],

        'other' => [
            'name'   => 'Alex',
            'avatar' => '/uploads/avatars/alex.jpg',
            'side'   => 'incoming',
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
            'sender' => 'other',
            'text' => 'you used to not like being touched in an intimate way...',
            'delay' => 5000,
            'typing' => 3200,
        ],
        [
            'sender' => 'other',
            'text' => 'but I guess now you do...',
            'delay' => 4500,
            'typing' => 2400,
        ],
        [
            'sender' => 'other',
            'text' => 'just not with me, right?',
            'delay' => 2200,
            'typing' => 1800,
        ],
        [
            'sender' => 'me',
            'text' => 'NVM on the flirting & roleplaying with me 😌',
            'delay' => 3500,

            // Put this text into the composer first.
            'compose' => true,

            // Simulated typing speed in milliseconds per character.
            'typingSpeed' => 55,

            // Wait after finishing typing before sending.
            'sendDelay' => 1000,
        ],
    ],
];
