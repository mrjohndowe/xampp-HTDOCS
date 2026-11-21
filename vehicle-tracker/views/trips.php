<?php
// Vehicle Tracker - trips
declare(strict_types = 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
/** @var PDO $pdo */
$pageTitle = 'Trips';
$activePage = 'trips';

$vehicles = $pdo->query('SELECT id, make, model, license_plate FROM vehicles ORDER BY id')->fetchAll();

// Filters
$fVehicle = $_GET['vehicle_id'] ?? '';
$fFrom = $_GET['from'] ?? '';
$fTo = $_GET['to'] ?? '';

$where = [];
$vals = [];
if ($fVehicle !== '') { $where[] = 'vehicle_id = ?'; $vals[] = $fVehicle; }
if ($fFrom !== '') { $where[] = 'trip_date >= ?'; $vals[] = $fFrom; }
if ($fTo !== '') { $where[] = 'trip_date <= ?'; $vals[] = $fTo; }
$sql = 'SELECT t.*, v.make, v.model, v.license_plate FROM trips t LEFT JOIN vehicles v ON v.id = t.vehicle_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY t.trip_date DESC, t.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($vals);
$trips = $stmt->fetchAll();

$totalDistance = 0.0;
foreach ($trips as $t) $totalDistance += (float)$t['distance'];

// Create / update / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $start = (float)($_POST['start_odometer'] ?? 0);
        $end = (float)($_POST['end_odometer'] ?? 0);
        if ($end < $start) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'End odometer must be greater than or equal to the start odometer.'];
        } else {
            $exists = $pdo->prepare('SELECT id FROM vehicles WHERE id = ?')->execute([$_POST['vehicle_id']])->fetch();
            if (!$exists) {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Vehicle not found.'];
            } else {
                $pdo->prepare("INSERT INTO trips (vehicle_id, trip_date, start_odometer, end_odometer, distance, purpose, notes, created_at)
                               VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$_POST['vehicle_id'], $_POST['trip_date'], $start, $end, $end - $start, $_POST['purpose'] ?? '', $_POST['notes'] ?? '', now_iso()]);
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Trip logged.'];
            }
        }
        header('Location: trips.php?' . http_build_query(array_filter(['vehicle_id' => $fVehicle, 'from' => $fFrom, 'to' => $fTo])));
        exit;
    } elseif ($action === 'update') {
        $start = (float)($_POST['start_odometer'] ?? 0);
        $end = (float)($_POST['end_odometer'] ?? 0);
        if ($end < $start) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'End odometer must be greater than or equal to the start odometer.'];
        } else {
            $pdo->prepare("UPDATE trips SET vehicle_id=?, trip_date=?, start_odometer=?, end_odometer=?, distance=?, purpose=?, notes=? WHERE id=?")
                ->execute([$_POST['vehicle_id'], $_POST['trip_date'], $start, $end, $end - $start, $_POST['purpose'] ?? '', $_POST['notes'] ?? '', (int)$_POST['id']]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Trip updated.'];
        }
        header('Location: trips.php?' . http_build_query(array_filter(['vehicle_id' => $fVehicle, 'from' => $fFrom, 'to' => $fTo])));
        exit;
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM trips WHERE id = ?')->execute([(int)$_POST['id']]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Trip deleted.'];
        header('Location: trips.php?' . http_build_query(array_filter(['vehicle_id' => $fVehicle, 'from' => $fFrom, 'to' => $fTo])));
        exit;
    }
}

$editTrip = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM trips WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editTrip = $stmt->fetch();
}

ob_start();
?>
<div class="page-title">
  <span>&#128689;</span>
  <div>
    <div>Trips</div>
    <div class="page-sub">Dated trips with start and end odometer readings; distance is calculated automatically.</div>
  </div>
</div>

<div class="actions" style="margin-bottom:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
  <a href="#add" class="btn btn-primary" onclick="document.getElementById('add').scrollIntoView({behavior:'smooth',block:'nearest'}); return false;">&#128689; Log trip</a>
  <a href="api/export/trips.csv<?php echo $fVehicle !== '' || $fFrom !== '' || $fTo !== '' ? '?' . http_build_query(array_filter(['vehicle_id' => $fVehicle, 'from' => $fFrom, 'to' => $fTo])) : ''; ?>" class="btn btn-outline btn-sm">&#128470; Export CSV</a>
