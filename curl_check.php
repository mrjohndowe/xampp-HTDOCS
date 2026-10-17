<?php
echo 'PHP version: ' . PHP_VERSION . '<br>';
echo 'Loaded php.ini: ' . (php_ini_loaded_file() ?: 'None') . '<br>';
echo 'cURL enabled: ' . (extension_loaded('curl') ? 'YES' : 'NO') . '<br>';

if (function_exists('curl_init')) {
    echo 'curl_init(): AVAILABLE';
} else {
    echo 'curl_init(): NOT AVAILABLE';
}
