<?php

declare(strict_types=1);

startAdminSession();

$loginError = '';
$errors = [];
$success = '';

if (isset($_GET['logout'])) {
    adminLogout();
    redirect('index.php?p=admin');
}

if (!isAdminAuthenticated()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = postString('username');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $loginError = 'Username and password are required.';
        } elseif (!adminLogin($pdo, $username, $password)) {
            $loginError = 'Invalid administrator credentials.';
        } else {
            redirect('index.php?p=admin');
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Administration Login</title>
        <link rel="stylesheet" href="assets/css/style.css?v=<?= getVersionNumber() ?>">
        <script src="assets/js/app.js?v=<?= getVersionNumber() ?>" defer></script>
    </head>
    <body>

    <header class="topbar">
        <div class="topbar-inner">
            <a href="index.php?p=dashboard" class="brand">
                <span class="brand-icon">IC</span>
                <span>Insurance Cards</span>
            </a>

            <nav>
                <a href="index.php?p=dashboard">Dashboard</a>
            </nav>
        </div>
    </header>

    <main class="container">

        <div class="page-header">
            <div>
                <h1>Administration</h1>
                <p>Administrator authentication required.</p>
            </div>
        </div>

        <?php if ($loginError !== ''): ?>
            <div class="alert alert-error">
                <?= e($loginError) ?>
            </div>
        <?php endif; ?>

        <section class="form-card" style="max-width:480px;margin:0 auto;">

            <div class="form-card-header">
                <h2>Administrator Login</h2>
            </div>

            <form method="post" style="padding:24px;">

                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" autocomplete="username" required>
                </div>

                <div class="field" style="margin-top:16px;">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>

                <div class="form-actions" style="margin-top:24px;">
                    <a href="index.php?p=dashboard" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>

            </form>

        </section>

    </main>

    </body>
    </html>
    <?php
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'models') {
    header('Content-Type: application/json; charset=utf-8');

    $makeId = (int)($_GET['make_id'] ?? 0);

    if ($makeId <= 0) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, name
        FROM vehicle_models
        WHERE make_id = :make_id
          AND active = 1
        ORDER BY name ASC
    ");

    $stmt->execute([
        ':make_id' => $makeId
    ]);

    echo json_encode($stmt->fetchAll());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = postString('action');

    if ($action === 'add_make') {
        $name = postString('make_name');

        if ($name === '') {
            $errors[] = 'Make name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO vehicle_makes (name)
                    VALUES (:name)
                ");

                $stmt->execute([
                    ':name' => $name
                ]);

                $success = 'Vehicle make added successfully.';
            } catch (PDOException $e) {
                $errors[] = 'That vehicle make already exists.';
            }
        }
    }

    if ($action === 'add_model') {
        $makeId = postInt('model_make_id');
        $name = postString('model_name');

        if (!$makeId) {
            $errors[] = 'Please select a make.';
        }

        if ($name === '') {
            $errors[] = 'Model name is required.';
        }

        if (!$errors) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO vehicle_models (
                        make_id,
                        name
                    )
                    VALUES (
                        :make_id,
                        :name
                    )
                ");

                $stmt->execute([
                    ':make_id' => $makeId,
                    ':name' => $name
                ]);

                $success = 'Vehicle model added successfully.';
            } catch (PDOException $e) {
                $errors[] = 'That vehicle model already exists for the selected make.';
            }
        }
    }

    if ($action === 'toggle_make') {
        $makeId = postInt('make_id');

        if ($makeId) {
            $stmt = $pdo->prepare("
                UPDATE vehicle_makes
                SET
                    active = CASE WHEN active = 1 THEN 0 ELSE 1 END,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $makeId
            ]);

            $success = 'Vehicle make status updated.';
        }
    }

    if ($action === 'toggle_model') {
        $modelId = postInt('model_id');

        if ($modelId) {
            $stmt = $pdo->prepare("
                UPDATE vehicle_models
                SET
                    active = CASE WHEN active = 1 THEN 0 ELSE 1 END,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $modelId
            ]);

            $success = 'Vehicle model status updated.';
        }
    }
}

