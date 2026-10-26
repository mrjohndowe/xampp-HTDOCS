<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$db = store_db();

$localConfigPath = __DIR__ . '/config.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
$passwordHash = is_array($localConfig) ? ($localConfig['admin_password_hash'] ?? '') : '';
$configured = is_string($passwordHash) && str_starts_with($passwordHash, '$') && strlen($passwordHash) > 40;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    store_verify_csrf();
    if (($_POST['action'] ?? '') === 'logout') {
        unset($_SESSION['store_admin']);
        store_redirect('admin.php');
    }
    $password = $_POST['password'] ?? '';
    if (!$configured) {
        $error = 'Order inbox access is not configured. Follow the setup instructions in storefront/README.md, then refresh this page.';
    } elseif (!is_string($password) || !password_verify($password, $passwordHash)) {
        $error = 'The password was not recognized.';
    } else {
        session_regenerate_id(true);
        $_SESSION['store_admin'] = true;
        store_redirect('admin.php');
    }
}

store_page_start('Order inbox');
?>
<section class="section section-narrow admin-section">
    <?php if (empty($_SESSION['store_admin'])): ?>
        <p class="eyebrow">Store owner</p><h2>Order inbox</h2>
        <p class="section-lead">Sign in to review order requests. This area is separate from the public menu.</p>
        <?php if ($error !== ''): ?><div class="form-errors" role="alert"><?= store_h($error) ?></div><?php endif; ?>
        <?php if (!$configured): ?>
            <div class="notice-card"><strong>Admin access needs one-time setup.</strong><p>Create <code>storefront/config.local.php</code> using the instructions in <code>storefront/README.md</code>. Choose a unique password hash; do not use the public demo or a shared password.</p></div>
        <?php else: ?>
            <form class="checkout-form admin-login" method="post">
                <input type="hidden" name="csrf_token" value="<?= store_h(store_csrf_token()) ?>">
                <label>Admin password<input type="password" name="password" autocomplete="current-password" required></label>
                <button class="button button-dark" type="submit">Sign in</button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <?php
        $orders = $db->query('SELECT * FROM orders ORDER BY id DESC LIMIT 100')->fetchAll();
        $itemQuery = $db->prepare('SELECT product_name, quantity, unit_price_cents FROM order_items WHERE order_id = :id ORDER BY id');
        ?>
        <div class="admin-heading"><div><p class="eyebrow">Private store dashboard</p><h2>Order inbox</h2><p class="section-lead">Showing the latest 100 requests. Contact customers to confirm terms; no payment has been collected.</p></div>
            <form method="post"><input type="hidden" name="csrf_token" value="<?= store_h(store_csrf_token()) ?>"><input type="hidden" name="action" value="logout"><button class="text-button" type="submit">Sign out</button></form>
        </div>
        <?php if (!$orders): ?><div class="empty-state"><h3>No orders yet</h3><p>New order requests will appear here.</p></div><?php else: ?>
        <div class="admin-orders">
            <?php foreach ($orders as $order): ?>
                <?php $itemQuery->execute([':id' => $order['id']]); $orderItems = $itemQuery->fetchAll(); ?>
                <article class="admin-order">
                    <div class="admin-order-top"><div><p class="eyebrow">#<?= store_h($order['order_code']) ?> · <?= store_h($order['created_at']) ?></p><h3><?= store_h($order['customer_name']) ?></h3></div><span class="status-pill"><?= store_h(str_replace('_', ' ', $order['status'])) ?></span></div>
                    <div class="admin-details">
                        <p><strong>Email</strong><a href="mailto:<?= store_h($order['customer_email']) ?>"><?= store_h($order['customer_email']) ?></a></p>
                        <p><strong>Payment preference</strong><?= store_h($order['payment_preference']) ?></p>
                        <?php if ($order['delivery_preference'] !== ''): ?><p><strong>Delivery preference</strong><?= nl2br(store_h($order['delivery_preference'])) ?></p><?php endif; ?>
                        <p><strong>Estimated<?= (int)$order['quote_required'] ? ' / starting' : '' ?> total</strong><?= store_h(store_money((int)$order['estimated_total_cents'])) ?></p>
                        <p><strong>Items</strong><?= $orderItems ? implode(', ', array_map(static fn(array $item): string => store_h($item['product_name']) . ' × ' . (int)$item['quantity'], $orderItems)) : 'Custom package inquiry' ?></p>
                        <?php if ($order['customer_notes'] !== ''): ?><p><strong>Notes</strong><?= nl2br(store_h($order['customer_notes'])) ?></p><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php store_page_end(); ?>
