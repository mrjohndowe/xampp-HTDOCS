<!-- Created by Ada -->

<!DOCTYPE html>
<html>

<head>
    <title>Baby Game</title>
    <link rel="stylesheet" href="assets/css/styles.css" />
</head>

<body>

    <div id="phone">
        <div id="header">MY BABY 👶 Day 1</div>
        <div id="stats">
            <div>HUNGER<div class="bar">
                    <div id="hungerFill" class="fill"></div>
                </div>
            </div>
            <div>HAPPY<div class="bar">
                    <div id="happyFill" class="fill"></div>
                </div>
            </div>
            <div>SLEEP<div class="bar">
                    <div id="sleepFill" class="fill"></div>
                </div>
            </div>
        </div>
        <div id="babyZone">
            <div id="bubble">Ask me something! 👇</div>
            <div id="baby">👶</div>
        </div>
        <div id="chat"></div>
        <div id="controls">
            <button class="btn" id="feed" onclick="feed()">🍼 FEED</button>
            <button class="btn" id="play" onclick="play()">🎈 PLAY</button>
            <button class="btn" id="sleepBtn" onclick="sleepBaby()">😴 SLEEP</button>
        </div>
        <div id="questions">
            <button class="qbtn" onclick="ask(0)">Are you hungry?</button>
            <button class="qbtn" onclick="ask(1)">Do you love me?</button>
            <button class="qbtn" onclick="ask(2)">Want to play?</button>
            <button class="qbtn" onclick="ask(3)">Are you sleepy?</button>
            <button class="qbtn" onclick="ask(4)">What's your name?</button>
            <button class="qbtn" onclick="ask(5)">Are you happy?</button>
        </div>

        <div id="dead" class="hidden">
            <h1>ARE YOU <span>SERIOUS?</span></h1>
            <p id="deadReason">you let your baby starve to death.</p>
            <br><br>
            <button onclick="location.reload()"
                style="background:white;color:black;padding:14px 28px;border-radius:30px;border:none;font-weight:900;font-size:14px;cursor:pointer">TRY
                AGAIN</button>
        </div>

    </div>

<script src="assets/js/app.js"></script>
</body>

</html>
