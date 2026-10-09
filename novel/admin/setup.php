<?php
require_once __DIR__ . '/../config.php';
if (admin_exists()) { header('Location: ' . app_url('admin/login.php')); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = $_POST['csrf_token'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) $err = 'Invalid security token.';
    else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $p2 = (string)($_POST['password2'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $u)) $err = 'Username must be 3-40 letters, numbers, dot, dash, or underscore.';
        elseif (strlen($p) < 12) $err = 'Password must be at least 12 characters.';
        elseif ($p !== $p2) $err = 'Passwords do not match.';
        else {
            try {
                $id = create_admin($u, $p);
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $id;
                $_SESSION['admin_username'] = $u;
                header('Location: ' . app_url('admin/index.php'));
                exit;
            } catch (PDOException $e) {
                $err = 'That administrator username already exists.';
            }
        }
    }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="<?= e(app_url('assets/css/styles.css')) ?>"><title>Admin Setup</title></head><body class="auth-page"><div class="auth-card"><img src="<?= e(app_url('assets/img/logo.png')) ?>" alt=""><div class="eyebrow">FIRST-RUN SECURITY</div><h1>Create Admin</h1><p>This setup closes itself after the first account is created.</p><?php if ($err): ?><div class="notice error"><?= e($err) ?></div><?php endif; ?><form method="post" class="upload-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Username<input name="username" required></label><label>Password<input type="password" name="password" required></label><label>Confirm password<input type="password" name="password2" required></label><button>Create Administrator</button></form></div></body></html>
