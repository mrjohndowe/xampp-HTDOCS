<?php
// Vehicle Tracker - invoices & mechanic debt
declare(strict_types = 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
/** @var PDO $pdo */
$pageTitle = 'Invoices';
$activePage = 'invoices';

function invoice_balance(PDO $pdo, int $invoiceId): array
{
    $inv = db_statement($pdo, 'SELECT amount, status FROM invoices WHERE id = ?', [$invoiceId])->fetch();
    if (!$inv) return ['amount' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'overdue' => false, 'status' => 'open'];
    $paid = (float)db_statement($pdo, 'SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = ?', [$invoiceId])->fetchColumn();
    $amount = (float)$inv['amount'];
    $balance = $amount - $paid;
    // due_date may be null-safe
    $due = db_statement($pdo, 'SELECT due_date FROM invoices WHERE id = ?', [$invoiceId])->fetchColumn();
    $overdue = $balance > 0 && $due !== false && $due !== '' && date('Y-m-d') > $due;
    return ['amount' => $amount, 'paid' => $paid, 'balance' => $balance, 'overdue' => $overdue, 'status' => $inv['status']];
}

$mechanics = $pdo->query('SELECT * FROM mechanics ORDER BY name')->fetchAll();

$stmt = $pdo->query('
  SELECT i.*, m.name AS mechanic_name
  FROM invoices i
  LEFT JOIN mechanics m ON m.id = i.mechanic_id
  ORDER BY i.invoice_date DESC, i.id DESC
');
$invoices = [];
while ($inv = $stmt->fetch()) {
    $inv = array_merge($inv, invoice_balance($pdo, (int)$inv['id']));
    $invoices[] = $inv;
}

$totalDebt = 0.0;
$overdueDebt = 0.0;
foreach ($invoices as $inv) {
    if ($inv['balance'] > 0) $totalDebt += $inv['balance'];
    if ($inv['overdue']) $overdueDebt += $inv['balance'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'create_mechanic') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Mechanic name is required.'];
        } else {
            $pdo->prepare("INSERT INTO mechanics (name, phone, email, address, notes, created_at) VALUES (?,?,?,?,?,?)")
                ->execute([$name, $_POST['phone'] ?? '', $_POST['email'] ?? '', $_POST['address'] ?? '', $_POST['notes'] ?? '', now_iso()]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Mechanic added.'];
        }
        header('Location: invoices.php');
        exit;
    } elseif ($action === 'create_invoice') {
        $mechId = (int)($_POST['mechanic_id'] ?? 0);
        $exists = db_statement($pdo, 'SELECT id FROM mechanics WHERE id = ?', [$mechId])->fetch();
        if (!$exists) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Mechanic not found.'];
        } elseif ((float)($_POST['amount'] ?? 0) < 0) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Amount cannot be negative.'];
        } else {
            $pdo->prepare("INSERT INTO invoices (mechanic_id, invoice_number, invoice_date, due_date, amount, status, notes, created_at)
                           VALUES (?,?,?,?,?,'open',?,?)")
                ->execute([$mechId, $_POST['invoice_number'], $_POST['invoice_date'], $_POST['due_date'], (float)$_POST['amount'], $_POST['notes'] ?? '', now_iso()]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Invoice created.'];
        }
        header('Location: invoices.php');
        exit;
    } elseif ($action === 'record_payment') {
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $inv = db_statement($pdo, 'SELECT id, amount FROM invoices WHERE id = ?', [$invoiceId])->fetch();
        if (!$inv) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invoice not found.'];
        } elseif ($amount <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Payment amount must be greater than zero.'];
        } else {
            $paid = (float)db_statement($pdo, 'SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = ?', [$invoiceId])->fetchColumn();
            $balance = (float)$inv['amount'] - $paid;
            if ($amount > $balance) {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Payment exceeds the outstanding balance of ' . money($balance, $_SESSION['vt_currency'] ?? 'USD') . '.'];
            } else {
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO payments (invoice_id, payment_date, amount, method, notes, created_at) VALUES (?,?,?,?,?,?)")
                    ->execute([$invoiceId, $_POST['payment_date'], $amount, $_POST['method'] ?? '', $_POST['notes'] ?? '', now_iso()]);
                $paid += $amount;
                $newStatus = $paid >= (float)$inv['amount'] ? 'paid' : 'open';
                $pdo->prepare('UPDATE invoices SET status = ? WHERE id = ?')->execute([$newStatus, $invoiceId]);
                $pdo->commit();
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Payment recorded.'];
            }
        }
        header('Location: invoices.php');
        exit;
    } elseif ($action === 'update_invoice') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE invoices SET invoice_number=?, invoice_date=?, due_date=?, amount=?, status=?, notes=? WHERE id=?")
            ->execute([$_POST['invoice_number'], $_POST['invoice_date'], $_POST['due_date'], (float)$_POST['amount'], $_POST['status'], $_POST['notes'] ?? '', $id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Invoice updated.'];
        header('Location: invoices.php');
        exit;
    }
}

$editInvoice = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editInvoice = $stmt->fetch();
}

