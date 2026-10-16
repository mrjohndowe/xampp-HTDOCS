<?php
require_once __DIR__ . '/../config.php';

if (!admin_exists()) {
    header('Location: ' . app_url('admin/setup.php'));
    exit;
}
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        flash('error', 'Invalid security token.');
        header('Location: ' . app_url('admin/upload.php'));
        exit;
    }

    $savedBook = null;
    $savedCover = null;
    try {
        $title = trim((string)($_POST['title'] ?? ''));
        $author = trim((string)($_POST['author'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $category = $category === '' ? 'Uncategorized' : text_cut($category, 80);

        if ($title === '') throw new RuntimeException('Title is required.');
        if (empty($_FILES['book']['tmp_name']) || !is_uploaded_file($_FILES['book']['tmp_name'])) {
            throw new RuntimeException('A PDF or EPUB upload is required.');
        }
        if ((int)$_FILES['book']['size'] > MAX_BOOK_BYTES) {
            throw new RuntimeException('Book file is larger than 100 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $uploadName = (string)($_FILES['book']['name'] ?? '');
        $extension = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));
        $mime = (string)$finfo->file($_FILES['book']['tmp_name']);
        $isPdf = $extension === 'pdf' && $mime === 'application/pdf';
        $isEpub = $extension === 'epub' && in_array($mime, ['application/epub+zip', 'application/zip', 'application/octet-stream'], true);
        if (!$isPdf && !$isEpub) throw new RuntimeException('Only valid PDF or EPUB files are allowed.');

        $id = bin2hex(random_bytes(16));
        $fileType = $isEpub ? 'epub' : 'pdf';
        $bookName = $id . '.' . $fileType;
        $savedBook = __DIR__ . '/../uploads/' . $bookName;
        if (!move_uploaded_file($_FILES['book']['tmp_name'], $savedBook)) {
            throw new RuntimeException('Could not save book file.');
        }

        $coverName = null;
        if (!empty($_FILES['cover']['tmp_name']) && is_uploaded_file($_FILES['cover']['tmp_name'])) {
            if ((int)$_FILES['cover']['size'] > MAX_COVER_BYTES) {
                throw new RuntimeException('Cover is larger than 10 MB.');
            }
            $coverMime = $finfo->file($_FILES['cover']['tmp_name']);
            $ext = match ($coverMime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => throw new RuntimeException('Cover must be JPG, PNG, or WEBP.')
            };
            $coverName = $id . '.' . $ext;
            $savedCover = __DIR__ . '/../covers/' . $coverName;
            if (!move_uploaded_file($_FILES['cover']['tmp_name'], $savedCover)) {
                throw new RuntimeException('Could not save cover.');
            }
        } elseif ($fileType === 'pdf' && !empty($_POST['auto_cover_data'])) {
            $coverName = save_data_url_cover((string)$_POST['auto_cover_data'], $id);
            $savedCover = $coverName ? __DIR__ . '/../covers/' . $coverName : null;
        }

        add_book($id, $title, $author, $desc, $category, $fileType, $bookName, $coverName);
        flash('success', 'Book uploaded successfully.');
        header('Location: ' . app_url('admin/index.php'));
        exit;
    } catch (Throwable $e) {
        if ($savedBook && is_file($savedBook)) @unlink($savedBook);
        if ($savedCover && is_file($savedCover)) @unlink($savedCover);
        flash('error', $e->getMessage());
        header('Location: ' . app_url('admin/upload.php'));
        exit;
    }
}

$success = flash('success');
$error = flash('error');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="<?= e(app_url('assets/css/styles.css')) ?>">
<title>Upload Book | <?= e(APP_NAME) ?></title>
</head>
<body>
<header class="topbar">
  <a class="brand" href="<?= e(app_url('admin/index.php')) ?>"><img src="<?= e(app_url('assets/img/logo.png')) ?>" alt=""><span>Library Admin</span></a>
  <nav>
    <a href="<?= e(app_url('admin/index.php')) ?>">Dashboard</a>
    <a class="active" href="<?= e(app_url('admin/upload.php')) ?>">Upload Book</a>
    <a href="<?= e(app_url('index.php')) ?>">View Site</a>
    <a href="<?= e(app_url('admin/logout.php')) ?>">Logout</a>
  </nav>
</header>
<main class="site-main">
  <?php if ($success): ?><div class="notice success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
  <section class="page-heading">
    <div class="eyebrow">ADMINISTRATION</div>
    <h1>Upload a Book</h1>
    <p>Add a PDF or EPUB, give it a category, and optionally add a cover. PDFs automatically use page one as their cover unless you choose your own image.</p>
  </section>
  <section class="panel upload-panel standalone">
    <form method="post" enctype="multipart/form-data" class="upload-form" id="bookUploadForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="auto_cover_data" id="autoCoverData">
      <label>Title<input type="text" name="title" maxlength="200" placeholder="Book title" required></label>
      <label>Author<input type="text" name="author" maxlength="200" placeholder="Author name"></label>
      <label>Category<input type="text" name="category" maxlength="80" list="categorySuggestions" placeholder="e.g. Fantasy, Mystery, Romance"></label>
      <datalist id="categorySuggestions"><option value="Fantasy"><option value="Mystery"><option value="Romance"><option value="Science Fiction"><option value="Thriller"><option value="Historical Fiction"><option value="Nonfiction"></datalist>
      <label>Description<textarea name="description" rows="6" maxlength="5000" placeholder="Back-cover description"></textarea></label>
      <div class="form-grid">
        <label>Book File (PDF or EPUB)<input type="file" name="book" accept="application/pdf,application/epub+zip,.pdf,.epub" required></label>
        <label>Cover Image<input type="file" name="cover" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"></label>
      </div>
      <div class="admin-form-actions">
        <button type="submit">Upload Book</button>
        <a class="secondary-link" href="<?= e(app_url('admin/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </section>
</main>
<script type="module" src="<?= e(app_url('assets/js/pdf-cover.js')) ?>"></script>
</body>
</html>
