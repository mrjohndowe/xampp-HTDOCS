<?php
declare(strict_types=1);

$scriptUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$page = $_GET['p'] ?? '';
$page = is_string($page) ? $page : '';


$cssDIR = '../.global/assets/css';
$files = scandir($cssDIR);
$mainFile = '/index.php';
if(!$page){
    foreach($files as $file){
        if($file == '.' || $file == '..' || $file == 'style2.css' || $file == 'style.css') continue;
        $href = $cssDIR . '/' . rawurlencode($file) . '?v=' . filemtime($cssDIR . '/' . $file);
        echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;

    }
}

$apps = [];

foreach (new DirectoryIterator(__DIR__) as $entry) {
    if ($entry->isDot() || !$entry->isDir()) {
        continue;
    }

    $folder = $entry->getFilename();
    $appIndex = $entry->getPathname() . DIRECTORY_SEPARATOR . 'index.php';

    if (is_file($appIndex)) {
        $apps[$folder] = [
            'index' => $appIndex,
            'directory' => $entry->getPathname(),
        ];
    }
}

if ($page !== '' && isset($apps[$page])) {
    $cssDir = $apps[$page]['directory'] . DIRECTORY_SEPARATOR . 'css';
    if (!isset($_GET['api']) && !isset($_GET['autosave']) && is_dir($cssDir)) {
        $cssFiles = scandir($cssDir);
        if ($cssFiles === false) {
            throw new RuntimeException('Unable to read the app CSS directory.');
        }

        $cssUrl = $scriptUrl . '/' . rawurlencode($page) . '/css';
        foreach ($cssFiles as $file) {
            if ($file === '.' || $file === '..' ||
                strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'css') {
                continue;
            }

            $href = $cssUrl . '/' . rawurlencode($file);
            $href .= '?v=' . filemtime($cssDir . DIRECTORY_SEPARATOR . $file);
            echo '<link rel="stylesheet" href="' .
                htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
        }
    }

    include $apps[$page]['index'];
    exit;
}

?>
<body>

<div class="container">
    <h1>Joke Form Links</h1>
    <hr>
<?php
foreach ($apps as $folder => $app) {
    $referenceFile = is_file($app['directory'] . DIRECTORY_SEPARATOR . 'reference.svg')
        ? 'reference.svg'
        : 'reference.jpg';
    $referenceImage = $app['directory'] . DIRECTORY_SEPARATOR . $referenceFile;
    if (!is_file($referenceImage)) {
        continue;
    }

    $imageUrl = $scriptUrl . '/' . rawurlencode($folder) . '/' . $referenceFile;
    $appUrl = $scriptUrl . '/' . rawurlencode($folder) . '/';
    echo '<a href="' . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . '">';
    echo '<img style="width:25%;" src="' .
        htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' .
        htmlspecialchars($folder, ENT_QUOTES, 'UTF-8') . '"></a>' . PHP_EOL;
}


?>
</div>
</body>
