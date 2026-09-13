<?php
require_once __DIR__ . '/partials.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare("SELECT * FROM books WHERE id=?");
$stmt->execute([$id]);
$book = $stmt->fetch();
if (!$book) { http_response_code(404); exit('Book not found.'); }
site_header('Read ' . $book['title']);
?>
<div class="reader-head">
<div><h1><?= e($book['title']) ?></h1><p class="muted"><?= e($book['author']) ?></p></div>
<a class="btn secondary" href="<?= e($book['file_path']) ?>" download="<?= e($book['original_filename']) ?>">Download PDF</a>
</div>
<div class="reader-shell">
<iframe src="<?= e($book['file_path']) ?>#toolbar=1&navpanes=0" title="<?= e($book['title']) ?>"></iframe>
</div>
<?php site_footer(); ?>
