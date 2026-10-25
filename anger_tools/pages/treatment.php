<?php
if (empty($_SESSION['user'])) { echo 'Please sign in to continue.'; return; }
if (empty($_SESSION['reflection_csrf'])) { $_SESSION['reflection_csrf'] = bin2hex(random_bytes(32)); }
?>
<link rel="stylesheet" href="extra/css/reflection-wizard.css?v=1">
<div id="reflection-app">
<main class="layout"><aside class="intro"><div class="eyebrow">A moment for yourself</div><h1>Pause. Reflect.<br>Move forward.</h1><p>Make space for what happened, how you felt, and what you want to do differently.</p><ol class="steps" id="stepList"></ol><div class="side-note">One question at a time.<br>Every answer matters.<p>Your answers are not saved until you choose a visibility option and submit.</p></div></aside><section><div class="panel"><div id="wizard"><div class="meta"><span id="stepText" aria-live="polite"></span><span class="pill">Guided reflection</span></div><progress id="progress" max="8" value="1" aria-label="Current question"></progress><form id="reflection" novalidate><div id="questions"></div><div class="error" id="error" role="alert"></div><div class="actions"><button class="secondary" id="back" type="button">← Back</button><button class="primary" id="next" type="submit">Continue →</button></div></form></div><div id="success" class="success" hidden tabindex="-1"><div class="success-icon">✓</div><h2 id="successTitle"></h2><p id="successText"></p><button class="primary" id="again" type="button">Start a new reflection</button><p><a id="logsLink" href="?p=anger">View my anger logs →</a></p></div></div><p class="footnote">Take your time. You can go back and change any answer.</p></section></main>
</div>
<script>document.body.dataset.csrf = <?= json_encode($_SESSION['reflection_csrf'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="extra/js/reflection-wizard.js?v=1" defer></script>
