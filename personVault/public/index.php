<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
app_headers();
if (app_config_path() === null) { header('Location: install.php'); exit; }
$user = app_user();
?><!doctype html>
<html lang="en" data-theme="<?= htmlspecialchars($user['theme'] ?? 'system', ENT_QUOTES, 'UTF-8') ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="pv-csrf" content="<?= htmlspecialchars(app_csrf(), ENT_QUOTES, 'UTF-8') ?>"><meta name="color-scheme" content="light dark"><title>PersonVault</title><link rel="stylesheet" href="assets/app.css"></head>
<body><div id="app" class="app-root"><div class="loading-screen"><span class="brand-mark">P</span><p>Opening your private vault…</p></div></div><script src="assets/app.js" defer></script></body></html>
