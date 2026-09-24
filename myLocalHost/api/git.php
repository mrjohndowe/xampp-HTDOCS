<?php

declare(strict_types=1);

header('Content-Type: application/json');

const ROOT = 'B:/htdocs';

function git(string $repository, string $command): string
{
    $cwd = escapeshellarg($repository);

    $output = [];

    @exec(
        "cd /d {$cwd} && git {$command} 2>NUL",
        $output
    );

    return trim(implode("\n", $output));
}

function hasFile(string $path): bool
{
    return file_exists($path);
}

$repositories = [];

$iterator = new DirectoryIterator(ROOT);

foreach ($iterator as $directory) {

    if (!$directory->isDir()) {
        continue;
    }

    if ($directory->isDot()) {
        continue;
    }

    $path = $directory->getPathname();

    if (!is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
        continue;
    }

    $status = git(
        $path,
        'status --porcelain'
    );

    $aheadBehind = git(
        $path,
        'rev-list --left-right --count @{upstream}...HEAD'
    );

    $ahead = 0;
    $behind = 0;

    if ($aheadBehind !== '') {

        $parts = preg_split('/\s+/', $aheadBehind);

        if (count($parts) >= 2) {

            $behind = (int)$parts[0];
            $ahead  = (int)$parts[1];

        }

    }

    $repositories[] = [

        /*
        ============================================
        Basic
        ============================================
        */

        'name' => $directory->getFilename(),

        'path' => str_replace('\\', '/', $path),

        /*
        ============================================
        Git
        ============================================
        */

        'branch' => git(
            $path,
            'branch --show-current'
        ),

        'clean' => $status === '',

        'modified' => $status === ''
            ? 0
            : substr_count($status, "\n") + 1,

        'ahead' => $ahead,

        'behind' => $behind,

        /*
        ============================================
        Commit
        ============================================
        */

        'lastCommit' => git(
            $path,
            'rev-parse --short HEAD'
        ),

        'lastMessage' => git(
            $path,
            'log -1 --pretty=%s'
        ),

        'lastAuthor' => git(
            $path,
            'log -1 --pretty=%an'
        ),

        'lastDate' => git(
            $path,
            'log -1 --pretty=%cr'
        ),

        /*
        ============================================
        Remote
        ============================================
        */

        'remote' => git(
            $path,
            'remote get-url origin'
        ),

        /*
        ============================================
        Detection
        ============================================
        */

        'website' => hasFile($path.'/index.php')
            || hasFile($path.'/index.html'),

        'composer' => hasFile($path.'/composer.json'),

        'node' => hasFile($path.'/package.json'),

        'readme' =>
            hasFile($path.'/README.md') ||
            hasFile($path.'/readme.md'),

        'laravel' =>
            hasFile($path.'/artisan'),

        'vite' =>
            hasFile($path.'/vite.config.js'),

        'docker' =>
            hasFile($path.'/docker-compose.yml') ||
            hasFile($path.'/docker-compose.yaml') ||
            hasFile($path.'/Dockerfile'),

        'env' =>
            hasFile($path.'/.env')

    ];

}

usort(

    $repositories,

    fn($a, $b) =>

        strcasecmp(

            $a['name'],

            $b['name']

        )

);

echo json_encode(

    [

        'success' => true,

        'count' => count($repositories),

        'repositories' => $repositories

    ],

    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES

);
