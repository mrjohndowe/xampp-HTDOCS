<?php
// Vehicle Tracker - engines
declare(strict_types = 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
/** @var PDO $pdo */
$pageTitle = 'Engines';
$activePage = 'engines';

$vehicles = $pdo->query('SELECT id, make, model, license_plate FROM vehicles ORDER BY id')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $exists = db_statement($pdo, 'SELECT id FROM vehicles WHERE id = ?', [$vehicleId])->fetch();
        if (!$exists) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Vehicle not found.'];
        } else {
            $odoAtInstall = (float)($_POST['vehicle_odometer_at_install'] ?? 0);
            $engineMiles = (float)($_POST['engine_mileage_at_install'] ?? 0);
            if ($odoAtInstall < 0 || $engineMiles < 0) {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Readings cannot be negative.'];
            } else {
                $conflict = false;
                $maxStmt = $pdo->prepare('SELECT MAX(end_odometer) m FROM trips WHERE vehicle_id = ?');
                $maxStmt->execute([$vehicleId]);
                $maxOdo = $maxStmt->fetchColumn();
                if ($maxOdo !== false && $maxOdo !== '' && $odoAtInstall > (float)$maxOdo) {
                    $conflict = true;
                }
                $pdo->prepare("INSERT INTO engines (vehicle_id, serial_number, installed_date, engine_mileage_at_install, vehicle_odometer_at_install, status, replacement_reason, notes, created_at)
                               VALUES (?,?,?,?,?,'active',?,?,?)")
                    ->execute([$vehicleId, $_POST['serial_number'] ?? '', $_POST['installed_date'], $engineMiles, $odoAtInstall, $_POST['replacement_reason'] ?? '', $_POST['notes'] ?? '', now_iso()]);
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Engine installed.' . ($conflict ? ' Warning: installation odometer is ahead of recorded vehicle usage — possible conflicting reading.' : '')];
            }
        }
        header('Location: engines.php');
        exit;
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $fields = [];
        $vals = [];
        foreach (['serial_number', 'installed_date', 'engine_mileage_at_install', 'vehicle_odometer_at_install', 'status', 'replacement_reason', 'notes'] as $k) {
            if (array_key_exists($k, $_POST)) {
                $fields[] = "$k = ?";
                $vals[] = $_POST[$k];
            }
        }
        if (!empty($_POST['status']) && $_POST['status'] === 'replaced') {
            $fields[] = 'replaced_at = ?';
            $vals[] = now_iso();
        }
        $vals[] = $id;
        $pdo->prepare("UPDATE engines SET " . implode(', ', $fields) . " WHERE id = ?")->execute($vals);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Engine updated.'];
        header('Location: engines.php');
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $row = db_statement($pdo, 'SELECT status FROM engines WHERE id = ?', [$id])->fetch();
        if (!$row) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Engine not found.'];
        } elseif ($row['status'] === 'active') {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Cannot delete the active engine. Mark it replaced first.'];
        } else {
            $pdo->prepare('DELETE FROM engines WHERE id = ?')->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Engine record deleted.'];
        }
        header('Location: engines.php');
        exit;
    }
}

$stmt = $pdo->query('
  SELECT e.*, v.make, v.model, v.license_plate
  FROM engines e
  LEFT JOIN vehicles v ON v.id = e.vehicle_id
  ORDER BY e.installed_date DESC, e.id DESC
');
$engines = [];
while ($e = $stmt->fetch()) {
  $maxStmt = $pdo->prepare('SELECT MAX(end_odometer) m FROM trips WHERE vehicle_id = ?');
  $maxStmt->execute([$e['vehicle_id']]);
  $maxOdo = $maxStmt->fetchColumn();
  $conflict = ($maxOdo !== false && $maxOdo !== '' && (float)$e['vehicle_odometer_at_install'] > (float)$maxOdo);
  $e['engine_miles'] = engine_current_miles($pdo, $e);
  $e['conflict'] = $conflict;
  $engines[] = $e;
}

$editEngine = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM engines WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editEngine = $stmt->fetch();
}

ob_start();
?>
<div class="page-title">
  <span>&#128214;</span>
  <div>
    <div>Engines</div>
    <div class="page-sub">Engine mileage = miles at installation + distance driven since installation.</div>
  </div>
</div>

