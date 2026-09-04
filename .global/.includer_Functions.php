<?php
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
