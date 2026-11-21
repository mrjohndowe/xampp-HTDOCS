<?php
// Vehicle Tracker - dashboard
declare(strict_types = 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

/** @var PDO $pdo */
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Recent trips
$stmt = $pdo->query('
  SELECT t.*, v.make, v.model, v.license_plate
  FROM trips t
  LEFT JOIN vehicles v ON v.id = t.vehicle_id
  ORDER BY t.trip_date DESC, t.id DESC
  LIMIT 8
');
$recentTrips = $stmt->fetchAll();

// Vehicles with totals
$stmt = $pdo->query('SELECT id, make, model, year, license_plate FROM vehicles ORDER BY id');
$vehicles = [];
while ($v = $stmt->fetch()) {
  $odo = vehicle_current_odometer($pdo, (int)$v['id']);
  $mStmt = $pdo->prepare('SELECT COALESCE(SUM(distance),0) AS d, COUNT(*) AS c FROM trips WHERE vehicle_id = ?');
  $mStmt->execute([$v['id']]);
  $m = $mStmt->fetch();
  $v['current_odometer'] = $odo;
  $v['total_miles'] = (float)($m['d'] ?? 0);
  $v['trip_count'] = (int)($m['c'] ?? 0);
  $vehicles[] = $v;
}

// Active engines with computed miles
$stmt = $pdo->query('
  SELECT e.*, v.make, v.model
  FROM engines e
  LEFT JOIN vehicles v ON v.id = e.vehicle_id
  WHERE e.status = "active"
  ORDER BY e.installed_date DESC, e.id DESC
');
$engines = [];
while ($e = $stmt->fetch()) {
  $e['engine_miles'] = engine_current_miles($pdo, $e);
  $engines[] = $e;
}

// Expense total
$stmt = $pdo->query('SELECT COALESCE(SUM(amount),0) AS t FROM expenses');
$expenseTotal = (float)$stmt->fetchColumn();

// Outstanding debt (unpaid invoice balances)
$stmt = $pdo->query('
  SELECT COALESCE(SUM(i.amount - COALESCE(p.paid, 0)), 0) AS debt
  FROM invoices i
  LEFT JOIN (SELECT invoice_id, SUM(amount) AS paid FROM payments GROUP BY invoice_id) p
    ON p.invoice_id = i.id
');
$outstandingDebt = (float)$stmt->fetchColumn();

// Overdue count
$stmt = $pdo->query("
  SELECT COUNT(*) AS n FROM invoices i
  LEFT JOIN (SELECT invoice_id, SUM(amount) AS paid FROM payments GROUP BY invoice_id) p
    ON p.invoice_id = i.id
  WHERE (i.amount - COALESCE(p.paid, 0)) > 0 AND i.due_date < CURRENT_DATE
");
$overdueCount = (int)$stmt->fetchColumn();

ob_start();
?>
<div class="page-title">
  <span>&#128206;</span>
  <div>
    <div>Dashboard</div>
    <div class="page-sub">Fleet overview at a glance</div>
  </div>
</div>

<div class="dashboard">
  <div class="stat good">
    <div class="label">Vehicles</div>
    <div class="value"><?= count($vehicles) ?></div>
  </div>
  <div class="stat">
    <div class="label">Trip miles (all)</div>
    <div class="value"><?= number_format(array_sum(array_column($vehicles, 'total_miles')), 1) ?> mi</div>
    <div class="delta"><?= count($recentTrips) ?> recent trips</div>
  </div>
  <div class="stat">
    <div class="label">Expenses total</div>
    <div class="value"><?= money($expenseTotal, $_SESSION['vt_currency'] ?? 'USD') ?></div>
  </div>
  <div class="stat <?= $outstandingDebt > 0 ? 'bad' : 'good' ?>">
    <div class="label">Outstanding mechanic debt</div>
    <div class="value"><?= money($outstandingDebt, $_SESSION['vt_currency'] ?? 'USD') ?></div>
    <div class="delta"><?= $overdueCount ?> overdue invoice<?= $overdueCount !== 1 ? 's' : '' ?></div>
  </div>
  <div class="stat warn">
    <div class="label">Active engines</div>
    <div class="value"><?= count($engines) ?></div>
  </div>
  <div class="stat">
    <div class="label">Engine miles (active)</div>
    <div class="value"><?= number_format(array_sum(array_map(fn($e) => (float)($e['engine_miles'] ?? 0), $engines)), 1) ?> mi</div>
  </div>
</div>

<div class="actions" style="margin-bottom:16px; display:flex; gap:8px; flex-wrap:wrap; justify-content:space-between; align-items:center;">
  <a href="vehicles.php" class="btn btn-primary">&#128680; Vehicles</a>
  <a href="trips.php" class="btn btn-primary">&#128689; Log a trip</a>
  <a href="expenses.php" class="btn btn-outline">&#128204; Expenses</a>
  <a href="invoices.php" class="btn btn-outline">&#128222; Invoices</a>
</div>

<?php if ($recentTrips): ?>
<div class="card">
  <div class="card-header">
    <span>Recent trips</span>
    <a href="trips.php" class="btn btn-sm btn-outline">View all</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Vehicle</th>
          <th>Start &#8594; End</th>
          <th class="numeric">Distance</th>
          <th>Purpose</th>
          <th class="center">Odo now</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentTrips as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t['trip_date']) ?></td>
            <td><?= htmlspecialchars(($t['make'] ?? '') . ' ' . ($t['model'] ?? '') . ' ' . ($t['license_plate'] ?? '')) ?></td>
            <td><?= number_format((float)$t['start_odometer'], 1) ?> &#8594; <?= number_format((float)$t['end_odometer'], 1) ?></td>
            <td class="numeric"><?= number_format((float)$t['distance'], 1) ?> mi</td>
            <td><?= htmlspecialchars($t['purpose'] ?? '') ?></td>
            <td class="center"><?= $t['end_odometer'] === null ? '—' : number_format((float)$t['end_odometer'], 1) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($vehicles): ?>
<div class="card" style="margin-top:16px;">
  <div class="card-header">
    <span>Vehicle mileage</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Vehicle</th>
          <th class="numeric">Current odometer</th>
          <th class="numeric">Trip miles</th>
          <th class="center">Trips</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vehicles as $v): ?>
          <tr>
            <td><?= htmlspecialchars($v['make'] . ' ' . $v['model'] . ' ' . ($v['license_plate'] ?? '')) ?></td>
            <td class="numeric"><?= $v['current_odometer'] === null ? '—' : number_format($v['current_odometer'], 1) ?></td>
            <td class="numeric"><?= number_format($v['total_miles'], 1) ?></td>
            <td class="center"><?= $v['trip_count'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($engines): ?>
<div class="card" style="margin-top:16px;">
  <div class="card-header">
    <span>Active engines</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Engine / Vehicle</th>
          <th>Serial</th>
          <th>Installed</th>
          <th class="numeric">Engine miles at install</th>
          <th class="numeric">Current engine miles</th>
          <th class="center">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($engines as $e): ?>
          <tr>
            <td>
              <?= htmlspecialchars(($e['make'] ?? '') . ' ' . ($e['model'] ?? '')) ?>
              <div style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($e['serial_number'] ?? '-') ?></div>
            </td>
            <td><?= htmlspecialchars($e['serial_number'] ?? '-') ?></td>
            <td><?= htmlspecialchars($e['installed_date']) ?></td>
            <td class="numeric"><?= number_format((float)($e['engine_mileage_at_install'] ?? 0), 1) ?></td>
            <td class="numeric"><?= $e['engine_miles'] === null ? '—' : number_format($e['engine_miles'], 1) ?></td>
            <td class="center"><span class="badge badge-ok">Active</span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
