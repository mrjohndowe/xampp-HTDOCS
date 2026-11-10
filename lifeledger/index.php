<?php
declare(strict_types=1);

use LifeLedger\Api;
use LifeLedger\Bootstrap;

require __DIR__ . '/src/Bootstrap.php';
require __DIR__ . '/src/Config.php';
require __DIR__ . '/src/Crypto.php';
require __DIR__ . '/src/Database.php';
require __DIR__ . '/src/Api.php';

$base = Bootstrap::baseUrl();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$relative = substr((string)$path, strlen($base));
if ($relative === '/downloads/lifeledger-1.0.0.zip') {
    Api::downloadRelease();
    return;
}
if (str_starts_with($relative, '/api/')) {
    Api::dispatch(substr($relative, 5), $_SERVER['REQUEST_METHOD'] ?? 'GET');
    return;
}
if (!in_array($relative, ['', '/', '/index.php'], true)) {
    http_response_code(404);
    exit;
}
if ($relative === '') {
    header('Location: ' . $base . '/', true, 308);
    exit;
}
header('Cache-Control: no-store');
$state = Api::screen();
$theme = Api::theme($_COOKIE['lifeledger_theme'] ?? 'system');
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-theme-pref="<?= $escape($theme) ?>" data-theme="<?= $theme === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LifeLedger — Personal Planner, Vault &amp; Timeline</title>
    <link rel="stylesheet" href="<?= $escape($base) ?>/assets/app.css">
    <script>
        const preference = document.documentElement.dataset.themePref;
        const theme = preference === 'system'
            ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : preference;
        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;
    </script>
    <script defer src="<?= $escape($base) ?>/assets/app.js"></script>
</head>
<body class="min-h-screen antialiased">
    <div id="lifeledger-root"></div>
    <script type="application/json" id="lifeledger-bootstrap"><?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
</body>
</html>
