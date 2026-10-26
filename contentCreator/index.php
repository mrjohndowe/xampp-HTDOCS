<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
store_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    store_verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'age_confirm') {
        $_SESSION['store_age_confirmed'] = true;
        session_regenerate_id(true);
        store_redirect('index.php');
    }
    if (empty($_SESSION['store_age_confirmed'])) {
        http_response_code(403);
        exit('Confirm that you are 18 or older before continuing.');
    }

    $productId = $_POST['product_id'] ?? '';
    $catalog = store_catalog();
    if (!is_string($productId) || !isset($catalog[$productId])) {
        http_response_code(400);
        exit('That item is not available. Please refresh the menu and try again.');
    }
    $cart = store_cart();
    if ($action === 'add') {
        $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity < 1 || $quantity > 10) {
            http_response_code(400);
            exit('Choose a quantity from 1 to 10.');
        }
        $cart[$productId] = min(10, ($cart[$productId] ?? 0) + $quantity);
    } elseif ($action === 'remove') {
        unset($cart[$productId]);
    } else {
        http_response_code(400);
        exit('The requested action is not supported.');
    }
    $_SESSION['store_cart'] = $cart;
    store_redirect('index.php?view=cart');
}

$ageConfirmed = !empty($_SESSION['store_age_confirmed']);
$view = ($_GET['view'] ?? '') === 'cart' ? 'cart' : 'shop';
$items = store_cart_items();
$categories = ['Video collection', 'Photo & video bundle', 'Membership', 'Digital experience', 'Girlfriend experience'];
store_page_start($view === 'cart' ? 'Your selection' : 'The menu');
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">A little more personal</p>
        <h1>Made for <em>your</em> kind of connection.</h1>
        <p class="hero-intro">Explore exclusive collections, private memberships and personalized digital experiences. Choose what feels right; every detail is agreed before payment.</p>
        <a class="button button-light" href="#collections">Explore the menu <span aria-hidden="true">↓</span></a>
        <p class="hero-footnote">Discreet · Personal · Always consensual</p>
    </div>
    <div class="hero-art" aria-hidden="true">
        <div class="art-orbit orbit-one"></div><div class="art-orbit orbit-two"></div>
        <div class="art-card"><span>YOUR</span><strong>moment</strong><span>YOUR WAY</span></div>
        <span class="art-spark spark-one">✦</span><span class="art-spark spark-two">✧</span>
    </div>
    <div class="hero-note"><span>01</span> Curated for you</div>
</section>

<?php if (!$ageConfirmed): ?>
<div class="age-overlay" role="dialog" aria-modal="true" aria-labelledby="age-title">
    <div class="age-card">
        <span class="age-seal">18+</span>
        <p class="eyebrow">A quick check-in</p>
        <h2 id="age-title">This menu is for adults.</h2>
        <p>By continuing, you confirm that you are at least 18 years old (or the age of majority where you live) and that viewing adult-oriented material is legal in your location.</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= store_h(store_csrf_token()) ?>">
            <input type="hidden" name="action" value="age_confirm">
            <button class="button button-dark" type="submit">I’m 18 or older — continue</button>
        </form>
        <p class="age-exit">If you are under 18, please leave this page.</p>
    </div>
</div>
<?php endif; ?>

<?php if ($view === 'cart'): ?>
<section class="section section-narrow cart-section">
    <p class="eyebrow">Your personal edit</p>
    <h2>Your selection</h2>
    <p class="section-lead">Review your choices before sending an order request. Nothing is charged here.</p>
    <?php if (!$items): ?>
        <div class="empty-state"><span class="empty-icon">♡</span><h3>Your selection is waiting to be made.</h3><p>Browse the menu and add anything that catches your eye.</p><a class="button button-dark" href="index.php#collections">Browse the menu</a></div>
    <?php else: ?>
        <div class="cart-list">
            <?php foreach ($items as $item): ?>
                <article class="cart-row">
                    <div><p class="eyebrow"><?= store_h($item['category']) ?></p><h3><?= store_h($item['name']) ?></h3><p><?= store_h($item['includes']) ?> · Qty <?= (int)$item['quantity'] ?></p></div>
                    <div class="cart-price"><?= store_h(store_money($item['price_cents'] * $item['quantity'])) ?><?= $item['starting_price'] ? '<small> starting price</small>' : '' ?></div>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= store_h(store_csrf_token()) ?>">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= store_h($item['id']) ?>">
                        <button class="text-button" type="submit" aria-label="Remove <?= store_h($item['name']) ?>">Remove</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="cart-total"><span>Estimated starting total</span><strong><?= store_h(store_money(store_cart_total($items))) ?></strong></div>
        <p class="fine-print">Final pricing, availability, access terms and delivery are confirmed with you before payment. No payment details are collected on this website.</p>
        <a class="button button-dark" href="checkout.php">Continue to order request <span aria-hidden="true">→</span></a>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="trust-strip" aria-label="Our promises">
    <div><span>✦</span><strong>Clear terms</strong><small>Agreed before payment</small></div>
    <div><span>♡</span><strong>Personal by design</strong><small>Choose what suits you</small></div>
    <div><span>⌑</span><strong>Privacy minded</strong><small>No payment details collected</small></div>
