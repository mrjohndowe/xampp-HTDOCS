<?php
declare(strict_types=1);
require dirname(__DIR__) . '/lib.php';
if (ic_installed() || is_file(ic_private_dir() . '/install.lock')) {
    ic_headers(); http_response_code(403);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Installation locked · innercircle</title><link rel="stylesheet" href="../assets/app.css"></head><body><main class="lock-page"><section class="lock-card"><p class="eyebrow">SAFE AND SOUND</p><h1>Installation is locked.</h1><p>Your inner circle is already set up. You can safely delete or rename the install folder; the configuration lock also prevents reinstallation.</p><a class="button primary" href="../index.php">Go to your workspace</a></section></main></body></html>';
    exit;
}
ic_render(true);
