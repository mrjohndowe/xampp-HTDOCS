<?php
require_once __DIR__ . '/partials.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = db()->prepare("SELECT b.*, COALESCE(AVG(r.rating),0) avg_rating, COUNT(r.id) review_count
                      FROM books b LEFT JOIN reviews r ON r.book_id=b.id
                      WHERE b.id=? GROUP BY b.id");
$stmt->execute([$id]);
$book = $stmt->fetch();
if (!$book) { http_response_code(404); exit('Book not found.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['reviewer_name'] ?? ''));
    $rating = (int)($_POST['rating'] ?? 0);
    $text = trim((string)($_POST['review_text'] ?? ''));
    if ($name === '' || $text === '' || $rating < 1 || $rating > 5) {
        flash('error', 'Name, rating, and review are required.');
    } else {
        $stmt = db()->prepare("INSERT INTO reviews(book_id,reviewer_name,rating,review_text) VALUES(?,?,?,?)");
        $stmt->execute([$id, mb_substr($name,0,80), $rating, mb_substr($text,0,3000)]);
        flash('success', 'Review added.');
    }
    header('Location: book.php?id=' . $id);
    exit;
}

$stmt = db()->prepare("SELECT * FROM reviews WHERE book_id=? ORDER BY created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();
site_header($book['title']);
?>
<section class="book-detail">
<div>
<?php if ($book['cover_path']): ?><img class="detail-cover" src="<?= e($book['cover_path']) ?>" alt=""><?php else: ?><div class="detail-cover placeholder">📖</div><?php endif; ?>
</div>
<div>
<p class="eyebrow">NOVEL</p>
<h1><?= e($book['title']) ?></h1>
<p class="muted">by <?= e($book['author']) ?></p>
<div class="big-rating">★ <?= number_format((float)$book['avg_rating'],1) ?> <span><?= (int)$book['review_count'] ?> reviews</span></div>
<p><?= nl2br(e($book['description'])) ?></p>
<a class="btn" href="reader.php?id=<?= $id ?>">Read Now</a>
</div>
</section>

<section class="reviews-layout">
<div class="panel">
<h2>Write a Review</h2>
<form method="post" class="form">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Your name<input name="reviewer_name" maxlength="80" required></label>
<label>Rating
<select name="rating" required>
<option value="">Choose</option>
<option value="5">★★★★★ - 5</option><option value="4">★★★★☆ - 4</option>
<option value="3">★★★☆☆ - 3</option><option value="2">★★☆☆☆ - 2</option><option value="1">★☆☆☆☆ - 1</option>
</select></label>
<label>Your review<textarea name="review_text" rows="5" maxlength="3000" required></textarea></label>
<button class="btn" type="submit">Submit Review</button>
</form>
</div>

<div>
<h2>Reviews</h2>
<?php if (!$reviews): ?><div class="empty">No reviews yet.</div><?php endif; ?>
<?php foreach ($reviews as $r): ?>
<article class="review">
<div><strong><?= e($r['reviewer_name']) ?></strong><span class="stars"><?= str_repeat('★',(int)$r['rating']) ?><?= str_repeat('☆',5-(int)$r['rating']) ?></span></div>
<p><?= nl2br(e($r['review_text'])) ?></p>
<small><?= e($r['created_at']) ?></small>
</article>
<?php endforeach; ?>
</div>
</section>
<?php site_footer(); ?>
