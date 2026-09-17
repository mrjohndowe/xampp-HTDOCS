<?php

function getHeader(string $need = ''): string
{
    $faviconUrl = '/.global/assets/favicon.svg?v=2';

    switch ($need) {
        case 'no-header':
            $display = '<link rel="icon shortcut icon" type="image/svg+xml" sizes="any" href="'. htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'). '">';
            break;

        default:
            $display = '<head><link rel="icon shortcut icon" type="image/svg+xml" sizes="any" href="'. htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'). '"></head>';
    }

    return $display;
}

$error_config = [
    E_ALL => true,  // Usually false if you want custom rules
    E_ERROR => true,  // Stop and log critical errors
    E_WARNING => true,  // Track warnings
    E_PARSE => true,  // Track syntax errors
    E_NOTICE => true,  // Ignore notices
    E_CORE_ERROR => true,
    E_CORE_WARNING => true,
    E_COMPILE_ERROR => true,
    E_COMPILE_WARNING => true,
    E_USER_ERROR => true,
    E_USER_WARNING => true,
    E_USER_NOTICE => true,
    // E_STRICT => false,  // Ignore strict standards Deprecated: Constant E_STRICT is deprecated since 8.4, the error level was removed
    E_RECOVERABLE_ERROR => true,
    E_DEPRECATED => true,
    E_USER_DEPRECATED => true
];

// Custom error handler function
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($error_config) {
    if (isset($error_config[$errno]) && $error_config[$errno]) {
        // Check if the triggered error level is set to true in your array
        error_log("Error [$errno]: $errstr in $errfile on line $errline");
        // Put your logging or tracking logic here
        echo 'An error occurred. Please try again later.';
    }

    // Return false to let PHP's internal error handler continue if needed
    return false;
});


// Set up global error handling
if (! function_exists('globalErrorHandler')) {
    function globalErrorHandler($errno, $errstr, $errfile, $errline)
    {
        date_default_timezone_set('America/Denver');

        $errorDir  = __DIR__ . '/logs/';
        $errorFile = $errorDir . 'siteError.log';

        // Ensure directory exists
        if (! is_dir($errorDir)) {
            mkdir($errorDir, 0755, true);
        }

        $timeStamp    = date('Y-m-d H:i:s');
        $errorMessage = $timeStamp . ' [' . $errno . '] ' . $errstr . ' in ' . $errfile . ' on line ' . $errline;

        error_log($errorMessage . PHP_EOL, 3, $errorFile);
    }
}
if (! function_exists('globalExceptionHandler')) {
    // Set up global exception handler
    function globalExceptionHandler($exception)
    {
        date_default_timezone_set('America/Denver');

        $errorDir  = __DIR__ . '/logs/';
        $errorFile = $errorDir . 'siteError.log';

        // Ensure directory exists
        if (! is_dir($errorDir)) {
            mkdir($errorDir, 0755, true);
        }

        $timeStamp    = date('Y-m-d H:i:s');
        $errorMessage = $timeStamp . ' [EXCEPTION] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine();

        error_log($errorMessage . PHP_EOL, 3, $errorFile);
    }
}
// Register the handlers
// set_error_handler('globalErrorHandler');
set_exception_handler('globalExceptionHandler');

// Handle fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        globalErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
    }
});
