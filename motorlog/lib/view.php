<?php
declare(strict_types=1);

function render_app(string $mode,string $csrf,?array $actor=null,array $settings=[],array $error=[]): never {
    header('Content-Type: text/html; charset=utf-8');
    $boot=json_encode(['mode'=>$mode,'csrf'=>$csrf,'actor'=>$actor,'site'=>$settings['site_name'] ?? 'Motorlog','version'=>installed_version(),
        'demo'=>preview_mode(),'today'=>today_iso(),'currencies'=>CURRENCIES,'error'=>$error],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
    $title=$mode==='setup'?'Set up your workspace':($mode==='login'?'Sign in':'Fleet dashboard');
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#f7f8fc">
<title><?=esc($title)?> · <?=esc($settings['site_name'] ?? 'Motorlog')?></title><meta name="description" content="Motorlog is your connected vehicle, trip, and maintenance workspace.">
<link rel="icon" href="assets/logo.svg" type="image/svg+xml"><link rel="stylesheet" href="assets/app.css"><script src="assets/theme.js"></script></head>
<body><div id="app" aria-busy="true"><div class="launch"><img src="assets/logo.svg" alt=""><div>Opening Motorlog<span>Getting your workspace ready…</span></div></div></div>
<noscript><p class="noscript">Motorlog requires JavaScript to edit and synchronize your workspace.</p></noscript>
<script type="application/json" id="boot"><?=$boot?></script><script src="assets/app.js" type="module"></script></body></html><?php
    exit;
}
