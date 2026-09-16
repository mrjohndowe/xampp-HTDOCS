<?php

$page = $_SERVER['PHP_SELF'];
$page = basename(__DIR__);

// echo $page;

$cssFile = 'B:\htdocs\conversation\public\assets\css\app.css';
$cssVersion = is_file($cssFile) ? filemtime($cssFile) : time();
// $cssFile = file_exists($cssFile) ? 'YES' : 'NO';

$jsFile = 'B:\htdocs\conversation\public\assets\js\app.js';
// $jsFile = file_exists($jsFile) ? 'YES' : 'NO';
//$jssFile = file_exists($jssFile) ? date('', filemtime($jssFile)) :  date('m/d/Y H:i:s');
$jsVersion = date('m/d/Y H:i:s', filemtime($jsFile));
$currentTime = date('m/d/Y H:i:s');

echo 'Current Date/Time: ' . $currentTime. '<br><code>File Versions: </code><br>CSS:' . $cssVersion .' - Translates to: '.date('m/d/Y H:i:s', $cssVersion) . '<br>JS: ' . $jsVersion . '<br> FILE NAMES:<br> CSS: ' . $cssFile . '<br>JS:' . $jsFile . '<br>';

