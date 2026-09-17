<?php
require_once __DIR__ . '/partials.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string)($_POST['title'] ?? ''));
    $author = trim((string)($_POST['author'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    try {
        if ($title === '' || $author === '') throw new RuntimeException('Title and author are required.');
        if (empty($_FILES['book']['tmp_name'])) throw new RuntimeException('A PDF file is required.');
        if ($_FILES['book']['size'] > MAX_BOOK_BYTES) throw new RuntimeException('Book file is too large.');

        $bookMime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['book']['tmp_name']);
        if ($bookMime !== 'application/pdf') throw new RuntimeException('Only PDF novels are supported.');

        $bookName = bin2hex(random_bytes(16)) . '.pdf';
        $bookRel = 'uploads/books/' . $bookName;
        if (!move_uploaded_file($_FILES['book']['tmp_name'], __DIR__ . '/' . $bookRel)) {
            throw new RuntimeException('Could not save the PDF.');
        }

        $coverRel = null;
        if (!empty($_FILES['cover']['tmp_name'])) {
            if ($_FILES['cover']['size'] > MAX_COVER_BYTES) throw new RuntimeException('Cover image is too large.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['cover']['tmp_name']);
            $ext = match($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => throw new RuntimeException('Cover must be JPG, PNG, or WEBP.')
            };
            $coverName = bin2hex(random_bytes(16)) . '.' . $ext;
            $coverRel = 'uploads/covers/' . $coverName;
            if (!move_uploaded_file($_FILES['cover']['tmp_name'], __DIR__ . '/' . $coverRel)) {
                throw new RuntimeException('Could not save the cover.');
            }
        }

        $stmt = db()->prepare("INSERT INTO books(title,author,description,cover_path,file_path,original_filename) VALUES(?,?,?,?,?,?)");
        $stmt->execute([$title,$author,$description,$coverRel,$bookRel,basename($_FILES['book']['name'])]);
        flash('success', 'Novel uploaded successfully.');
        header('Location: index.php');
        exit;
    } catch (Throwable $ex) {
        flash('error', $ex->getMessage());
    }
}

site_header('Upload');
?>
<div class="panel narrow">
<h1>Upload a Novel</h1>
<p class="muted">PDF books only. Cover image is optional.</p>
<form method="post" enctype="multipart/form-data" class="form">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Title<input name="title" maxlength="200" required></label>
<label>Author<input name="author" maxlength="200" required></label>
<label>Description<textarea name="description" rows="6"></textarea></label>
<label>Cover image<input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
<label>Novel PDF<input type="file" name="book" accept=".pdf,application/pdf" required></label>
<button class="btn" type="submit">Upload Novel</button>
</form>
</div>
<?php site_footer(); ?>
