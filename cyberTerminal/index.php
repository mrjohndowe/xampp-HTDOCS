<!-- Created by {------ANKIT ------} -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyber Terminal v2.5</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body data-theme="dark">
    <div id="terminal-container">
        <div id="output-window">
        </div>

        <div id="input-bar">
            <span class="prompt-text">OPERATOR@CYBER-V2.5 &gt;</span>
            <input type="text" id="command-input" autofocus autocapitalize="off" autocomplete="off" autocorrect="off">
            <span id="cursor">█</span>
        </div>
    </div>

    <div id="background-fx" class="matrix-hidden"></div>



    <script src="assets/js/app.js?v=<?= rand(1, 999999) ?>"></script>
</body>

</html>
