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
    include $apps[$page]['index'];
    exit;
}

?>
<body>

<?php require __DIR__ . '/navbar.php'; ?>

<div class="container">
    <h1 id="forms">Joke Form Links</h1>
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
    $appUrl = $scriptUrl . '/index.php?p=' . rawurlencode($folder);
    echo '<a href="' . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . '">';
    echo '<img style="width:25%;" src="' .
        htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' .
        htmlspecialchars($folder, ENT_QUOTES, 'UTF-8') . '"></a>' . PHP_EOL;
}


?>
</div>
</body>
