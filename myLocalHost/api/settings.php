<?php

declare(strict_types=1);

header('Content-Type: application/json');

const SETTINGS_FILE = __DIR__ . '/../storage/settings.json';

$defaults = [

    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    */

    'application' => [

        'name' => 'myLocalHost',

        'theme' => 'dark',

        'accent' => '#2f81f7',

        'refreshInterval' => 10000,

        'sidebarCollapsed' => false

    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    'dashboard' => [

        'showMachine' => true,

        'showServices' => true,

        'showGit' => true,

        'showProjects' => true,

        'showActivity' => true,

        'showDisk' => true

    ],

    /*
    |--------------------------------------------------------------------------
    | Git
    |--------------------------------------------------------------------------
    */

    'git' => [

        'scanRoot' => 'B:/htdocs',

        'autoFetch' => false,

        'showHiddenRepositories' => false

    ],

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */

    'projects' => [

        'root' => 'B:/htdocs',

        'showHiddenFolders' => false

    ],

    /*
    |--------------------------------------------------------------------------
    | Explorer
    |--------------------------------------------------------------------------
    */

    'explorer' => [

        'preferredEditor' => 'vscode'

    ]

];

if (!is_dir(dirname(SETTINGS_FILE))) {

    mkdir(

        dirname(SETTINGS_FILE),

        0777,

        true

    );
}

if (!file_exists(SETTINGS_FILE)) {

    file_put_contents(

        SETTINGS_FILE,

        json_encode(

            $defaults,

            JSON_PRETTY_PRINT |

                JSON_UNESCAPED_SLASHES

        )

    );
}

$settings = json_decode(

    file_get_contents(

        SETTINGS_FILE

    ),

    true

);

if (!is_array($settings)) {

    $settings = $defaults;
}

echo json_encode(

    [

        'success' => true,

        'settings' => $settings

    ],

    JSON_PRETTY_PRINT |

        JSON_UNESCAPED_SLASHES

);
