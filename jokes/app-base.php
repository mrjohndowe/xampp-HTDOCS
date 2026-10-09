<?php
declare(strict_types=1);

$appBaseScript = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$appBaseDirectory = dirname($appBaseScript);
$appBaseFolder = isset($_GET['p']) && is_string($_GET['p']) && $_GET['p'] !== ''
    ? $_GET['p']
    : basename($appBaseDirectory);

if (basename($appBaseDirectory) === $appBaseFolder) {
    $appBaseRoot = dirname($appBaseDirectory);
} else {
    $appBaseRoot = $appBaseDirectory;
}

$appBaseHref = rtrim($appBaseRoot, '/') . '/' . rawurlencode($appBaseFolder) . '/';
?>
<base href="<?= htmlspecialchars($appBaseHref, ENT_QUOTES, 'UTF-8') ?>">
