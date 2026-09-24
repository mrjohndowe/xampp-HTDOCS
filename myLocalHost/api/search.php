<?php

declare(strict_types=1);

header('Content-Type: application/json');

const ROOT = 'B:/htdocs';

$input = json_decode(

    file_get_contents('php://input'),

    true

);

$query = trim(

    strtolower(

        $input['query'] ?? ''

    )

);

if ($query === '') {

    echo json_encode([

        'success' => true,

        'query' => '',

        'results' => []

    ]);

    exit;

}

$results = [];

/*
==================================================
PROJECTS
==================================================
*/

foreach (new DirectoryIterator(ROOT) as $directory) {

    if (!$directory->isDir()) {

        continue;

    }

    if ($directory->isDot()) {

        continue;

    }

    $name = $directory->getFilename();

    $path = $directory->getPathname();

    if (

        str_contains(

            strtolower($name),

            $query

        )

    ) {

        $results[] = [

            'type' => 'project',

            'title' => $name,

            'subtitle' => str_replace(

                '\\',

                '/',

                $path

            ),

            'icon' => '📂'

        ];

    }

}

/*
==================================================
FILES
==================================================
*/

$iterator = new RecursiveIteratorIterator(

    new RecursiveDirectoryIterator(

        ROOT,

        FilesystemIterator::SKIP_DOTS

    )

);

foreach ($iterator as $file) {

    if (!$file->isFile()) {

        continue;

    }

    $filename =

        $file->getFilename();

    if (

        !str_contains(

            strtolower($filename),

            $query

        )

    ) {

        continue;

    }

    $results[] = [

        'type' => 'file',

        'title' => $filename,

        'subtitle' => str_replace(

            '\\',

            '/',

            $file->getPathname()

        ),

        'icon' => '📄'

    ];

    if (

        count($results) >= 100

    ) {

        break;

    }

}

/*
==================================================
SORT
==================================================
*/

usort(

    $results,

    fn($a,$b)=>

        strcasecmp(

            $a['title'],

            $b['title']

        )

);

echo json_encode(

    [

        'success' => true,

        'query' => $query,

        'count' => count($results),

        'results' => $results

    ],

    JSON_PRETTY_PRINT |

    JSON_UNESCAPED_SLASHES

);
