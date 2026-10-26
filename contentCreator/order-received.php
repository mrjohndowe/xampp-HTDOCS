<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$orderCode = $_SESSION['store_last_order'] ?? '';
if (!is_string($orderCode) || !preg_match('/^[A-F0-9]{8}$/', $orderCode)) {
    store_redirect('index.php');
}
unset($_SESSION['store_last_order']);
store_page_start('Request received');
?>
<section class="section section-narrow received-section">
    <div class="received-mark" aria-hidden="true">✓</div>
    <p class="eyebrow">Your request is in</p>
    <h2>Thank you for reaching out.</h2>
    <p class="section-lead">Your order request <strong>#<?= store_h($orderCode) ?></strong> has been saved. The store owner will confirm availability, package details, final pricing and arrangements with you before payment.</p>
    <div class="notice-card"><strong>No payment has been taken.</strong><p>This storefront does not collect card details or process payments. Please wait for the agreed terms and payment instructions.</p></div>
    <a class="button button-dark" href="index.php">Back to the menu</a>
</section>
<?php store_page_end(); ?>
