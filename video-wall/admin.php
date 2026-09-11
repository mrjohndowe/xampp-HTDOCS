<?php
    declare (strict_types = 1);
    require_once __DIR__ . '/functions.php';
    $duplicateMessage = '';
    if (isset($_GET['checkDuplicates'])) {
        $settings = loadSettings();
        buildCatalog(cleanFolders($settings['folders'] ?? []));
        $duplicateCount = (int) db()->query('SELECT COUNT(*) FROM videos WHERE duplicate_of IS NOT NULL')->fetchColumn();
        header('Location: admin.php?duplicates=' . $duplicateCount);
        exit;
    }
    if (isset($_GET['duplicates'])) {
        $duplicateMessage = (int) $_GET['duplicates'] . ' duplicate video' . ((int) $_GET['duplicates'] === 1 ? ' was' : 's were') . ' deactivated.';
    }

    $categories    = categoriesList();
    $removedVideos = removedVideos();
    $productions   = productionsList();
?>
<!doctype html>
    <html lang="en">
        <head><meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <meta name="color-scheme" content="dark">
            <title>Admin · <?php echo htmlspecialchars(APP_NAME) ?></title>
            <link rel="stylesheet" href="assets/css/app.css">
            <link rel="stylesheet" href="assets/css/category-manager.css">
            <link rel="stylesheet" href="assets/css/production-manager.css">
            <link rel="stylesheet" href="assets/css/removed-videos.css">
            <link rel="stylesheet" href="assets/css/admin.css">
            <link rel="stylesheet" href="assets/css/duplicates.css">
        </head>
        <body>
            <header class="topbar">
                <a class="brand" href="index.php">
                    <img src="assets/img/dowe-video-wall-logo.png" alt="" class="brand-logo">
                    <span><?php echo htmlspecialchars(APP_NAME) ?></span>
                </a>
                <div class="header-actions">
                    <a class="button subtle" href="admin.php?checkDuplicates=1">Check duplicates</a>
                    <a class="button primary" href="index.php">← Video wall</a>
                </div>
            </header>
                <main class="admin-page">
                    <div class="admin-heading">
                        <span class="eyebrow">ADMIN</span>
                        <h1>Library management</h1>
                        <p>Create categories and restore videos without changing or deleting the original files.</p>
                        <?php if ($duplicateMessage): ?>
                        <div class="duplicate-message">
                            <?php echo htmlspecialchars($duplicateMessage) ?>
                        </div><?php endif; ?>
                    </div>
                    <section class="admin-card category-manager">
                        <h2>My categories</h2>
                        <div class="category-add">
                            <input id="newCategoryName" placeholder="New category name" maxlength="80">
                            <button class="button primary" id="addCategory" type="button">Add category</button>
                        </div>
                        <div id="categoryList">
                            <?php foreach ($categories as $category): ?>
                            <div class="category-row" data-id="<?php echo (int)$category['id'] ?>">
                                <span><?php echo htmlspecialchars((string)$category['name']) ?></span>
                                <button type="button" class="delete-category">Delete</button>
                            </div><?php endforeach; ?>
                        </div>
                    </section>
                    <section class="admin-card removed-manager">
                        <h2>Removed videos <span>
                            <?php echo count($removedVideos) ?></span>
                        </h2>
                        <p>Restoring makes the video active in the library again.</p>
                        <div class="removed-list"><?php if (! $removedVideos): ?>
                            <div class="removed-empty">No removed videos.</div>
                            <?php endif; ?><?php foreach ($removedVideos as $removed): ?>
                                <div class="removed-row" data-id="<?php echo htmlspecialchars((string)$removed['id']) ?>">
                                    <div>
                                        <strong>
                                            <?php echo htmlspecialchars((string)$removed['name']) ?><?php echo $removed['duplicate_of']?'<em class="duplicate-badge">Duplicate</em>':'' ?>
                                        </strong>
                                        <small title="<?php echo htmlspecialchars((string)$removed['path']) ?>"><?php echo htmlspecialchars((string)$removed['path']) ?></small>
                                        <?php if ($removed['duplicate_of']): ?>
                                            <small maxLength="200" class="duplicate-source">Matches: <?php echo htmlspecialchars((string)($removed['duplicate_name']??'Active copy')) ?> · <?php echo htmlspecialchars((string)($removed['duplicate_path']??'')) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <button class="button subtle restore-video" type="button">Restore</button>
                                </div>
                                    <?php endforeach; ?>
                        </div>
                    </section>
                    <section class="admin-card studio-production">
                        <h2>Studio/Productions</h2>
                        <div class="production-add">
                            <input id="newProductionName" placeholder="New Production/Studio Name" maxlength="120">
                            <button class="button primary" id="addProduction" type="button">Add Production</button>
                        </div>
                        <div id="productionList">
                            <?php foreach($productions as $studio) : ?>
                            <div class="production-row" data-id="<?php echo (int)$studio['id'] ?>">
                                <span>
                                    <?php echo htmlspecialchars((string)$studio['name']) ?>
                                </span>
                                <button type="button" class="delete-production">Delete</button>
                            </div><?php endforeach; ?>
                        </div>
                    </section>
                </main>
            <script src="assets/js/admin.js"></script>
        </body>
    </html>
