<?php

declare(strict_types=1);

header('Content-Type: application/json');

function commandExists(string $command): bool
{
    @exec($command . ' --version 2>NUL', $output, $code);

    return $code === 0;
}

function commandOutput(string $command): string
{
    $output = [];

    @exec($command . ' 2>NUL', $output);

    return trim($output[0] ?? '');
}

$services = [];

/*
==================================================
PHP
==================================================
*/

$services[] = [

    'id' => 'php',

    'name' => 'PHP',

    'status' => 'online',

    'version' => PHP_VERSION

];

/*
==================================================
Apache
==================================================
*/

$services[] = [

    'id' => 'apache',

    'name' => 'Apache',

    'status' => isset($_SERVER['SERVER_SOFTWARE'])
        ? 'online'
        : 'offline',

    'version' => $_SERVER['SERVER_SOFTWARE'] ?? ''

];

/*
==================================================
MariaDB
==================================================
*/

$services[] = [

    'id' => 'mariadb',

    'name' => 'MariaDB',

    'status' => extension_loaded('mysqli')
        ? 'online'
        : 'offline',

    'version' => ''

];

/*
==================================================
Git
==================================================
*/

$services[] = [

    'id' => 'git',

    'name' => 'Git',

    'status' => commandExists('git')
        ? 'online'
        : 'offline',

    'version' => commandOutput('git --version')

];

/*
==================================================
Node
==================================================
*/

$services[] = [

    'id' => 'node',

    'name' => 'Node.js',

    'status' => commandExists('node')
        ? 'online'
        : 'offline',

    'version' => commandOutput('node -v')

];

/*
==================================================
Composer
==================================================
*/

$services[] = [

    'id' => 'composer',

    'name' => 'Composer',

    'status' => commandExists('composer')
        ? 'online'
        : 'offline',

    'version' => commandOutput('composer --version')

];

/*
==================================================
npm
==================================================
*/

$services[] = [

    'id' => 'npm',

    'name' => 'NPM',

    'status' => commandExists('npm')
        ? 'online'
        : 'offline',

    'version' => commandOutput('npm -v')

];

/*
==================================================
pnpm
==================================================
*/

$services[] = [

    'id' => 'pnpm',

    'name' => 'PNPM',

    'status' => commandExists('pnpm')
        ? 'online'
        : 'offline',

    'version' => commandOutput('pnpm -v')

];

/*
==================================================
Yarn
==================================================
*/

$services[] = [

    'id' => 'yarn',

    'name' => 'Yarn',

    'status' => commandExists('yarn')
        ? 'online'
        : 'offline',

    'version' => commandOutput('yarn -v')

];

echo json_encode(

    [

        'success' => true,

        'count' => count($services),

        'services' => $services

    ],

    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES

);
