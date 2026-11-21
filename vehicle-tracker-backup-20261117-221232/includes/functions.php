<?php
// Vehicle Tracker - shared helper functions

declare(strict_types = 1);

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('now_iso')) {
    function now_iso(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('only_fields')) {
    /** Keep only allowed fields from input array. */
    function only_fields(array $input, array $allowed): array
    {
        $out = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $input)) {
                $out[$f] = $input[$f];
            }
        }
        return $out;
    }
}

if (!function_exists('money')) {
    function money(float $value, string $currency = 'USD'): string
    {
        $symbols = ['USD' => '$', 'EUR' => "€", 'GBP' => "£", 'CAD' => 'C$', 'AUD' => 'A$'];
        $sym = $symbols[$currency] ?? '$';
        return $sym . number_format((float)$value, 2);
    }
}

if (!function_exists('odometer_value')) {
    function odometer_value(float $value, string $unit = 'mi'): string
    {
        return number_format((float)$value, 1) . ' ' . strtoupper($unit);
    }
}

/**
 * Idempotency support for safe retries.
 * Returns cached response if key was already processed, else null.
 */
if (!function_exists('idempotency_check')) {
    function idempotency_check(PDO $pdo, string $key): ?array
    {
        if ($key === '') {
            return null;
        }
        $stmt = $pdo->prepare("SELECT response FROM idempotency_keys WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if ($row) {
            return json_decode($row['response'], true);
        }
        return null;
    }
}

if (!function_exists('idempotency_store')) {
    function idempotency_store(PDO $pdo, string $key, array $response): void
    {
        if ($key === '') {
            return;
        }
        $insert = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'INSERT IGNORE' : 'INSERT OR IGNORE';
        $pdo->prepare("$insert INTO idempotency_keys (`key`, response, created_at) VALUES (?, ?, ?)")
            ->execute([$key, json_encode($response), now_iso()]);
        // prune keys older than 30 days
        $pdo->prepare("DELETE FROM idempotency_keys WHERE created_at < ?")->execute([date('Y-m-d H:i:s', strtotime('-30 days'))]);
    }
}

/**
 * Current odometer for a vehicle: the greatest of the latest trip end reading
 * and any engine installation baseline (vehicle_odometer_at_install).
 */
if (!function_exists('vehicle_current_odometer')) {
    function vehicle_current_odometer(PDO $pdo, int $vehicleId): ?float
    {
        $stmt = $pdo->prepare("
            SELECT
                COALESCE((SELECT MAX(end_odometer) FROM trips WHERE vehicle_id = ?), 0) AS trip_odometer,
                COALESCE((SELECT MAX(vehicle_odometer_at_install) FROM engines WHERE vehicle_id = ?), 0) AS engine_odometer
        ");
        $stmt->execute([$vehicleId, $vehicleId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return max((float)$row['trip_odometer'], (float)$row['engine_odometer']);
    }
}

/**
 * Engine mileage = engine miles at install + (current vehicle odometer - odometer at install).
 * Returns null if engine not installed yet relative to vehicle records.
 */
if (!function_exists('engine_current_miles')) {
    function engine_current_miles(PDO $pdo, array $engine): ?float
    {
        $current = vehicle_current_odometer($pdo, (int)$engine['vehicle_id']);
        if ($current === null) {
            return (float)$engine['engine_mileage_at_install'];
        }
        return (float)$engine['engine_mileage_at_install'] + ($current - (float)$engine['vehicle_odometer_at_install']);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_check')) {
    function csrf_check(): void
    {
        $provided = $_POST['csrf_token'] ?? '';
        $expected = $_SESSION['csrf_token'] ?? '';
        if ($provided === '' || !hash_equals($expected, $provided)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'CSRF validation failed']);
            exit;
        }
    }
}

if (!function_exists('has_column')) {
    function has_column(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare("PRAGMA table_info($table)");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            if ($row['name'] === $column) {
                return true;
            }
        }
        return false;
    }
}

function h(mixed $value, int $flags = ENT_QUOTES | ENT_SUBSTITUTE, ?string $encoding = 'UTF-8', bool $doubleEncode = true): string
{
    return htmlspecialchars((string)($value ?? ''), $flags, $encoding, $doubleEncode);
}

function db_statement(PDO $pdo, string $sql, array $params): PDOStatement
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
