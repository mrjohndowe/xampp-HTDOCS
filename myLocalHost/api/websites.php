<?php

declare(strict_types=1);

header('Content-Type: application/json');

const ROOT = 'B:/htdocs';

function exists(string $file): bool
{
    return is_file($file);
}

function detectType(string $path): string
{
    if (exists($path . '/artisan')) {
        return 'Laravel';
    }

    if (exists($path . '/wp-config.php')) {
        return 'WordPress';
    }

    if (exists($path . '/composer.json')) {
        return 'PHP';
    }

    if (exists($path . '/package.json')) {
        return 'Node';
    }

    return 'Static';
}

function websiteUrl(string $folder): string
{
    return "http://localhost/" . rawurlencode($folder) . "/";
}

$sites = [];

foreach (new DirectoryIterator(ROOT) as $directory) {

    if (!$directory->isDir()) {
        continue;
    }

    if ($directory->isDot()) {
        continue;
    }

    $path = $directory->getPathname();

    $website =

        exists($path . '/index.php') ||

        exists($path . '/index.html') ||

        exists($path . '/public/index.php');

    if (!$website) {
        continue;
    }

    $sites[] = [

        'name' => $directory->getFilename(),

        'url' => websiteUrl(

            $directory->getFilename()

        ),

        'path' => str_replace(

            '\\',

            '/',

            $path

        ),

        'type' => detectType($path),

        'ssl' => false,

        'online' => true,

        'modified' => date(

            'Y-m-d H:i:s',

            filemtime($path)

        )

    ];

}

usort(

    $sites,

    fn($a,$b)=>

        strcasecmp(

            $a['name'],

            $b['name']

        )

);

echo json_encode(

    [

        'success'=>true,

        'count'=>count($sites),

        'websites'=>$sites

    ],

    JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES

);
