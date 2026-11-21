<?php
// Vehicle Tracker - authenticated JSON API
// Single router used by both the website (via fetch) and the Python desktop app.

declare(strict_types = 1);

// ---- Installation guard: config.lock must exist (no credentials inside) ----
$APP_DIR = __DIR__ . '/..';
$LOCK_FILE = $APP_DIR . '/config.lock';

if (!file_exists($LOCK_FILE)) {
    http_response_code(409);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Onboarding not complete. Run the installer at /install first.']);
    exit;
}

require_once $APP_DIR . '/includes/db.php';
require_once $APP_DIR . '/includes/functions.php';
require_once $APP_DIR . '/includes/auth.php';

/** @var PDO $pdo */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($rawUri, PHP_URL_PATH);

// Route by the /api segment; works whether the app lives at the web root or in a subfolder.
$apiPos = strpos($path, '/api');
if ($apiPos === false) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}
$path = substr($path, $apiPos);
// $path is like /api/vehicles/12
$segments = array_filter(explode('/', trim($path, '/'))); // ['api','vehicles','12']

// No-auth routes
if ($segments[0] ?? '' === 'status') {
    $lock = json_decode(file_get_contents($LOCK_FILE), true);
    echo json_encode(['installed' => true, 'driver' => $lock['driver'] ?? '?', 'installed_at' => $lock['installed_at'] ?? '']);
    exit;
}

if ($segments[0] ?? '' === 'login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    $token = generate_token_string();
    $expires = date('Y-m-d H:i:s', strtotime('+90 days'));
    $pdo->prepare("INSERT INTO api_tokens (user_id, token_hash, name, created_at, expires_at) VALUES (?, ?, 'API login', ?, ?)")
        ->execute([(int)$user['id'], hash_token($token), now_iso(), $expires]);
    echo json_encode(['token' => $token, 'user' => ['id' => (int)$user['id'], 'username' => $user['username'], 'role' => $user['role']]]);
    exit;
}

// All remaining routes require authentication
$user = api_required();

// ---------- helpers ----------

function require_body(PDO $pdo, array $allowed): array
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body']);
        exit;
    }
    return only_fields($input, $allowed);
}

function idem_key(): ?string
{
    $headers = getallheaders() ?: [];
    return $headers['Idempotency-Key'] ?? $headers['IDEMPOTENCY-KEY'] ?? null;
}

function respond_idem(PDO $pdo, string $key, array $response, int $status = 200): void
{
    if ($key !== null) {
        idempotency_store($pdo, $key, $response);
    }
    http_response_code($status);
    echo json_encode($response);
    exit;
}

// ---------- route ----------

$resource = $segments[1] ?? null;
$id       = $segments[2] ?? null;
$sub      = $segments[3] ?? null;

