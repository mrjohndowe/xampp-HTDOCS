<?php
require_once __DIR__ . '/partials.php';

$sql = "SELECT b.*,
        COALESCE(AVG(r.rating),0) avg_rating,
        COUNT(r.id) review_count
        FROM books b
        LEFT JOIN reviews r ON r.book_id=b.id
        GROUP BY b.id
        ORDER BY b.created_at DESC";
$books = db()->query($sql)->fetchAll();
site_header('Library');
echo '<div class="splash-screen"><img class="splash-logo" src="assets/img/logo.png" alt="NovelShelf"></div>';
?>
<section class="hero">
    <div>
        <p class="eyebrow">YOUR PERSONAL STORY LIBRARY</p>
        <h1>Discover. Read. Review.</h1>
        <p>Upload novels, read them in your browser, and leave ratings and reviews.</p>
        <div class="actions"><a class="btn" href="upload.php">Upload a Novel</a></div>
    </div>
</section>

<section class="section-head"><h2>Library</h2><span><?= count($books) ?> title<?= count($books)==1?'':'s' ?></span></section>

<?php if (!$books): ?>
<div class="empty">No novels yet. The shelves are tragically civilized.</div>
<?php else: ?>
<div class="book-grid">
<?php foreach ($books as $book): ?>
<article class="book-card">
    <a href="book.php?id=<?= (int)$book['id'] ?>">
        <?php if ($book['cover_path']): ?>
            <img class="cover" src="<?= e($book['cover_path']) ?>" alt="">
        <?php else: ?>
            <div class="cover placeholder">📖</div>
        <?php endif; ?>
    </a>
    <div class="card-body">
        <h3><a href="book.php?id=<?= (int)$book['id'] ?>"><?= e($book['title']) ?></a></h3>
        <p class="muted">by <?= e($book['author']) ?></p>
        <div class="rating">★ <?= number_format((float)$book['avg_rating'],1) ?> <span>(<?= (int)$book['review_count'] ?>)</span></div>
    </div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php site_footer(); ?>
