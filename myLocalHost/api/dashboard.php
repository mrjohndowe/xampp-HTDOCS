<?php

declare(strict_types=1);

header('Content-Type: application/json');

const ROOT = 'B:/htdocs';

function websiteExists(string $path): bool
{
    foreach ([
        'index.php',
        'index.html',
        'index.htm',
        'public/index.php'
    ] as $file) {

        if (is_file($path . DIRECTORY_SEPARATOR . $file)) {
            return true;
        }

    }

    return false;
}

function framework(string $path): string
{
    if (is_file($path . '/artisan')) {
        return 'Laravel';
    }

    if (is_file($path . '/composer.json')) {
        $composer = @json_decode(
            file_get_contents($path . '/composer.json'),
            true
        );

        if (!empty($composer['require']['codeigniter4/framework'])) {
            return 'CodeIgniter';
        }

        if (!empty($composer['require']['symfony/framework-bundle'])) {
            return 'Symfony';
        }

        if (!empty($composer['require']['yiisoft/yii2'])) {
            return 'Yii';
        }

        return 'PHP';
    }

    if (is_file($path . '/package.json')) {

        $package = @json_decode(
            file_get_contents($path . '/package.json'),
            true
        );

        $deps = array_merge(
            $package['dependencies'] ?? [],
            $package['devDependencies'] ?? []
        );

        if (isset($deps['react'])) {
            return 'React';
        }

        if (isset($deps['vue'])) {
            return 'Vue';
        }

        if (isset($deps['svelte'])) {
            return 'Svelte';
        }

        if (isset($deps['next'])) {
            return 'NextJS';
        }

        return 'Node';
    }

    return '';
}

$projects = [];

foreach (new DirectoryIterator(ROOT) as $directory) {

    if (!$directory->isDir()) {
        continue;
    }

    if ($directory->isDot()) {
        continue;
    }

    $path = $directory->getPathname();

    $projects[] = [

        'name' => $directory->getFilename(),

        'path' => str_replace(
            '\\',
            '/',
            $path
        ),

        'modified' => date(
            'Y-m-d H:i:s',
            filemtime($path)
        ),

        'website' => websiteExists($path),

        'git' => is_dir(
            $path . DIRECTORY_SEPARATOR . '.git'
        ),

        'framework' => framework($path),

        'size' => 0

    ];

}

usort(

    $projects,

    fn($a, $b) =>

        strcasecmp(

            $a['name'],

            $b['name']

        )

);

$activity = [];

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

    $activity[] = [

        'file' => $file->getFilename(),

        'path' => str_replace(
            '\\',
            '/',
            $file->getPathname()
        ),

        'time' => date(
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

    50

);

foreach ($activity as &$row) {

    unset($row['timestamp']);

}

echo json_encode(

    [

        'success' => true,

        'timestamp' => date('c'),

        'projects' => $projects,

        'activity' => $activity

    ],

    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES

);