ob_start();
?>
<div class="page-title">
  <span>&#128222;</span>
  <div>
    <div>Invoices &amp; mechanic debt</div>
    <div class="page-sub">Track contacts, invoices, due dates, partial payments and overdue balances.</div>
  </div>
</div>

<div class="dashboard" style="margin-bottom:16px;">
  <div class="stat">
    <div class="label">Open invoices</div>
    <div class="value"><?= count(array_filter($invoices, fn($i) => $i['balance'] > 0)) ?></div>
  </div>
  <div class="stat">
    <div class="label">Outstanding balance</div>
    <div class="value"><?= money($totalDebt, $_SESSION['vt_currency'] ?? 'USD') ?></div>
  </div>
  <div class="stat <?= $overdueDebt > 0 ? 'bad' : 'good' ?>">
    <div class="label">Overdue</div>
    <div class="value"><?= money($overdueDebt, $_SESSION['vt_currency'] ?? 'USD') ?></div>
  </div>
</div>

<div class="actions" style="margin-bottom:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
  <a href="#addmech" class="btn btn-outline btn-sm">&#128100; Add mechanic</a>
  <a href="#addinv" class="btn btn-primary" onclick="document.getElementById('addinv').scrollIntoView({behavior:'smooth',block:'nearest'}); return false;">&#128222; New invoice</a>
</div>

<div class="card" id="addmech" style="margin-bottom:16px;">
  <div class="card-body">
    <h3 style="margin-top:0">Add mechanic</h3>
    <form method="post" action="">
      <input type="hidden" name="action" value="create_mechanic">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-grid">
        <div class="field"><label>Name</label><input name="name" required></div>
        <div class="field"><label>Phone</label><input name="phone"></div>
        <div class="field"><label>Email</label><input name="email" type="email"></div>
        <div class="field full-width"><label>Address</label><input name="address"></div>
        <div class="field full-width"><label>Notes</label><input name="notes"></div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add mechanic</button>
      </div>
    </form>
  </div>
</div>