$companyCount = (int)$pdo->query("
    SELECT COUNT(*)
    FROM insurance_companies
")->fetchColumn();

$cardCount = (int)$pdo->query("
    SELECT COUNT(*)
    FROM insurance_cards
")->fetchColumn();

$makeCount = (int)$pdo->query("
    SELECT COUNT(*)
    FROM vehicle_makes
    WHERE active = 1
")->fetchColumn();

$modelCount = (int)$pdo->query("
    SELECT COUNT(*)
    FROM vehicle_models
    WHERE active = 1
")->fetchColumn();

$vehicleMakes = $pdo->query("
    SELECT id, name, active
    FROM vehicle_makes
    ORDER BY name ASC
")->fetchAll();

$vehicleModels = $pdo->query("
    SELECT
        models.id,
        models.name,
        models.active,
        makes.name AS make_name
    FROM vehicle_models AS models
    INNER JOIN vehicle_makes AS makes
        ON makes.id = models.make_id
    ORDER BY makes.name ASC, models.name ASC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= getVersionNumber() ?>">
    <script src="assets/js/app.js?v=<?= getVersionNumber() ?>" defer></script>
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">

        <a href="index.php?p=dashboard" class="brand">
            <span class="brand-icon">IC</span>
            <span>Insurance Cards</span>
        </a>

        <nav>
            <a href="index.php?p=dashboard">Dashboard</a>
            <a href="index.php?p=companies">Companies</a>
            <a href="index.php?p=admin&logout=1">Logout</a>
        </nav>

        <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode" title="Toggle dark mode">🌙</button>

    </div>
</header>

<main class="container">

    <div class="page-header">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Manage insurance cards, companies, vehicle makes and models.</p>
        </div>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <div><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="insurance-grid">

        <section class="form-card">
            <div class="form-card-header">
                <h2>Insurance Companies</h2>
            </div>

            <div style="padding:24px;">
                <div style="font-size:32px;font-weight:800;color:var(--navy);">
                    <?= $companyCount ?>
                </div>

                <div style="margin-top:5px;color:var(--muted);font-size:13px;">
                    Companies configured
                </div>

                <div style="margin-top:20px;">
                    <a href="index.php?p=companies" class="btn btn-primary">
                        Manage Companies
                    </a>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header">
                <h2>Insurance Cards</h2>
            </div>

            <div style="padding:24px;">
                <div style="font-size:32px;font-weight:800;color:var(--navy);">
                    <?= $cardCount ?>
                </div>

                <div style="margin-top:5px;color:var(--muted);font-size:13px;">
                    Cards created
                </div>

                <div style="margin-top:20px;">
                    <a href="index.php?p=create" class="btn btn-primary">
                        Create Card
                    </a>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-header">
                <h2>Vehicle Database</h2>
            </div>

            <div style="padding:24px;">
                <div style="font-size:32px;font-weight:800;color:var(--navy);">
                    <?= $makeCount ?>
                </div>

                <div style="margin-top:5px;color:var(--muted);font-size:13px;">
                    Makes
                </div>

                <div style="font-size:22px;font-weight:700;margin-top:16px;color:var(--navy);">
                    <?= $modelCount ?>
                </div>

                <div style="margin-top:5px;color:var(--muted);font-size:13px;">
                    Models
                </div>
            </div>
        </section>

    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">

        <section class="form-card">

            <div class="form-card-header">
                <h2>Add Vehicle Make</h2>
            </div>

            <form method="post" style="padding:24px;">

                <input type="hidden" name="action" value="add_make">

                <div class="field">
                    <label for="make_name">Make</label>
                    <input type="text" id="make_name" name="make_name" placeholder="Example: Ford" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Add Make
                    </button>
                </div>

            </form>

        </section>

        <section class="form-card">

            <div class="form-card-header">
                <h2>Add Vehicle Model</h2>
            </div>

            <form method="post" style="padding:24px;">

                <input type="hidden" name="action" value="add_model">

                <div class="field">
                    <label for="model_make_id">Make</label>
                    <select id="model_make_id" name="model_make_id" required>
                        <option value="">Select Make</option>

                        <?php foreach ($vehicleMakes as $make): ?>
                            <?php if ((int)$make['active'] === 1): ?>
                                <option value="<?= (int)$make['id'] ?>">
                                    <?= e($make['name']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="field" style="margin-top:16px;">
                    <label for="model_name">Model</label>
                    <input type="text" id="model_name" name="model_name" placeholder="Example: F-150" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Add Model
                    </button>
                </div>

            </form>

        </section>

    </div>

    <section class="form-card" style="margin-top:20px;">

        <div class="form-card-header">
            <h2>Vehicle Makes</h2>
        </div>

        <div style="padding:0 24px 24px;">

            <?php foreach ($vehicleMakes as $make): ?>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:15px;padding:14px 0;border-bottom:1px solid var(--border);">

                    <div>
                        <strong><?= e($make['name']) ?></strong>

                        <span style="margin-left:8px;font-size:11px;color:var(--muted);">
                            <?= (int)$make['active'] === 1 ? 'Active' : 'Disabled' ?>
                        </span>
                    </div>

                    <form method="post">
                        <input type="hidden" name="action" value="toggle_make">
                        <input type="hidden" name="make_id" value="<?= (int)$make['id'] ?>">

                        <button type="submit" class="btn btn-secondary">
                            <?= (int)$make['active'] === 1 ? 'Disable' : 'Enable' ?>
                        </button>
                    </form>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

    <section class="form-card" style="margin-top:20px;">

        <div class="form-card-header">
            <h2>Vehicle Models</h2>
        </div>

        <div style="padding:0 24px 24px;">

            <?php foreach ($vehicleModels as $model): ?>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:15px;padding:14px 0;border-bottom:1px solid var(--border);">

                    <div>
                        <strong><?= e($model['make_name']) ?></strong>
                        <span style="margin:0 5px;color:var(--muted);">•</span>
                        <span><?= e($model['name']) ?></span>

                        <span style="margin-left:8px;font-size:11px;color:var(--muted);">
                            <?= (int)$model['active'] === 1 ? 'Active' : 'Disabled' ?>
                        </span>
                    </div>

                    <form method="post">
                        <input type="hidden" name="action" value="toggle_model">
                        <input type="hidden" name="model_id" value="<?= (int)$model['id'] ?>">

                        <button type="submit" class="btn btn-secondary">
                            <?= (int)$model['active'] === 1 ? 'Disable' : 'Enable' ?>
                        </button>
                    </form>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

</main>

</body>
</html>
