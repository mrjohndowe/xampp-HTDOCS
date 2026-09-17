<?php
declare(strict_types=1);

const APP_NAME = 'Private Novel Library';
const MAX_PDF_BYTES = 100 * 1024 * 1024;
const MAX_COVER_BYTES = 10 * 1024 * 1024;
const DATA_DIR = __DIR__ . '/data';
const DB_PATH = DATA_DIR . '/novel_library.sqlite';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function app_base(): string {
    static $base = null;
    if ($base !== null) return $base;
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $dir = rtrim(dirname($script), '/');
    if (str_ends_with($dir, '/api') || str_ends_with($dir, '/admin')) $dir = dirname($dir);
    $base = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    return $base;
}

function app_url(string $path = ''): string { return app_base() . '/' . ltrim($path, '/'); }
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function text_cut(string $value, int $length): string { return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length); }
function text_preview(string $value, int $length = 120): string {
    if (function_exists('mb_strimwidth')) return mb_strimwidth($value, 0, $length, '…');
    return strlen($value) > $length ? substr($value, 0, max(0, $length - 3)) . '...' : $value;
}
function json_response(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('PDO SQLite is not enabled in PHP. Enable extension=pdo_sqlite in XAMPP php.ini and restart Apache.');
    }
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0775, true) && !is_dir(DATA_DIR)) {
        throw new RuntimeException('Could not create the data directory.');
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('CREATE TABLE IF NOT EXISTS books (
        id TEXT PRIMARY KEY,
        title TEXT NOT NULL,
        author TEXT NOT NULL DEFAULT \'\',
        description TEXT NOT NULL DEFAULT \'\',
        filename TEXT NOT NULL UNIQUE,
        cover_filename TEXT,
        created_at INTEGER NOT NULL
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        book_id TEXT NOT NULL,
        reviewer_name TEXT NOT NULL,
        rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
        review_text TEXT NOT NULL,
        created_at INTEGER NOT NULL,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_reviews_book_id ON reviews(book_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_reviews_created_at ON reviews(created_at DESC)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE COLLATE NOCASE,
        password_hash TEXT NOT NULL,
        created_at INTEGER NOT NULL
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        attempted_at INTEGER NOT NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts(ip, attempted_at)');
    return $pdo;
}

function normalize_book_row(array $row): array {
    return [
        'id' => (string)$row['id'],
        'title' => (string)$row['title'],
        'author' => (string)($row['author'] ?? ''),
        'description' => (string)($row['description'] ?? ''),
        'filename' => (string)$row['filename'],
        'coverFilename' => !empty($row['cover_filename']) ? (string)$row['cover_filename'] : null,
        'createdAt' => (int)$row['created_at'],
    ];
}

function books_all(): array {
    $rows = db()->query('SELECT * FROM books ORDER BY created_at DESC')->fetchAll();
    return array_map('normalize_book_row', $rows);
}

function reviews_all(): array {
    return db()->query('SELECT id, book_id, reviewer_name, rating, review_text, created_at FROM reviews ORDER BY created_at DESC, id DESC')->fetchAll();
}

function admins_all(): array {
    return db()->query('SELECT id, username, password_hash, created_at FROM admins ORDER BY id')->fetchAll();
}

function find_book(string $id): ?array {
    $stmt = db()->prepare('SELECT * FROM books WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? normalize_book_row($row) : null;
}

function reviews_for_book(string $id): array {
    $stmt = db()->prepare('SELECT id, book_id, reviewer_name, rating, review_text, created_at FROM reviews WHERE book_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function book_stats(string $id): array {
    $stmt = db()->prepare('SELECT COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS average_rating FROM reviews WHERE book_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: ['review_count' => 0, 'average_rating' => 0];
    return ['average_rating' => (float)$row['average_rating'], 'review_count' => (int)$row['review_count']];
}

function book_payload(array $b): array {
    $s = book_stats((string)$b['id']);
    $filename = basename((string)$b['filename']);
    $cover = !empty($b['coverFilename']) ? basename((string)$b['coverFilename']) : null;
    return [
        'id' => (string)$b['id'],
        'title' => (string)($b['title'] ?? ''),
        'author' => (string)($b['author'] ?? ''),
        'description' => (string)($b['description'] ?? ''),
        'filename' => $filename,
        'fileUrl' => app_url('uploads/' . rawurlencode($filename)),
        'coverFilename' => $cover,
        'coverUrl' => $cover ? app_url('covers/' . rawurlencode($cover)) : null,
        'createdAt' => (int)($b['createdAt'] ?? 0),
        'averageRating' => (float)$s['average_rating'],
        'reviewCount' => (int)$s['review_count'],
    ];
}

function add_book(string $id, string $title, string $author, string $description, string $filename, ?string $coverFilename): void {
    $stmt = db()->prepare('INSERT INTO books (id, title, author, description, filename, cover_filename, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$id, $title, $author, $description, $filename, $coverFilename, (int)round(microtime(true) * 1000)]);
}

function add_review(string $bookId, string $name, int $rating, string $text): void {
    $stmt = db()->prepare('INSERT INTO reviews (book_id, reviewer_name, rating, review_text, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$bookId, text_cut($name, 80), $rating, text_cut($text, 3000), time()]);
}

function delete_review(int $id): void {
    $stmt = db()->prepare('DELETE FROM reviews WHERE id = ?');
    $stmt->execute([$id]);
}

function delete_book_db(string $id): ?array {
    $book = find_book($id);
    if (!$book) return null;
    $stmt = db()->prepare('DELETE FROM books WHERE id = ?');
    $stmt->execute([$id]);
    return $book;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf_or_fail(): void {
    $t = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(403);
        exit('Invalid security token.');
    }
}

function admin_exists(): bool { return (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0; }
function is_admin(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void {
    if (!is_admin()) {
        header('Location: ' . app_url('admin/login.php'));
        exit;
    }
}
function create_admin(string $username, string $password): int {
    $stmt = db()->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, ?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), time()]);
    return (int)db()->lastInsertId();
}
function find_admin_by_username(string $username): ?array {
    $stmt = db()->prepare('SELECT id, username, password_hash, created_at FROM admins WHERE username = ? COLLATE NOCASE LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    return $row ?: null;
}
function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? 'unknown'; }
function login_blocked(): bool {
    $cut = time() - 900;
    $pdo = db();
    $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')->execute([$cut]);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= ?');
    $stmt->execute([client_ip(), $cut]);
    return (int)$stmt->fetchColumn() >= 5;
}
function failed_login(): void {
    $stmt = db()->prepare('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)');
    $stmt->execute([client_ip(), time()]);
}
function clear_logins(): void {
    $stmt = db()->prepare('DELETE FROM login_attempts WHERE ip = ?');
    $stmt->execute([client_ip()]);
}
function flash(string $key, ?string $value = null): ?string {
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}

// Initialize the SQLite file and schema on every request if necessary.
db();
