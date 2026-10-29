<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 80500) {
    throw new RuntimeException('This storefront requires PHP 8.5 or newer.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

const STORE_CURRENCY = 'USD';

function store_catalog(): array
{
    static $catalog;
    if ($catalog !== null) {
        return $catalog;
    }

    $items = [
        ['oral-pleasure', 'Oral Pleasure Collection', 'Video collection', '10 videos', 10000, 'A curated collection of 10 videos.'],
        ['deepthroat', 'Deepthroat Collection', 'Video collection', '10 videos', 10000, 'A curated collection of 10 videos.'],
        ['missionary', 'Missionary Collection', 'Video collection', '10 videos', 10000, 'A curated collection of 10 videos.'],
        ['doggy-style', 'Doggy Style Collection', 'Video collection', '10 videos', 10000, 'A curated collection of 10 videos.'],
        ['fingering-dildo', 'Fingering & Dildo Play Collection', 'Video collection', '10 videos', 10000, 'A curated collection of 10 videos.'],
        ['blowjob', 'Blowjob Collection', 'Video collection', '10 videos', 10500, 'A curated collection of 10 videos.'],
        ['cream-pie', 'Cream Pie Collection', 'Video collection', '15 videos', 10000, 'A curated collection of 15 videos.'],
        ['full-body', 'Full-Body Nude Collection', 'Photo & video bundle', '10 videos + 20 photos', 10000, 'A bundle of 10 videos and 20 photos.'],
        ['telegram-premium', 'Telegram Premium', 'Membership', 'Lifetime, as specified in the offer', 15000, 'Access terms and included content are confirmed before purchase.'],
        ['snapchat-premium', 'Snapchat Premium', 'Membership', 'Lifetime, as specified in the offer', 15000, 'Access terms and included content are confirmed before purchase.'],
        ['facetime-session', 'FaceTime Session', 'Digital experience', 'Duration agreed in advance', 15000, 'A personalized video-call experience, scheduled subject to availability.'],
        ['dropbox-access', 'Full Dropbox Access', 'Digital experience', 'Access terms confirmed before purchase', 18000, 'Access to the agreed content collection and permissions.'],
        ['sweetheart', 'Sweetheart Package', 'Girlfriend experience', 'Customized duration', 15000, 'Personalized conversation, affectionate messages, and attention within agreed limits.', true],
        ['premium-gfe', 'Premium Girlfriend Experience', 'Girlfriend experience', 'Customized duration', 30000, 'More conversation, shared activities, virtual dates, and personalized interaction.', true],
        ['vip-gfe', 'VIP Girlfriend Experience', 'Girlfriend experience', 'Custom package', 50000, 'Priority scheduling, personalized virtual dates, and agreed interactive experiences.', true],
        // ['fisting', 'Fisting Collection', 'Video collection', '15 videos', 10000, 'A curated collection of 15 videos.'],
        // ['squirting', 'Squirting Collection', 'Video collection', '10 videos', 10500, 'A curated collection of 10 videos.'],
        // ['girls-on-girls', 'Girls-on-Girls Collection', 'Video collection', '10 videos', 10000, 'A curated collection of 10 videos.'],
        // ['anal', 'Anal Collection', 'Video collection', '10 videos', 10000, 'A curated collection of 10 videos.'],
    ];

    $catalog = [];
    foreach ($items as $item) {
        $catalog[$item[0]] = [
            'id' => $item[0],
            'name' => $item[1],
            'category' => $item[2],
            'includes' => $item[3],
            'price_cents' => $item[4],
            'description' => $item[5],
            'starting_price' => $item[6] ?? false,
        ];
    }
    return $catalog;
}

function store_db(): PDO
{
    static $db;
    if ($db instanceof PDO) {
        return $db;
    }
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('PDO SQLite is not enabled. Enable pdo_sqlite in your XAMPP PHP configuration and restart Apache.');
    }

    $storage = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
    if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
        throw new RuntimeException('Unable to create the storefront storage directory. Check its permissions.');
    }

    try {
        $db = new PDO('sqlite:' . $storage . DIRECTORY_SEPARATOR . 'storefront.sqlite', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec('PRAGMA journal_mode = WAL');
        $db->exec(
            'CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_code TEXT NOT NULL UNIQUE,
                customer_name TEXT NOT NULL,
                customer_email TEXT NOT NULL,
                payment_preference TEXT NOT NULL,
                delivery_preference TEXT NOT NULL,
                customer_notes TEXT NOT NULL,
                estimated_total_cents INTEGER NOT NULL,
                quote_required INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT "awaiting_confirmation",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                product_id TEXT NOT NULL,
                product_name TEXT NOT NULL,
                quantity INTEGER NOT NULL CHECK (quantity BETWEEN 1 AND 10),
                unit_price_cents INTEGER NOT NULL
            )'
        );
        $db->exec('CREATE INDEX IF NOT EXISTS orders_created_at_idx ON orders(created_at)');
    } catch (PDOException $exception) {
        error_log('Storefront database initialization failed: ' . $exception->getMessage());
        throw new RuntimeException('The storefront database could not be opened. Check the storage folder permissions and PDO SQLite configuration.');
    }
    return $db;
}

function store_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function store_money(int $cents): string
{
    return '$' . number_format($cents / 100, 2);
}

function store_csrf_token(): string
{
    if (empty($_SESSION['store_csrf'])) {
        $_SESSION['store_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['store_csrf'];
}

function store_verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(store_csrf_token(), $submitted)) {
        http_response_code(403);
        exit('Your session expired or the request could not be verified. Please go back and try again.');
    }
}

function store_redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function store_cart(): array
{
    return is_array($_SESSION['store_cart'] ?? null) ? $_SESSION['store_cart'] : [];
}

function store_cart_items(): array
{
    $catalog = store_catalog();
    $items = [];
    foreach (store_cart() as $id => $quantity) {
        if (!isset($catalog[$id]) || !is_int($quantity) || $quantity < 1 || $quantity > 10) {
            continue;
        }
        $items[] = $catalog[$id] + ['quantity' => $quantity];
    }
    return $items;
}

function store_cart_total(array $items): int
{
    return array_sum(array_map(
        static fn(array $item): int => $item['price_cents'] * $item['quantity'],
        $items
    ));
}

function store_page_start(string $title): void
{
    $cartCount = array_sum(store_cart());
?>
    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#281923">
        <title><?= store_h($title) ?> · Private & Personal</title>
        <link rel="stylesheet" href="assets/store.css">
    </head>

    <body>
        <header class="site-header">
            <a class="wordmark" href="index.php" aria-label="Private and Personal home">
                <span class="wordmark-mark">P</span><span>PRIVATE <i>&</i> PERSONAL</span>
            </a>
            <nav aria-label="Main navigation">
                <a href="index.php#collections">The menu</a>
                <a href="index.php#how-it-works">How it works</a>
                <a class="cart-link" href="index.php?view=cart">Your selection <span><?= (int)$cartCount ?></span></a>
            </nav>
        </header>
        <main>
        <?php
    }

    function store_page_end(): void
    {
        ?>
        </main>
        <footer class="site-footer">
            <span>Private & Personal · All prices in USD</span>
            <span>Adults only · 18+ · All interactions consensual</span>
            <a href="admin.php">Order inbox</a>
        </footer>
        <script src="assets/store.js" defer></script>
    </body>

    </html>
<?php
    }