// ---- Vehicles ----
if ($resource === 'vehicles') {
    if ($method === 'GET' && $id === null) {
        $stmt = $pdo->query('SELECT * FROM vehicles ORDER BY id DESC');
        echo json_encode(['vehicles' => $stmt->fetchAll()]);
        exit;
    }
    if ($method === 'GET' && $id !== null) {
        $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
        $stmt->execute([$id]);
        $v = $stmt->fetch();
        if (!$v) { http_response_code(404); echo json_encode(['error' => 'Vehicle not found']); exit; }
        echo json_encode(['vehicle' => $v]);
        exit;
    }
    if ($method === 'POST' && $id === null) {
        $input = require_body($pdo, ['vin','make','model','year','license_plate','color','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        if ($input['make'] === '' || $input['model'] === '') {
            http_response_code(400); echo json_encode(['error' => 'Make and model required']); exit;
        }
        $stmt = $pdo->prepare("INSERT INTO vehicles (vin, make, model, year, license_plate, color, notes, created_at)
                               VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$input['vin'] ?? '', $input['make'], $input['model'], $input['year'] ?? null,
                        $input['license_plate'] ?? '', $input['color'] ?? '', $input['notes'] ?? '', now_iso()]);
        $resp = ['vehicle_id' => (int)$pdo->lastInsertId()];
        respond_idem($pdo, $key, $resp, 201);
    }
    if (($method === 'PATCH' || $method === 'PUT') && $id !== null) {
        $input = require_body($pdo, ['vin','make','model','year','license_plate','color','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM vehicles WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { http_response_code(404); echo json_encode(['error' => 'Vehicle not found']); exit; }
        $fields = [];
        $vals = [];
        foreach ($input as $k => $v) {
            $fields[] = "$k = ?";
            $vals[] = $v;
        }
        $vals[] = $id;
        $pdo->prepare("UPDATE vehicles SET " . implode(', ', $fields) . " WHERE id = ?")->execute($vals);
        $resp = ['updated' => true];
        respond_idem($pdo, $key, $resp);
    }
    if ($method === 'DELETE' && $id !== null) {
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM vehicles WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { http_response_code(404); echo json_encode(['error' => 'Vehicle not found']); exit; }
        $pdo->prepare('DELETE FROM vehicles WHERE id = ?')->execute([$id]);
        respond_idem($pdo, $key, ['deleted' => true]);
    }
}

// ---- Trips ----
if ($resource === 'trips') {
    if ($method === 'GET' && $id === null) {
        $where = [];
        $vals = [];
        if (!empty($_GET['vehicle_id'])) { $where[] = 'vehicle_id = ?'; $vals[] = $_GET['vehicle_id']; }
        if (!empty($_GET['from'])) { $where[] = 'trip_date >= ?'; $vals[] = $_GET['from']; }
        if (!empty($_GET['to'])) { $where[] = 'trip_date <= ?'; $vals[] = $_GET['to']; }
        $sql = 'SELECT t.*, v.make, v.model, v.license_plate FROM trips t LEFT JOIN vehicles v ON v.id = t.vehicle_id';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY trip_date DESC, id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);
        echo json_encode(['trips' => $stmt->fetchAll()]);
        exit;
    }
    if ($method === 'GET' && $id !== null) {
        $stmt = $pdo->prepare('SELECT * FROM trips WHERE id = ?');
        $stmt->execute([$id]);
        $t = $stmt->fetch();
        if (!$t) { http_response_code(404); echo json_encode(['error' => 'Trip not found']); exit; }
        echo json_encode(['trip' => $t]);
        exit;
    }
    if ($method === 'POST' && $id === null) {
        $input = require_body($pdo, ['vehicle_id','trip_date','start_odometer','end_odometer','purpose','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $start = (float)$input['start_odometer'];
        $end   = (float)$input['end_odometer'];
        if ($end < $start) { http_response_code(400); echo json_encode(['error' => 'End odometer must be >= start odometer']); exit; }
        // vehicle exists
        $stmt = $pdo->prepare('SELECT id FROM vehicles WHERE id = ?');
        $stmt->execute([$input['vehicle_id']]);
        if (!$stmt->fetch()) { http_response_code(400); echo json_encode(['error' => 'Vehicle not found']); exit; }
        $distance = $end - $start;
        $stmt = $pdo->prepare("INSERT INTO trips (vehicle_id, trip_date, start_odometer, end_odometer, distance, purpose, notes, created_at)
                               VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$input['vehicle_id'], $input['trip_date'], $start, $end, $distance, $input['purpose'] ?? '', $input['notes'] ?? '', now_iso()]);
        $resp = ['trip_id' => (int)$pdo->lastInsertId(), 'distance' => $distance];
        respond_idem($pdo, $key, $resp, 201);
    }
    if (($method === 'PATCH' || $method === 'PUT') && $id !== null) {
        $input = require_body($pdo, ['trip_date','start_odometer','end_odometer','purpose','notes','vehicle_id']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $start = isset($input['start_odometer']) ? (float)$input['start_odometer'] : null;
        $end   = isset($input['end_odometer']) ? (float)$input['end_odometer'] : null;
        if ($start !== null && $end !== null && $end < $start) {
            http_response_code(400); echo json_encode(['error' => 'End odometer must be >= start odometer']); exit;
        }
        $stmt = $pdo->prepare('SELECT id FROM trips WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { http_response_code(404); echo json_encode(['error' => 'Trip not found']); exit; }
        $fields = []; $vals = [];
        foreach ($input as $k => $v) {
            if (in_array($k, ['trip_date','start_odometer','end_odometer','purpose','notes','vehicle_id'])) {
                $fields[] = "$k = ?";
                $vals[] = $v;
            }
        }
        if (isset($input['start_odometer']) && isset($input['end_odometer'])) {
            $distance = (float)$input['end_odometer'] - (float)$input['start_odometer'];
            $fields[] = 'distance = ?';
            $vals[] = $distance;
        }
        $vals[] = $id;
        $pdo->prepare("UPDATE trips SET " . implode(', ', $fields) . " WHERE id = ?")->execute($vals);
        $resp = ['updated' => true];
        respond_idem($pdo, $key, $resp);
    }
    if ($method === 'DELETE' && $id !== null) {
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM trips WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { http_response_code(404); echo json_encode(['error' => 'Trip not found']); exit; }
        $pdo->prepare('DELETE FROM trips WHERE id = ?')->execute([$id]);
        respond_idem($pdo, $key, ['deleted' => true]);
    }
}

// ---- Engines ----
if ($resource === 'engines') {
    if ($method === 'GET' && $id === null) {
        $where = !empty($_GET['vehicle_id']) ? 'WHERE vehicle_id = ?' : '';
        $vals = !empty($_GET['vehicle_id']) ? [$_GET['vehicle_id']] : [];
        $stmt = $pdo->prepare("SELECT e.*, v.make, v.model FROM engines e LEFT JOIN vehicles v ON v.id = e.vehicle_id $where ORDER BY e.installed_date DESC, e.id DESC");
        $stmt->execute($vals);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $e) {
            $currentMiles = engine_current_miles($pdo, $e);
            $conflict = false;
            $maxOdoStmt = $pdo->prepare('SELECT MAX(end_odometer) m FROM trips WHERE vehicle_id = ?');
            $maxOdoStmt->execute([$e['vehicle_id']]);
            $maxOdo = $maxOdoStmt->fetchColumn();
            if ($maxOdo !== false && $maxOdo !== '' && (float)$e['vehicle_odometer_at_install'] > (float)$maxOdo) {
                $conflict = true;
            }
            $out[] = array_merge($e, ['engine_miles' => $currentMiles === null ? null : (float)$currentMiles, 'conflict' => $conflict]);
        }
        echo json_encode(['engines' => $out]);
        exit;
    }
    if ($method === 'GET' && $id !== null) {
        $stmt = $pdo->prepare('SELECT * FROM engines WHERE id = ?');
        $stmt->execute([$id]);
        $e = $stmt->fetch();
        if (!$e) { http_response_code(404); echo json_encode(['error' => 'Engine not found']); exit; }
        echo json_encode(['engine' => array_merge($e, ['engine_miles' => engine_current_miles($pdo, $e)])]);
        exit;
    }
    if ($method === 'POST' && $id === null) {
        $input = require_body($pdo, ['vehicle_id','serial_number','installed_date','engine_mileage_at_install','vehicle_odometer_at_install','replacement_reason','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM vehicles WHERE id = ?');
        $stmt->execute([$input['vehicle_id']]);
        if (!$stmt->fetch()) { http_response_code(400); echo json_encode(['error' => 'Vehicle not found']); exit; }
        // conflict check against current recorded vehicle odometer
        $conflict = false;
        $maxOdoStmt = $pdo->prepare('SELECT MAX(end_odometer) m FROM trips WHERE vehicle_id = ?');
        $maxOdoStmt->execute([$input['vehicle_id']]);
        $maxOdo = $maxOdoStmt->fetchColumn();
        if ($maxOdo !== false && $maxOdo !== '' && (float)$input['vehicle_odometer_at_install'] > (float)$maxOdo) {
            $conflict = true;
        }
        $stmt = $pdo->prepare("INSERT INTO engines (vehicle_id, serial_number, installed_date, engine_mileage_at_install,
                               vehicle_odometer_at_install, status, replacement_reason, notes, created_at)
                               VALUES (?,?,?,?,?,'active',?,?,?)");
        $stmt->execute([$input['vehicle_id'], $input['serial_number'] ?? '', $input['installed_date'],
                        (float)$input['engine_mileage_at_install'], (float)$input['vehicle_odometer_at_install'],
                        $input['replacement_reason'] ?? '', $input['notes'] ?? '', now_iso()]);
        $resp = ['engine_id' => (int)$pdo->lastInsertId(), 'conflict' => $conflict];
        respond_idem($pdo, $key, $resp, 201);
    }
    if (($method === 'PATCH' || $method === 'PUT') && $id !== null) {
        $input = require_body($pdo, ['serial_number','installed_date','engine_mileage_at_install','vehicle_odometer_at_install','status','replacement_reason','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT * FROM engines WHERE id = ?');
        $stmt->execute([$id]);
        $e = $stmt->fetch();
        if (!$e) { http_response_code(404); echo json_encode(['error' => 'Engine not found']); exit; }
        if (!empty($input['status']) && $input['status'] === 'replaced' && empty($e['replaced_at'])) {
            $input['replaced_at'] = now_iso();
        }
        $fields = []; $vals = [];
        foreach ($input as $k => $v) {
            if (in_array($k, ['serial_number','installed_date','engine_mileage_at_install','vehicle_odometer_at_install','status','replaced_at','replacement_reason','notes'])) {
                $fields[] = "$k = ?";
                $vals[] = $v;
            }
        }
        $vals[] = $id;
        $pdo->prepare("UPDATE engines SET " . implode(', ', $fields) . " WHERE id = ?")->execute($vals);
        $resp = ['updated' => true];
        respond_idem($pdo, $key, $resp);
    }
    if ($method === 'DELETE' && $id !== null) {
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM engines WHERE id = ? AND status = ?');
        $stmt->execute([$id, 'active']);
        if (!$stmt->fetch()) { http_response_code(400); echo json_encode(['error' => 'Cannot delete the active engine']); exit; }
        $pdo->prepare('DELETE FROM engines WHERE id = ?')->execute([$id]);
        respond_idem($pdo, $key, ['deleted' => true]);
    }
}

// ---- Mechanics ----
if ($resource === 'mechanics') {
    if ($method === 'GET' && $id === null) {
        $stmt = $pdo->query('SELECT * FROM mechanics ORDER BY name');
        echo json_encode(['mechanics' => $stmt->fetchAll()]);
        exit;
    }
    if ($method === 'GET' && $id !== null) {
        $stmt = $pdo->prepare('SELECT * FROM mechanics WHERE id = ?');
        $stmt->execute([$id]);
        $m = $stmt->fetch();
        if (!$m) { http_response_code(404); echo json_encode(['error' => 'Mechanic not found']); exit; }
        echo json_encode(['mechanic' => $m]);
        exit;
    }
    if ($method === 'POST' && $id === null) {
        $input = require_body($pdo, ['name','phone','email','address','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare("INSERT INTO mechanics (name, phone, email, address, notes, created_at) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$input['name'], $input['phone'] ?? '', $input['email'] ?? '', $input['address'] ?? '', $input['notes'] ?? '', now_iso()]);
        respond_idem($pdo, $key, ['mechanic_id' => (int)$pdo->lastInsertId()], 201);
    }
    if (($method === 'PATCH' || $method === 'PUT') && $id !== null) {
        $input = require_body($pdo, ['name','phone','email','address','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM mechanics WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { http_response_code(404); echo json_encode(['error' => 'Mechanic not found']); exit; }
        $fields = []; $vals = [];
        foreach ($input as $k => $v) {
            $fields[] = "$k = ?";
            $vals[] = $v;
        }
        $vals[] = $id;
        $pdo->prepare("UPDATE mechanics SET " . implode(', ', $fields) . " WHERE id = ?")->execute($vals);
        respond_idem($pdo, $key, ['updated' => true]);
    }
    if ($method === 'DELETE' && $id !== null) {
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $pdo->prepare('DELETE FROM mechanics WHERE id = ?')->execute([$id]);
        respond_idem($pdo, $key, ['deleted' => true]);
    }
}

// ---- Invoices ----
if ($resource === 'invoices') {
    if ($method === 'GET' && $id === null && $sub !== 'payments') {
        $where = []; $vals = [];
        if (!empty($_GET['mechanic_id'])) { $where[] = 'mechanic_id = ?'; $vals[] = $_GET['mechanic_id']; }
        if (!empty($_GET['status'])) { $where[] = 'status = ?'; $vals[] = $_GET['status']; }
        $sql = 'SELECT i.*, m.name AS mechanic_name FROM invoices i LEFT JOIN mechanics m ON m.id = i.mechanic_id';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY i.invoice_date DESC, i.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $inv) {
            $paidStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = ?');
            $paidStmt->execute([$inv['id']]);
            $paid = (float)$paidStmt->fetchColumn();
            $balance = (float)$inv['amount'] - $paid;
            $overdue = $balance > 0 && $inv['due_date'] < date('Y-m-d');
            $status = $inv['status'];
            if ($balance <= 0) $status = 'paid';
            elseif ($overdue) $status = 'overdue';
            $out[] = array_merge($inv, ['paid' => $paid, 'balance' => $balance, 'overdue' => $overdue, 'status_calc' => $status]);
        }
        echo json_encode(['invoices' => $out]);
        exit;
    }
    if ($method === 'GET' && $id !== null && $sub !== 'payments') {
        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
        $stmt->execute([$id]);
        $inv = $stmt->fetch();
        if (!$inv) { http_response_code(404); echo json_encode(['error' => 'Invoice not found']); exit; }
        echo json_encode(['invoice' => $inv]);
        exit;
    }
    if ($method === 'POST' && $id === null) {
        $input = require_body($pdo, ['mechanic_id','invoice_number','invoice_date','due_date','amount','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM mechanics WHERE id = ?');
        $stmt->execute([$input['mechanic_id']]);
        if (!$stmt->fetch()) { http_response_code(400); echo json_encode(['error' => 'Mechanic not found']); exit; }
        $stmt = $pdo->prepare("INSERT INTO invoices (mechanic_id, invoice_number, invoice_date, due_date, amount, status, notes, created_at)
                               VALUES (?,?,?,?,?,'open',?,?)");
        $stmt->execute([$input['mechanic_id'], $input['invoice_number'], $input['invoice_date'], $input['due_date'],
                        (float)$input['amount'], $input['notes'] ?? '', now_iso()]);
        respond_idem($pdo, $key, ['invoice_id' => (int)$pdo->lastInsertId()], 201);
    }
    if (($method === 'PATCH' || $method === 'PUT') && $id !== null) {
        $input = require_body($pdo, ['invoice_number','invoice_date','due_date','amount','status','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM invoices WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { http_response_code(404); echo json_encode(['error' => 'Invoice not found']); exit; }
        $fields = []; $vals = [];
        foreach ($input as $k => $v) {
            if (in_array($k, ['invoice_number','invoice_date','due_date','amount','status','notes'])) {
                $fields[] = "$k = ?";
                $vals[] = $v;
            }
        }
        $vals[] = $id;
        $pdo->prepare("UPDATE invoices SET " . implode(', ', $fields) . " WHERE id = ?")->execute($vals);
        respond_idem($pdo, $key, ['updated' => true]);
    }
}

// ---- Invoice payments ----
if ($resource === 'invoices' && $id !== null && $sub === 'payments') {
    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC, id DESC');
        $stmt->execute([$id]);
        echo json_encode(['payments' => $stmt->fetchAll()]);
        exit;
    }
    if ($method === 'POST') {
        $input = require_body($pdo, ['payment_date','amount','method','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM invoices WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { http_response_code(404); echo json_encode(['error' => 'Invoice not found']); exit; }
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO payments (invoice_id, payment_date, amount, method, notes, created_at) VALUES (?,?,?,?,?,?)")
            ->execute([$id, $input['payment_date'], (float)$input['amount'], $input['method'] ?? '', $input['notes'] ?? '', now_iso()]);
        $paidStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = ?');
        $paidStmt->execute([$id]);
        $paid = (float)$paidStmt->fetchColumn();
        $invStmt = $pdo->prepare('SELECT amount FROM invoices WHERE id = ?');
        $invStmt->execute([$id]);
        $amount = (float)$invStmt->fetchColumn();
        $newStatus = $paid >= $amount ? 'paid' : 'open';
        $pdo->prepare('UPDATE invoices SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
        $pdo->commit();
        respond_idem($pdo, $key, ['payment_id' => (int)$pdo->lastInsertId(), 'balance' => $amount - $paid], 201);
    }
}
if ($resource === 'payments' && $id !== null && $sub === null) {
    if ($method === 'POST') {
        // top-level payment creation linked to invoice_id in body
        $input = require_body($pdo, ['invoice_id','payment_date','amount','method','notes']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $invId = (int)$input['invoice_id'];
        $stmt = $pdo->prepare('SELECT id, amount FROM invoices WHERE id = ?');
        $stmt->execute([$invId]);
        $inv = $stmt->fetch();
        if (!$inv) { http_response_code(404); echo json_encode(['error' => 'Invoice not found']); exit; }
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO payments (invoice_id, payment_date, amount, method, notes, created_at) VALUES (?,?,?,?,?,?)")
            ->execute([$invId, $input['payment_date'], (float)$input['amount'], $input['method'] ?? '', $input['notes'] ?? '', now_iso()]);
        $paidStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = ?');
        $paidStmt->execute([$invId]);
        $paid = (float)$paidStmt->fetchColumn();
        $amount = (float)$inv['amount'];
        $newStatus = $paid >= $amount ? 'paid' : 'open';
        $pdo->prepare('UPDATE invoices SET status = ? WHERE id = ?')->execute([$newStatus, $invId]);
        $pdo->commit();
        respond_idem($pdo, $key, ['payment_id' => (int)$pdo->lastInsertId(), 'balance' => $amount - $paid], 201);
    }
}

// ---- Expenses ----
if ($resource === 'expenses') {
    if ($method === 'GET' && $id === null) {
        $where = []; $vals = [];
        if (!empty($_GET['vehicle_id'])) { $where[] = 'vehicle_id = ?'; $vals[] = $_GET['vehicle_id']; }
        if (!empty($_GET['category'])) { $where[] = 'category = ?'; $vals[] = $_GET['category']; }
        if (!empty($_GET['from'])) { $where[] = 'expense_date >= ?'; $vals[] = $_GET['from']; }
        if (!empty($_GET['to'])) { $where[] = 'expense_date <= ?'; $vals[] = $_GET['to']; }
        $sql = 'SELECT e.*, v.make, v.model FROM expenses e LEFT JOIN vehicles v ON v.id = e.vehicle_id';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY expense_date DESC, id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);
        $rows = $stmt->fetchAll();
        $total = 0.0;
        $byCat = [];
        foreach ($rows as $x) {
            $total += (float)$x['amount'];
            $byCat[$x['category']] = ($byCat[$x['category']] ?? 0.0) + (float)$x['amount'];
        }
        echo json_encode(['expenses' => $rows, 'total' => $total, 'by_category' => $byCat]);
        exit;
    }
    if ($method === 'GET' && $id !== null) {
        $stmt = $pdo->prepare('SELECT * FROM expenses WHERE id = ?');
        $stmt->execute([$id]);
        $x = $stmt->fetch();
        if (!$x) { http_response_code(404); echo json_encode(['error' => 'Expense not found']); exit; }
        echo json_encode(['expense' => $x]);
        exit;
    }
    if ($method === 'POST' && $id === null) {
        $input = require_body($pdo, ['vehicle_id','expense_date','category','vendor','amount','description','invoice_id']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM vehicles WHERE id = ?');
        $stmt->execute([$input['vehicle_id']]);
        if (!$stmt->fetch()) { http_response_code(400); echo json_encode(['error' => 'Vehicle not found']); exit; }
        if (!empty($input['invoice_id'])) {
            $stmt = $pdo->prepare('SELECT id FROM invoices WHERE id = ?');
            $stmt->execute([$input['invoice_id']]);
            if (!$stmt->fetch()) { http_response_code(400); echo json_encode(['error' => 'Invoice not found']); exit; }
        }
        $stmt = $pdo->prepare("INSERT INTO expenses (vehicle_id, expense_date, category, vendor, amount, description, invoice_id, created_at)
                               VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$input['vehicle_id'], $input['expense_date'], $input['category'], $input['vendor'] ?? '',
                        (float)$input['amount'], $input['description'] ?? '', $input['invoice_id'] ?? null, now_iso()]);
        respond_idem($pdo, $key, ['expense_id' => (int)$pdo->lastInsertId()], 201);
    }
    if (($method === 'PATCH' || $method === 'PUT') && $id !== null) {
        $input = require_body($pdo, ['vehicle_id','expense_date','category','vendor','amount','description','invoice_id']);
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $stmt = $pdo->prepare('SELECT id FROM expenses WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { http_response_code(404); echo json_encode(['error' => 'Expense not found']); exit; }
        $fields = []; $vals = [];
        foreach ($input as $k => $v) {
            if (in_array($k, ['vehicle_id','expense_date','category','vendor','amount','description','invoice_id'])) {
                $fields[] = "$k = ?";
                $vals[] = $v;
            }
        }
        $vals[] = $id;
        $pdo->prepare("UPDATE expenses SET " . implode(', ', $fields) . " WHERE id = ?")->execute($vals);
        respond_idem($pdo, $key, ['updated' => true]);
    }
    if ($method === 'DELETE' && $id !== null) {
        $key = idem_key();
        if ($key !== null) {
            $cached = idempotency_check($pdo, $key);
            if ($cached !== null) { http_response_code(200); echo json_encode($cached); exit; }
        }
        $pdo->prepare('DELETE FROM expenses WHERE id = ?')->execute([$id]);
        respond_idem($pdo, $key, ['deleted' => true]);
    }
}

// ---- Dashboard ----
if ($resource === 'dashboard' && $id === null) {
    $stmt = $pdo->query('SELECT t.*, v.make, v.model, v.license_plate FROM trips t LEFT JOIN vehicles v ON v.id = t.vehicle_id ORDER BY trip_date DESC, id DESC LIMIT 10');
    $recentTrips = $stmt->fetchAll();

    $stmt = $pdo->query('SELECT id, make, model, license_plate FROM vehicles ORDER BY id');
    $vehicles = [];
    $vehicleMiles = [];
    foreach ($stmt->fetchAll() as $v) {
        $vehicles[] = $v;
        $mStmt = $pdo->prepare('SELECT COALESCE(SUM(distance),0) FROM trips WHERE vehicle_id = ?');
        $mStmt->execute([$v['id']]);
        $vehicleMiles[(int)$v['id']] = (float)$mStmt->fetchColumn();
    }

    $stmt = $pdo->query('SELECT e.*, v.make, v.model FROM engines e LEFT JOIN vehicles v ON v.id = e.vehicle_id WHERE e.status = "active"');
    $engines = [];
    foreach ($stmt->fetchAll() as $e) {
        $engines[] = array_merge($e, ['engine_miles' => engine_current_miles($pdo, $e)]);
    }

    $stmt = $pdo->query('SELECT COALESCE(SUM(amount),0) AS total FROM expenses');
    $expenseTotal = (float)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COALESCE(SUM(i.amount - COALESCE(p.paid,0)),0) AS debt FROM invoices i LEFT JOIN (SELECT invoice_id, SUM(amount) paid FROM payments GROUP BY invoice_id) p ON p.invoice_id = i.id");
    $outstandingDebt = (float)$stmt->fetchColumn();

    echo json_encode([
        'recent_trips' => $recentTrips,
        'vehicles' => $vehicles,
        'vehicle_miles' => $vehicleMiles,
        'engines' => $engines,
        'expense_total' => $expenseTotal,
        'outstanding_debt' => $outstandingDebt,
    ]);
    exit;
}

// ---- Exports (CSV) ----
if ($resource === 'export' && $id === null) {
    $target = $sub ?? null;
    if ($target === 'trips.csv' && $method === 'GET') {
        $where = []; $vals = [];
        if (!empty($_GET['vehicle_id'])) { $where[] = 'vehicle_id = ?'; $vals[] = $_GET['vehicle_id']; }
        if (!empty($_GET['from'])) { $where[] = 'trip_date >= ?'; $vals[] = $_GET['from']; }
        if (!empty($_GET['to'])) { $where[] = 'trip_date <= ?'; $vals[] = $_GET['to']; }
        $sql = 'SELECT t.trip_date, v.make, v.model, v.license_plate, t.start_odometer, t.end_odometer, t.distance, t.purpose, t.notes FROM trips t LEFT JOIN vehicles v ON v.id = t.vehicle_id';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY t.trip_date DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="trips.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date','Vehicle','Start odometer','End odometer','Distance','Purpose','Notes']);
        foreach ($stmt->fetchAll() as $r) {
            fputcsv($out, [$r['trip_date'], $r['make'] . ' ' . $r['model'], $r['start_odometer'], $r['end_odometer'], $r['distance'], $r['purpose'], $r['notes']]);
        }
        fclose($out);
        exit;
    }
    if ($target === 'expenses.csv' && $method === 'GET') {
        $where = []; $vals = [];
        if (!empty($_GET['vehicle_id'])) { $where[] = 'vehicle_id = ?'; $vals[] = $_GET['vehicle_id']; }
        if (!empty($_GET['category'])) { $where[] = 'category = ?'; $vals[] = $_GET['category']; }
        if (!empty($_GET['from'])) { $where[] = 'expense_date >= ?'; $vals[] = $_GET['from']; }
        if (!empty($_GET['to'])) { $where[] = 'expense_date <= ?'; $vals[] = $_GET['to']; }
        $sql = 'SELECT e.expense_date, v.make, v.model, e.category, e.vendor, e.amount, e.description FROM expenses e LEFT JOIN vehicles v ON v.id = e.vehicle_id';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY e.expense_date DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="expenses.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date','Vehicle','Category','Vendor','Amount','Description']);
        foreach ($stmt->fetchAll() as $r) {
            fputcsv($out, [$r['expense_date'], $r['make'] . ' ' . $r['model'], $r['category'], $r['vendor'], $r['amount'], $r['description']]);
        }
        fclose($out);
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']);
