<?php
// Vehicle Tracker - vehicles
declare(strict_types = 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

/** @var PDO $pdo */
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Vehicles';
$activePage = 'vehicles';
$msg = null;
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $make = trim($_POST['make'] ?? '');
        $model = trim($_POST['model'] ?? '');
        if ($make === '' || $model === '') {
            $msg = 'Make and model are required.';
            $msgType = 'error';
        } else {
            $pdo->prepare("INSERT INTO vehicles (vin, make, model, year, license_plate, color, notes, created_at)
                           VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$_POST['vin'] ?? '', $make, $model, $_POST['year'] ?? null, $_POST['license_plate'] ?? '', $_POST['color'] ?? '', $_POST['notes'] ?? '', now_iso()]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Vehicle added.'];
            header('Location: vehicles.php');
            exit;
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE vehicles SET vin=?, make=?, model=?, year=?, license_plate=?, color=?, notes=? WHERE id=?")
            ->execute([$_POST['vin'] ?? '', trim($_POST['make'] ?? ''), trim($_POST['model'] ?? ''), $_POST['year'] ?? null, $_POST['license_plate'] ?? '', $_POST['color'] ?? '', $_POST['notes'] ?? '', $id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Vehicle updated.'];
        header('Location: vehicles.php');
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM vehicles WHERE id = ?')->execute([$id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Vehicle deleted.'];
        header('Location: vehicles.php');
        exit;
    }
}

$stmt = $pdo->query('SELECT * FROM vehicles ORDER BY id DESC');
$vehicles = $stmt->fetchAll();

$editVehicle = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editVehicle = $stmt->fetch();
}

ob_start();
?>
<div class="page-title">
  <span>&#128680;</span>
  <div>
    <div>Vehicles</div>
    <div class="page-sub">Manage your fleet. Trips and expenses roll up to each vehicle.</div>
  </div>
</div>

<?php if ($msg): ?>
  <div class="flash <?= $msgType ?>"><b><?= h(ucfirst($msgType)) ?>:</b> <?= h($msg) ?></div>
<?php endif; ?>

<div class="actions" style="margin-bottom:16px; display:flex; gap:8px; flex-wrap:wrap;">
  <a href="#add" class="btn btn-primary" onclick="document.getElementById('add').scrollIntoView({behavior:'smooth',block:'nearest'}); return false;">&#128680; Add vehicle</a>
</div>

<div class="card" id="add">
  <div class="card-body">
    <form method="post" action="">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-grid">
        <div class="field">
          <label>Make</label>
          <input name="make" required>
        </div>
        <div class="field">
          <label>Model</label>
          <input name="model" required>
        </div>
        <div class="field">
          <label>Year</label>
          <input name="year" type="number" min="1900" max="2100" value="<?= date('Y') ?>">
        </div>
        <div class="field">
          <label>VIN</label>
          <input name="vin">
        </div>
        <div class="field">
          <label>License plate</label>
          <input name="license_plate">
        </div>
        <div class="field">
          <label>Color</label>
          <input name="color">
        </div>
        <div class="field full-width">
          <label>Notes</label>
          <input name="notes">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add vehicle</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editVehicle): ?>
<div class="card" id="edit">
  <div class="card-body">
    <h3 style="margin-top:0">Edit vehicle</h3>
    <form method="post" action="">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= (int)$editVehicle['id'] ?>">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-grid">
        <div class="field">
          <label>Make</label>
          <input name="make" value="<?= h($editVehicle['make']) ?>" required>
        </div>
        <div class="field">
          <label>Model</label>
          <input name="model" value="<?= h($editVehicle['model']) ?>" required>
        </div>
        <div class="field">
          <label>Year</label>
          <input name="year" type="number" value="<?= h((string)($editVehicle['year'] ?? '')) ?>">
        </div>
        <div class="field">
          <label>VIN</label>
          <input name="vin" value="<?= h($editVehicle['vin']) ?>">
        </div>
        <div class="field">
          <label>License plate</label>
          <input name="license_plate" value="<?= h($editVehicle['license_plate']) ?>">
        </div>
        <div class="field">
          <label>Color</label>
          <input name="color" value="<?= h($editVehicle['color']) ?>">
        </div>
        <div class="field full-width">
          <label>Notes</label>
          <input name="notes" value="<?= h($editVehicle['notes']) ?>">
        </div>
      </div>
      <div class="form-actions" style="margin-top:12px">
        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="vehicles.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card" style="margin-top:16px;">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Vehicle</th>
          <th class="numeric">Year</th>
          <th>VIN</th>
          <th>Plate</th>
          <th class="center">Trips</th>
          <th class="numeric">Miles</th>
          <th class="center">Current odometer</th>
          <th class="center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vehicles as $v): ?>
          <?php
          $odo = vehicle_current_odometer($pdo, (int)$v['id']);
          $mStmt = $pdo->prepare('SELECT COALESCE(SUM(distance),0) AS d, COUNT(*) AS c FROM trips WHERE vehicle_id = ?');
          $mStmt->execute([$v['id']]);
          $m = $mStmt->fetch();
          ?>
          <tr>
            <td><?= h($v['make'] . ' ' . $v['model']) ?></td>
            <td class="numeric"><?= h((string)($v['year'] ?? '-')) ?></td>
            <td><?= h($v['vin'] ?? '-') ?></td>
            <td><?= h($v['license_plate'] ?? '-') ?></td>
            <td class="center"><?= (int)($m['c'] ?? 0) ?></td>
            <td class="numeric"><?= number_format((float)($m['d'] ?? 0), 1) ?></td>
            <td class="center"><?= $odo === null ? '—' : number_format($odo, 1) ?></td>
            <td class="center">
              <a href="vehicles.php?edit=<?= (int)$v['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this vehicle? Its trips and expenses will be removed too.')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
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

<?php if (!$vehicles): ?>
<div class="empty">
  <span class="big">&#128680;</span>
  <p>No vehicles yet. Add your first vehicle above.</p>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
