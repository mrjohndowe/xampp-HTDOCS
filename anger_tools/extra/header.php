<?php
$page = isset($_GET['p']) ? $_GET['p'] : '';

require_once 'liteConnect.php';
require_once 'functions.php';

switch ($page) {
	case "anger":
		$angerActive = 'class="active"';
		$publicAngerActive = '';
		$treatmentActive = '';
		$homeActive = '';
		$allLogsActive = '';
		break;
	case "publicanger":
		$angerActive = '';
		$publicAngerActive = 'class="active"';
		$treatmentActive = '';
		$homeActive = '';
		$allLogsActive = '';
		break;
	case "viewLog":
		$angerActive = '';
		$publicAngerActive = '';
		$treatmentActive = '';
		$homeActive = '';
		$allLogsActive = '';
		break;
	case "treatment":
		$angerActive = '';
		$publicAngerActive = '';
		$treatmentActive = 'class="active"';
		$homeActive = '';
		$allLogsActive = '';
		break;
	case "allLogs":
		$angerActive = '';
		$publicAngerActive = '';
		$treatmentActive = '';
		$homeActive = '';
		$allLogsActive = 'class="active"';
		break;
	default:
		$angerActive = '';
		$publicAngerActive = '';
		$homeActive = 'class="active"';
		$treatmentActive = '';
		$allLogsActive = '';
}

$time = date('F jS, Y');
?>
<!DOCTYPE HTML>

<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="extra/css/admin.css" rel="stylesheet">
	<link href="extra/css/fontawesome.css" rel="stylesheet">
	<link href="extra/css/custom.css" rel="stylesheet">
	<link href="extra/css/modern.css" rel="stylesheet">
	<link href="extra/css/am-modern.css" rel="stylesheet">
	<script src="extra/js/am-nav.js" defer></script>
	<script src="extra/js/slider.js" type="text/javascript"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<header class="am-nav">
  <div class="am-nav-inner">

    <a class="am-brand" href="." aria-label="Anger Tools — Home">
      <svg class="am-brand-mark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1L12 20l7.7-6.6 1.1-1a5.5 5.5 0 0 0 0-7.8z"/>
        <path d="M4 12h3.5l1.5-3 3 6 1.5-3H16"/>
      </svg>
      <span class="am-brand-name">Anger<span class="am-brand-accent">Tools</span></span>
    </a>

    <button class="am-nav-toggle" type="button" aria-expanded="false" aria-controls="am-nav-menu" aria-label="Toggle menu">
      <span class="am-toggle-bar" aria-hidden="true"></span>
      <span class="am-toggle-bar" aria-hidden="true"></span>
      <span class="am-toggle-bar" aria-hidden="true"></span>
    </button>

    <nav aria-label="Main navigation">
      <ul class="am-nav-menu" id="am-nav-menu">
        <li><a class="am-nav-link am-nav-link--home" href=".">Home</a></li>
        <?php if (isset($_SESSION['user'])): ?>
          <li><span class="am-nav-link" aria-label="Signed in user"><?= htmlspecialchars(strtoupper(display($_SESSION['user'])), ENT_QUOTES, 'UTF-8') ?></span></li>
          <?php endif; ?>
        <li><a class="am-nav-link" href="?p=treatment">Treatment Tool</a></li>
        <li><a class="am-nav-link" href="?p=anger">Anger Log</a></li>
        <li><a class="am-nav-link" href="?p=publicanger">Public Anger Log</a></li>
        <li><a class="am-nav-link" href="?p=allLogs">All Anger Logs</a></li>
        <li class="am-nav-time" title="Timezone: America/Denver"><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></li>
        <?php if (isset($_SESSION['user'])): ?><li><a class="am-nav-link" href="?p=logout">Logout</a></li><?php endif; ?>
      </ul>
    </nav>

  </div>
</header>