</div>

<?php if ($fVehicle !== '' || $fFrom !== '' || $fTo !== ''): ?>
  <div class="flash info" style="background:var(--primary-light);color:#1e3a8a;border-color:#bfdbfe;">
    <b>Filter active:</b>
    <?php if ($fVehicle !== ''): ?>
      <span style="margin-right:8px">Vehicle <?= htmlspecialchars($fVehicle) ?></span>
    <?php endif; ?>
    <?php if ($fFrom !== ''): ?>
      <span style="margin-right:8px">From <?= htmlspecialchars($fFrom) ?></span>
    <?php endif; ?>
    <?php if ($fTo !== ''): ?>
      <span>To <?= htmlspecialchars($fTo) ?></span>
    <?php endif; ?>
    <a href="trips.php" class="btn btn-sm btn-outline" style="margin-left:10px">Clear</a>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <div style="display:flex; gap:8px; align-items:flex-end; margin-bottom:10px; flex-wrap:wrap;">
      <div style="font-size:13px;color:var(--muted); font-weight:600">Total: <span style="color:var(--text)"><?= number_format($totalDistance, 1) ?></span> mi across <?= count($trips) ?> trip<?= count($trips) !== 1 ? 's' : '' ?></div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Vehicle</th>
            <th class="numeric">Start</th>
            <th class="numeric">End</th>
            <th class="numeric">Distance</th>
            <th>Purpose</th>
            <th>Notes</th>
            <th class="center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trips as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['trip_date']) ?></td>
              <td><?= htmlspecialchars(($t['make'] ?? '') . ' ' . ($t['model'] ?? '') . ' ' . ($t['license_plate'] ?? '')) ?></td>
              <td class="numeric"><?= number_format((float)$t['start_odometer'], 1) ?></td>
              <td class="numeric"><?= number_format((float)$t['end_odometer'], 1) ?></td>
              <td class="numeric"><?= number_format((float)$t['distance'], 1) ?></td>
              <td><?= htmlspecialchars($t['purpose'] ?? '') ?></td>
              <td><?= htmlspecialchars($t['notes'] ?? '') ?></td>
              <td class="center">
                <a href="trips.php?edit=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this trip?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (!$trips): ?>
<div class="empty">
  <span class="big">&#128689;</span>
  <p>No trips yet. Log your first trip below.</p>
</div>
<?php endif; ?>

<div class="card" id="add" style="margin-top:16px;">
  <div class="card-body">
    <h3 style="margin-top:0"><?= $editTrip ? 'Edit trip' : 'Log a trip' ?></h3>
    <form method="post" action="">
      <input type="hidden" name="action" value="<?= $editTrip ? 'update' : 'create' ?>">
      <?php if ($editTrip): ?><input type="hidden" name="id" value="<?= (int)$editTrip['id'] ?>"><?php endif; ?>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-grid">
        <div class="field">
          <label>Vehicle</label>
          <select name="vehicle_id" required>
            <option value="">Select vehicle</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= (int)$v['id'] ?>" <?= ($editTrip && (int)$editTrip['vehicle_id'] === (int)$v['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['make'] . ' ' . $v['model'] . ' ' . ($v['license_plate'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Date</label>
          <input name="trip_date" type="date" value="<?= $editTrip ? htmlspecialchars($editTrip['trip_date']) : date('Y-m-d') ?>" required>
        </div>
        <div class="field">
          <label>Start odometer</label>
          <input name="start_odometer" type="number" step="0.1" min="0" value="<?= $editTrip ? htmlspecialchars($editTrip['start_odometer']) : '' ?>" required>
        </div>
        <div class="field">
          <label>End odometer</label>
          <input name="end_odometer" type="number" step="0.1" min="0" value="<?= $editTrip ? htmlspecialchars($editTrip['end_odometer']) : '' ?>" required>
          <div class="field-hint">Distance is calculated automatically (end − start).</div>
        </div>
        <div class="field">
          <label>Purpose</label>
          <input name="purpose" value="<?= $editTrip ? htmlspecialchars($editTrip['purpose']) : '' ?>">
        </div>
        <div class="field full-width">
          <label>Notes</label>
          <input name="notes" value="<?= $editTrip ? htmlspecialchars($editTrip['notes']) : '' ?>">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $editTrip ? 'Save changes' : 'Log trip' ?></button>
        <a href="trips.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
