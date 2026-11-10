<?php

/**
 * Example questionnaire configuration.
 *
 * Copy this file to config.php and edit it, or use it as a reference. Only
 * "questions" is required; the other settings have sensible defaults.
 *
 * Global settings:
 *   title         (string)  page title and saved response title
 *   intro         (string)  introduction shown on the first section
 *   submit_label  (string)  label on the last section's submit button
 *   response_dir  (string)  folder for saved JSON; relative to this file
 *
 * Supported question types:
 *   text      single-line input, or a textarea when "multiline" is true
 *   choice    radio buttons, answered with one option
 *   multiple  checkboxes, answered with zero or more options
 *   confirm   yes/no radio buttons (answer stored as true/false)
 *
 * Fields for every question:
 *   id            (string, required)  unique key used when saving the answer
 *   prompt        (string, required)  text shown above the field
 *   type          (string)            one of the types above (default: text)
 *   required      (bool)              reject an empty answer while visible
 *   default       (mixed)             preselected value
 *   options       (string[])          required for choice and multiple
 *   pattern       (string)            optional PCRE regex for non-empty text
 *   pattern_error (string)            message shown when the pattern fails
 *   help          (string)            optional hint shown under the prompt
 *   multiline     (bool)              render text answers as a textarea
 *
 * Sections (optional):
 *   Each section is one page, in the order listed:
 *     id, title, description (optional), questions (question ids above)
 *   Questions not listed in a section are added to a final "More questions"
 *   page. If "sections" is omitted, each question gets its own page.
 *
 * Conditions (optional):
 *   'targetQuestion' => ['question' => 'sourceQuestion', 'equals' => value]
 *   The target is only shown and validated while the source matches. "equals"
 *   may be an array to accept multiple values. The source must appear earlier
 *   in the flow. Choice and multiple values compare against the option text
 *   (case-insensitive), confirm values compare against true/false, and text
 *   values compare after trimming whitespace.
 *
 *   When the source and target are in the same section, JavaScript reveals the
 *   follow-up immediately. Hidden questions are skipped by validation and are
 *   not saved, so "required" only applies while a question is visible.
 *
 * This example keeps its page flow in the "sections" and "conditions" keys
 * below. If neither key is present, index.php falls back to flow.php. When
 * either key is present here, flow.php is ignored for this configuration.
 *
 * Answers are saved as JSON in "response_dir". On Apache the folder is
 * protected by a .htaccess file that index.php creates on the first submission.
 */

return [
    'title' => 'Creator Profile Setup',
    'intro' => 'Tell us about your profile. Fields marked * are required, and you can go back to change an earlier answer.',
    'submit_label' => 'Save profile',
    'response_dir' => 'responses',

    'questions' => [
        [
            'id' => 'name',
            'prompt' => 'What should we call you?',
            'type' => 'text',
            'required' => true,
            'help' => 'This is shown as your display name.',
        ],
        [
            'id' => 'email',
            'prompt' => 'What is your email address?',
            'type' => 'text',
            'required' => true,
            'pattern' => '/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
            'pattern_error' => 'Please enter a valid email address.',
            'help' => 'We use this only for account messages.',
        ],
        [
            'id' => 'accountType',
            'prompt' => 'Which best describes you?',
            'type' => 'choice',
            'required' => true,
            'options' => ['Creator', 'Studio', 'Agency', 'Fan'],
            'default' => 'Creator',
        ],
        [
            'id' => 'studioName',
            'prompt' => 'What is your studio or agency called?',
            'type' => 'text',
            'required' => true,
            'help' => 'Only shown for Studio and Agency accounts.',
        ],
        [
            'id' => 'platforms',
            'prompt' => 'Where can people find your work?',
            'type' => 'multiple',
            'options' => ['Website', 'Social media', 'Video', 'Other'],
            'default' => ['Website'],
            'help' => 'Select all that apply.',
        ],
        [
            'id' => 'otherPlatform',
            'prompt' => 'Where else should we link to?',
            'type' => 'text',
            'help' => 'Only shown when Other is selected above.',
        ],
        [
            'id' => 'primaryFocus',
            'prompt' => 'What do you create most often?',
            'type' => 'choice',
            'required' => true,
            'options' => ['Photography', 'Video', 'Writing', 'Other'],
            'default' => 'Photography',
        ],
        [
            'id' => 'portfolio',
            'prompt' => 'Add a link to your best work.',
            'type' => 'text',
            'pattern' => '/^https?:\/\/\S+$/i',
            'pattern_error' => 'Please enter a full URL starting with http:// or https://.',
            'help' => 'Optional. Leave blank if you do not have a link yet.',
        ],
        [
            'id' => 'newsletter',
            'prompt' => 'Should we send occasional profile tips?',
            'type' => 'confirm',
            'default' => false,
            'help' => 'You can unsubscribe at any time.',
        ],
        [
            'id' => 'contactEmail',
            'prompt' => 'Which email should we use for those tips?',
            'type' => 'text',
            'required' => true,
            'pattern' => '/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
            'pattern_error' => 'Please enter a valid email address.',
            'help' => 'Only shown when profile tips are enabled.',
        ],
        [
            'id' => 'notes',
            'prompt' => 'Anything else we should know?',
            'type' => 'text',
            'multiline' => true,
        ],
    ],

    // FLOW.php


    'sections' => [
        [
            'id' => 'about',
            'title' => 'About you',
            'description' => 'The basics that appear at the top of your profile.',
            'questions' => ['name', 'email', 'accountType', 'studioName'],
        ],
        [
            'id' => 'work',
            'title' => 'Your work',
            'questions' => ['platforms', 'otherPlatform', 'primaryFocus', 'portfolio'],
        ],
        [
            'id' => 'preferences',
            'title' => 'Preferences',
            'description' => 'Choose how we should contact you.',
            'questions' => ['newsletter', 'contactEmail', 'notes'],
        ],
    ],

    'conditions' => [
        'studioName' => [
            'question' => 'accountType',
            'equals' => ['Studio', 'Agency'],
        ],
        'otherPlatform' => [
            'question' => 'platforms',
            'equals' => 'Other',
        ],
        'contactEmail' => [
            'question' => 'newsletter',
            'equals' => true,
        ],
    ],
];
