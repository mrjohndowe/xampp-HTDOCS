<?php

declare(strict_types=1);

/**
 * Escape HTML output.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a unique insurance policy number.
 */
function generatePolicyNumber( PDO $pdo, int $companyId, string $type = 'numbers', int $length = 8, string $prefix = '' ): string {$type = strtolower(trim($type));

    if (!in_array($type, ['numbers', 'words'], true)) {
        $type = 'numbers';
    }

    $length = max(1, min(32, $length));

    $prefix = strtoupper(trim($prefix));
    $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);

    if (strlen($prefix) >= $length) {
        $prefix = substr($prefix, 0, $length - 1);
    }

    $remainingLength = $length - strlen($prefix);

    if ($type === 'words') {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    } else {
        $characters = '0123456789';
    }

    $suffix = '';

    $characterCount = strlen($characters);

    for ($i = 0; $i < $remainingLength; $i++) {
        $suffix .= $characters[random_int(0, $characterCount - 1)];
    }

    $policyNumber = $prefix . $suffix;

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM insurance_cards
        WHERE company_id = :company_id
            AND policy_number = :policy_number
    ");

    $stmt->execute([
        ':company_id' => $companyId,
        ':policy_number' => $policyNumber
    ]);

    if ((int)$stmt->fetchColumn() > 0) {
        return generatePolicyNumber(
            $pdo,
            $companyId,
            $type,
            $length,
            $prefix
        );
    }

    return $policyNumber;
}

/**
 * Format date for display.
 */
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

/**
 * Remove ZIP formatting before saving.
 */
function normalizeZip(string $zip): string
{
    return preg_replace('/\D/', '', trim($zip));
}

/**
 * Format ZIP for display.
 *
 * 12345 -> 12345
 * 123456789 -> 12345-6789
 */
function formatZip(?string $zip): string
{
    $zip = preg_replace('/\D/', '', (string)$zip);

    if (strlen($zip) === 9) {
        return substr($zip, 0, 5) . '-' . substr($zip, 5);
    }

    return $zip;
}

/**
 * Validate ZIP code.
 */
function isValidZip(string $zip): bool
{
    $zip = normalizeZip($zip);

    return $zip === '' || strlen($zip) === 5 || strlen($zip) === 9;
}

/**
 * Redirect.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Get ID from URL.
 */
function getId(): int
{
    return max(0, (int)($_GET['id'] ?? 0));
}

/**
 * Get POST string.
 */
function postString(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

/**
 * Get POST integer.
 */
function postInt(string $key): ?int
{
    $value = $_POST[$key] ?? null;

    if ($value === null || $value === '') {
        return null;
    }

    return (int)$value;
}

/**
 * Generate cache-busting version number for CSS/JS.
 */
function getVersionNumber(): string
{
    $v1 = rand(1, 99);
    $v2 = rand(1, 99);
    $v3 = rand(1, 99);

    return $v1 . '.' . $v2 . '.' . $v3;
}

/**
 * Output data to browser console.
 */
function console_log(mixed $data): void
{
    echo '<script>console.log(' . json_encode($data) . ');</script>';
}
