<?php
// API endpoint for system actions
// Handles Explorer, VS Code, Terminal, Website launches

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$path = $input['path'] ?? '';
$name = $input['name'] ?? '';

$response = ['success' => false, 'message' => ''];

switch ($action) {
    case 'explorer':
        $response = launchExplorer($path);
        break;
        
    case 'vscode':
        $response = launchVSCode($path);
        break;
        
    case 'terminal':
        $response = launchTerminal($path);
        break;
        
    case 'website':
        $response = launchWebsite($name);
        break;
        
    default:
        $response = ['success' => false, 'message' => 'Unknown action'];
}

echo json_encode($response);

function launchExplorer($path) {
    if (empty($path) || !is_dir($path)) {
        return ['success' => false, 'message' => 'Invalid path: ' . $path];
    }
    
    if (PHP_OS_FAMILY === 'Windows') {
        // Convert to Windows path format
        $path = str_replace('/', '\\', $path);
        $command = 'explorer "' . $path . '"';
        $result = pclose(popen('start /B ' . $command, 'r'));
        return ['success' => true, 'message' => 'Explorer opened: ' . $path];
    } else {
        $command = 'xdg-open "' . $path . '"';
        shell_exec($command);
        return ['success' => true, 'message' => 'File manager opened'];
    }
}

function launchVSCode($path) {
    if (empty($path) || !is_dir($path)) {
        return ['success' => false, 'message' => 'Invalid path: ' . $path];
    }
    
    // Convert to Windows path format
    if (PHP_OS_FAMILY === 'Windows') {
        $path = str_replace('/', '\\', $path);
        
        // Try to find VS Code executable
        $vscodePaths = [
            getenv('LOCALAPPDATA') . '\\Programs\\Microsoft VS Code\\Code.exe',
            getenv('PROGRAMFILES') . '\\Microsoft VS Code\\Code.exe',
            getenv('PROGRAMFILES(X86)') . '\\Microsoft VS Code\\Code.exe'
        ];
        
        $vscodePath = null;
        foreach ($vscodePaths as $vscodePathCheck) {
            if ($vscodePathCheck && file_exists($vscodePathCheck)) {
                $vscodePath = $vscodePathCheck;
                break;
            }
        }
        
        if ($vscodePath) {
            $command = '"' . $vscodePath . '" "' . $path . '"';
            pclose(popen('start /B ' . $command, 'r'));
            return ['success' => true, 'message' => 'VS Code opened: ' . $path];
        } else {
            // Try using 'code' command if in PATH
            $command = 'code "' . $path . '"';
            $result = shell_exec($command . ' 2>&1');
            if (empty($result) || strpos($result, 'error') === false) {
                return ['success' => true, 'message' => 'VS Code opened via PATH: ' . $path];
            }
        }
        
        return ['success' => false, 'message' => 'VS Code not found in system'];
    } else {
        $command = 'code "' . $path . '"';
        shell_exec($command);
        return ['success' => true, 'message' => 'VS Code opened'];
    }
}

function launchTerminal($path) {
    if (empty($path) || !is_dir($path)) {
        return ['success' => false, 'message' => 'Invalid path: ' . $path];
    }
    
    // Convert to Windows path format
    if (PHP_OS_FAMILY === 'Windows') {
        $path = str_replace('/', '\\', $path);
        
        // Try Windows Terminal first, then fallback to cmd
        $wtPath = getenv('LOCALAPPDATA') . '\\Microsoft\\WindowsApps\\wt.exe';
        if ($wtPath && file_exists($wtPath)) {
            $command = '"' . $wtPath . '" -d "' . $path . '"';
            pclose(popen('start /B ' . $command, 'r'));
            return ['success' => true, 'message' => 'Windows Terminal opened: ' . $path];
        } else {
            $command = 'cmd /k "cd /d "' . $path . '""';
            pclose(popen('start /B ' . $command, 'r'));
            return ['success' => true, 'message' => 'Command Prompt opened: ' . $path];
        }
    } else {
        // Linux terminal
        $terminals = ['gnome-terminal', 'xterm', 'konsole'];
        foreach ($terminals as $terminal) {
            $command = $terminal . ' --working-directory="' . $path . '"';
            shell_exec($command . ' 2>&1');
            return ['success' => true, 'message' => 'Terminal opened'];
        }
        return ['success' => false, 'message' => 'No terminal found'];
    }
}

function launchWebsite($name) {
    if (empty($name)) {
        return ['success' => false, 'message' => 'Invalid website name'];
    }
    
    $url = 'http://localhost/' . $name;
    return ['success' => true, 'message' => 'Opening website', 'url' => $url];
}