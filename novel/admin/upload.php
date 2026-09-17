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

    $savedPdf = null;
    $savedCover = null;
    try {
        $title = trim((string)($_POST['title'] ?? ''));
        $author = trim((string)($_POST['author'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));

        if ($title === '') throw new RuntimeException('Title is required.');
        if (empty($_FILES['book']['tmp_name']) || !is_uploaded_file($_FILES['book']['tmp_name'])) {
            throw new RuntimeException('PDF upload is required.');
        }
        if ((int)$_FILES['book']['size'] > MAX_PDF_BYTES) {
            throw new RuntimeException('PDF is larger than 100 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($_FILES['book']['tmp_name']) !== 'application/pdf') {
            throw new RuntimeException('Only PDF files are allowed.');
        }

        $id = bin2hex(random_bytes(16));
        $pdfName = $id . '.pdf';
        $savedPdf = __DIR__ . '/../uploads/' . $pdfName;
        if (!move_uploaded_file($_FILES['book']['tmp_name'], $savedPdf)) {
            throw new RuntimeException('Could not save PDF.');
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
        }

        add_book($id, $title, $author, $desc, $pdfName, $coverName);
        flash('success', 'Book uploaded successfully.');
        header('Location: ' . app_url('admin/index.php'));
        exit;
    } catch (Throwable $e) {
        if ($savedPdf && is_file($savedPdf)) @unlink($savedPdf);
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
    <p>Add a PDF and optional cover to the library. Only signed-in administrators can upload books.</p>
  </section>
  <section class="panel upload-panel standalone">
    <form method="post" enctype="multipart/form-data" class="upload-form">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <label>Title<input type="text" name="title" maxlength="200" placeholder="Book title" required></label>
      <label>Author<input type="text" name="author" maxlength="200" placeholder="Author name"></label>
      <label>Description<textarea name="description" rows="6" maxlength="5000" placeholder="Back-cover description"></textarea></label>
      <div class="form-grid">
        <label>PDF File<input type="file" name="book" accept="application/pdf,.pdf" required></label>
        <label>Cover Image<input type="file" name="cover" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"></label>
      </div>
      <div class="admin-form-actions">
        <button type="submit">Upload Book</button>
        <a class="secondary-link" href="<?= e(app_url('admin/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </section>
</main>
</body>
</html>