</section>

<section class="section" id="collections">
    <div class="section-heading">
        <div><p class="eyebrow">Find your favorite</p><h2>The menu</h2></div>
        <p>Thoughtfully curated experiences, from ready-to-choose collections to fully customized connection.</p>
    </div>
    <div class="filter-row" role="group" aria-label="Filter menu">
        <button class="filter-chip is-active" type="button" data-filter="all">Everything</button>
        <?php foreach ($categories as $category): ?>
            <button class="filter-chip" type="button" data-filter="<?= store_h($category) ?>"><?= store_h($category) ?></button>
        <?php endforeach; ?>
    </div>
    <div class="product-grid">
        <?php foreach (store_catalog() as $product): ?>
            <article class="product-card" data-category="<?= store_h($product['category']) ?>">
                <div class="product-art art-<?= store_h(str_replace('&', '', str_replace(' ', '-', strtolower($product['category'])))) ?>">
                    <span class="product-icon" aria-hidden="true"><?= $product['category'] === 'Membership' ? '✦' : ($product['category'] === 'Girlfriend experience' ? '♡' : ($product['category'] === 'Digital experience' ? '◌' : '✧')) ?></span>
                    <span class="product-type"><?= store_h($product['category']) ?></span>
                </div>
                <div class="product-body">
                    <p class="product-includes"><?= store_h($product['includes']) ?></p>
                    <h3><?= store_h($product['name']) ?></h3>
                    <p class="product-description"><?= store_h($product['description']) ?></p>
                    <div class="product-buy">
                        <div class="product-price"><?= $product['starting_price'] ? '<small>Starting at</small>' : '' ?><strong><?= store_h(store_money($product['price_cents'])) ?></strong></div>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= store_h(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= store_h($product['id']) ?>">
                            <button class="add-button" type="submit" aria-label="Add <?= store_h($product['name']) ?>">Add <span aria-hidden="true">+</span></button>
                        </form>
                    </div>
                    <?php if ($product['starting_price']): ?><p class="card-note">Custom duration and limits confirmed in advance.</p><?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <article class="product-card custom-card" data-category="custom">
            <div class="product-art"><span class="product-icon" aria-hidden="true">✧</span><span class="product-type">Made for you</span></div>
            <div class="product-body">
                <p class="product-includes">Your interests, your way</p><h3>Custom package</h3>
                <p class="product-description">Combine eligible collections, virtual dates, gaming or a personalized schedule. Pricing is quoted before payment.</p>
                <div class="product-buy"><div class="product-price"><small>Custom quote</small><strong>Let’s talk</strong></div><a class="add-button" href="checkout.php?custom=1">Inquire <span aria-hidden="true">→</span></a></div>
            </div>
        </article>
    </div>
</section>

<section class="section upgrade-section" id="upgrades">
    <div class="section-heading">
        <div><p class="eyebrow">Make it yours</p><h2>Optional upgrades</h2></div>
        <p>Add a little extra to a selected experience. Each upgrade is individually quoted based on features, duration and availability.</p>
    </div>
    <div class="upgrade-grid">
        <?php foreach ([
            ['Gamer Girlfriend', 'Co-op sessions, friendly competition and gaming conversations.'],
            ['Extra Attention', 'Additional scheduled messaging and agreed conversation time.'],
            ['Romantic Messages', 'Personalized romantic messages and affectionate check-ins.'],
            ['Virtual Date', 'A planned movie night or shared digital activity.'],
            ['Everyday Companion', 'Agreed conversations about hobbies, routines and everyday life.'],
            ['Personalized Experience', 'A plan tailored to mutually agreed interests and preferences.'],
        ] as [$name, $description]): ?>
        <div class="upgrade-item"><span aria-hidden="true">✦</span><div><h3><?= store_h($name) ?></h3><p><?= store_h($description) ?></p></div><span class="quote-label">Quote</span></div>
        <?php endforeach; ?>
    </div>
</section>

<section class="how-section" id="how-it-works">
    <div class="how-intro"><p class="eyebrow">Easy, clear, and on your terms</p><h2>How it<br><em>works</em></h2><p>Send an inquiry with what you have in mind. We’ll confirm availability, the final price, boundaries and delivery details with you before any payment is made.</p></div>
    <ol class="steps">
        <li><span>01</span><div><h3>Choose your experience</h3><p>Add a collection or select a starting package. Share any upgrade ideas in your request.</p></div></li>
        <li><span>02</span><div><h3>Agree the details</h3><p>Duration, access, scheduling, boundaries and final price are confirmed together.</p></div></li>
        <li><span>03</span><div><h3>Payment, then delivery</h3><p>Payment arrangements and delivery are discussed directly after confirmation. This site does not process payments.</p></div></li>
    </ol>
</section>
<section class="closing-cta"><p class="eyebrow">Your next step</p><h2>Ready to make it personal?</h2><p>Pick a package and send a no-pressure order request.</p><a class="button button-light" href="index.php?view=cart">Review your selection <span aria-hidden="true">→</span></a></section>
<?php endif; ?>
<?php store_page_end(); ?>
