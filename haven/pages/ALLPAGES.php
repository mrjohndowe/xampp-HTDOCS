<?php
declare(strict_types=1);

$stylePath = dirname(__DIR__) . '/public/assets/css/styles.css';
$scriptPath = dirname(__DIR__) . '/public/assets/js/scripts.js';
$styleVersion = is_file($stylePath) ? (string)filemtime($stylePath) : '1';
$scriptVersion = is_file($scriptPath) ? (string)filemtime($scriptPath) : '1';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Haven</title>
  <link rel="stylesheet" href="assets/css/styles.css?v=<?= htmlspecialchars($styleVersion, ENT_QUOTES, 'UTF-8') ?>" />
</head>
<body data-installed="<?= $installed ? 'true' : 'false' ?>">
<div class="wrap" id="app"></div>
<script src="assets/js/scripts.js?v=<?= htmlspecialchars($scriptVersion, ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
