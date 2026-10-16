<?php
declare(strict_types=1);

$navScriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$navScriptDirectory = dirname($navScriptName);
$navDirectoryName = basename($navScriptDirectory);
$navRequestedApp = isset($_GET['p']) && is_string($_GET['p']) ? $_GET['p'] : '';
$navIsAppPage = $navRequestedApp !== '' &&
    is_file(__DIR__ . DIRECTORY_SEPARATOR . $navRequestedApp . DIRECTORY_SEPARATOR . 'index.php');
$navDirectApp = $navDirectoryName !== '.' &&
    is_file(__DIR__ . DIRECTORY_SEPARATOR . $navDirectoryName . DIRECTORY_SEPARATOR . 'index.php');
$navCurrentApp = $navIsAppPage ? $navRequestedApp : ($navDirectApp ? $navDirectoryName : '');
$navRootUrl = $navDirectApp && !$navIsAppPage ? dirname($navScriptDirectory) : $navScriptDirectory;
$navRootUrl = $navRootUrl === '.' ? '' : rtrim($navRootUrl, '/');

$navApps = [];
foreach (new DirectoryIterator(__DIR__) as $navEntry) {
    if ($navEntry->isDot() || !$navEntry->isDir()) {
        continue;
    }

    $navAppIndex = $navEntry->getPathname() . DIRECTORY_SEPARATOR . 'index.php';
    if (is_file($navAppIndex)) {
        $navApps[$navEntry->getFilename()] = $navEntry->getFilename();
    }
}
ksort($navApps, SORT_NATURAL | SORT_FLAG_CASE);
?>
<style>
    .joke-nav {
        position: sticky;
        top: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        width: 100%;
        margin: -18px 0 16px;
        padding: 10px max(20px, calc((100% - 1180px) / 2));
        border-bottom: 1px solid #dedbe4;
        background: #fff;
        box-shadow: 0 2px 10px rgb(35 25 50 / 8%);
        font-family: Arial, Helvetica, sans-serif;
    }

    .joke-nav a {
        color: #30283d;
        text-decoration: none;
    }

    .joke-nav-brand {
        flex: 0 0 auto;
        font-size: 18px;
        font-weight: 700;
    }

    .joke-nav-links {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 7px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .joke-nav-links a {
        display: block;
        padding: 7px 10px;
        border-radius: 7px;
        color: #514761;
        font-size: 13px;
        font-weight: 600;
    }

    .joke-nav-links a:hover,
    .joke-nav-links a:focus-visible,
    .joke-nav-links a[aria-current="page"] {
        background: #e8def5;
        color: #392653;
    }

    @media (max-width: 700px) {
        .joke-nav {
            align-items: flex-start;
            flex-direction: column;
            gap: 7px;
            margin-top: -8px;
            padding: 10px 14px;
        }

        .joke-nav-links {
            justify-content: flex-start;
        }
    }

    @media print {
        .joke-nav {
            display: none !important;
        }
    }
</style>
<nav class="joke-nav" aria-label="Main navigation">
    <a class="joke-nav-brand" href="<?= htmlspecialchars($navRootUrl . '/', ENT_QUOTES, 'UTF-8') ?>">Joke Forms</a>
    <ul class="joke-nav-links">
        <li>
            <a href="<?= htmlspecialchars($navRootUrl . '/', ENT_QUOTES, 'UTF-8') ?>"<?= $navCurrentApp === '' ? ' aria-current="page"' : '' ?>>Home</a>
        </li>
        <?php foreach ($navApps as $navFolder): ?>
            <?php $navHref = $navRootUrl . '/index.php?p=' . rawurlencode($navFolder); ?>


            <li>
                <a href="<?= htmlspecialchars($navHref, ENT_QUOTES, 'UTF-8') ?>"<?= $navFolder === $navCurrentApp ? ' aria-current="page"' : '' ?>>
                    <?= getFolderName($navFolder) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>


<?php

function getFolderName($data){
    $names = [
        'bfApp' => 'Boyfriend Apps',
        'cuddlebuddy' => 'Cuddle Buddy',
        'gfApp' => 'Girlfriend Apps',
        'hurtFeelings' => 'Hurt Feelings Report',
        'rfApp' => 'Real Friend Apps',
        'piece_of_ass_app' => 'Sex Apps',
    ];

    return $names[$data];

}
