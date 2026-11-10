<?php

declare(strict_types=1);

/**
 * Page flow: sections and conditional questions.
 *
 * config.php still owns the questions. This file only decides how they are
 * grouped into pages and when a follow-up question is shown.
 *
 * sections
 *   One entry per wizard page, in page order.
 *   id / title / description / questions (question ids from config.php).
 *   Questions not listed here are added to a final "More questions" page.
 *
 * conditions
 *   target question id => ['question' => 'source id', 'equals' => 'trigger'].
 *   "equals" can be an array to allow more than one value.
 *   Choice and multiple questions compare against option text, confirm against
 *   true/false, and text against the answer. The source must come earlier.
 */

return [
    'sections' => [
        [
            'id' => 'site',
            'title' => 'Site settings',
            'description' => 'The title, description and public address used by the site.',
            'questions' => ['siteTitle', 'siteDescription', 'siteUrl', 'siteLanguage', 'siteShareImage'],
        ],
        [
            'id' => 'creator',
            'title' => 'Creator profile',
            'description' => 'The public identity, images and hero content.',
            'questions' => [
                'name', 'handle', 'tagline', 'badge', 'shortBio',
                'avatar', 'avatarAlt', 'portrait', 'portraitAlt',
                'heroMedia', 'heroBackground', 'heroVideo',
                'location', 'status', 'highlights',
            ],
        ],
        [
            'id' => 'about',
            'title' => 'About section',
            'description' => 'The longer introduction, personality, interests and quote.',
            'questions' => ['aboutEyebrow', 'aboutHeading', 'paragraphs', 'personality', 'interests', 'quote'],
        ],
        [
            'id' => 'categories',
            'title' => 'Content categories',
            'description' => 'The categories displayed in the “What I create” section.',
            'questions' => ['categoriesEyebrow', 'categoriesHeading', 'categoriesIntro', 'categories'],
        ],
        [
            'id' => 'gallery',
            'title' => 'Preview gallery',
            'description' => 'The free promotional items and the members-only card.',
            'questions' => [
                'galleryEyebrow', 'galleryHeading', 'galleryIntro', 'galleryNotice',
                'galleryShowFilters', 'galleryAllFilterLabel', 'galleryPreviewBadge',
                'gallery', 'membersCard',
            ],
        ],
        [
            'id' => 'platforms',
            'title' => 'Subscription platforms',
            'description' => 'The official paid platforms and their membership details.',
            'questions' => ['platformsEyebrow', 'platformsHeading', 'platformsIntro', 'platforms'],
        ],
        [
            'id' => 'socials',
            'title' => 'Social media',
            'description' => 'The public social profiles shown on the site.',
            'questions' => ['socialsEyebrow', 'socialsHeading', 'socialsIntro', 'socials'],
        ],
        [
            'id' => 'contact',
            'title' => 'Contact',
            'description' => 'Business email, collaborations and optional management details.',
            'questions' => [
                'contactEyebrow', 'contactHeading', 'contactIntro', 'email', 'collabEmail',
                'collabNote', 'management', 'formUrl', 'formLabel', 'responseTime',
            ],
        ],
        [
            'id' => 'theme',
            'title' => 'Theme',
            'description' => 'The default colour mode and accent colours.',
            'questions' => ['themeDefaultMode', 'themeAllowToggle', 'themeAccent', 'themeAccent2'],
        ],
        [
            'id' => 'age-gate',
            'title' => 'Age gate',
            'description' => 'The 18+ notice, buttons and how long the confirmation is remembered.',
            'questions' => [
                'ageGateEnabled', 'ageGateMinimumAge', 'ageGateTitle', 'ageGateMessage',
                'ageGateEnterLabel', 'ageGateExitLabel', 'ageGateExitUrl',
                'ageGateStorage', 'ageGateRememberDays', 'ageGateDisclaimer',
            ],
        ],
        [
            'id' => 'footer',
            'title' => 'Footer',
            'description' => 'Copyright, tagline and adult-content notices.',
            'questions' => ['copyrightYear', 'copyrightName', 'footerTagline', 'footerDisclaimer', 'footerAgeNotice'],
        ],
        [
            'id' => 'legal',
            'title' => 'Legal pages',
            'description' => 'The privacy notice and terms shown from the footer.',
            'questions' => ['privacyTitle', 'privacyUpdated', 'privacySections', 'termsTitle', 'termsUpdated', 'termsSections'],
        ],
    ],

    'conditions' => [
        'heroBackground' => [
            'question' => 'heroMedia',
            'equals' => 'Image',
        ],
        'heroVideo' => [
            'question' => 'heroMedia',
            'equals' => 'Video',
        ],
    ],
];
