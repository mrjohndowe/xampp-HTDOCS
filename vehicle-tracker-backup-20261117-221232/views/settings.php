<?php
// Vehicle Tracker - settings
declare(strict_types = 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
/** @var PDO $pdo */
$pageTitle = 'Settings';
$activePage = 'settings';

$stmt = $pdo->query('SELECT * FROM settings WHERE id = 1');
$settings = $stmt->fetch();

$currencies = ['USD' => '$ US Dollar', 'EUR' => 'â‚¬ Euro', 'GBP' => '&pound; British Pound', 'CAD' => 'Canadian Dollar', 'AUD' => 'Australian Dollar'];
$units = ['mi' => 'Miles (mi)', 'km' => 'Kilometers (km)'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'update_prefs') {
        $pdo->prepare("UPDATE settings SET site_name = ?, currency = ?, distance_unit = ? WHERE id = 1")
            ->execute([trim($_POST['site_name'] ?? 'Vehicle Tracker'), $_POST['currency'] ?? 'USD', $_POST['distance_unit'] ?? 'mi']);
        $_SESSION['vt_site_name'] = $_POST['site_name'] ?? 'Vehicle Tracker';
        $_SESSION['vt_currency'] = $_POST['currency'] ?? 'USD';
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Preferences saved.'];
        header('Location: settings.php');
        exit;
    } elseif ($action === 'create_token') {
        $name = trim($_POST['token_name'] ?? 'API token');
        $token = bin2hex(random_bytes(24));
        $expires = $_POST['token_expires'] !== '' ? date('Y-m-d H:i:s', strtotime('+' . (int)$_POST['token_expires'] . ' days')) : null;
        $userId = (int)($settings['id'] ?? 1);
        // attach to first admin user
        $user = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetch();
        if ($user) {
            $pdo->prepare("INSERT INTO api_tokens (user_id, token_hash, name, created_at, expires_at) VALUES (?, ?, ?, ?, ?)")
                ->execute([(int)$user['id'], hash('sha256', $token), $name, now_iso(), $expires]);
            $_SESSION['vt_new_token'] = $token;
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'New API token created. Copy it now — it is shown only once.'];
        }
        header('Location: settings.php');
        exit;
    } elseif ($action === 'revoke_token') {
        $pdo->prepare('UPDATE api_tokens SET revoked = 1 WHERE id = ?')->execute([(int)$_POST['token_id']]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Token revoked.'];
        header('Location: settings.php');
        exit;
    }
}

$tokens = $pdo->query('
  SELECT t.id, t.name, t.created_at, t.expires_at, t.revoked, t.last_used_at, u.username
  FROM api_tokens t JOIN users u ON u.id = t.user_id
  ORDER BY t.id DESC
')->fetchAll();

$newToken = $_SESSION['vt_new_token'] ?? null;
unset($_SESSION['vt_new_token']);

ob_start();
?>
<div class="page-title">
  <span>&#128100;</span>
  <div>
    <div>Settings</div>
    <div class="page-sub">Currency, distance units, API tokens and theme preference.</div>
  </div>
</div>

<?php if ($newToken): ?>
<div class="flash success"><b>New API token:</b>
  <div class="token-box" style="margin:8px 0 0"><?= h($newToken) ?></div>
  <div style="font-size:12px;color:var(--muted)">Use this token in the Python desktop app (Authorization: Bearer &lt;token&gt;). Revoke it here anytime.</div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post" action="">
      <input type="hidden" name="action" value="update_prefs">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-grid">
        <div class="field">
          <label>Site name</label>
          <input name="site_name" value="<?= h($settings['site_name'] ?? 'Vehicle Tracker') ?>">
        </div>
        <div class="field">
          <label>Currency</label>
          <select name="currency">
            <?php foreach ($currencies as $code => $label): ?>
              <option value="<?= $code ?>" <?= ($settings['currency'] ?? 'USD') === $code ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Distance unit</label>
          <select name="distance_unit">
            <?php foreach ($units as $code => $label): ?>
              <option value="<?= $code ?>" <?= ($settings['distance_unit'] ?? 'mi') === $code ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save preferences</button>
      </div>
    </form>
  </div>
</div>

<div class="card" style="margin-top:16px;">
  <div class="card-body">
    <h3 style="margin-top:0">Theme</h3>
    <p style="margin:6px 0 12px">Choose light or dark mode with the toggle in the top bar. Your choice is saved in the browser and persists after reloading.</p>
    <div class="form-actions">
      <button type="button" class="btn btn-outline" onclick="window.VT.setTheme('light')">Force light mode</button>
      <button type="button" class="btn btn-outline" onclick="window.VT.setTheme('dark')">Force dark mode</button>
      <button type="button" class="btn btn-outline" onclick="window.VT.setTheme('system')">Use system preference</button>
    </div>
  </div>
</div>

<div class="card" style="margin-top:16px;">
  <div class="card-body">
    <h3 style="margin-top:0">API tokens</h3>
    <p style="margin:6px 0 12px">The Python desktop app authenticates with a bearer token. Create tokens here, copy them at creation, and revoke any token to cut off access.</p>
    <form method="post" action="" style="margin-bottom:14px">
      <input type="hidden" name="action" value="create_token">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;">
        <input name="token_name" type="text" placeholder="Token name (e.g. Desktop app)" style="padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--panel);color:var(--text);width:220px">
        <label style="margin:0 8px 0 4px;font-size:13px">Expires in</label>
        <input name="token_expires" type="number" min="0" value="90" style="width:70px;padding:8px 10px;border:1px solid var(--border);border-radius:8px"> days
        <button type="submit" class="btn btn-primary">Create token</button>
      </div>
    </form>
    <?php if ($tokens): ?>
      <table style="width:100%;font-size:13px">
        <thead>
          <tr>
            <th style="text-align:left;padding:8px">Name</th>
            <th style="text-align:left;padding:8px">User</th>
            <th style="text-align:left;padding:8px">Created</th>
            <th style="text-align:left;padding:8px">Expires</th>
            <th style="text-align:left;padding:8px">Last used</th>
            <th style="text-align:center;padding:8px">Revoked</th>
            <th style="text-align:center;padding:8px">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tokens as $t): ?>
            <tr>
              <td style="padding:8px"><?= h($t['name']) ?></td>
              <td style="padding:8px"><?= h($t['username']) ?></td>
              <td style="padding:8px"><?= h($t['created_at']) ?></td>
              <td style="padding:8px"><?= $t['expires_at'] ?: '&#8212;' ?></td>
              <td style="padding:8px"><?= $t['last_used_at'] ?: '&#8212;' ?></td>
              <td style="padding:8px;text-align:center"><span class="badge <?= $t['revoked'] ? 'badge-bad' : 'badge-ok' ?>"><?= $t['revoked'] ? 'Yes' : 'No' ?></span></td>
              <td style="padding:8px;text-align:center">
                <?php if (!$t['revoked']): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Revoke this token?')">
                    <input type="hidden" name="action" value="revoke_token">
                    <input type="hidden" name="token_id" value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Revoke</button>
                  </form>
                <?php else: ?>
                  <span class="badge badge-neutral">Revoked</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="color:var(--muted);font-size:13px">No API tokens yet. Create one to connect the Python desktop app.</p>
    <?php endif; ?>
  </div>
</div>

<style>
.token-box { background:#0f172a; color:#a5f3fc; padding:10px 14px; border-radius:8px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; word-break: break-all; font-size: 13px; }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
