<?php
require_once __DIR__ . '/partials.php';
$id = (string)($_GET['id'] ?? '');
$book = find_book($id);
if (!$book) { http_response_code(404); exit('Book not found.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        flash('error', 'Invalid security token.');
        header('Location: ' . app_url('book.php?id=' . rawurlencode($id)));
        exit;
    }
    $name = trim((string)($_POST['reviewer_name'] ?? ''));
    $rating = (int)($_POST['rating'] ?? 0);
    $text = trim((string)($_POST['review_text'] ?? ''));
    if ($name === '' || $text === '' || $rating < 1 || $rating > 5) {
        flash('error', 'Name, rating, and review are required.');
    } else {
        add_review($id, $name, $rating, $text);
        flash('success', 'Review posted.');
    }
    header('Location: ' . app_url('book.php?id=' . rawurlencode($id)));
    exit;
}

$stats = book_stats($id);
$reviews = reviews_for_book($id);
$cover = $book['coverFilename'] ?? null;
page_header((string)$book['title'], 'library'); ?>
<section class="book-detail panel"><div class="detail-cover-wrap"><?php if ($cover): ?><img class="detail-cover" src="<?= e(app_url('covers/' . rawurlencode(basename((string)$cover)))) ?>" alt="<?= e((string)$book['title']) ?> cover"><?php else: ?><div class="detail-cover cover-fallback"><?= e((string)$book['title']) ?></div><?php endif; ?></div><div><div class="eyebrow">BOOK DETAILS</div><h1><?= e((string)$book['title']) ?></h1><p class="detail-author">by <?= e((string)($book['author'] ?: 'Unknown author')) ?></p><div class="rating-large"><span class="stars"><?= str_repeat('★', (int)round((float)$stats['average_rating'])) ?><?= str_repeat('☆', 5 - (int)round((float)$stats['average_rating'])) ?></span><strong><?= number_format((float)$stats['average_rating'], 1) ?></strong><span><?= (int)$stats['review_count'] ?> review<?= (int)$stats['review_count'] === 1 ? '' : 's' ?></span></div><p class="detail-description"><?= nl2br(e((string)($book['description'] ?? ''))) ?></p><a class="button-link" href="<?= e(app_url('index.php?read=' . rawurlencode($id))) ?>">Open in Book Reader</a></div></section>
<section class="review-grid"><div class="panel"><h2>Leave a Review</h2><form method="post" class="upload-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Your name<input name="reviewer_name" maxlength="80" required></label><label>Rating<select name="rating" required><option value="">Choose a rating</option><option value="5">★★★★★ 5</option><option value="4">★★★★☆ 4</option><option value="3">★★★☆☆ 3</option><option value="2">★★☆☆☆ 2</option><option value="1">★☆☆☆☆ 1</option></select></label><label>Review<textarea name="review_text" rows="5" maxlength="3000" required></textarea></label><button>Post Review</button></form></div><div><div class="section-title"><h2>Reader Reviews</h2><span><?= count($reviews) ?></span></div><?php if (!$reviews): ?><div class="empty-state">No reviews yet.</div><?php endif; ?><?php foreach ($reviews as $review): ?><article class="review-card"><div class="review-head"><strong><?= e((string)$review['reviewer_name']) ?></strong><span class="stars"><?= str_repeat('★', (int)$review['rating']) ?><?= str_repeat('☆', 5 - (int)$review['rating']) ?></span></div><p><?= nl2br(e((string)$review['review_text'])) ?></p><small><?= date('M j, Y g:i A', (int)$review['created_at']) ?></small></article><?php endforeach; ?></div></section>
<?php page_footer(); ?>
