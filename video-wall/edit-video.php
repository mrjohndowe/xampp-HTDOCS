<?php

    declare (strict_types = 1);
    require_once __DIR__ . '/functions.php';
    $id        = (string) ($_GET['id'] ?? $_POST['id'] ?? '');
    $database  = db();
    $statement = $database->prepare("SELECT v.*,COALESCE(NULLIF(v.display_name,''),v.original_name)AS name FROM videos v WHERE v.id=?");
    $statement->execute([$id]);
    $video = $statement->fetch();
    if (! $video) {
    http_response_code(404);
    exit('Video not found.');
    }
    $categories        = categoriesList();
    $selectedStatement = $database->prepare('SELECT category_id FROM video_categories WHERE video_id=?');
    $selectedStatement->execute([$id]);
    $selectedCategoryIds = array_map('intval', $selectedStatement->fetchAll(PDO::FETCH_COLUMN));
    $productions         = productionsList();
    $selectedProduction  = $database->prepare('SELECT production_id FROM video_productions WHERE video_id=?');
    $selectedProduction->execute([$id]);
    $selectedProductionIds = array_map('intval', $selectedProduction->fetchAll(PDO::FETCH_COLUMN));
    $error               = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Video name is required.');
        }
        $postedProductions   = $_POST['productionIds'] ?? [];
        $selectedProductionIds = array_map('intval', is_array($postedProductions) ? $postedProductions : []);


        $postedCategories    = $_POST['categoryIds'] ?? [];
        $selectedCategoryIds = array_map('intval', is_array($postedCategories) ? $postedCategories : []);
        $actors              = trim((string) ($_POST['actors'] ?? ''));
        $characters          = trim((string) ($_POST['characters'] ?? ''));
        $publishDate         = trim((string) ($_POST['publishDate'] ?? ''));
        //$production          = trim((string) ($_POST['production'] ?? ''));
        $active              = isset($_POST['active']) ? 1 : 0;
        $database->beginTransaction();
        $update = $database->prepare('UPDATE videos SET display_name=?,actors=?,characters=?,publish_date=?,active=? WHERE id=?');
        $update->execute([$name, $actors, $characters, $publishDate, $active, $id]);
        setVideoCategories($database, $id, $selectedCategoryIds);
        setVideoProduction($database, $id, $selectedProductionIds);
        $database->commit();
        header('Location: ' . ($active ? 'index.php' : 'admin.php'));
        exit;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        $error = $exception->getMessage();
    }
    }
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Edit video · <?php echo htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/edit-video.css">
    <link rel="stylesheet" href="assets/css/active-switch.css">
</head>

<body>
    <header class="topbar">
        <a class="brand" href="index.php">
            <span class="brand-icon">▶</span>
            <span>
                <?php echo htmlspecialchars(APP_NAME) ?>
            </span>
        </a>
        <a class="button subtle" href="index.php">Cancel</a>
    </header>
    <main class="edit-page">
        <form method="post" class="edit-card">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id) ?>">
                <span class="eyebrow">EDIT VIDEO</span>
                <h1>
                    <?php echo htmlspecialchars((string) $video['name']) ?>
                </h1>
                <p class="source-path" title="<?php echo htmlspecialchars((string) $video['path']) ?>">
                    <?php echo htmlspecialchars((string) $video['path']) ?>
                </p>
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
                    <div class="edit-grid">
                        <label class="wide">Video name<input name="name" maxlength="180" value="<?php echo htmlspecialchars((string) ($_POST['name'] ?? $video['name'])) ?>" required></label>
                        <fieldset class="wide category-choices">
                            <legend>Categories</legend>
                                <p>Select as many categories as you want. Leave all unchecked for Uncategorized.</p>
                                    <div>
                                    <?php foreach ($categories as $category): ?><label>
                                        <input type="checkbox" name="categoryIds[]" value="<?php echo (int) $category['id'] ?>" <?php echo in_array((int) $category['id'], $selectedCategoryIds, true) ? 'checked' : '' ?>>
                                        <span>
                                            <?php echo htmlspecialchars((string) $category['name']) ?>
                                        </span>
                                        </label>
                                        <?php endforeach; ?>
                                        <?php if (! $categories): ?>
                                            <small>No categories yet. Add your own categories in Admin.</small>
                                            <?php endif; ?>
                                    </div>
                        </fieldset>
                        <hr>

                        <fieldset class="wide production-choices">
                            <legend>Productions</legend>
                                <p>Select as many productions/studios as you want. Leave all unchecked for Unknown.</p>
                                    <div>
                                    <?php foreach ($productions as $studio): ?><label>
                                        <input type="checkbox" name="productionIds[]" value="<?php echo (int) $studio['id'] ?>" <?php echo in_array((int) $studio['id'], $selectedProductionIds, true) ? 'checked' : '' ?>>
                                        <span>
                                            <?php echo htmlspecialchars((string) $studio['name']) ?>
                                        </span>
                                        </label>
                                        <?php endforeach; ?>
                                        <?php if (! $productions): ?>
                                            <small>No Productions / Studios yet. Add your own Production/Studio in Admin.</small>
                                            <?php endif; ?>
                                    </div>
                        </fieldset>
                        <label>Publish date
                            <input type="date" name="publishDate" value="<?php echo htmlspecialchars((string) ($_POST['publishDate'] ?? $video['publish_date'])) ?>">
                        </label>
                        <!--<label class="wide">Production video / studio<input name="production" maxlength="300" value="<?php echo htmlspecialchars((string) ($_POST['production'] ?? $video['production'])) ?>" placeholder="Production company, studio, creator, or production title">

                        </label>-->
                        <label class="wide">Actors
                            <textarea name="actors" rows="3" maxlength="2000" placeholder="Separate multiple actors with commas">
                                <?php echo htmlspecialchars((string) ($_POST['actors'] ?? $video['actors'])) ?>
                            </textarea>
                        </label>
                        <label class="wide">Characters<textarea name="characters" rows="3" maxlength="2000" placeholder="Separate multiple characters with commas">
                            <?php echo htmlspecialchars((string) ($_POST['characters'] ?? $video['characters'])) ?>
                        </textarea>
                    </label>
                    <label class="wide active-switch">
                        <input type="checkbox" name="active" value="1" <?php echo(isset($_POST['id']) ? isset($_POST['active']) : (int) $video['active'] === 1) ? 'checked' : '' ?>><span><strong>Active in library</strong><small>Turn this off to hide the video without deleting its original file.</small></span></label>
            </div>
            <div class="edit-actions"><a class="button subtle" href="<?php echo (int) $video['active'] === 1 ? 'index.php' : 'admin.php' ?>">Cancel</a><button class="button primary" type="submit">Save video</button></div>
        </form>
    </main>
</body>

</html>
