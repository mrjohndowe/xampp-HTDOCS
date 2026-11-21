<?php
// Vehicle Tracker - shared layout shell
declare(strict_types = 1);

$pageTitle = $pageTitle ?? 'Vehicle Tracker';
$activePage = $activePage ?? 'dashboard';
$flash = $_SESSION['flash'] ?? null;
if ($flash) unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?> - Vehicle Tracker</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="assets/js/app.js" defer></script>
</head>
<body>
<header class="topbar">
  <div class="topbar-inner">
    <a href="index.php" class="brand">
      <span class="mark">VT</span>
      <span><?= h($_SESSION['vt_site_name'] ?? 'Vehicle Tracker') ?></span>
    </a>
    <button class="mobile-menu-toggle" aria-label="Open menu">&#9776;</button>
    <nav class="nav">
      <a href="index.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
      <a href="vehicles.php" class="<?= $activePage === 'vehicles' ? 'active' : '' ?>">Vehicles</a>
      <a href="trips.php" class="<?= $activePage === 'trips' ? 'active' : '' ?>">Trips</a>
      <a href="engines.php" class="<?= $activePage === 'engines' ? 'active' : '' ?>">Engines</a>
      <a href="expenses.php" class="<?= $activePage === 'expenses' ? 'active' : '' ?>">Expenses</a>
      <a href="invoices.php" class="<?= $activePage === 'invoices' ? 'active' : '' ?>">Invoices</a>
      <a href="settings.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>">Settings</a>
      <div class="theme-toggle">
        <button id="themeToggle" type="button">Dark mode</button>
      </div>
    </nav>
  </div>
</header>

<div class="container">
  <?php if ($flash): ?>
    <div class="flash <?= $flash['type'] ?>"><b><?= h(ucfirst($flash['type'])) ?>:</b> <?= h($flash['msg']) ?></div>
  <?php endif; ?>

  <?= $content ?? '' ?>
</div>

<footer style="color:var(--muted);font-size:12px;padding:26px 20px;border-top:1px solid var(--border);max-width:1200px;margin:0 auto;">
  Vehicle Tracker &middot; <a href="download.php?file=vehicle_tracker.py">Download Python desktop app</a>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const btn = document.querySelector('.mobile-menu-toggle');
  const nav = document.querySelector('.nav');
  if (btn && nav) {
    btn.addEventListener('click', function () {
      const open = nav.style.display === 'flex';
      nav.style.display = open ? '' : 'flex';
      nav.style.flexDirection = 'column';
      nav.style.position = 'absolute';
      nav.style.top = '52px';
      nav.style.left = '0';
      nav.style.right = '0';
      nav.style.background = 'var(--panel)';
      nav.style.borderTop = '1px solid var(--border)';
      nav.style.padding = '12px';
      nav.style.gap = '0';
    });
  }
});
</script>
</body>
</html>
