<?php
// Vehicle Tracker - API token authentication (hashed, revocable tokens)

declare(strict_types = 1);

require_once __DIR__ . '/db.php';

/** @var PDO $pdo */

function api_required(): array
{
    global $pdo;

    $headers = getallheaders() ?: [];
    $token = '';

    if (isset($headers['Authorization']) && preg_match('/^Bearer\s+(.+)$/i', $headers['Authorization'], $m)) {
        $token = $m[1];
    } elseif (isset($headers['X-Api-Token'])) {
        $token = $headers['X-Api-Token'];
    } elseif (isset($_SERVER['HTTP_X_API_TOKEN'])) {
        $token = $_SERVER['HTTP_X_API_TOKEN'];
    } elseif (!empty($_GET['token'])) {
        $token = $_GET['token']; // fallback for simple clients
    }

    if ($token === '') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }

    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare("
        SELECT t.*, u.username, u.role
        FROM api_tokens t
        JOIN users u ON u.id = t.user_id
        WHERE t.token_hash = ? AND t.revoked = 0
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid or revoked token']);
        exit;
    }

    if (!empty($row['expires_at']) && $row['expires_at'] < date('Y-m-d H:i:s')) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Token expired']);
        exit;
    }

    $pdo->prepare("UPDATE api_tokens SET last_used_at = ? WHERE id = ?")
        ->execute([date('Y-m-d H:i:s'), (int)$row['id']]);

    return [
        'user_id' => (int)$row['user_id'],
        'username' => $row['username'],
        'role' => $row['role'],
        'token_id' => (int)$row['id'],
    ];
}

function generate_token_string(): string
{
    return bin2hex(random_bytes(24));
}

function hash_token(string $token): string
{
    return hash('sha256', $token);
}
