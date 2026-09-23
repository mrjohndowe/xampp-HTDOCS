<?php

declare(strict_types=1);

header('Content-Type: application/json');

date_default_timezone_set('America/Denver');

$root = realpath($_SERVER['DOCUMENT_ROOT']);
// $root = realpath("G:\.gitClones");

$response = [
    "success" => true,
    "repositories" => []
];

$ignore = [
    '.git',
    'vendor',
    'node_modules',
    '.idea',
    '.vs',
    '.vscode'
];

$projects = scandir($root);

foreach ($projects as $project) {

    if ($project === "." || $project === "..") {
        continue;
    }

    if (in_array($project, $ignore, true)) {
        continue;
    }

    $path = realpath($root . DIRECTORY_SEPARATOR . $project);

    if ($path === false || !is_dir($path)) {
        continue;
    }

    if (!is_dir($path . DIRECTORY_SEPARATOR . ".git")) {
        continue;
    }

    $repo = [

        "name" => $project,

        "branch" => "",

        "clean" => true,

        "modified" => 0,

        "ahead" => 0,

        "behind" => 0,

        "lastCommit" => "",

        "lastAuthor" => ""

    ];

    exec(
        'git -C ' . escapeshellarg($path) . ' branch --show-current',
        $output
    );

    $repo["branch"] = trim($output[0] ?? "");

    $output = [];

    exec(
        'git -C ' . escapeshellarg($path) . ' status --porcelain',
        $output
    );

    $repo["modified"] = count($output);

    $repo["clean"] = empty($output);

    $output = [];

    exec(
        'git -C ' . escapeshellarg($path) . ' log -1 --pretty=format:"%an|%ar"',
        $output
    );

    if (!empty($output)) {

        [$author, $time] = array_pad(
            explode("|", $output[0], 2),
            2,
            ""
        );

        $repo["lastAuthor"] = $author;

        $repo["lastCommit"] = $time;
    }

    $output = [];

    exec(
        'git -C ' .
            escapeshellarg($path) .
            ' rev-list --left-right --count @{upstream}...HEAD 2>NUL',
        $output
    );

    if (!empty($output)) {

        $parts = preg_split('/\s+/', trim($output[0]));

        if (count($parts) === 2) {

            $repo["behind"] = (int)$parts[0];

            $repo["ahead"] = (int)$parts[1];
        }
    }

    $response["repositories"][] = $repo;
}

echo json_encode(
    $response,
    JSON_PRETTY_PRINT
);
