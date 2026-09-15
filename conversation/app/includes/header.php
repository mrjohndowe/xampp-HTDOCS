<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= e($config['app_name']) ?>
    </title>

  <?php

    $cssFile = dirname(__DIR__, 2) . '/public/assets/css/app.css';
    $cssVersion = is_file($cssFile) ? filemtime($cssFile) : time();

    ?>

    <link rel="stylesheet" href="/conversation/assets/css/app.css?v=<?= $cssVersion ?>">

    <!-- <link rel="stylesheet" href="<?= e($config['base_path'] ?? '') ?> .'/assets/css/app.css?v=' . <?= $cssVersion ?> .'"'> -->
</head>

<body>
