<?php
require_once __DIR__ . '/../config.php';
if (!admin_exists()) { header('Location: setup.php'); exit; }
if (!is_admin()) { header('Location: login.php'); exit; }

$bookCount = (int)db()->query("SELECT COUNT(*) FROM books")->fetchColumn();
$reviewCount = (int)db()->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$books = db()->query("SELECT b.*, COUNT(r.id) review_count FROM books b LEFT JOIN reviews r ON r.book_id=b.id GROUP BY b.id ORDER BY b.created_at DESC")->fetchAll();
$reviews = db()->query("SELECT r.*, b.title FROM reviews r JOIN books b ON b.id=r.book_id ORDER BY r.created_at DESC LIMIT 50")->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin | <?= APP_NAME ?></title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<header class="topbar"><a class="brand" href="../index.php"><img src="../assets/img/logo.png"><span><?= APP_NAME ?> Admin</span></a>
<nav><a href="../index.php">Site</a><a href="logout.php">Logout</a></nav></header>
<main class="container">
<div class="stats"><div class="stat"><strong><?= $bookCount ?></strong><span>Novels</span></div><div class="stat"><strong><?= $reviewCount ?></strong><span>Reviews</span></div></div>

<div class="panel">
<h2>Manage Novels</h2>
<div class="table-wrap"><table><tr><th>Title</th><th>Author</th><th>Reviews</th><th>Action</th></tr>
<?php foreach($books as $b): ?><tr><td><?= e($b['title']) ?></td><td><?= e($b['author']) ?></td><td><?= (int)$b['review_count'] ?></td>
<td><form method="post" action="delete_book.php" onsubmit="return confirm('Delete this novel and its reviews?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="danger">Delete</button></form></td></tr><?php endforeach; ?>
</table></div>
</div>

<div class="panel">
<h2>Manage Reviews</h2>
<div class="table-wrap"><table><tr><th>Novel</th><th>Name</th><th>Rating</th><th>Review</th><th>Action</th></tr>
<?php foreach($reviews as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= e($r['reviewer_name']) ?></td><td><?= (int)$r['rating'] ?>/5</td><td><?= e(mb_strimwidth($r['review_text'],0,90,'…')) ?></td>
<td><form method="post" action="delete_review.php" onsubmit="return confirm('Delete this review?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="danger">Delete</button></form></td></tr><?php endforeach; ?>
</table></div>
</div>
</main></body></html>
