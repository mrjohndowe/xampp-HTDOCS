<?php
require_once __DIR__ . '/../config.php';
if (!admin_exists()) { header('Location: ' . app_url('admin/setup.php')); exit; }
require_admin();

$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$book = find_book($id);
if (!$book) { http_response_code(404); exit('Book not found.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $title = trim((string)($_POST['title'] ?? ''));
    $author = trim((string)($_POST['author'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $category = trim((string)($_POST['category'] ?? '')) ?: 'Uncategorized';
    if ($title === '') {
        flash('error', 'Title is required.');
        header('Location: ' . app_url('admin/edit_book.php?id=' . rawurlencode($id)));
        exit;
    }
    try {
        $coverName = $book['coverFilename'];
        if (!empty($_FILES['cover']['tmp_name']) && is_uploaded_file($_FILES['cover']['tmp_name'])) {
            if ((int)$_FILES['cover']['size'] > MAX_COVER_BYTES) throw new RuntimeException('Cover is larger than 10 MB.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['cover']['tmp_name']);
            $ext = match ($mime) { 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', default => throw new RuntimeException('Cover must be JPG, PNG, or WEBP.') };
            $coverName = $id . '.' . $ext;
            if (!move_uploaded_file($_FILES['cover']['tmp_name'], __DIR__ . '/../covers/' . $coverName)) throw new RuntimeException('Could not save cover image.');
            if (!empty($book['coverFilename']) && $book['coverFilename'] !== $coverName) @unlink(__DIR__ . '/../covers/' . basename((string)$book['coverFilename']));
        } elseif (!empty($_POST['auto_cover_data']) && $book['fileType'] === 'pdf') {
            $newCover = save_data_url_cover((string)$_POST['auto_cover_data'], $id);
            if ($newCover) {
                if (!empty($book['coverFilename']) && $book['coverFilename'] !== $newCover) @unlink(__DIR__ . '/../covers/' . basename((string)$book['coverFilename']));
                $coverName = $newCover;
            }
        }
        update_book_details($id, text_cut($title, 200), text_cut($author, 200), text_cut($description, 5000), text_cut($category, 80), $coverName);
        flash('success', 'Book details updated.');
        header('Location: ' . app_url('admin/index.php'));
        exit;
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        header('Location: ' . app_url('admin/edit_book.php?id=' . rawurlencode($id)));
        exit;
    }
}
$cover = !empty($book['coverFilename']) ? app_url('covers/' . rawurlencode(basename((string)$book['coverFilename']))) : null;
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="<?= e(app_url('assets/css/styles.css')) ?>"><title>Edit Book | <?= e(APP_NAME) ?></title></head><body>
<header class="topbar"><a class="brand" href="<?= e(app_url('admin/index.php')) ?>"><img src="<?= e(app_url('assets/img/logo.png')) ?>" alt=""><span>Library Admin</span></a><nav><a href="<?= e(app_url('admin/index.php')) ?>">Dashboard</a><a href="<?= e(app_url('admin/upload.php')) ?>">Upload Book</a><a href="<?= e(app_url('index.php')) ?>">View Site</a><a href="<?= e(app_url('admin/logout.php')) ?>">Logout</a></nav></header>
<main class="site-main"><section class="page-heading"><div class="eyebrow">ADMINISTRATION</div><h1>Edit Book</h1><p>Update the book information or replace its cover whenever you need to.</p></section><section class="panel upload-panel standalone"><form method="post" enctype="multipart/form-data" class="upload-form" id="bookEditForm"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="auto_cover_data" id="autoCoverData"><label>Title<input type="text" name="title" maxlength="200" required value="<?= e($book['title']) ?>"></label><label>Author<input type="text" name="author" maxlength="200" value="<?= e($book['author']) ?>"></label><label>Category<input type="text" name="category" maxlength="80" value="<?= e($book['category']) ?>"></label><label>Description<textarea name="description" rows="6" maxlength="5000"><?= e($book['description']) ?></textarea></label><?php if ($cover): ?><img class="edit-cover-preview" src="<?= e($cover) ?>" alt="Current cover"><?php endif; ?><label>Replace / add cover image<input type="file" name="cover" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"></label><?php if ($book['fileType'] === 'pdf'): ?><button type="button" class="secondary" id="makeFirstPageCover" data-pdf-url="<?= e(app_url('uploads/' . rawurlencode(basename($book['filename'])))) ?>">Use PDF page 1 as cover</button><small id="coverStatus">Use this if you want to regenerate the cover from the first page.</small><?php endif; ?><div class="admin-form-actions"><button type="submit">Save Changes</button><a class="secondary-link" href="<?= e(app_url('admin/index.php')) ?>">Cancel</a></div></form></section></main><script type="module" src="<?= e(app_url('assets/js/edit-cover.js')) ?>"></script></body></html>
