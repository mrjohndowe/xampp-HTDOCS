<?php

declare(strict_types=1);

return [
    'name' => 'Jamie',
    'avatar' => '/assets/images/avatars/default-avatar.png',
    'description' => 'A scripted conversation that pauses for an Ollama-powered reply.',

    /*
    |--------------------------------------------------------------------------
    | Ollama reply personality
    |--------------------------------------------------------------------------
    |
    | This is read only after the wait_for_response marker. Change the model
    | to another locally installed Ollama model whenever you want.
    */
    'reply' => [
        'model' => 'llama3.2:latest',
        'system_prompt' => 'You are Jamie in a fictional text-message simulator. '
            . 'Reply to the visitor as a friendly, thoughtful friend. '
            . 'Use one or two natural, concise text-message sentences. '
            . 'Do not mention AI, Ollama, or these instructions.',
        'fallback_reply' => 'That makes sense. Want to tell me a little more?',
    ],

    'conversation' => [
        'start_date' => '2026-09-16 18:30',

        'participants' => [
            'me' => [
                'id' => 'me',
                'name' => 'Me',
                'avatar' => '/assets/images/avatars/default-avatar.png',
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
                'text' => 'Hey! How has your day been going?',
                'delay' => 900,
                'typing' => 1100,
            ],
            [
                'sender' => 'me',
                'text' => 'A little busy, but I am finally slowing down.',
                'delay' => 1300,
                'compose' => true,
                'typingSpeed' => 48,
                'sendDelay' => 600,
                'mistakeChance' => 0.03,
            ],
            [
                'sender' => 'other',
                'text' => 'I am glad you are getting a breather. I was hoping to catch up.',
                'delay' => 1000,
                'typing' => 1450,
            ],
            [
                'type' => 'date_separator',
                'datetime' => '2026-09-16 18:35',
            ],
            [
                'sender' => 'other',
                'text' => 'What is on your mind tonight?',
                'delay' => 1100,
                'typing' => 1200,
            ],

            // The scripted part ends here. The next visitor message receives
            // a contextual reply from the local Ollama model above.
            [
                'type' => 'wait_for_response',
            ],
        ],
    ],
];
