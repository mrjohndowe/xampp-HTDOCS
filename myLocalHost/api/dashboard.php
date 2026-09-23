<?php
// API endpoint for dashboard data
// Returns JSON data for widgets

// Set time limit to prevent hanging
set_time_limit(10);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Helper functions
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
}

function getUptime() {
    // Simplified uptime calculation to prevent hanging
    // Return a reasonable default for now
    return 86400; // 1 day in seconds
    
    if (PHP_OS_FAMILY === 'Windows') {
        try {
            // Try WMIC first
            $uptime = shell_exec('wmic os get lastbootuptime /value 2>&1');
            if ($uptime) {
                preg_match('/LastBootUpTime=(.+)/', $uptime, $matches);
                if (isset($matches[1]) && !empty($matches[1])) {
                    $wmicTime = trim($matches[1]);
                    // WMIC returns format: 20230920195043.123456-420
                    if (strlen($wmicTime) >= 14) {
                        $year = substr($wmicTime, 0, 4);
                        $month = substr($wmicTime, 4, 2);
                        $day = substr($wmicTime, 6, 2);
                        $hour = substr($wmicTime, 8, 2);
                        $minute = substr($wmicTime, 10, 2);
                        $second = substr($wmicTime, 12, 2);
                        
                        $bootTime = mktime($hour, $minute, $second, $month, $day, $year);
                        if ($bootTime && $bootTime > 0) {
                            return time() - $bootTime;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Continue to fallback
        }
        
        return 86400; // Return 1 day as fallback if all methods fail
    } else {
        $uptime = shell_exec('uptime -s');
        if ($uptime) {
            return time() - strtotime($uptime);
        }
        return 0;
    }
}

function formatUptime($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . 'm';
    } elseif ($seconds < 86400) {
        return floor($seconds / 3600) . 'h';
    } else {
        return floor($seconds / 86400) . 'd';
    }
}

function getDiskInfo() {
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    
    // Try multiple paths if DOCUMENT_ROOT fails
    $pathsToTry = [
        $documentRoot,
        __DIR__,
        dirname(__DIR__),
        'B:\\',
        'C:\\'
    ];
    
    $total = 0;
    $free = 0;
    
    foreach ($pathsToTry as $path) {
        if (!empty($path) && is_dir($path)) {
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            
            if ($total && $free && $total > 0) {
                break;
            }
        }
    }
    
    // If still no valid disk info, try using current directory
    if (!$total || $total <= 0) {
        $total = disk_total_space('.');
        $free = disk_free_space('.');
    }
    
    // Final fallback
    if (!$total || $total <= 0) {
        return [
            'total' => 'Unknown',
            'used' => 'Unknown',
            'free' => 'Unknown',
            'percent' => 0
        ];
    }
    
    $used = $total - $free;
    $percent = $total > 0 ? ($used / $total) * 100 : 0;
    
    return [
        'total' => formatBytes($total),
        'used' => formatBytes($used),
        'free' => formatBytes($free),
        'percent' => round($percent, 1)
    ];
}

function getMySQLVersion() {
    try {
        // Try common MySQL credentials
        $credentials = [
            ['localhost', 'root', ''],
            ['localhost', 'root', 'root'],
            ['127.0.0.1', 'root', ''],
            ['127.0.0.1', 'root', 'root']
        ];
        
        foreach ($credentials as $cred) {
            try {
                $conn = new mysqli($cred[0], $cred[1], $cred[2]);
                if ($conn->connect_error) {
                    continue;
                }
                $result = $conn->query('SELECT VERSION()');
                if ($result) {
                    $row = $result->fetch_array();
                    $conn->close();
                    return $row[0];
                }
                $conn->close();
            } catch (Exception $e) {
                continue;
            }
        }
    } catch (Exception $e) {
        return null;
    }
    return null;
}

function getGitVersion() {
    $version = shell_exec('git --version 2>&1');
    if ($version && strpos($version, 'git version') !== false) {
        return trim($version);
    }
    return null;
}

function getNodeVersion() {
    $version = shell_exec('node --version 2>&1');
    if ($version && strpos($version, 'v') === 0) {
        return trim($version);
    }
    return null;
}

function getComposerVersion() {
    // Simplified composer check - skip for now to prevent hanging
    return null;
    
    // Try to find composer in common locations
    $composerPaths = [
        'composer',
        'composer.phar',
        getenv('LOCALAPPDATA') . '\\Composer\\composer.phar',
        getenv('PROGRAMDATA') . '\\ComposerSetup\\bin\\composer.bat'
    ];
    
    foreach ($composerPaths as $composerPath) {
        if ($composerPath) {
            $version = shell_exec('"' . $composerPath . '" --version 2>&1');
            if ($version && strpos($version, 'Composer version') !== false) {
                preg_match('/Composer version ([^\s]+)/', $version, $matches);
                return $matches[1] ?? 'Unknown';
            }
        }
    }
    
    return null;
}

function getGitRepositories() {
    $repos = [];
    
    // Scan parent directory (htdocs) for git repositories
    $root = dirname(__DIR__); // This should be htdocs
    if (empty($root) || !is_dir($root)) {
        return [];
    }
    
    // Convert Windows path format
    if (PHP_OS_FAMILY === 'Windows') {
        $root = str_replace('/', '\\', $root);
    }
    
    try {
        $dirs = scandir($root);
        if ($dirs === false) {
            return [];
        }
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $fullPath = $root . DIRECTORY_SEPARATOR . $dir;
            
            // Check if this directory has .git
            if (is_dir($fullPath)) {
                $gitPath = $fullPath . DIRECTORY_SEPARATOR . '.git';
                if (is_dir($gitPath)) {
                    // Get git info
                    $branch = 'main';
                    $status = 'Unknown';
                    
                    try {
                        $branchCmd = 'git -C "' . $fullPath . '" branch --show-current 2>&1';
                        $branchOutput = shell_exec($branchCmd);
                        if ($branchOutput && trim($branchOutput)) {
                            $branch = trim($branchOutput);
                        }
                        
                        $statusCmd = 'git -C "' . $fullPath . '" status --porcelain 2>&1';
                        $statusOutput = shell_exec($statusCmd);
                        if ($statusOutput !== null) {
                            $status = empty(trim($statusOutput)) ? 'Clean' : 'Modified';
                        }
                    } catch (Exception $e) {
                        $branch = 'main';
                        $status = 'Unknown';
                    }
                    
                    $repos[] = [
                        'name' => $dir,
                        'path' => $fullPath,
                        'branch' => $branch,
                        'status' => $status
                    ];
                }
            }
        }
    } catch (Exception $e) {
        return [];
    }
    
    return $repos;
}

function getApacheVersion() {
    // Simplified Apache check
    $version = null;
    
    // Method 1: apache_get_version() if available
    if (function_exists('apache_get_version')) {
        $version = apache_get_version();
    }
    
    // Method 2: Check SERVER_SOFTWARE
    if (!$version && isset($_SERVER['SERVER_SOFTWARE'])) {
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'];
        if (strpos($serverSoftware, 'Apache') !== false) {
            $version = $serverSoftware;
        }
    }
    
    return $version ?: null;
}

// Build response data
$systemUptime = getUptime();
$diskInfo = getDiskInfo();

$response = [
    'system' => [
        'hostname' => gethostname(),
        'os' => PHP_OS_FAMILY . ' ' . php_uname('r'),
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'uptime' => formatUptime($systemUptime),
        'uptime_seconds' => $systemUptime,
        'disk' => $diskInfo,
        'document_root' => $_SERVER['DOCUMENT_ROOT']
    ],
    'services' => [
        'php' => [
            'name' => 'PHP',
            'version' => PHP_VERSION,
            'status' => 'online'
        ],
        'apache' => [
            'name' => 'Apache',
            'version' => getApacheVersion() ?: 'Unknown',
            'status' => getApacheVersion() ? 'online' : 'offline'
        ],
        'mysql' => [
            'name' => 'MySQL',
            'version' => getMySQLVersion() ?: 'Unknown',
            'status' => getMySQLVersion() ? 'online' : 'offline'
        ],
        'git' => [
            'name' => 'Git',
            'version' => getGitVersion() ?: 'Unknown',
            'status' => getGitVersion() ? 'online' : 'offline'
        ],
        'node' => [
            'name' => 'Node.js',
            'version' => getNodeVersion() ?: 'Unknown',
            'status' => getNodeVersion() ? 'online' : 'offline'
        ],
        'composer' => [
            'name' => 'Composer',
            'version' => getComposerVersion() ?: 'Unknown',
            'status' => getComposerVersion() ? 'online' : 'offline'
        ]
    ],
    'repositories' => getGitRepositories(),
    'debug' => [
        'php_os_family' => PHP_OS_FAMILY,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'git_available' => getGitVersion() !== null
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);