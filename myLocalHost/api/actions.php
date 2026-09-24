<?php

declare(strict_types=1);

header('Content-Type: application/json');

const ROOT = 'B:/htdocs';

$input = json_decode(

    file_get_contents('php://input'),

    true

);

$action = strtolower(

    $input['action'] ?? ''

);

$name =

    $input['project']

    ??

    $input['repository']

    ??

    '';

$path = ROOT . DIRECTORY_SEPARATOR . $name;

if (

    $name !== '' &&

    !is_dir($path)

) {

    respond(

        false,

        'Project not found.'

    );

}

switch ($action) {

    /*
    ==================================================
        WINDOWS EXPLORER
    ==================================================
    */

    case 'explorer':

        shell_exec(

            'explorer '

            . escapeshellarg($path)

        );

        respond(

            true,

            'Explorer opened.'

        );

        break;

    /*
    ==================================================
        VS CODE
    ==================================================
    */

    case 'vscode':

        shell_exec(

            'code '

            . escapeshellarg($path)

        );

        respond(

            true,

            'VS Code opened.'

        );

        break;

    /*
    ==================================================
        TERMINAL
    ==================================================
    */

    case 'terminal':

        pclose(

            popen(

                'start cmd /K cd /d '

                . escapeshellarg($path),

                'r'

            )

        );

        respond(

            true,

            'Terminal opened.'

        );

        break;

    /*
    ==================================================
        WEBSITE
    ==================================================
    */

    case 'website':

        $url =

            'http://localhost/'

            . rawurlencode($name)

            . '/';

        shell_exec(

            'start "" '

            . escapeshellarg($url)

        );

        respond(

            true,

            'Website opened.',

            [

                'url'=>$url

            ]

        );

        break;

    /*
    ==================================================
        GIT FETCH
    ==================================================
    */

    case 'fetch':

        git(

            $path,

            'fetch --all'

        );

        respond(

            true,

            'Fetch completed.'

        );

        break;

    /*
    ==================================================
        GIT PULL
    ==================================================
    */

    case 'pull':

        $output = git(

            $path,

            'pull'

        );

        respond(

            true,

            'Pull completed.',

            [

                'output'=>$output

            ]

        );

        break;

    /*
    ==================================================
        GIT STATUS
    ==================================================
    */

    case 'status':

        $output = git(

            $path,

            'status'

        );

        respond(

            true,

            'Status',

            [

                'output'=>$output

            ]

        );

        break;

    /*
    ==================================================
        GIT LOG
    ==================================================
    */

    case 'history':

        $output = git(

            $path,

            'log --oneline -25'

        );

        respond(

            true,

            'History',

            [

                'output'=>$output

            ]

        );

        break;

    default:

        respond(

            false,

            'Unknown action.'

        );

}

/*
==================================================
HELPERS
==================================================
*/

function git(

    string $directory,

    string $command

): string {

    $output = [];

    @exec(

        'cd /d '

        . escapeshellarg($directory)

        . ' && git '

        . $command

        . ' 2>NUL',

        $output

    );

    return implode(

        PHP_EOL,

        $output

    );

}

function respond(

    bool $success,

    string $message,

    array $extra=[]

): never {

    echo json_encode(

        array_merge(

            [

                'success'=>$success,

                'message'=>$message

            ],

            $extra

        ),

        JSON_PRETTY_PRINT |

        JSON_UNESCAPED_SLASHES

    );

    exit;

}
