<?php
declare(strict_types=1);

function fuel_schema(PDO $pdo): void
{
    $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    $columns = $mysql ? $pdo->query('SHOW COLUMNS FROM expenses')->fetchAll(PDO::FETCH_COLUMN) : array_column($pdo->query('PRAGMA table_info(expenses)')->fetchAll(), 'name');
    foreach (['fuel_odometer' => 'REAL', 'gallons' => 'REAL', 'price_per_gallon' => 'REAL', 'full_tank' => 'INTEGER'] as $name => $type) {
        if (!in_array($name, $columns, true)) $pdo->exec("ALTER TABLE expenses ADD COLUMN $name $type NULL");
    }
}

function fuel_values(PDO $pdo, array $input, ?int $id = null): array
{
    $fuel = ($input['category'] ?? '') === 'fuel';
    $out = ['fuel_odometer'=>null, 'gallons'=>null, 'price_per_gallon'=>null, 'full_tank'=>null];
    if (!$fuel) return $out;
    foreach (['fuel_odometer','gallons','price_per_gallon'] as $field) {
        $value = $input[$field] ?? null;
        if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0 || ($field === 'gallons' && (float)$value <= 0)) {
            throw new InvalidArgumentException('Enter a nonnegative odometer, gallons greater than zero, and a nonnegative price per gallon.');
        }
        $out[$field] = (float)$value;
    }
    $date = (string)($input['expense_date'] ?? '');
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new InvalidArgumentException('Enter a valid fuel date.');
    $out['full_tank'] = !empty($input['full_tank']) ? 1 : 0;
    $out['amount'] = round($out['gallons'] * $out['price_per_gallon'], 2);
    if (!is_finite($out['amount'])) throw new InvalidArgumentException('Fuel total is too large.');
    $stmt = $pdo->prepare("SELECT id, expense_date, fuel_odometer FROM expenses WHERE category='fuel' AND vehicle_id=? AND fuel_odometer IS NOT NULL AND id<>? ORDER BY expense_date, id");
    $stmt->execute([(int)($input['vehicle_id'] ?? 0), $id ?? 0]);
    foreach ($stmt->fetchAll() as $row) {
        $before = $row['expense_date'] < $date || ($row['expense_date'] === $date && ($id === null || (int)$row['id'] < $id));
        if (($before && (float)$row['fuel_odometer'] > $out['fuel_odometer']) || (!$before && (float)$row['fuel_odometer'] < $out['fuel_odometer'])) {
            throw new InvalidArgumentException('Fuel odometer conflicts with another fill-up. Check the date and reading.');
        }
    }
    return $out;
}

function fuel_mpg_map(PDO $pdo): array
{
    $unit = $pdo->query('SELECT distance_unit FROM settings WHERE id=1')->fetchColumn();
    $rows = $pdo->query("SELECT * FROM expenses WHERE category='fuel' ORDER BY vehicle_id, expense_date, id")->fetchAll();
    $states = []; $result = [];
    foreach ($rows as $row) {
        $v = $row['vehicle_id'];
        if (!isset($states[$v])) $states[$v] = ['baseline'=>null, 'gallons'=>0.0];
        $state = &$states[$v];
        $result[$row['id']] = null;
        if ($row['fuel_odometer'] === null || $row['gallons'] === null) {
            $state = ['baseline'=>null, 'gallons'=>0.0];
            unset($state); continue;
        }
        if ($state['baseline'] !== null) $state['gallons'] += (float)$row['gallons'];
        if ($row['full_tank']) {
            $distance = (float)$row['fuel_odometer'] - (float)($state['baseline'] ?? $row['fuel_odometer']);
            if ($state['baseline'] !== null && $distance > 0 && $state['gallons'] > 0) {
                $result[$row['id']] = round(($unit === 'km' ? $distance / 1.609344 : $distance) / $state['gallons'], 2);
            }
            $state = ['baseline'=>(float)$row['fuel_odometer'], 'gallons'=>0.0];
        }
        unset($state);
    }
    return $result;
}
