<?php
declare(strict_types=1);
include_once __DIR__ . '/../.global/.includer_Functions.php';


const APP_NAME = 'Dowe\'s Video Wall';
const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'data';
const DATABASE_FILE = DATA_DIR . DIRECTORY_SEPARATOR . 'video-wall.sqlite';
const VIDEO_EXTENSIONS = ['mp4', 'm4v', 'webm', 'ogv', 'ogg', 'mov', 'avi', 'mkv', 'mpeg', 'mpg'];
const FFMPEG_PATH = '';

getErrors(date('m/d/Y H:i:s'), 'ok');

// Log any errors that occur during analysis
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        date_default_timezone_set('America/Denver');
        $errorDir = __DIR__ . '/logs/';
        $errorFile = $errorDir . 'siteError.log';

        if (!is_dir($errorDir)) {
            mkdir($errorDir, 0755, true);
        }

        $timeStamp = date('m/d/Y H:i:s');
        $errorMessage = $timeStamp . ' [VIDEO-ANALYSIS] ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line'] . PHP_EOL . PHP_EOL;

        error_log($errorMessage, 3, $errorFile);
        getErrors($timeStamp, 'error');
    }
});
