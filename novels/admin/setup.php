<?php
require_once __DIR__ . '/../config.php';
if (admin_exists()) { header('Location: login.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    $p2 = (string)($_POST['password2'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $u)) $error = 'Username must be 3-40 characters using letters, numbers, _, ., or -.';
    elseif (strlen($p) < 12) $error = 'Use a password with at least 12 characters.';
    elseif ($p !== $p2) $error = 'Passwords do not match.';
    else {
        $stmt = db()->prepare("INSERT INTO admins(username,password_hash) VALUES(?,?)");
        $stmt->execute([$u,password_hash($p,PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)db()->lastInsertId();
        $_SESSION['admin_username'] = $u;
        header('Location: index.php'); exit;
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Setup</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="auth-page"><div class="auth-card"><img src="../assets/img/logo.png"><h1>Create Admin</h1><p>This page works only until the first administrator is created.</p>
<?php if($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Username<input name="username" autocomplete="username" required></label><label>Password<input type="password" name="password" autocomplete="new-password" required></label><label>Confirm password<input type="password" name="password2" autocomplete="new-password" required></label><button class="btn">Create Admin</button></form></div></body></html>
