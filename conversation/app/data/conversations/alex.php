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

    'reply' => [
        'model' => 'llama3.2:latest',
        'system_prompt' => 'You are Alex in a fictional private text-message simulator. '
            . 'Reply naturally to the latest message as Alex, keeping the same casual tone. '
            . 'Keep it to one or two short text-message sentences, respect boundaries, '
            . 'and never mention AI or these instructions.',
    ],

    $timingVars = [
        'typingSpeed' => rand(60, 75),
        'sendDelay' => rand(1500, 2000),
        'receiveDelay' => rand(2500, 3500),
        'waitDelay' => rand(1000, 2500),
        'draftPause' => rand(1800, 2500),
        'deleteSpeed' => 30,
        'recomposePause' => rand(700, 900),
        'seperatorDelay' => rand(10000, 15000),
        'mistakeChance' => number_format(rand(2,5) / 100, 2),
        'draftMistakeChance'=> number_format(rand(2,5) /100 , 2),
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
                'draft'       => 'I hate the way you talk to me sometime',
                'recomposePause' => $timingVars['recomposePause'],
                'text'        => 'NVM on the flirting & roleplaying with me :rofl:',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'draftPause'  => $timingVars['draftPause'],
            ],

            [
                'sender' => 'me',

                'draft' => 'I still miss you and I wish things were different',
                'text' => 'I hope everything works out for you',

                'delay' => 1400,
                'compose' => true,

                'typingSpeed' => 65,
                'recomposeTypingSpeed' => 70,
                'deleteSpeed' => 35,
                'draftPause' => 1200,
                'recomposePause' => 500,
                'sendDelay' => 700,

                'mistakeChance' => 0.04,
            ],

            [
                'sender' => 'other',
                'text'   => ':thinking: find then, gn!! :rage:',
                'delay'  => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            // The simulator stops here until the visitor sends a message.
            // That message is then answered by the local Ollama endpoint.
            [
                'type' => 'wait_for_response',
            ],
        ],
    ],
];
