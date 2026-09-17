<?php
require_once __DIR__ . '/../config.php';
if (!admin_exists()) { header('Location: setup.php'); exit; }
if (is_admin()) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (login_blocked()) {
        $error = 'Too many failed attempts. Try again in about 15 minutes.';
    } else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $stmt = db()->prepare("SELECT * FROM admins WHERE username=?");
        $stmt->execute([$u]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($p,$admin['password_hash'])) {
            clear_failed_logins();
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: index.php'); exit;
        }
        record_failed_login();
        usleep(350000);
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="auth-page"><div class="auth-card"><img src="../assets/img/logo.png"><h1>Admin Login</h1><p>Restricted access.</p>
<?php if($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Username<input name="username" autocomplete="username" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="btn">Login</button></form><p><a href="../index.php">← Back to library</a></p></div></body></html>
