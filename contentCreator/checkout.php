<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
store_db();

if (empty($_SESSION['store_age_confirmed'])) {
    store_redirect('index.php');
}

$errors = [];
$customOnly = ($_GET['custom'] ?? '') === '1';
$items = store_cart_items();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    store_verify_csrf();
    $name = trim((string)($_POST['customer_name'] ?? ''));
    $email = trim((string)($_POST['customer_email'] ?? ''));
    $payment = trim((string)($_POST['payment_preference'] ?? ''));
    $delivery = trim((string)($_POST['delivery_preference'] ?? ''));
    $notes = trim((string)($_POST['customer_notes'] ?? ''));
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Enter a name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($payment === '' || mb_strlen($payment) > 120) {
        $errors[] = 'Tell us how you would prefer to discuss payment.';
    }
    if (($_POST['adult_confirmed'] ?? '') !== '1') {
        $errors[] = 'Confirm that you are an adult and that requested interactions are consensual.';
    }
    if (mb_strlen($delivery) > 500) {
        $errors[] = 'Keep delivery preferences under 500 characters.';
    }
    if (mb_strlen($notes) > 3000) {
        $errors[] = 'Keep your notes under 3,000 characters.';
    }

    if (!$items && !$customOnly) {
        $errors[] = 'Your selection is empty. Add a package or request a custom quote.';
    }

    if (!$errors) {
        $db = store_db();
        $estimatedTotal = store_cart_total($items);
        $quoteRequired = $customOnly || $notes !== '';
        foreach ($items as $item) {
            $quoteRequired = $quoteRequired || $item['starting_price'];
        }
        $orderCode = strtoupper(bin2hex(random_bytes(4)));
        try {
            $db->beginTransaction();
            $insertOrder = $db->prepare(
                'INSERT INTO orders (order_code, customer_name, customer_email, payment_preference, delivery_preference, customer_notes, estimated_total_cents, quote_required)
                 VALUES (:code, :name, :email, :payment, :delivery, :notes, :total, :quote)'
            );
            $insertOrder->execute([
                ':code' => $orderCode,
                ':name' => $name,
                ':email' => $email,
                ':payment' => $payment,
                ':delivery' => $delivery,
                ':notes' => $notes,
                ':total' => $estimatedTotal,
                ':quote' => $quoteRequired ? 1 : 0,
            ]);
            $orderId = (int)$db->lastInsertId();
            $insertItem = $db->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price_cents)
                 VALUES (:order_id, :product_id, :name, :quantity, :price)'
            );
            foreach ($items as $item) {
                $insertItem->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $item['id'],
                    ':name' => $item['name'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price_cents'],
                ]);
            }
            $db->commit();
        } catch (PDOException $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Storefront order submission failed: ' . $exception->getMessage());
            http_response_code(500);
            exit('Your request could not be saved. Please try again later or contact the store owner.');
        }
        $_SESSION['store_cart'] = [];
        $_SESSION['store_last_order'] = $orderCode;
        store_redirect('order-received.php');
    }
}

store_page_start('Order request');
?>
<section class="section section-narrow checkout-section">
    <p class="eyebrow">A simple first step</p>
    <h2>Send an order request</h2>
    <p class="section-lead">No payment is collected. Your details and final price are confirmed with you before anything is due.</p>
    <?php if ($errors): ?>
        <div class="form-errors" role="alert"><strong>Please check the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= store_h($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form class="checkout-layout" method="post" novalidate>
        <div class="checkout-form">
            <input type="hidden" name="csrf_token" value="<?= store_h(store_csrf_token()) ?>">
            <label>Your name<input name="customer_name" maxlength="100" autocomplete="name" required value="<?= store_h((string)($_POST['customer_name'] ?? '')) ?>"></label>
            <label>Email address<input type="email" name="customer_email" maxlength="254" autocomplete="email" required value="<?= store_h((string)($_POST['customer_email'] ?? '')) ?>"><small>Used only to respond to this request.</small></label>
            <label>Payment preference<input name="payment_preference" maxlength="120" required placeholder="Discuss with me before payment" value="<?= store_h((string)($_POST['payment_preference'] ?? '')) ?>"><small>No payment method is processed by this site. Never enter card or account numbers here.</small></label>
            <label>Delivery or access preference <span class="optional">Optional</span><input name="delivery_preference" maxlength="500" placeholder="Any preferred access or delivery arrangement" value="<?= store_h((string)($_POST['delivery_preference'] ?? '')) ?>"></label>
            <label>Notes, preferred duration or upgrade ideas <span class="optional">Optional</span><textarea name="customer_notes" rows="5" maxlength="3000" placeholder="Share any details you'd like to discuss. Please do not include sensitive payment information."><?= store_h((string)($_POST['customer_notes'] ?? '')) ?></textarea><small>Any requested upgrades or custom details will be quoted individually.</small></label>
            <label class="consent-check"><input type="checkbox" name="adult_confirmed" value="1" required><span>I confirm I am 18 or older (or the age of majority where I live), and that all requested interactions are consensual.</span></label>
            <button class="button button-dark" type="submit">Send order request <span aria-hidden="true">→</span></button>
        </div>
        <aside class="order-summary">
            <h3>Your request</h3>
            <?php if ($items): ?>
                <ul class="summary-items">
                    <?php foreach ($items as $item): ?><li><span><?= store_h($item['name']) ?> <small>× <?= (int)$item['quantity'] ?></small></span><strong><?= store_h(store_money($item['price_cents'] * $item['quantity'])) ?></strong></li><?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="summary-custom">Custom package inquiry</p>
            <?php endif; ?>
            <div class="summary-total"><span>Estimated<?= array_filter($items, static fn(array $item): bool => $item['starting_price']) ? ' starting' : '' ?> total</span><strong><?= store_h(store_money(store_cart_total($items))) ?></strong></div>
            <p class="fine-print">This is not a charge or invoice. Availability, any requested upgrades, terms and the final price are agreed before payment.</p>
            <a class="back-link" href="index.php?view=cart">← Edit your selection</a>
        </aside>
    </form>
</section>
<?php store_page_end(); ?>
