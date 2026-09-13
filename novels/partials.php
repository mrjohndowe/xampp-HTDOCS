<?php
require_once __DIR__ . '/config.php';

function site_header(string $title = APP_NAME): void { ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php">
        <img src="assets/img/logo.png" alt="<?= APP_NAME ?> logo">
        <span><?= APP_NAME ?></span>
    </a>
    <nav>
        <a href="index.php">Library</a>
        <a href="upload.php">Upload</a>
        <a href="admin/index.php">Admin</a>
    </nav>
</header>
<main class="container">
<?php if ($m = flash('success')): ?><div class="notice success"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="notice error"><?= e($m) ?></div><?php endif; ?>
<?php }

function site_footer(): void { ?>
</main>
<footer>© <?= date('Y') ?> <?= APP_NAME ?>. Built for XAMPP.</footer>
<script src="assets/js/app.js"></script>
</body>
</html>
<?php }
