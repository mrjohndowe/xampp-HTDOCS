<?php
require_once __DIR__ . '/config.php';
function page_header(string $title, string $active=''): void { ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(app_url('assets/css/styles.css')) ?>">
<script>window.NOVEL_APP_BASE=<?= json_encode(app_base()) ?>;window.NOVEL_CSRF=<?= json_encode(csrf_token()) ?>;</script>
</head><body>
<div class="site-splash" id="siteSplash" aria-live="polite">
  <div class="splash-image"></div>
  <div class="splash-library" aria-hidden="true">
    <div class="library-gif-scene"></div>
  </div>
  <div class="splash-panel">
    <img src="<?= e(app_url('assets/img/logo.png')) ?>" alt="Novel library logo" class="splash-logo">
    <div class="splash-overline">PRIVATE NOVEL LIBRARY</div>
    <h1>Opening the shelves</h1>
    <p id="splashMessage">Preparing your library…</p>
    <div class="loading-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="loading-bar" id="loadingBar"></div></div>
    <div class="loading-meta"><span id="loadingStage">Checking the collection</span><strong id="loadingPercent">0%</strong></div>
  </div>
</div>
<header class="topbar">
  <a class="brand" href="<?= e(app_url('index.php')) ?>"><img src="<?= e(app_url('assets/img/logo.png')) ?>" alt=""><span>Private Novel Library</span></a>
  <nav><a class="<?= $active==='library'?'active':'' ?>" href="<?= e(app_url('index.php')) ?>">Library</a><a href="<?= e(app_url('admin/index.php')) ?>">Admin</a></nav>
</header><main class="site-main">
<?php if ($m=flash('success')): ?><div class="notice success"><?= e($m) ?></div><?php endif; ?>
<?php if ($m=flash('error')): ?><div class="notice error"><?= e($m) ?></div><?php endif; ?>
<?php }
function page_footer(bool $reader=false): void { ?></main><footer>Private Novel Library · XAMPP Edition</footer>
<script src="<?= e(app_url('assets/js/site.js')) ?>"></script>
<?php if ($reader): ?><script type="module" src="<?= e(app_url('assets/js/library.js')) ?>"></script><?php endif; ?>
</body></html><?php }
