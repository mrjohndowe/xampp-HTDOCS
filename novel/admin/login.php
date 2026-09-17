<?php
require_once __DIR__ . '/../config.php';
if (!admin_exists()) { header('Location: ' . app_url('admin/setup.php')); exit; }
if (is_admin()) { header('Location: ' . app_url('admin/index.php')); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = $_POST['csrf_token'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) $err = 'Invalid security token.';
    elseif (login_blocked()) $err = 'Too many failed attempts. Try again in about 15 minutes.';
    else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $a = find_admin_by_username($u);
        if ($a && password_verify($p, (string)$a['password_hash'])) {
            clear_logins();
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$a['id'];
            $_SESSION['admin_username'] = (string)$a['username'];
            header('Location: ' . app_url('admin/index.php'));
            exit;
        }
        failed_login();
        usleep(350000);
        $err = 'Invalid username or password.';
    }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="<?= e(app_url('assets/css/styles.css')) ?>"><title>Admin Login</title></head><body class="auth-page"><div class="auth-card"><img src="<?= e(app_url('assets/img/logo.png')) ?>" alt=""><div class="eyebrow">RESTRICTED AREA</div><h1>Admin Login</h1><?php if ($err): ?><div class="notice error"><?= e($err) ?></div><?php endif; ?><form method="post" class="upload-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Username<input name="username" autocomplete="username" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button>Login</button></form><a class="back-link" href="<?= e(app_url('index.php')) ?>">← Back to library</a></div></body></html>
