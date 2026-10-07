<?php

declare(strict_types=1);

require '../.global/extra/functions.php';

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function generatePolicyNumber(
    PDO $pdo,
    int $companyId,
    string $type = 'numbers',
    int $length = 8,
    string $prefix = ''
): string {
    $type = strtolower(trim($type));

    if (!in_array($type, ['numbers', 'words'], true)) {
        $type = 'numbers';
    }

    $length = max(1, min(32, $length));

    $prefix = strtoupper(trim($prefix));
    $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);

    if (strlen($prefix) >= $length) {
        $prefix = substr($prefix, 0, max(0, $length - 1));
    }

    $remainingLength = $length - strlen($prefix);

    if ($type === 'words') {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    } else {
        $characters = '0123456789';
    }

    do {
        $suffix = '';
        $characterCount = strlen($characters);

        for ($i = 0; $i < $remainingLength; $i++) {
            $suffix .= $characters[random_int(0, $characterCount - 1)];
        }

        $policyNumber = $prefix . $suffix;

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM insurance_cards
            WHERE policy_number = :policy_number
        ");

        $stmt->execute([
            ':policy_number' => $policyNumber
        ]);

        $exists = (int)$stmt->fetchColumn() > 0;
    } while ($exists);

    return $policyNumber;
}

function formatDate(?string $date): string
{
    if (!$date) {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('m/d/Y', $timestamp);
}

function normalizeZip(string $zip): string
{
    return preg_replace('/\D/', '', trim($zip));
}

function formatZip(?string $zip): string
{
    $zip = preg_replace('/\D/', '', (string)$zip);

    if (strlen($zip) === 9) {
        return substr($zip, 0, 5) . '-' . substr($zip, 5);
    }

    return $zip;
}

function isValidZip(string $zip): bool
{
    $zip = normalizeZip($zip);

    return $zip === '' || strlen($zip) === 5 || strlen($zip) === 9;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function getId(): int
{
    return max(0, (int)($_GET['id'] ?? 0));
}

function postString(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function postInt(string $key): ?int
{
    $value = $_POST[$key] ?? null;

    if ($value === null || $value === '') {
        return null;
    }

    return (int)$value;
}

function getVersionNumber(): string
{
    $v1 = rand(1, 99);
    $v2 = rand(1, 99);
    $v3 = rand(1, 99);

    return $v1 . '.' . $v2 . '.' . $v3;
}

function console_log(mixed $data): void
{
    echo '<script>console.log(' . json_encode($data) . ');</script>';
}

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

function isAdminAuthenticated(): bool
{
    startAdminSession();

    return !empty($_SESSION['insurance_admin_authenticated'])
        && $_SESSION['insurance_admin_authenticated'] === true;
}

function requireAdmin(): void
{
    if (!isAdminAuthenticated()) {
        redirect('index.php?p=admin');
    }
}

function adminLogin(PDO $pdo, string $username, string $password): bool
{
    startAdminSession();

    $stmt = $pdo->prepare("
        SELECT id, username, password_hash
        FROM admin_users
        WHERE username = :username
          AND active = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':username' => $username
    ]);

    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['insurance_admin_authenticated'] = true;
    $_SESSION['insurance_admin_id'] = (int)$user['id'];
    $_SESSION['insurance_admin_username'] = $user['username'];

    return true;
}

function adminLogout(): void
{
    startAdminSession();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function barcodeValue(array $card): string
{
    return implode('|', array_filter([
        trim((string)($card['policy_number'] ?? '')),
        trim((string)($card['vin'] ?? '')),
        trim((string)($card['effective_date'] ?? '')),
        trim((string)($card['expiration_date'] ?? ''))
    ], static fn(string $value): bool => $value !== ''));
}

function generateBarcodeSvg(string $value, int $width = 520, int $height = 90): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    $encoded = rawurlencode($value);

    $url = 'https://bwipjs-api.metafloor.com/?bcid=code128'
        . '&text=' . $encoded
        . '&scale=2'
        . '&height=' . max(20, (int)round($height / 10))
        . '&includetext';

    return $url;
}

function generatePhoneNumber(string $areaCode = ''): string {
    if($areaCode !== ''){
        $areaCode = preg_replace('/\D/', '', $areaCode);
        if(strlen($areaCode) !== 3) {
            $areaCode = '';
        }
    }

    if($areaCode === ''){
        $areaCode = (string)randon_int(200, 999);
    }

    $prefix = (string)random_int(200, 999);
    $lineNumber = (string)random_int(0, 9999);

    return sprintf(
        '(%s) %s-%04d',
        $areaCode,
        $prefix,
        (int)$lineNumber
    );
}
