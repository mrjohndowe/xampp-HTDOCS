<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$showSplash = !isset($_COOKIE['firstTimer']);
if ($showSplash) {
    setcookie('firstTimer', '0', time() + (10 * 365 * 24 * 60 * 60), '/');
}

$settings = loadSettings();
$folders = $settings['folders'] ?? [];
$ffmpegPath = (string) ($settings['ffmpegPath'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $requested = $_POST['folders'] ?? [];
        $valid = cleanFolders(is_array($requested) ? $requested : []);
        if (!$valid) throw new RuntimeException('Add at least one folder that exists on this computer.');
        $ffmpegPath = trim((string) ($_POST['ffmpegPath'] ?? $ffmpegPath));
        if ($ffmpegPath !== '' && !is_file($ffmpegPath)) throw new RuntimeException('The FFmpeg path does not point to an existing file.');
        $settings = ['folders'=>$valid,'ffmpegPath'=>$ffmpegPath,'autoNext'=>!empty($settings['autoNext']),'startMuted'=>!empty($settings['startMuted'])];
        saveSettings($settings);
        buildCatalog($valid);
        header('Location: index.php');
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        $folders = is_array($_POST['folders'] ?? null) ? $_POST['folders'] : [];
    }
}

if (isset($_GET['rescan']) && $folders) {
    buildCatalog(cleanFolders($folders));
    header('Location: index.php');
    exit;
}

$existingCatalog = catalog();
if ($folders && !$existingCatalog) $existingCatalog = buildCatalog(cleanFolders($folders));
$videos = array_map('publicVideo', $existingCatalog);
$needsSetup = !$folders;
$categories = categoriesList();
$removedVideos = removedVideos();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="dark">
  <title><?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/app.css">
  <link rel="stylesheet" href="assets/css/folder-browser.css">
  <link rel="stylesheet" href="assets/css/player-layout.css">
  <link rel="stylesheet" href="assets/css/thumbnails.css">
  <link rel="stylesheet" href="assets/css/ffmpeg.css">
  <link rel="stylesheet" href="assets/css/player-settings.css">
  <link rel="stylesheet" href="assets/css/uniform-controls.css">
  <link rel="stylesheet" href="assets/css/categories.css">
  <link rel="stylesheet" href="assets/css/category-manager.css">
  <link rel="stylesheet" href="assets/css/home-icon.css">
  <link rel="stylesheet" href="assets/css/removed-videos.css">
  <link rel="stylesheet" href="assets/css/video-information.css">
  <link rel="stylesheet" href="assets/css/splash-screen.css">
  <link rel="stylesheet" href="assets/css/pagination.css">
</head>
<body>
  <?php if ($showSplash): ?>
<div class="app-splash" id="appSplash" role="status" aria-live="polite">
  <div class="splash-content"><img src="assets/img/dowe-video-wall-logo.png" alt="" class="splash-logo"><p class="splash-name"><?= htmlspecialchars(APP_NAME) ?></p><div class="splash-loader" aria-hidden="true"><span></span><span></span><span></span></div><p class="splash-label">Loading your library</p></div>
</div>
<?php endif; ?>
<header class="topbar">
  <a class="brand" href="index.php"><img src="assets/img/dowe-video-wall-logo.png" alt="" class="brand-logo"><span><?= htmlspecialchars(APP_NAME) ?></span></a>
  <?php if (!$needsSetup): ?>
  <div class="header-actions">
    <input id="search" class="search" type="search" placeholder="Search your library…" autocomplete="off">
    <button class="button subtle" id="openSettings" type="button">Folders</button>
    <a class="button subtle" href="admin.php">Admin</a>
    <a class="button primary" href="?rescan=1">Rescan</a>
  </div>
  <?php endif; ?>
</header>

<?php if ($needsSetup): ?>
<main class="setup-page">
  <section class="setup-card">
    <span class="eyebrow">FIRST-RUN SETUP</span>
    <h1>Where are your videos?</h1>
    <p>Add every folder you want included. Windows paths such as <code>D:\Videos</code> and network paths are supported when Apache has permission to read them.</p>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" id="folderForm">
      <div id="folderFields"><div class="folder-row"><input class="folder-path" name="folders[]" placeholder="D:\Videos" autocomplete="off" spellcheck="false" required><button type="button" class="browse-folder">Browse</button><button type="button" class="remove" aria-label="Remove">×</button></div></div>
      <label class="ffmpeg-field"><span>FFmpeg executable <small>(optional, your Dowe LanCaster copy works)</small></span><input name="ffmpegPath" value="<?= htmlspecialchars($ffmpegPath) ?>" placeholder="Full path to ffmpeg.exe" autocomplete="off" spellcheck="false"></label>
      <button type="button" class="button subtle" id="addFolder">+ Add another folder</button>
      <button type="submit" class="button primary save">Build my video wall</button>
    </form>
  </section>
</main>
<?php else: ?>
<main class="library-page">
  <div class="library-heading"><div><span class="eyebrow">MY LIBRARY</span><h1>Video Wall</h1></div><div class="library-filters"><label for="categoryFilter">Category</label><select id="categoryFilter"><option value="">All categories</option><option value="Uncategorized">Uncategorized</option><?php foreach ($categories as $category): ?><option value="<?= htmlspecialchars((string) $category['name']) ?>"><?= htmlspecialchars((string) $category['name']) ?></option><?php endforeach; ?></select><label for="itemsPerPage">Items per page</label><select id="itemsPerPage"><option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="all">ALL</option></select><span id="resultCount"><?= count($videos) ?> videos</span></div></div>
  <section class="video-grid" id="videoGrid"></section>
  <div class="empty-state" id="emptyState"><h2>No videos found</h2><p>Add another folder or rescan your current folders.</p></div>
  <div class="pagination" id="pagination"><button class="button subtle" id="prevPage" disabled>← Previous</button><span id="pageInfo">Page 1 of 1</span><button class="button subtle" id="nextPage" disabled>Next →</button></div>
