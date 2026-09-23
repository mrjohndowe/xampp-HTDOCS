<?php
// API endpoint for dashboard data
// Returns JSON data for widgets

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Helper functions
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
}

function getUptime() {
    if (PHP_OS_FAMILY === 'Windows') {
        $uptime = shell_exec('wmic os get lastbootuptime /value');
        if ($uptime) {
            preg_match('/LastBootUpTime=(.+)/', $uptime, $matches);
            if (isset($matches[1])) {
                $bootTime = strtotime(substr($matches[1], 0, 14));
                return time() - $bootTime;
            }
        }
        return 0;
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
    $total = disk_total_space($_SERVER['DOCUMENT_ROOT']);
    $free = disk_free_space($_SERVER['DOCUMENT_ROOT']);
    $used = $total - $free;
    $percent = ($used / $total) * 100;
    
    return [
        'total' => formatBytes($total),
        'used' => formatBytes($used),
        'free' => formatBytes($free),
        'percent' => round($percent, 1)
    ];
}

function getMySQLVersion() {
    try {
        $conn = new mysqli('localhost', 'root', '');
        if ($conn->connect_error) {
            return null;
        }
        $result = $conn->query('SELECT VERSION()');
        if ($result) {
            $row = $result->fetch_array();
            $conn->close();
            return $row[0];
        }
        $conn->close();
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
    $version = shell_exec('composer --version 2>&1');
    if ($version && strpos($version, 'Composer version') !== false) {
        preg_match('/Composer version ([^\s]+)/', $version, $matches);
        return $matches[1] ?? 'Unknown';
    }
    return null;
}

function getGitRepositories() {
    $repos = [];
    $root = $_SERVER['DOCUMENT_ROOT'];
    
    // Scan for .git directories
    $directories = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($directories as $dir) {
        if ($dir->isDir() && $dir->getFilename() === '.git') {
            $repoPath = dirname($dir->getPathname());
            $repoName = basename($repoPath);
            
            // Get git info
            $branch = shell_exec('cd ' . escapeshellarg($repoPath) . ' && git branch --show-current 2>&1');
            $status = shell_exec('cd ' . escapeshellarg($repoPath) . ' && git status --porcelain 2>&1');
            
            $repos[] = [
                'name' => $repoName,
                'path' => $repoPath,
                'branch' => trim($branch) ?: 'main',
                'status' => empty(trim($status)) ? 'Clean' : 'Modified'
            ];
        }
    }
    
    return $repos;
}

// Build response data
$response = [
    'system' => [
        'hostname' => gethostname(),
        'os' => PHP_OS_FAMILY,
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'uptime' => formatUptime(getUptime()),
        'disk' => getDiskInfo()
    ],
    'services' => [
        'php' => [
            'name' => 'PHP',
            'version' => PHP_VERSION,
            'status' => 'online'
        ],
        'apache' => [
            'name' => 'Apache',
            'version' => apache_get_version() ?: 'Unknown',
            'status' => apache_get_version() ? 'online' : 'offline'
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
    'repositories' => getGitRepositories()
];

echo json_encode($response, JSON_PRETTY_PRINT);