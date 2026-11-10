<?php

/**
 * Questionnaire configuration.
 *
 * Edit the questions below; index.php reads this file on every request.
 *
 * Supported question types:
 *   text      single-line input, or textarea when "multiline" is true
 *   choice    radio buttons, answered with one option
 *   multiple  checkboxes, answered with zero or more options
 *   confirm   yes/no radio buttons (answer stored as true/false)
 *
 * Fields for every question:
 *   id            (string, required)  unique key used when saving the answer
 *   prompt        (string, required)  text shown above the field
 *   type          (string)            one of the types above (default: text)
 *   required      (bool)              reject an empty answer (default: false)
 *   default       (mixed)             preselected value (choice/multiple/confirm/text)
 *   options       (string[])          required for choice and multiple
 *   pattern       (string)            optional PCRE regex a text answer must match
 *   pattern_error (string)            message shown when the pattern does not match
 *   help          (string)            optional hint shown under the prompt
 *   multiline     (bool)              render text answers as a textarea
 *
 * Answers are saved as JSON in "response_dir". Relative paths are resolved
 * from this file's directory. On Apache the folder is protected by an
 * .htaccess file that index.php creates on the first submission.
 */



return [
    'title' => 'Scarlette Brohansen Site Questionaire',
    'intro' => 'Answer one question at a time. Fields marked * are required, and you can go back to change an answer at any point.',
    'submit_label' => 'Submit answers',
    'response_dir' => 'responses',

    'questions' => [
        [
            'id' => 'name',
            'prompt' => 'What name do you wanna use for your profile?',
            'type' => 'text',
            'required' => true,
        ],
        [
            'id' => 'email',
            'prompt' => 'What email do you wanna use for your profile?',
            'required' => true,
            'type' => 'text',
            'pattern' => '/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
            'pattern_error' => 'Please enter a valid email address.',
            'help' => 'Optional, but we would like to follow up with you.',
        ],
        [
            'id' => 'handle',
            'prompt' => 'What will you use for your handle',
            'type' => 'text',
            'multiline' => false,
            'required' => true,
            'help' => '@handle shown under your name.',
        ],
        [
            'id' => 'tagline',
            'prompt' => 'What will you use for your TagLine',
            'type' => 'text',
            'multiline' => false,
            'required' => true,
            'help' => 'One-line tagline displayed in the hero',
        ],
        [
            'id' => 'shortBio',
            'prompt' => 'What will you use for your short Bio',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'help' => 'Short intro shown in the hero (1–2 sentences)',
        ],
        [
            'id' => 'avatar',
            'prompt' => 'Need a Link for an Avatar Pic',
            'type' => 'text',
            'pattern' => '/^https?:\/\/\S+$/i',
            'multiline' => false,
            'required' => true,
            'help' => 'Square avatar used in the hero (at least 400×400 px)',
        ],
        [
            'id' => 'avatarAlt',
            'prompt' => 'What would you like it to say?',
            'type' => 'text',
            'multiline' => false,
            'required' => true,
            'help' => 'Portrait of Scarlett Vale in a black satin dress against a deep red backdrop',
        ],
        [
            'id' => 'portrait',
            'prompt' => 'Need a Link for an Portrait Pic',
            'type' => 'text',
            'pattern' => '/^https?:\/\/\S+$/i',
            'multiline' => false,
            'required' => true,
            'help' => 'Tall portrait used in the About section (around 720×1080 px).',
        ],
        [
            'id' => 'portraitAlt',
            'prompt' => 'What would you like it to say?',
            'type' => 'text',
            'multiline' => false,
            'required' => true,
            'help' => 'Scarlett Vale smiling in a black satin high-neck dress, warm rim lighting',
        ],
        [
            'id' => 'heroBackground',
            'prompt' => 'Would like like a video in the background or an image?',
            'type' => 'choice',
            'required' => true,
            'options' => ['Image', 'Video', 'IDC'],
            'default' => 'IDC',
            'help' => 'hero background video (muted, looping)',
        ],
        [
            'id' => 'heroVideo',
            'prompt' => 'If you selected `Video` Above then please provide me a link?',
            'type' => 'text',
            'pattern' => '/^https?:\/\/\S+$/i',
            'required' => false,
            'multiline' => false,
            'help' => 'Keep videos small (under ~5 MB)',
        ],
        [
            'id' => 'location',
            'prompt' => 'What location do you want to use?',
            'type' => 'text',
            'required' => true,
            'multiline' => false,
            'help' => 'General region only — never publish an exact address',
        ],
        [
            'id' => 'status',
            'prompt' => 'Small status line shown in the hero',
            'type' => 'text',
            'required' => true,
            'multiline' => false,
            'help' => 'New content every week',
        ],
        [
            'id' => 'highlights',
            'prompt' => 'Quick facts shown as a stats strip in the hero.',
            'type' => 'choice',
            'required' => true,
            'options' => ['1+ Years Creating', '500 Photos & Sets and more weekly', 'Weekly New Releases', 'All of the above'],
            'default' => 'All of the above',
            'help' => 'Remove lines to hide.',
        ],
        [
            'id' => 'categories',
            'prompt' => 'What do you create?',
            'type' => 'text',
            'multiline' => true,
            'help' => 'Create a items you want to sell or selling on other platforms for example;<br> icon: sparkles, title: Exclusive Content, description: Sets and concepts you wont find anywhere elsem released only to members, tag: Members<br>
            Choices for each section are:<br>
                icons:
                    sparkles
                    camera
                    film
                    image
                    crown
                    star
                    heart
                    gem
                    flame
                    gift
                    clapperboard
                    calendar
                    users
                    zap
                    lock
                    eye-off
                    clock
                    message-circle
                    badge-check
                title:
                    Your Choice of titles
                description:
                    provide a clear description for the section
                tag: 
                    Members
                    Free
                    Premium
                    Limited
                    Free & Members'
        ],
        [
            'id' => 'gallery',
            ''
        ]
        
       
    ],
];