</main>

<div class="modal" id="settingsModal" aria-hidden="true">
  <div class="modal-backdrop" data-close></div>
  <section class="settings-panel" role="dialog" aria-modal="true" aria-labelledby="settingsTitle">
    <button class="modal-close" type="button" data-close>×</button>
    <span class="eyebrow">LIBRARY SETTINGS</span><h2 id="settingsTitle">Video folders</h2>
    <form method="post" id="settingsForm">
      <div id="settingsFields">
      <?php foreach ($folders as $folder): ?><div class="folder-row"><input class="folder-path" name="folders[]" value="<?= htmlspecialchars((string) $folder) ?>" title="<?= htmlspecialchars((string) $folder) ?>" autocomplete="off" spellcheck="false" required><button type="button" class="browse-folder">Browse</button><button type="button" class="remove">×</button></div><?php endforeach; ?>
      </div>
      <label class="ffmpeg-field"><span>FFmpeg executable <small>(optional)</small></span><input name="ffmpegPath" value="<?= htmlspecialchars($ffmpegPath) ?>" placeholder="Full path to ffmpeg.exe" autocomplete="off" spellcheck="false"></label>
      <button type="button" class="button subtle" id="addSettingsFolder">+ Add folder</button>
      <button type="submit" class="button primary save">Save and rebuild</button>
    </form>
    <div class="category-manager"><h3>My categories</h3><div class="category-add"><input id="newCategoryName" placeholder="New category name" maxlength="80"><button class="button primary" id="addCategory" type="button">Add</button></div><div id="categoryList"><?php foreach($categories as$category): ?><div class="category-row" data-id="<?= (int)$category['id'] ?>"><span><?= htmlspecialchars((string)$category['name']) ?></span><button type="button" class="delete-category">Delete</button></div><?php endforeach; ?></div></div>
    <div class="removed-manager"><h3>Removed videos <span><?= count($removedVideos) ?></span></h3><p>Removed videos stay in their original folders and can be restored here.</p><div class="removed-list"><?php if(!$removedVideos): ?><div class="removed-empty">No removed videos.</div><?php endif; ?><?php foreach($removedVideos as$removed): ?><div class="removed-row" data-id="<?= htmlspecialchars((string)$removed['id']) ?>"><div><strong><?= htmlspecialchars((string)$removed['name']) ?></strong><small title="<?= htmlspecialchars((string)$removed['path']) ?>"><?= htmlspecialchars((string)$removed['path']) ?></small></div><button class="button subtle restore-video" type="button">Restore</button></div><?php endforeach; ?></div></div>
  </section>
</div>

<div class="theater" id="theater" aria-hidden="true">
  <div class="theater-head"><div><h2 id="playerTitle"></h2><p id="playerMeta"></p></div><div class="player-actions"><button class="button subtle" id="homePlayer" type="button"><span class="home-icon">⌂</span><span>Home</span></button><button class="button subtle" id="nextPlayer" type="button">Next ▶</button><button class="button subtle" id="renameCurrent" type="button">✎ Rename</button><button class="button subtle danger" id="deactivateCurrent" type="button">Deactivate video</button><button class="button subtle" id="fullscreenPlayer" type="button">⛶ Full screen</button><button id="closePlayer" type="button" aria-label="Close">×</button></div></div>
  <div class="player-stage"><video id="player" controls autoplay playsinline></video></div>
  <div class="video-information" id="videoInformation"><div><strong>Actors / Characters</strong><span id="playerActors">Not added</span></div><div><strong>Video info / notes</strong><span id="playerNotes">Not added</span></div><div><strong>Publish date</strong><span id="playerPublishDate">Not added</span></div><div><strong>Production</strong><span id="playerProduction">Not added</span></div></div>
  <div class="filmstrip-wrap"><button class="strip-arrow" id="stripLeft">‹</button><div class="filmstrip" id="filmstrip"></div><button class="strip-arrow" id="stripRight">›</button></div>
</div>

<?php endif; ?>

<div class="modal" id="folderBrowser" aria-hidden="true">
  <div class="modal-backdrop" data-browser-close></div>
  <section class="settings-panel folder-browser" role="dialog" aria-modal="true" aria-labelledby="browserTitle">
    <button class="modal-close" type="button" data-browser-close>×</button>
    <span class="eyebrow">LOCAL COMPUTER</span><h2 id="browserTitle">Choose a video folder</h2>
    <div class="browser-path"><strong>Full path:</strong><code id="browserPath">Select a drive</code></div>
    <div class="browser-toolbar"><button type="button" class="button subtle" id="browserUp">↑ Up</button><button type="button" class="button subtle" id="browserDrives">Drives</button></div>
    <div class="browser-list" id="browserList"></div>
    <div class="browser-error" id="browserError"></div>
    <button type="button" class="button primary save" id="selectFolder" disabled>Use this folder</button>
  </section>
</div>

<script>window.VIDEO_LIBRARY = <?= json_encode($videos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;window.VIDEO_CATEGORIES=<?= json_encode($categories,JSON_UNESCAPED_UNICODE) ?>;window.PLAYER_SETTINGS=<?= json_encode(['autoNext'=>(bool)$settings['autoNext'],'startMuted'=>(bool)$settings['startMuted']]) ?>;</script>
<script src="assets/js/app.js"></script>
<script src="assets/js/player-features.js"></script>
<script src="assets/js/custom-categories.js"></script>
<script src="assets/js/splash-screen.js"></script>
</body>
</html>
