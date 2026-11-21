<?php
// Vehicle Tracker - expenses
declare(strict_types = 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
/** @var PDO $pdo */
$pageTitle = 'Expenses';
$activePage = 'expenses';

$vehicles = $pdo->query('SELECT id, make, model, license_plate FROM vehicles ORDER BY id')->fetchAll();
$categories = ['fuel' => 'Fuel', 'repair' => 'Repair', 'maintenance' => 'Maintenance', 'parts' => 'Parts', 'other' => 'Other'];

$fVehicle = $_GET['vehicle_id'] ?? '';
$fCategory = $_GET['category'] ?? '';
$fFrom = $_GET['from'] ?? '';
$fTo = $_GET['to'] ?? '';

$where = [];
$vals = [];
if ($fVehicle !== '') { $where[] = 'vehicle_id = ?'; $vals[] = $fVehicle; }
if ($fCategory !== '') { $where[] = 'category = ?'; $vals[] = $fCategory; }
if ($fFrom !== '') { $where[] = 'expense_date >= ?'; $vals[] = $fFrom; }
if ($fTo !== '') { $where[] = 'expense_date <= ?'; $vals[] = $fTo; }
$sql = 'SELECT e.*, v.make, v.model FROM expenses e LEFT JOIN vehicles v ON v.id = e.vehicle_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY e.expense_date DESC, e.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($vals);
$expenses = $stmt->fetchAll();

$total = 0.0;
$byCat = [];
foreach ($expenses as $x) {
  $total += (float)$x['amount'];
  $byCat[$x['category']] = ($byCat[$x['category']] ?? 0.0) + (float)$x['amount'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $exists = $pdo->prepare('SELECT id FROM vehicles WHERE id = ?')->execute([$vehicleId])->fetch();
        if (!$exists) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Vehicle not found.'];
        } elseif ((float)($_POST['amount'] ?? 0) < 0) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Amount cannot be negative.'];
        } else {
            $invId = $_POST['invoice_id'] ?? null;
            if ($invId !== '' && $invId !== null) {
                $chk = $pdo->prepare('SELECT id FROM invoices WHERE id = ?')->execute([$invId])->fetch();
                if (!$chk) {
                    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invoice not found.'];
                    header('Location: expenses.php');
                    exit;
                }
            }
            $pdo->prepare("INSERT INTO expenses (vehicle_id, expense_date, category, vendor, amount, description, invoice_id, created_at)
                           VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$vehicleId, $_POST['expense_date'], $_POST['category'], $_POST['vendor'] ?? '', (float)$_POST['amount'], $_POST['description'] ?? '', $invId === '' ? null : (int)$invId, now_iso()]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Expense recorded.'];
        }
        header('Location: expenses.php?' . http_build_query(array_filter(['vehicle_id' => $fVehicle, 'category' => $fCategory, 'from' => $fFrom, 'to' => $fTo])));
        exit;
    } elseif ($action === 'update') {
        $pdo->prepare("UPDATE expenses SET vehicle_id=?, expense_date=?, category=?, vendor=?, amount=?, description=?, invoice_id=? WHERE id=?")
            ->execute([(int)$_POST['vehicle_id'], $_POST['expense_date'], $_POST['category'], $_POST['vendor'] ?? '', (float)$_POST['amount'], $_POST['description'] ?? '', $_POST['invoice_id'] === '' ? null : (int)$_POST['invoice_id'], (int)$_POST['id']]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Expense updated.'];
        header('Location: expenses.php?' . http_build_query(array_filter(['vehicle_id' => $fVehicle, 'category' => $fCategory, 'from' => $fFrom, 'to' => $fTo])));
        exit;
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM expenses WHERE id = ?')->execute([(int)$_POST['id']]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Expense deleted.'];
        header('Location: expenses.php?' . http_build_query(array_filter(['vehicle_id' => $fVehicle, 'category' => $fCategory, 'from' => $fFrom, 'to' => $fTo])));
        exit;
    }
}

$editExpense = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM expenses WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editExpense = $stmt->fetch();
}

// Invoices for linking
$invoices = $pdo->query('SELECT id, invoice_number, amount FROM invoices ORDER BY id DESC')->fetchAll();

ob_start();
?>
<div class="page-title">
  <span>&#128204;</span>
  <div>
    <div>Expenses</div>
    <div class="page-sub">Fuel, repairs, maintenance, parts and other costs by vehicle, date, category and vendor.</div>
  </div>
</div>

<div class="actions" style="margin-bottom:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
  <a href="#add" class="btn btn-primary" onclick="document.getElementById('add').scrollIntoView({behavior:'smooth',block:'nearest'}); return false;">&#128204; Add expense</a>
  <a href="api/export/expenses.csv<?php echo ($fVehicle !== '' || $fCategory !== '' || $fFrom !== '' || $fTo !== '') ? '?' . http_build_query(array_filter(['vehicle_id' => $fVehicle, 'category' => $fCategory, 'from' => $fFrom, 'to' => $fTo])) : ''; ?>" class="btn btn-outline btn-sm">&#128470; Export CSV</a>
</div>

