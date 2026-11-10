<?php

declare(strict_types=1);

/**
 * Questionnaire configuration.
 *
 * Edit the questions below; index.php reads this file on every request.
 * Every optional "config_path" below is the matching key in the site's
 * window.SITE_CONFIG object, so a saved answer can be copied into that file.
 *
 * Supported question types:
 *   text      single-line input, or textarea when "multiline" is true
 *   number    numeric input, saved as an integer or decimal
 *   choice    radio buttons, answered with one option
 *   multiple  checkboxes, answered with zero or more options
 *   confirm   yes/no radio buttons (answer stored as true/false)
 *   list      textarea, one string per line, saved as an array
 *   record    one guided line, saved as an object
 *   records   one guided line per item, saved as an array of objects
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
 *   config_path   (string)            optional target key in SITE_CONFIG
 *
 * Guided record fields (record and records questions):
 *   Each field has a label, a type (text, boolean, number or list), and
 *   optional required, separator and default settings.
 *
 * Guided syntax:
 *   Separate an item's fields with " | " and write each one as field: value.
 *       enabled: true | name: OnlyFans | url: https://example.com/
 *   For a list field, separate its values with a semicolon:
 *       perks: Full sets; Direct messages; Weekly releases
 *   A list question uses one plain item per line. A records question uses
 *   one guided line per item.
 *
 * Answers are saved as JSON in "response_dir". Relative paths are resolved
 * from this file's directory. On Apache the folder is protected by an
 * .htaccess file that index.php creates on the first submission.
 */

$assetPattern = '#^(?:https?://\S+|[A-Za-z0-9][A-Za-z0-9._/-]*\.[A-Za-z0-9]+)$#i';
$urlPattern = '#^https?://\S+$#i';
$siteUrlPattern = '#^https?://\S+/$#i';

