<?php

declare(strict_types=1);

session_start();
ini_set("display_errors", 1);

const APP_NAME = 'MrJohnDowe';
// const APP_VERSION = getVersionNumber();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title><?= APP_NAME ?></title>

    <link
        rel="stylesheet"
        href="assets/css/app.css?v=<?= getVersionNumber() ?>">

</head>

<body>

    <div id="app">

        <aside id="sidebar">

            <div class="logo">

                <span class="logo-icon">⚡</span>

                <span class="logo-text">

                    MrJohnDowe

                </span>

            </div>

            <nav>

                <a class="active" href="#">Dashboard</a>

                <a href="myLocalhost.php">File Manager</a>

                <a href="#">Websites</a>

                <a href="#">Databases</a>

                <a href="#">Git</a>

                <a href="#">Terminal</a>

                <a href="#">Settings</a>

            </nav>

        </aside>

        <main>

            <header id="dashboardHeader">

                <div>

                    <h1>Dashboard</h1>

                    <small>

                        Local Development Environment

                    </small>

                </div>

                <div class="profile">

                    Administrator

                </div>

            </header>

            <section class="hero">

                <input

                    id="search"

                    type="search"

                    placeholder="Search projects, files, commands...">

            </section>

            <section

                id="dashboardGrid"

                class="grid">

            </section>

        </main>

    </div>

    <script type="module" src="assets/js/app.js?v=<?= getVersionNumber() ?>"></script>

</body>

</html>

<?php
function getVersionNumber()
{
    $v1 = rand(1, 99);
    $v2 = rand(1, 99);
    $v3 = rand(1, 99);
    $versionNumber = $v1 . '.' . $v2 . '.' . $v3;

    return $versionNumber;
}
function console_log($data)
{

    $display = '<script>';
    $display .= 'console.log(' . json_encode($data) . ');';
    $display .= '</script>';
    echo $display;
}
$versionNumber = getVersionNumber();
file_put_contents('storage/logs/oldVersion.log', 'Version Number: ' . date('m/d/Y H:i:s') . ' ' . $versionNumber . PHP_EOL, FILE_APPEND);
console_log('Version Number: v' . $versionNumber);
// console_log('Old Version Number: v' . $oldVersion);

?>