<div class="actions" style="margin-bottom:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
  <a href="#add" class="btn btn-primary" onclick="document.getElementById('add').scrollIntoView({behavior:'smooth',block:'nearest'}); return false;">&#128214; Add engine</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Vehicle</th>
          <th>Serial</th>
          <th>Installed</th>
          <th class="numeric">Engine miles at install</th>
          <th class="numeric">Current engine miles</th>
          <th class="center">Status</th>
          <th class="center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($engines as $e): ?>
          <tr>
            <td><?= h(($e['make'] ?? '') . ' ' . ($e['model'] ?? '')) ?></td>
            <td><?= h($e['serial_number'] ?? '-') ?></td>
            <td><?= h($e['installed_date']) ?></td>
            <td class="numeric"><?= number_format((float)($e['engine_mileage_at_install'] ?? 0), 1) ?></td>
            <td class="numeric"><?= $e['engine_miles'] === null ? '—' : number_format($e['engine_miles'], 1) ?></td>
            <td class="center">
              <span class="badge <?= $e['status'] === 'active' ? 'badge-ok' : 'badge-neutral' ?>">
                <?= h(ucfirst($e['status'])) ?>
              </span>
            </td>
            <td class="center">
              <?php if ($e['conflict']): ?>
                <span class="badge badge-warn" title="Installation odometer is ahead of recorded vehicle usage">Conflict</span>
              <?php endif; ?>
              <a href="engines.php?edit=<?= (int)$e['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
              <?php if ($e['status'] !== 'active'): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this engine record?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (!$engines): ?>
<div class="empty">
  <span class="big">&#128214;</span>
  <p>No engines recorded yet. Add your first engine below.</p>
</div>
<?php endif; ?>

<div class="card" id="add" style="margin-top:16px;">
  <div class="card-body">
    <h3 style="margin-top:0"><?= $editEngine ? 'Edit engine' : 'Add engine' ?></h3>
    <form method="post" action="">
      <input type="hidden" name="action" value="<?= $editEngine ? 'update' : 'create' ?>">
      <?php if ($editEngine): ?><input type="hidden" name="id" value="<?= (int)$editEngine['id'] ?>"><?php endif; ?>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-grid">
        <div class="field">
          <label>Vehicle</label>
          <select name="vehicle_id" required>
            <option value="">Select vehicle</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= (int)$v['id'] ?>" <?= ($editEngine && (int)$editEngine['vehicle_id'] === (int)$v['id']) ? 'selected' : '' ?>>
                <?= h($v['make'] . ' ' . $v['model'] . ' ' . ($v['license_plate'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Serial number</label>
          <input name="serial_number" value="<?= $editEngine ? h($editEngine['serial_number']) : '' ?>">
        </div>
        <div class="field">
          <label>Installation date</label>
          <input name="installed_date" type="date" value="<?= $editEngine ? h(substr((string)($editEngine['installed_date'] ?? ''), 0, 10)) : date('Y-m-d') ?>" required>
        </div>
        <div class="field">
          <label>Engine miles at installation</label>
          <input name="engine_mileage_at_install" type="number" step="0.1" min="0" value="<?= $editEngine ? h((string)($editEngine['engine_mileage_at_install'] ?? 0)) : '0' ?>" required>
        </div>
        <div class="field">
          <label>Vehicle odometer at installation</label>
          <input name="vehicle_odometer_at_install" type="number" step="0.1" min="0" value="<?= $editEngine ? h((string)($editEngine['vehicle_odometer_at_install'] ?? 0)) : '0' ?>" required>
          <div class="field-hint">Used to compute current engine miles as trips accumulate.</div>
        </div>
        <div class="field">
          <label>Status</label>
          <select name="status">
            <option value="active" <?= (!$editEngine || $editEngine['status'] === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="replaced" <?= ($editEngine && $editEngine['status'] === 'replaced') ? 'selected' : '' ?>>Replaced</option>
          </select>
        </div>
        <div class="field">
          <label>Replacement reason</label>
          <input name="replacement_reason" value="<?= $editEngine ? h((string)($editEngine['replacement_reason'] ?? '')) : '' ?>">
        </div>
        <div class="field full-width">
          <label>Notes</label>
          <input name="notes" value="<?= $editEngine ? h((string)($editEngine['notes'] ?? '')) : '' ?>">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $editEngine ? 'Save changes' : 'Install engine' ?></button>
        <a href="engines.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
