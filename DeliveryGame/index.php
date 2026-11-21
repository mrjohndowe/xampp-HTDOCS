<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#101914">
<link rel="manifest" href="manifest.webmanifest">
<link rel="icon" href="icon-192.png">
<link rel="stylesheet" href="style.css">
<script type="module" src="app.js"></script>
<title>Delivery Game</title>
</head>
<body>
<main>
<label class="park"><input id="parked" type="checkbox" autocomplete="off"> We're safely parked</label>
<div id="scoresheet" hidden>
<header><h1>Delivery Driver Challenge</h1><p>John vs. Sierra</p></header>
<section class="drive" aria-label="Driver rotation">
<p id="order"></p><h2 id="driver"></h2><p id="next"></p>
</section>
<section class="totals" aria-label="Scores">
<div>John<strong id="john">150</strong></div>
<div>Sierra<strong id="sierra">150</strong></div>
</section>

<p id="status" role="status">Check the box to unlock scoring.</p>
<div class="actions"><button id="undo">Undo</button><button id="report">Shift report</button></div>
<section aria-labelledby="rules-title">
<h2 id="rules-title">Rules &amp; scoring</h2>
<p>Have fun. Points don’t actually matter. Start at 150 each.</p>
<ol>
<li>One person drives. The other navigates and handles the phone.</li>
<li>Switch drivers every 3 completed orders. John starts.</li>
<li>Once safely parked, record points for the order before tapping Complete delivery.</li>
<li>No arguing over points.</li>
<li>After the final order, the highest score wins the title of Delivery Champion.</li>
</ol>
<h3>Bonuses</h3><div id="bonuses" class="grid"></div>
<h3>Penalties</h3><p class="hint">Never penalize necessary emergency braking.</p><div id="penalties" class="grid"></div>
<h3>Optional chaos rule</h3><div id="chaos" class="grid"></div>
</section>
<button id="complete" class="primary" type="button">Complete delivery (+10)</button>
</div>
</main>
<dialog id="shift" aria-labelledby="shift-title">
<h2 id="shift-title">Shift report</h2><p id="summary"></p>
<div id="history"></div>
<div class="actions"><button id="reset">Reset shift</button><button id="close">Close</button></div>
</dialog>
<dialog id="editor" aria-labelledby="edit-title">
<form id="edit-form"><h2 id="edit-title">Edit score</h2>
<label>Player<select id="edit-player"><option>John</option><option>Sierra</option></select></label>
<label>Reason<select id="edit-reason"></select></label>
<div class="actions"><button type="button" id="cancel">Cancel</button><button type="submit">Confirm edit</button></div>
</form>
</dialog>
<dialog id="confirmation" aria-labelledby="confirm-title">
<h2 id="confirm-title">Reset this shift?</h2><p>Both scores return to 150 and John starts again at order 1.</p>
<div class="actions"><button id="keep">Cancel</button><button id="erase">Confirm reset</button></div>
</dialog>
</body></html>




