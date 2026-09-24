-- Active: 1790149885058@@127.0.0.1@3306
<?php

declare(strict_types=1);

header('Content-Type: application/json');

const ROOT = 'B:/htdocs';

const MAX_RESULTS = 100;

$ignore = [

    '.git',
    'vendor',
    'node_modules',
    '.vs',
    '.idea',
    'bin',
    'obj',
    'storage/cache',
    'storage/logs',
    'logs'

];

$activity = [];

$iterator = new RecursiveIteratorIterator(

    new RecursiveDirectoryIterator(

        ROOT,

        FilesystemIterator::SKIP_DOTS

    ),

    RecursiveIteratorIterator::SELF_FIRST

);

foreach ($iterator as $file) {

    if (!$file->isFile()) {

        continue;

    }

    $path = str_replace(

        '\\',

        '/',

        $file->getPathname()

    );

    $skip = false;

    foreach ($ignore as $folder) {

        if (stripos($path, '/' . $folder . '/') !== false) {

            $skip = true;

            break;

        }

    }

    if ($skip) {

        continue;

    }

    $extension = strtolower(

        pathinfo(

            $path,

            PATHINFO_EXTENSION

        )

    );

    $activity[] = [

        'file' => $file->getFilename(),

        'path' => $path,

        'directory' => dirname($path),

        'extension' => $extension,

        'size' => $file->getSize(),

        'modified' => date(

            'Y-m-d H:i:s',

            $file->getMTime()

        ),

        'timestamp' => $file->getMTime()

    ];

}

usort(

    $activity,

    fn($a, $b) =>

        $b['timestamp'] <=> $a['timestamp']

);

$activity = array_slice(

    $activity,

    0,

    MAX_RESULTS

);

foreach ($activity as &$row) {

    unset($row['timestamp']);

}

echo json_encode(

    [

        'success' => true,

        'count' => count($activity),

        'activity' => $activity,

        'generated' => date('c')

    ],

    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES

);