<?php if ($fVehicle !== '' || $fCategory !== '' || $fFrom !== '' || $fTo !== ''): ?>
  <div class="flash info" style="background:var(--primary-light);color:#1e3a8a;border-color:#bfdbfe;">
    <b>Filter active.</b>
    <a href="expenses.php" class="btn btn-sm btn-outline" style="margin-left:10px">Clear</a>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <div style="display:flex; gap:24px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
      <div style="font-size:13px;color:var(--muted); font-weight:600">Filtered total:
        <span style="color:var(--text); font-size:16px; font-weight:700; margin-left:6px"><?= money($total, $_SESSION['vt_currency'] ?? 'USD') ?></span>
      </div>
      <div style="font-size:13px;color:var(--muted)">Count: <span style="color:var(--text)"><?= count($expenses) ?></span></div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Vehicle</th>
            <th class="center">Category</th>
            <th>Vendor</th>
            <th class="numeric">Amount</th>
            <th>Description</th>
            <th class="center">Invoice</th>
            <th class="center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($expenses as $x): ?>
            <tr>
              <td><?= htmlspecialchars($x['expense_date']) ?></td>
              <td><?= htmlspecialchars(($x['make'] ?? '') . ' ' . ($x['model'] ?? '')) ?></td>
              <td class="center"><span class="cat-<?= htmlspecialchars($x['category']) ?>"><?= htmlspecialchars(ucfirst($x['category'])) ?></span></td>
              <td><?= htmlspecialchars($x['vendor'] ?? '-') ?></td>
              <td class="numeric"><?= money((float)$x['amount'], $_SESSION['vt_currency'] ?? 'USD') ?></td>
              <td><?= htmlspecialchars($x['description'] ?? '-') ?></td>
              <td class="center"><?= $x['invoice_id'] ? (int)$x['invoice_id'] : '—' ?></td>
              <td class="center">
                <a href="expenses.php?edit=<?= (int)$x['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this expense?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
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

<?php if ($byCat): ?>
<div class="card" style="margin-top:16px;">
  <div class="card-body">
    <h3 style="margin:0 0 10px">By category</h3>
    <table style="width:auto; font-size:13px">
      <thead>
        <tr><th style="padding:6px 10px">Category</th><th style="padding:6px 10px; text-align:right">Amount</th></tr>
      </thead>
      <tbody>
        <?php foreach ($byCat as $cat => $sum): ?>
          <tr>
            <td style="padding:6px 10px"><span class="cat-<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars(ucfirst($cat)) ?></span></td>
            <td style="padding:6px 10px; text-align:right"><?= money($sum, $_SESSION['vt_currency'] ?? 'USD') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (!$expenses): ?>
<div class="empty">
  <span class="big">&#128204;</span>
  <p>No expenses yet. Record your first expense below.</p>
</div>
<?php endif; ?>

<div class="card" id="add" style="margin-top:16px;">
  <div class="card-body">
    <h3 style="margin-top:0"><?= $editExpense ? 'Edit expense' : 'Record an expense' ?></h3>
    <form method="post" action="">
      <input type="hidden" name="action" value="<?= $editExpense ? 'update' : 'create' ?>">
      <?php if ($editExpense): ?><input type="hidden" name="id" value="<?= (int)$editExpense['id'] ?>"><?php endif; ?>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-grid">
        <div class="field">
          <label>Vehicle</label>
          <select name="vehicle_id" required>
            <option value="">Select vehicle</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= (int)$v['id'] ?>" <?= ($editExpense && (int)$editExpense['vehicle_id'] === (int)$v['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['make'] . ' ' . $v['model'] . ' ' . ($v['license_plate'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Date</label>
          <input name="expense_date" type="date" value="<?= $editExpense ? htmlspecialchars($editExpense['expense_date']) : date('Y-m-d') ?>" required>
        </div>
        <div class="field">
          <label>Category</label>
          <select name="category" required>
            <?php foreach ($categories as $key => $label): ?>
              <option value="<?= $key ?>" <?= ($editExpense && $editExpense['category'] === $key) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Vendor</label>
          <input name="vendor" value="<?= $editExpense ? htmlspecialchars($editExpense['vendor']) : '' ?>">
        </div>
        <div class="field">
          <label>Amount</label>
          <input name="amount" type="number" step="0.01" min="0" value="<?= $editExpense ? htmlspecialchars($editExpense['amount']) : '' ?>" required>
        </div>
        <div class="field">
          <label>Related invoice (optional)</label>
          <select name="invoice_id">
            <option value="">None</option>
            <?php foreach ($invoices as $inv): ?>
              <option value="<?= (int)$inv['id'] ?>" <?= ($editExpense && (int)$editExpense['invoice_id'] === (int)$inv['id']) ? 'selected' : '' ?>>
                #<?= (int)$inv['id'] ?> <?= htmlspecialchars($inv['invoice_number']) ?> - <?= money((float)$inv['amount'], $_SESSION['vt_currency'] ?? 'USD') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="field-hint">Linking an expense to an invoice records the repair cost once.</div>
        </div>
        <div class="field full-width">
          <label>Description</label>
          <input name="description" value="<?= $editExpense ? htmlspecialchars($editExpense['description']) : '' ?>">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $editExpense ? 'Save changes' : 'Record expense' ?></button>
        <a href="expenses.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