return [
    'title' => 'Scarlette Brohansen Site Questionnaire',
    'intro' => 'Answer the sections below. Fields marked * are required, and you can go back to change an earlier answer.',
    'submit_label' => 'Submit answers',
    'response_dir' => 'responses',

    'questions' => [
        // -------------------------------------------------------------------
        // 1. SITE
        // -------------------------------------------------------------------
        [
            'id' => 'siteTitle',
            'prompt' => 'What is the website title?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'site.title',
            'help' => 'Shown in the browser tab and search results.',
        ],
        [
            'id' => 'siteDescription',
            'prompt' => 'Write the short website description.',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'config_path' => 'site.description',
            'help' => 'Used by search engines and link previews. Keep it under about 160 characters.',
        ],
        [
            'id' => 'siteUrl',
            'prompt' => 'What will the final public website address be?',
            'type' => 'text',
            'required' => true,
            'pattern' => $siteUrlPattern,
            'pattern_error' => 'Use a full http:// or https:// address with a trailing slash.',
            'config_path' => 'site.url',
            'help' => 'Include the trailing slash, for example https://www.example.com/.',
        ],
        [
            'id' => 'siteLanguage',
            'prompt' => 'What language code should the website use?',
            'type' => 'text',
            'required' => true,
            'default' => 'en',
            'config_path' => 'site.language',
            'help' => 'Use a two-letter language code such as en, de or fr.',
        ],
        [
            'id' => 'siteShareImage',
            'prompt' => 'What image should appear when the site is shared?',
            'type' => 'text',
            'config_path' => 'site.shareImage',
            'pattern' => $assetPattern,
            'pattern_error' => 'Use a relative assets/... path or a full http:// or https:// image URL.',
            'help' => 'A 1200x630 image works best. You can leave this blank to use the site default.',
        ],

        // -------------------------------------------------------------------
        // 2. CREATOR
        // -------------------------------------------------------------------
        [
            'id' => 'name',
            'prompt' => 'What name do you want to use for your profile?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.displayName',
            'help' => 'This becomes the public display name.',
        ],
        [
            'id' => 'handle',
            'prompt' => 'What public handle should be shown under your name?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.handle',
            'help' => 'Include the @ symbol. The handle is shown under your display name.',
        ],
        [
            'id' => 'tagline',
            'prompt' => 'What one-line tagline should appear in the hero?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.tagline',
            'help' => 'A short phrase such as “Elegance after dark.”',
        ],
        [
            'id' => 'badge',
            'prompt' => 'What small badge should appear above your name?',
            'type' => 'text',
            'config_path' => 'creator.badge',
            'help' => 'Optional. Leave blank to hide it.',
        ],
        [
            'id' => 'shortBio',
            'prompt' => 'Write your short hero introduction.',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'config_path' => 'creator.shortBio',
            'help' => 'One or two sentences shown in the hero.',
        ],
        [
            'id' => 'avatar',
            'prompt' => 'What image should be used for your avatar?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.avatar',
            'pattern' => $assetPattern,
            'pattern_error' => 'Use a relative assets/... path or a full http:// or https:// image URL.',
            'help' => 'A square image at least 400x400 pixels works best.',
        ],
        [
            'id' => 'avatarAlt',
            'prompt' => 'Describe the avatar image for screen readers.',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.avatarAlt',
            'help' => 'Describe what is visible in the avatar image.',
        ],
        [
            'id' => 'portrait',
            'prompt' => 'What image should be used for the About portrait?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.portrait',
            'pattern' => $assetPattern,
            'pattern_error' => 'Use a relative assets/... path or a full http:// or https:// image URL.',
            'help' => 'A tall image around 720x1080 pixels works best.',
        ],
        [
            'id' => 'portraitAlt',
            'prompt' => 'Describe the portrait image for screen readers.',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.portraitAlt',
            'help' => 'Describe what is visible in the portrait image.',
        ],
        [
            'id' => 'heroMedia',
            'prompt' => 'What should the hero background use?',
            'type' => 'choice',
            'required' => true,
            'options' => ['Image', 'Video', 'No background'],
            'default' => 'Image',
            'help' => 'This chooses which background field to ask for. It is not written to the site config.',
        ],
        [
            'id' => 'heroBackground',
            'prompt' => 'What image should be used for the hero background?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.heroBackground',
            'pattern' => $assetPattern,
            'pattern_error' => 'Use a relative assets/... path or a full http:// or https:// image URL.',
            'help' => 'Shown when Image is selected above.',
        ],
        [
            'id' => 'heroVideo',
            'prompt' => 'What video should be used for the hero background?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.heroVideo',
            'pattern' => $assetPattern,
            'pattern_error' => 'Use a relative assets/... path or a full http:// or https:// video URL.',
            'help' => 'Shown when Video is selected above. Use a small muted looping video under about 5 MB.',
        ],
        [
            'id' => 'location',
            'prompt' => 'What general location should be shown?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'creator.location',
            'help' => 'Use a general region only. Never publish an exact address.',
        ],
        [
            'id' => 'status',
            'prompt' => 'What small status line should appear in the hero?',
            'type' => 'text',
            'config_path' => 'creator.status',
            'help' => 'Optional. Leave blank to hide it.',
        ],
        [
            'id' => 'highlights',
            'prompt' => 'Add the quick facts shown in the hero stats strip.',
            'type' => 'records',
            'config_path' => 'creator.highlights',
            'fields' => [
                'value' => ['type' => 'text', 'required' => true],
                'label' => ['type' => 'text', 'required' => true],
            ],
            'help' => 'One stat per line. For example: value: 5+ | label: Years creating. Leave blank to hide the stats strip.',
        ],

        // -------------------------------------------------------------------
        // 3. ABOUT
        // -------------------------------------------------------------------
        [
            'id' => 'aboutEyebrow',
            'prompt' => 'What small label should introduce the About section?',
            'type' => 'text',
            'default' => 'About me',
            'config_path' => 'about.eyebrow',
        ],
        [
            'id' => 'aboutHeading',
            'prompt' => 'What heading should the About section use?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'about.heading',
        ],
        [
            'id' => 'paragraphs',
            'prompt' => 'Write the About section paragraphs.',
            'type' => 'list',
            'required' => true,
            'config_path' => 'about.paragraphs',
            'help' => 'Enter one paragraph per line.',
        ],
        [
            'id' => 'personality',
            'prompt' => 'Which personality words describe you?',
            'type' => 'list',
            'config_path' => 'about.personality',
            'help' => 'Enter one word or short phrase per line. Leave blank to hide this list.',
        ],
        [
            'id' => 'interests',
            'prompt' => 'Which interests and content themes should be shown?',
            'type' => 'list',
            'config_path' => 'about.interests',
            'help' => 'Enter one interest per line. Leave blank to hide this list.',
        ],
        [
            'id' => 'quote',
            'prompt' => 'What personal quote should appear in the About section?',
            'type' => 'text',
            'multiline' => true,
            'config_path' => 'about.quote',
            'help' => 'Optional. Leave blank to hide the quote.',
        ],

        // -------------------------------------------------------------------
        // 4. CATEGORIES
        // -------------------------------------------------------------------
        [
            'id' => 'categoriesEyebrow',
            'prompt' => 'What small label should introduce the content categories?',
            'type' => 'text',
            'default' => 'What I create',
            'config_path' => 'categories.eyebrow',
        ],
        [
            'id' => 'categoriesHeading',
            'prompt' => 'What heading should the content categories use?',
            'type' => 'text',
            'default' => 'Content categories',
            'required' => true,
            'config_path' => 'categories.heading',
        ],
        [
            'id' => 'categoriesIntro',
            'prompt' => 'Write the introduction for the content categories.',
            'type' => 'text',
            'required' => true,
            'config_path' => 'categories.intro',
        ],
        [
            'id' => 'categories',
            'prompt' => 'Add the content categories.',
            'type' => 'records',
            'required' => true,
            'config_path' => 'categories.items',
            'fields' => [
                'icon' => ['type' => 'text', 'required' => true],
                'title' => ['type' => 'text', 'required' => true],
                'description' => ['type' => 'text', 'required' => true],
                'tag' => ['type' => 'text'],
            ],
            'help' => 'One category per line. Available icons: sparkles, camera, film, image, crown, star, heart, gem, flame, gift, clapperboard, calendar, users, zap, lock, eye-off, clock, message-circle and badge-check.',
        ],

        // -------------------------------------------------------------------
        // 5. GALLERY
        // -------------------------------------------------------------------
        [
            'id' => 'galleryEyebrow',
            'prompt' => 'What small label should introduce the gallery?',
            'type' => 'text',
            'default' => 'Free previews',
            'config_path' => 'gallery.eyebrow',
        ],
        [
            'id' => 'galleryHeading',
            'prompt' => 'What heading should the gallery use?',
            'type' => 'text',
            'default' => 'Preview gallery',
            'required' => true,
            'config_path' => 'gallery.heading',
        ],
        [
            'id' => 'galleryIntro',
            'prompt' => 'Write the introduction for the gallery.',
            'type' => 'text',
            'required' => true,
            'config_path' => 'gallery.intro',
        ],
        [
            'id' => 'galleryNotice',
            'prompt' => 'Write the free-preview notice shown above the gallery.',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'config_path' => 'gallery.notice',
        ],
        [
            'id' => 'galleryShowFilters',
            'prompt' => 'Should visitors be able to filter the gallery by category?',
            'type' => 'confirm',
            'default' => true,
            'config_path' => 'gallery.showFilters',
        ],
        [
            'id' => 'galleryAllFilterLabel',
            'prompt' => 'What should the “All” filter button say?',
            'type' => 'text',
            'default' => 'All',
            'required' => true,
            'config_path' => 'gallery.allFilterLabel',
        ],
        [
            'id' => 'galleryPreviewBadge',
            'prompt' => 'What badge should appear on each free preview?',
            'type' => 'text',
            'default' => 'Free preview',
            'required' => true,
            'config_path' => 'gallery.previewBadge',
        ],
        [
            'id' => 'gallery',
            'prompt' => 'Add the free preview gallery items.',
            'type' => 'records',
            'required' => true,
            'config_path' => 'gallery.items',
            'fields' => [
                'type' => ['type' => 'text', 'required' => true],
                'src' => ['type' => 'text', 'required' => true],
                'thumb' => ['type' => 'text'],
                'poster' => ['type' => 'text'],
                'alt' => ['type' => 'text', 'required' => true],
                'caption' => ['type' => 'text'],
                'category' => ['type' => 'text', 'required' => true],
                'width' => ['type' => 'number', 'required' => true],
                'height' => ['type' => 'number', 'required' => true],
            ],
            'help' => 'One media item per line. Set type to image or video. Separate the fields with " | ". Thumb and poster are optional.',
        ],
        [
            'id' => 'membersCard',
            'prompt' => 'Complete the members-only card shown after the gallery.',
            'type' => 'record',
            'required' => true,
            'config_path' => 'gallery.membersCard',
            'default' => [
                'enabled' => true,
                'title' => 'Full sets are members-only',
                'text' => 'Hundreds more photos and videos are waiting on my official subscription pages.',
                'buttonLabel' => 'See official platforms',
                'buttonLink' => '#platforms',
            ],
            'fields' => [
                'enabled' => ['type' => 'boolean', 'default' => true],
                'title' => ['type' => 'text', 'required' => true],
                'text' => ['type' => 'text', 'required' => true],
                'buttonLabel' => ['type' => 'text', 'required' => true],
                'buttonLink' => ['type' => 'text', 'required' => true],
            ],
            'help' => 'Enter this as one line.',
        ],

        // -------------------------------------------------------------------
        // 6. PLATFORMS
        // -------------------------------------------------------------------
        [
            'id' => 'platformsEyebrow',
            'prompt' => 'What small label should introduce the subscription platforms?',
            'type' => 'text',
            'default' => 'Official platforms',
            'config_path' => 'platforms.eyebrow',
        ],
        [
            'id' => 'platformsHeading',
            'prompt' => 'What heading should the subscription platforms use?',
            'type' => 'text',
            'default' => 'Subscribe & support',
            'required' => true,
            'config_path' => 'platforms.heading',
        ],
        [
            'id' => 'platformsIntro',
            'prompt' => 'Write the introduction for the subscription platforms.',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'config_path' => 'platforms.intro',
        ],
        [
            'id' => 'platforms',
            'prompt' => 'Add the subscription and membership platforms.',
            'type' => 'records',
            'required' => true,
            'config_path' => 'platforms.items',
            'fields' => [
                'enabled' => ['type' => 'boolean', 'default' => true],
                'featured' => ['type' => 'boolean', 'default' => false],
                'name' => ['type' => 'text', 'required' => true],
                'icon' => ['type' => 'text'],
                'url' => ['type' => 'text', 'required' => true],
                'description' => ['type' => 'text', 'required' => true],
                'price' => ['type' => 'text'],
                'period' => ['type' => 'text'],
                'priceNote' => ['type' => 'text'],
                'perks' => ['type' => 'list', 'separator' => ';'],
                'buttonLabel' => ['type' => 'text', 'required' => true],
            ],
            'help' => 'One platform per line. Set enabled and featured to true or false. Separate perks with semicolons.',
        ],

        // -------------------------------------------------------------------
        // 7. SOCIALS
        // -------------------------------------------------------------------
        [
            'id' => 'socialsEyebrow',
            'prompt' => 'What small label should introduce the social links?',
            'type' => 'text',
            'default' => 'Follow along',
            'config_path' => 'socials.eyebrow',
        ],
        [
            'id' => 'socialsHeading',
            'prompt' => 'What heading should the social links use?',
            'type' => 'text',
            'default' => 'Social media',
            'required' => true,
            'config_path' => 'socials.heading',
        ],
        [
            'id' => 'socialsIntro',
            'prompt' => 'Write the introduction for the social links.',
            'type' => 'text',
            'required' => true,
            'config_path' => 'socials.intro',
        ],
        [
            'id' => 'socials',
            'prompt' => 'Add the social media links.',
            'type' => 'records',
            'required' => true,
            'config_path' => 'socials.items',
            'fields' => [
                'enabled' => ['type' => 'boolean', 'default' => true],
                'network' => ['type' => 'text', 'required' => true],
                'label' => ['type' => 'text', 'required' => true],
                'handle' => ['type' => 'text'],
                'url' => ['type' => 'text', 'required' => true],
            ],
            'help' => 'One social link per line. Set enabled to true or false. Network examples: instagram, x, reddit, tiktok, youtube, telegram.',
        ],

        // -------------------------------------------------------------------
        // 8. CONTACT
        // -------------------------------------------------------------------
        [
            'id' => 'contactEyebrow',
            'prompt' => 'What small label should introduce the contact section?',
            'type' => 'text',
            'default' => 'Business',
            'config_path' => 'contact.eyebrow',
        ],
        [
            'id' => 'contactHeading',
            'prompt' => 'What heading should the contact section use?',
            'type' => 'text',
            'default' => 'Contact & collaborations',
            'required' => true,
            'config_path' => 'contact.heading',
        ],
        [
            'id' => 'contactIntro',
            'prompt' => 'Write the introduction for the contact section.',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'config_path' => 'contact.intro',
        ],
        [
            'id' => 'email',
            'prompt' => 'What business email should be listed?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'contact.email',
            'pattern' => '/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
            'pattern_error' => 'Please enter a valid email address.',
        ],
        [
            'id' => 'collabEmail',
            'prompt' => 'What collaboration email should be listed?',
            'type' => 'text',
            'config_path' => 'contact.collabEmail',
            'pattern' => '/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
            'pattern_error' => 'Please enter a valid email address.',
            'help' => 'Optional. Leave blank to use the business email above.',
        ],
        [
            'id' => 'collabNote',
            'prompt' => 'What note should accompany collaboration enquiries?',
            'type' => 'text',
            'multiline' => true,
            'config_path' => 'contact.collabNote',
            'help' => 'Optional.',
        ],
        [
            'id' => 'management',
            'prompt' => 'Add management contact details, if you use them.',
            'type' => 'record',
            'config_path' => 'contact.management',
            'fields' => [
                'name' => ['type' => 'text', 'required' => true],
                'company' => ['type' => 'text', 'required' => true],
                'email' => ['type' => 'text', 'required' => true],
            ],
            'help' => 'Enter this as one line, for example: name: Jordan Hale | company: Velvet Talent | email: management@example.com. Leave blank if you have no management.',
        ],
        [
            'id' => 'formUrl',
            'prompt' => 'What external booking form URL should be offered?',
            'type' => 'text',
            'config_path' => 'contact.formUrl',
            'pattern' => $urlPattern,
            'pattern_error' => 'Please enter a full http:// or https:// address.',
            'help' => 'Optional. Leave blank to hide the booking button.',
        ],
        [
            'id' => 'formLabel',
            'prompt' => 'What should the booking form button say?',
            'type' => 'text',
            'default' => 'Open booking form',
            'config_path' => 'contact.formLabel',
        ],
        [
            'id' => 'responseTime',
            'prompt' => 'What response time note should be shown?',
            'type' => 'text',
            'config_path' => 'contact.responseTime',
            'help' => 'Optional, for example: I usually reply within 2–3 business days.',
        ],

        // -------------------------------------------------------------------
        // 9. THEME
        // -------------------------------------------------------------------
        [
            'id' => 'themeDefaultMode',
            'prompt' => 'Which colour mode should be the default?',
            'type' => 'choice',
            'required' => true,
            'options' => ['dark', 'light'],
            'default' => 'dark',
            'config_path' => 'theme.defaultMode',
        ],
        [
            'id' => 'themeAllowToggle',
            'prompt' => 'Should visitors be allowed to switch between light and dark mode?',
            'type' => 'confirm',
            'default' => true,
            'config_path' => 'theme.allowToggle',
        ],
        [
            'id' => 'themeAccent',
            'prompt' => 'What is the main accent colour?',
            'type' => 'text',
            'default' => '#e3a587',
            'required' => true,
            'config_path' => 'theme.accent',
            'help' => 'Use a CSS colour such as #e3a587.',
        ],
        [
            'id' => 'themeAccent2',
            'prompt' => 'What is the secondary gradient accent colour?',
            'type' => 'text',
            'default' => '#c2456b',
            'required' => true,
            'config_path' => 'theme.accent2',
            'help' => 'Use a CSS colour such as #c2456b.',
        ],

        // -------------------------------------------------------------------
        // 10. AGE GATE
        // -------------------------------------------------------------------
        [
            'id' => 'ageGateEnabled',
            'prompt' => 'Should the age gate be enabled?',
            'type' => 'confirm',
            'default' => true,
            'config_path' => 'ageGate.enabled',
        ],
        [
            'id' => 'ageGateMinimumAge',
            'prompt' => 'What minimum age should be stated?',
            'type' => 'number',
            'required' => true,
            'default' => 18,
            'config_path' => 'ageGate.minimumAge',
        ],
        [
            'id' => 'ageGateTitle',
            'prompt' => 'What heading should the age gate use?',
            'type' => 'text',
            'required' => true,
            'default' => 'Adults only (18+)',
            'config_path' => 'ageGate.title',
        ],
        [
            'id' => 'ageGateMessage',
            'prompt' => 'Write the age gate message.',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'config_path' => 'ageGate.message',
        ],
        [
            'id' => 'ageGateEnterLabel',
            'prompt' => 'What should the age gate enter button say?',
            'type' => 'text',
            'required' => true,
            'default' => 'I am 18 or older — Enter',
            'config_path' => 'ageGate.enterLabel',
        ],
        [
            'id' => 'ageGateExitLabel',
            'prompt' => 'What should the age gate exit button say?',
            'type' => 'text',
            'required' => true,
            'default' => 'Exit',
            'config_path' => 'ageGate.exitLabel',
        ],
        [
            'id' => 'ageGateExitUrl',
            'prompt' => 'Where should the age gate exit button send visitors?',
            'type' => 'text',
            'required' => true,
            'default' => 'https://www.google.com/',
            'config_path' => 'ageGate.exitUrl',
            'pattern' => $urlPattern,
            'pattern_error' => 'Please enter a full http:// or https:// address.',
        ],
        [
            'id' => 'ageGateStorage',
            'prompt' => 'How long should the age confirmation be remembered?',
            'type' => 'choice',
            'required' => true,
            'options' => ['local', 'session'],
            'default' => 'local',
            'config_path' => 'ageGate.storage',
            'help' => 'Choose local to remember the confirmation, or session until the tab closes.',
        ],
        [
            'id' => 'ageGateRememberDays',
            'prompt' => 'How many days should the local age confirmation be remembered?',
            'type' => 'number',
            'required' => true,
            'default' => 30,
            'config_path' => 'ageGate.rememberDays',
        ],
        [
            'id' => 'ageGateDisclaimer',
            'prompt' => 'Write the age gate disclaimer.',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'config_path' => 'ageGate.disclaimer',
        ],

        // -------------------------------------------------------------------
        // 11. FOOTER
        // -------------------------------------------------------------------
        [
            'id' => 'copyrightYear',
            'prompt' => 'What copyright year should the footer use?',
            'type' => 'text',
            'required' => true,
            'default' => 'auto',
            'config_path' => 'footer.copyrightYear',
            'help' => 'Use auto for the current year, or enter a fixed range such as 2024–2026.',
        ],
        [
            'id' => 'copyrightName',
            'prompt' => 'What name should appear in the copyright line?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'footer.copyrightName',
        ],
        [
            'id' => 'footerTagline',
            'prompt' => 'What tagline should appear in the footer?',
            'type' => 'text',
            'required' => true,
            'config_path' => 'footer.tagline',
        ],
        [
            'id' => 'footerDisclaimer',
            'prompt' => 'Write the footer disclaimer.',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'config_path' => 'footer.disclaimer',
        ],
        [
            'id' => 'footerAgeNotice',
            'prompt' => 'Write the footer age notice.',
            'type' => 'text',
            'multiline' => true,
            'required' => true,
            'config_path' => 'footer.ageNotice',
        ],

        // -------------------------------------------------------------------
        // 12. LEGAL
        // -------------------------------------------------------------------
        [
            'id' => 'privacyTitle',
            'prompt' => 'What title should the privacy notice use?',
            'type' => 'text',
            'required' => true,
            'default' => 'Privacy notice',
            'config_path' => 'legal.privacy.title',
        ],
        [
            'id' => 'privacyUpdated',
            'prompt' => 'What updated date should the privacy notice show?',
            'type' => 'text',
            'required' => true,
            'default' => 'Last updated: January 2026',
            'config_path' => 'legal.privacy.updated',
        ],
        [
            'id' => 'privacySections',
            'prompt' => 'Add the sections of the privacy notice.',
            'type' => 'records',
            'required' => true,
            'config_path' => 'legal.privacy.sections',
            'fields' => [
                'heading' => ['type' => 'text', 'required' => true],
                'text' => ['type' => 'text', 'required' => true],
            ],
            'help' => 'One section per line, for example: heading: What this website stores | text: This website does not use accounts or trackers.',
        ],
        [
            'id' => 'termsTitle',
            'prompt' => 'What title should the terms and conditions use?',
            'type' => 'text',
            'required' => true,
            'default' => 'Terms & conditions',
            'config_path' => 'legal.terms.title',
        ],
        [
            'id' => 'termsUpdated',
            'prompt' => 'What updated date should the terms and conditions show?',
            'type' => 'text',
            'required' => true,
            'default' => 'Last updated: January 2026',
            'config_path' => 'legal.terms.updated',
        ],
        [
            'id' => 'termsSections',
            'prompt' => 'Add the sections of the terms and conditions.',
            'type' => 'records',
            'required' => true,
            'config_path' => 'legal.terms.sections',
            'fields' => [
                'heading' => ['type' => 'text', 'required' => true],
                'text' => ['type' => 'text', 'required' => true],
            ],
            'help' => 'One section per line, using the same format as the privacy sections.',
        ],
    ],
];
