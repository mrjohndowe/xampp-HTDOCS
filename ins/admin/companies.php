<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = postString('action');

    if ($action === 'add') {

        $name = postString('name');
        $address = postString('address');
        $city = postString('city');
        $state = strtoupper(postString('state'));
        $zip = normalizeZip(postString('zip'));
        $phone = postString('phone');
        $website = postString('website');

        if ($name === '') {
            $errors[] = 'Insurance company name is required.';
        }

        if (!isValidZip($zip)) {
            $errors[] = 'ZIP code must contain 5 or 9 numbers.';
        }

        if ($state !== '' && strlen($state) !== 2) {
            $errors[] = 'State must be 2 characters.';
        }

        if (!$errors) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO insurance_companies
                    (
                        name,
                        address,
                        city,
                        state,
                        zip,
                        phone,
                        website
                    )
                    VALUES
                    (
                        :name,
                        :address,
                        :city,
                        :state,
                        :zip,
                        :phone,
                        :website
                    )
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':address' => $address,
                    ':city' => $city,
                    ':state' => $state,
                    ':zip' => $zip,
                    ':phone' => $phone,
                    ':website' => $website
                ]);

                redirect('companies.php?success=added');

            } catch (PDOException $e) {
                $errors[] = 'Unable to add the insurance company. The company name may already exist.';
            }
        }
    }

    if ($action === 'delete') {

        $id = postInt('id');

        if ($id) {
            try {
                $stmt = $pdo->prepare("
                    DELETE FROM insurance_companies
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':id' => $id
                ]);

                redirect('companies.php?success=deleted');

            } catch (PDOException $e) {
                $errors[] = 'This company cannot be deleted because an insurance card is using it.';
            }
        }
    }
}

if (($_GET['success'] ?? '') === 'added') {
    $success = 'Insurance company added successfully.';
}

if (($_GET['success'] ?? '') === 'deleted') {
    $success = 'Insurance company deleted successfully.';
}

$companies = $pdo->query("
    SELECT *
    FROM insurance_companies
    ORDER BY name ASC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Companies</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= getVersionNumber() ?>">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a href="../index.php" class="brand">
            <span class="brand-icon">IC</span>
            <span>Insurance Cards</span>
        </a>

        <nav>
            <a href="../index.php">Dashboard</a>
            <a href="index.php">Admin</a>
            <a href="companies.php">Companies</a>
        </nav>
    </div>
</header>

<main class="container">

    <div class="page-header">
        <div>
            <h1>Insurance Companies</h1>
            <p>Add and manage insurance company information.</p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <div><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <section class="form-card">

        <div class="form-card-header">
            <h2>Add Insurance Company</h2>
        </div>

        <form method="post">

            <input type="hidden" name="action" value="add">

            <div class="form-grid">

                <div class="field field-full">
                    <label for="name">Company Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="field field-full">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="123 Main Street">
                </div>

                <div class="field">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" placeholder="Pueblo">
                </div>

                <div class="field">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" maxlength="2" placeholder="CO">
                </div>

                <div class="field">
                    <label for="zip">ZIP Code</label>
                    <input type="text" id="zip" name="zip" maxlength="10" inputmode="numeric" placeholder="81001 or 81001-1234" autocomplete="postal-code">
                </div>

                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" placeholder="(555) 555-5555">
                </div>

                <div class="field field-full">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" placeholder="https://example.com">
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Company</button>
            </div>

        </form>

    </section>

    <section class="table-card">

        <div class="table-card-header">
            <h2>Saved Companies</h2>
        </div>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Website</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!$companies): ?>

                    <tr>
                        <td colspan="5" class="empty-state">No insurance companies have been added.</td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($companies as $company): ?>

                        <tr>

                            <td>
                                <strong><?= e($company['name']) ?></strong>
                            </td>

                            <td>
                                <?php if ($company['address']): ?>
                                    <?= e($company['address']) ?><br>
                                <?php endif; ?>

                                <?php if ($company['city'] || $company['state'] || $company['zip']): ?>
                                    <?= e($company['city']) ?><?php if ($company['city'] && $company['state']): ?>, <?php endif; ?><?= e($company['state']) ?> <?= e(formatZip($company['zip'])) ?>
                                <?php endif; ?>
                            </td>

                            <td><?= e($company['phone']) ?></td>

                            <td>
                                <?php if ($company['website']): ?>
                                    <a href="<?= e($company['website']) ?>" target="_blank" rel="noopener">Website</a>
                                <?php endif; ?>
                            </td>

                            <td class="table-actions">
                                <form method="post" onsubmit="return confirm('Delete this insurance company?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$company['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<script>
const zipInput = document.getElementById('zip');

if (zipInput) {
    zipInput.addEventListener('input', function () {
        let digits = this.value.replace(/\D/g, '').slice(0, 9);

        if (digits.length > 5) {
            digits = digits.substring(0, 5) + '-' + digits.substring(5);
        }

        this.value = digits;
    });
}
</script>

</body>
</html>
