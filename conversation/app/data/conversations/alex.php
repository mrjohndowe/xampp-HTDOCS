<?php

declare(strict_types=1);
// $typingSpeed = rand(60, 120);
// $timingVars['sendDelay'] = rand(3500, 4500);
// $receiveDelay = rand(2500, 3500);
// $timingVars['waitDelay'] = rand(1000, 2500);

return [

    //SPEED VARIABLES


    /*
    |--------------------------------------------------------------------------
    | Conversation metadata
    |--------------------------------------------------------------------------
    */

    'name'         => 'Alex',

    'avatar' => 'public/assets/images/avatars/alex.jpg',

    'description'  => 'A conversation about flirting and mixed signals.',

    $timingVars = [
        'typingSpeed' => rand(60, 75),
        'sendDelay' => rand(1500, 2000),
        'receiveDelay' => rand(2500, 3500),
        'waitDelay' => rand(1000, 2500),
    ],
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
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender' => 'me',
                'text' => 'hey yourself :rofl:',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'sender' => 'me',
                'image' => '/assets/images/textImages/test.jpg',
                'caption' => 'look at this :rofl:',
                'delay' => $timingVars['waitDelay'],
                'compose' => true,
                'sendDelay' => $timingVars['sendDelay'],


            ],

            [
                'sender' => 'other',
                'text' => 'you used to not like being touched in an intimate way...',
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender' => 'other',
                'text' => 'but I guess now you do?',
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender'      => 'me',
                'text'        => 'yea so :rolling_eyes:',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'sender' => 'other',
                'text' => 'just not with me, right? :thinking:',
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender'      => 'me',
                'text'        => 'You know what I am tired of this shit :laughing:',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'sender'      => 'me',
                'text'        => 'NVM on the flirting & roleplaying with me :rofl:',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'sender' => 'other',
                'text'   => ':thinking: find then, gn!! :rage:',
                'delay'  => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],
        ],
    ],
];
