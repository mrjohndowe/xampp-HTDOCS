<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>myLocalHost</title>

    <link rel="stylesheet" href="assets/css/theme.css?v=<?= getVersionNumber() ?>">
    <link rel="stylesheet" href="assets/css/layout.css?v=<?= getVersionNumber() ?>">
    <link rel="stylesheet" href="assets/css/sidebar.css?v=<?= getVersionNumber() ?>">
    <link rel="stylesheet" href="assets/css/widgets.css?v=<?= getVersionNumber() ?>">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= getVersionNumber() ?>">

</head>

<body>

    <div id="app">

        <aside id="sidebar">

            <div class="logo">

                myLocalHost

            </div>

            <nav>

                <button class="nav active">🏠 Dashboard</button>

                <button class="nav">📂 Projects</button>

                <button class="nav">🌐 Websites</button>

                <button class="nav">🗄 Databases</button>

                <button class="nav">🌿 Git</button>

                <button class="nav">💻 Terminal</button>

                <button class="nav">🤖 AI</button>

                <button class="nav">📜 Logs</button>

                <button class="nav">⚙ Settings</button>

            </nav>

        </aside>

        <main>

            <header>

                <h1>Dashboard</h1>

                <input
                    id="search"
                    type="search"
                    placeholder="Search... (Ctrl+K)">

            </header>

            <section id="dashboardGrid">

            </section>

        </main>

    </div>

    <script type="module" src="frontend/app.js?v=<?= getVersionNumber() ?>"></script>

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