<div class="card" id="addinv" style="margin-bottom:16px;">
  <div class="card-body">
    <h3 style="margin-top:0"><?= $editInvoice ? 'Edit invoice' : 'New invoice' ?></h3>
    <form method="post" action="">
      <input type="hidden" name="action" value="<?= $editInvoice ? 'update_invoice' : 'create_invoice' ?>">
      <?php if ($editInvoice): ?><input type="hidden" name="id" value="<?= (int)$editInvoice['id'] ?>"><?php endif; ?>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-grid">
        <div class="field">
          <label>Mechanic</label>
          <select name="mechanic_id" required>
            <option value="">Select mechanic</option>
            <?php foreach ($mechanics as $m): ?>
              <option value="<?= (int)$m['id'] ?>" <?= ($editInvoice && (int)$editInvoice['mechanic_id'] === (int)$m['id']) ? 'selected' : '' ?>>
                <?= h($m['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Invoice number</label><input name="invoice_number" value="<?= $editInvoice ? h($editInvoice['invoice_number']) : '' ?>" required></div>
        <div class="field"><label>Invoice date</label><input name="invoice_date" type="date" value="<?= $editInvoice ? h($editInvoice['invoice_date']) : date('Y-m-d') ?>" required></div>
        <div class="field"><label>Due date</label><input name="due_date" type="date" value="<?= $editInvoice ? h($editInvoice['due_date']) : '' ?>" required></div>
        <div class="field"><label>Amount</label><input name="amount" type="number" step="0.01" min="0" value="<?= $editInvoice ? h($editInvoice['amount']) : '' ?>" required></div>
        <div class="field"><label>Status</label>
          <select name="status">
            <option value="open" <?= (!$editInvoice || $editInvoice['status'] === 'open') ? 'selected' : '' ?>>Open</option>
            <option value="paid" <?= ($editInvoice && $editInvoice['status'] === 'paid') ? 'selected' : '' ?>>Paid</option>
            <option value="overdue" <?= ($editInvoice && $editInvoice['status'] === 'overdue') ? 'selected' : '' ?>>Overdue</option>
            <option value="void" <?= ($editInvoice && $editInvoice['status'] === 'void') ? 'selected' : '' ?>>Void</option>
          </select>
        </div>
        <div class="field full-width"><label>Notes</label><input name="notes" value="<?= $editInvoice ? h($editInvoice['notes']) : '' ?>"></div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $editInvoice ? 'Save changes' : 'Create invoice' ?></button>
        <a href="invoices.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span>Invoices</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Number</th>
          <th>Mechanic</th>
          <th class="numeric">Amount</th>
          <th class="numeric">Paid</th>
          <th class="numeric">Balance</th>
          <th class="center">Due</th>
          <th class="center">Status</th>
          <th class="center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
          <tr>
            <td><?= h($inv['invoice_date']) ?></td>
            <td>#<?= (int)$inv['id'] ?> <?= h($inv['invoice_number']) ?></td>
            <td><?= h($inv['mechanic_name'] ?? '-') ?></td>
            <td class="numeric"><?= money((float)$inv['amount'], $_SESSION['vt_currency'] ?? 'USD') ?></td>
            <td class="numeric"><?= money((float)$inv['paid'], $_SESSION['vt_currency'] ?? 'USD') ?></td>
            <td class="numeric <?= $inv['balance'] > 0 ? 'bad' : '' ?>"><?= money((float)$inv['balance'], $_SESSION['vt_currency'] ?? 'USD') ?></td>
            <td class="center"><?= h($inv['due_date']) . ($inv['overdue'] ? ' <span class="badge badge-bad">overdue</span>' : '') ?></td>
            <td class="center">
              <span class="badge <?= $inv['overdue'] ? 'badge-bad' : ($inv['balance'] <= 0 ? 'badge-ok' : 'badge-warn') ?>">
                <?= h(ucfirst($inv['overdue'] ? 'overdue' : ($inv['balance'] <= 0 ? 'paid' : 'open'))) ?>
              </span>
            </td>
            <td class="center">
              <a href="invoices.php?edit=<?= (int)$inv['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
              <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('pay-<?= (int)$inv['id'] ?>').scrollIntoView({behavior:'smooth',block:'nearest'})">Record payment</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (!$invoices): ?>
<div class="empty">
  <span class="big">&#128222;</span>
  <p>No invoices yet. Create one above.</p>
</div>
<?php endif; ?>

<!-- Payment forms (one per open invoice) -->
<?php foreach ($invoices as $inv): ?>
  <?php if ($inv['balance'] <= 0) continue; ?>
  <div class="card" style="margin-top:16px;">
    <div class="card-body" id="pay-<?= (int)$inv['id'] ?>">
      <h3 style="margin-top:0">Record payment &#8212; invoice #<?= (int)$inv['id'] ?> (balance <?= money((float)$inv['balance'], $_SESSION['vt_currency'] ?? 'USD') ?>)</h3>
      <form method="post" action="">
        <input type="hidden" name="action" value="record_payment">
        <input type="hidden" name="invoice_id" value="<?= (int)$inv['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-grid">
          <div class="field">
            <label>Payment date</label>
            <input name="payment_date" type="date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="field">
            <label>Amount</label>
            <input name="amount" type="number" step="0.01" min="0" value="<?= number_format((float)$inv['balance'], 2) ?>" required>
          </div>
          <div class="field">
            <label>Method</label>
            <select name="method"><option>Cash</option><option>Card</option><option>Transfer</option><option>Check</option></select>
          </div>
          <div class="field full-width">
            <label>Notes</label>
            <input name="notes">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Record payment</button>
        </div>
      </form>
      <div style="margin-top:10px">
        <h4 style="margin:0 0 6px;font-size:13px">Payment history</h4>
        <table style="width:auto;font-size:12.5px">
          <thead>
            <tr><th style="padding:4px 8px">Date</th><th style="padding:4px 8px;text-align:right">Amount</th><th style="padding:4px 8px">Method</th><th style="padding:4px 8px">Notes</th></tr>
          </thead>
          <tbody>
            <?php
            $payments = $pdo->prepare('SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC, id DESC');
            $payments->execute([(int)$inv['id']]);
            foreach ($payments->fetchAll() as $p): ?>
              <tr>
                <td style="padding:4px 8px"><?= h($p['payment_date']) ?></td>
                <td style="padding:4px 8px;text-align:right"><?= money((float)$p['amount'], $_SESSION['vt_currency'] ?? 'USD') ?></td>
                <td style="padding:4px 8px"><?= h($p['method'] ?? '-') ?></td>
                <td style="padding:4px 8px"><?= h($p['notes'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
