<?php

declare(strict_types=1);

header('Content-Type: application/json');

const ROOT = 'B:/htdocs';

function detectFramework(string $path): string
{
    if (is_file($path . '/artisan')) {
        return 'Laravel';
    }

    if (is_file($path . '/composer.json')) {

        $composer = @json_decode(
            file_get_contents($path . '/composer.json'),
            true
        );

        $require = $composer['require'] ?? [];

        if (isset($require['laravel/framework'])) {
            return 'Laravel';
        }

        if (isset($require['codeigniter4/framework'])) {
            return 'CodeIgniter';
        }

        if (isset($require['symfony/framework-bundle'])) {
            return 'Symfony';
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

function hasWebsite(string $path): bool
{
    return
        is_file($path . '/index.php') ||
        is_file($path . '/index.html') ||
        is_file($path . '/public/index.php');
}

function folderSize(string $directory): int
{
    $size = 0;

    try {

        $iterator = new RecursiveIteratorIterator(

            new RecursiveDirectoryIterator(

                $directory,

                FilesystemIterator::SKIP_DOTS

            )

        );

        foreach ($iterator as $file) {

            $size += $file->getSize();
        }
    } catch (Throwable) {
    }

    return $size;
}

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $power = $bytes > 0
        ? floor(log($bytes, 1024))
        : 0;

    $power = min($power, count($units) - 1);

    return round(

        $bytes / pow(1024, $power),

        2

    ) . ' ' . $units[$power];
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

        'framework' => detectFramework($path),

        'website' => hasWebsite($path),

        'git' => is_dir($path . '/.git'),

        'modified' => date(

            'Y-m-d H:i:s',

            filemtime($path)

        ),

        'size' => formatBytes(

            folderSize($path)

        )

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

echo json_encode(

    [

        'success' => true,

        'count' => count($projects),

        'projects' => $projects

    ],

    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES

);
