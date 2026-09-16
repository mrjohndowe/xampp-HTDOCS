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

    'name'         => 'Kevin',

    'avatar' => 'public/assets/images/avatars/default-avatar.png',

    'description'  => 'I didnt do it, I swear!',


    $timingVars = [
        'typingSpeed' => rand(60, 75),
        'sendDelay' => rand(1500, 2000),
        'receiveDelay' => rand(2500, 3500),
        'waitDelay' => rand(5000, 7500),
        'seperatorDelay' => rand(10000, 15000),
        'mistakeChance' => number_format(rand(2, 15) / 1000, 2),
    ],
    /*
    |--------------------------------------------------------------------------
    | Conversation
    |--------------------------------------------------------------------------
    */

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
                'sender' => 'other',
                'text' => 'I got a question...',
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender' => 'me',
                'text' => 'whats your question...',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'sender' => 'other',
                'text' => 'Would you wanna have sex?',
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender' => 'other',
                'text' => 'and maybe give me a bj?',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'sender' => 'other',
                'text' => 'suck my balls to maybe?',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => false,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],
            [
                'sender' => 'other',
                'text' => "I'll start squirming lmaoooo",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => false,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'sender'      => 'me',
                'text'        => 'lol maybeeee',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'sender' => 'other',
                'text' => "where do you want me to cummmm",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => false,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'sender' => 'other',
                'text' => "wherever I want?",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => false,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-08-27 12:34',
                'delay' => $timingVars['seperatorDelay'],
            ],

            [
                'sender'      => 'me',
                'text'        => 'I want you to do so many things to meee',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-08-27 19:57',
                'delay' => $timingVars['seperatorDelay'],
            ],

            [
                'sender' => 'other',
                'text' => 'wyd',
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender'      => 'me',
                'text'        => 'being a crybaby about my phones',
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'sender' => 'other',
                'text' => "I'm sorry",
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender' => 'other',
                'text' => 'I had to work late to smh',
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender' => 'other',
                'text' => 'but do you have cnap?',
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender'      => 'me',
                'text'        => "I thought my paycheck was gonna be more but it wasn't even enough to cover one",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'sender' => 'other',
                'text' => "awww I'm sorry",
                'delay' => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-08-27 20:57',
                'delay' => $timingVars['seperatorDelay'],
            ],

            [
                'sender'      => 'me',
                'text'        => "Me too it really makes me depressed as fuck lmaoo",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'sender'      => 'me',
                'text'        => "Im off Sunday and get off early on saturday",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-08-27 21:07',
                'delay' => $timingVars['seperatorDelay'],
            ],

            [
                'sender' => 'other',
                'text'   => "mmm let's please have sex",
                'delay'  => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-08-27 21:19',
                'delay' => $timingVars['seperatorDelay'],
            ],

            [
                'sender'      => 'me',
                'text'        => "Whenever you want darling :kissing_heart:",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'sender' => 'other',
                'text'   => "do u have cnap?",
                'delay'  => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender'      => 'me',
                'text'        => "Yeah bbyyy",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'sender' => 'other',
                'text'   => "theKevin1.0",
                'delay'  => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'sender' => 'other',
                'text'   => "add me bby",
                'delay'  => $timingVars['waitDelay'],
                'typing' => $timingVars['typingSpeed'],
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-08-28 23:37',
                'delay' => $timingVars['seperatorDelay'],
            ],

            [
                'sender'      => 'me',
                'text'        => "Bbyyy",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-08-30 16:30',
                'delay' => $timingVars['seperatorDelay'],
            ],

            [
                'sender'      => 'me',
                'text'        => "Oh?",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-09-01 15:05',
                'delay' => $timingVars['seperatorDelay'],
            ],

            [
                'sender'      => 'other',
                'text'        => "Samantha",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => false,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
            ],

            [
                'sender'      => 'me',
                'text'        => "Kevin",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],

            [
                'type' => 'date_separator',
                'datetime' => '2026-09-02 10:05',
                'delay' => $timingVars['seperatorDelay'],
            ],

            [
                'sender'      => 'me',
                'text'        => "How are you just gonna message me my name and not say anything?",
                'delay'       => $timingVars['waitDelay'],
                'compose'     => true,
                'typingSpeed' => $timingVars['typingSpeed'],
                'sendDelay'   => $timingVars['sendDelay'],
                'mistakeChance' => $timingVars['mistakeChance'],
            ],
        ],
    ],
];
