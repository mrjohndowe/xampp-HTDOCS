<?php

declare(strict_types=1);

$editId = max(0, (int)($_GET['edit'] ?? 0));
$editCompany = null;

if ($editId > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM insurance_companies
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $editId
    ]);

    $editCompany = $stmt->fetch();

    if (!$editCompany) {
        redirect('index.php?p=companies');
    }
}

/*
 * Handle add/edit/delete actions.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = postString('action');

    if ($action === 'save') {

        $companyId = postInt('company_id');

        $name = postString('name');
        $address = postString('address');
        $city = postString('city');
        $state = postString('state');
        $zip = normalizeZip(postString('zip'));
        $phone = postString('phone');
        $website = postString('website');
        $cardTemplate = postString('card_template');

        $errors = [];

        if ($name === '') {
            $errors[] = 'Company name is required.';
        }

        if (!isValidZip($zip)) {
            $errors[] = 'ZIP code must be 5 or 9 digits.';
        }

        if ($state !== '') {
            $stateCheck = $pdo->prepare("
                SELECT abbreviation
                FROM states
                WHERE abbreviation = :state
                   OR LOWER(name) = LOWER(:state)
                LIMIT 1
            ");

            $stateCheck->execute([
                ':state' => $state
            ]);

            $stateRow = $stateCheck->fetch();

            if (!$stateRow) {
                $errors[] = 'Please select a valid state.';
            } else {
                $state = $stateRow['abbreviation'];
            }
        }

        if (!$errors) {

            $logoPath = null;

            if ($companyId) {

                $stmt = $pdo->prepare("
                    SELECT logo
                    FROM insurance_companies
                    WHERE id = :id
                    LIMIT 1
                ");

                $stmt->execute([
                    ':id' => $companyId
                ]);

                $existingCompany = $stmt->fetch();

                if (!$existingCompany) {
                    redirect('index.php?p=companies');
                }

                $logoPath = $existingCompany['logo'];
            }

            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {

                $uploadDir = __DIR__ . '/../uploads/companies';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $tmpFile = $_FILES['logo']['tmp_name'];

                $imageInfo = @getimagesize($tmpFile);

                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/svg+xml' => 'svg',
                ];

                $mimeType = $imageInfo['mime'] ?? '';

                if ($mimeType === '' && strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION)) === 'svg') {
                    $mimeType = 'image/svg+xml';
                }

                if (!isset($allowedMimeTypes[$mimeType])) {
                    $errors[] = 'Logo must be PNG, JPG, WebP, or SVG.';
                } else {

                    $extension = $allowedMimeTypes[$mimeType];

                    $filename = 'company_' . ($companyId ?: 'new') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

                    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

                    if (!move_uploaded_file($tmpFile, $destination)) {
                        $errors[] = 'Unable to save the company logo.';
                    } else {
                        $logoPath = 'uploads/companies/' . $filename;
                    }
                }
            }

            if (!$errors) {

                if ($companyId) {

                    $stmt = $pdo->prepare("
                        UPDATE insurance_companies
                        SET
                            name = :name,
                            address = :address,
                            city = :city,
                            state = :state,
                            zip = :zip,
                            phone = :phone,
                            website = :website,
                            logo = :logo,
                            card_template = :card_template,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ");

                    $stmt->execute([
                        ':name' => $name,
                        ':address' => $address,
                        ':city' => $city,
                        ':state' => $state,
                        ':zip' => $zip,
                        ':phone' => $phone,
                        ':website' => $website,
                        ':logo' => $logoPath,
                        ':id' => $companyId,
                        ':card_template' => $cardTemplate,
                    ]);

                    redirect('index.php?p=companies&success=updated');

                } else {

                    $stmt = $pdo->prepare("
                        INSERT INTO insurance_companies (
                            name,
                            address,
                            city,
                            state,
                            zip,
                            phone,
                            website,
                            logo,
                            card_template
                        )
                        VALUES (
                            :name,
                            :address,
                            :city,
                            :state,
                            :zip,
                            :phone,
                            :website,
                            :logo,
                            :card_template

                        )
                    ");

                    $stmt->execute([
                        ':name' => $name,
                        ':address' => $address,
                        ':city' => $city,
                        ':state' => $state,
                        ':zip' => $zip,
                        ':phone' => $phone,
                        ':website' => $website,
                        ':logo' => $logoPath,
                        ':card_template' => $cardTemplate,

                    ]);

                    redirect('index.php?p=companies&success=added');
                }
            }
        }
    }

    if ($action === 'delete') {

        $companyId = postInt('company_id');

        if ($companyId) {

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM insurance_cards
                WHERE company_id = :company_id
            ");

            $stmt->execute([
                ':company_id' => $companyId
            ]);

            if ((int)$stmt->fetchColumn() === 0) {

                $stmt = $pdo->prepare("
                    DELETE FROM insurance_companies
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':id' => $companyId
                ]);

                redirect('index.php?p=companies&success=deleted');
            }

            redirect('index.php?p=companies&success=error');
        }
    }
}

$states = $pdo->query("
    SELECT name, abbreviation
    FROM states
    WHERE active = 1
    ORDER BY name
")->fetchAll();

$companies = $pdo->query("
    SELECT *
    FROM insurance_companies
    ORDER BY name
")->fetchAll();

$success = $_GET['success'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editCompany ? 'Edit Company' : 'Insurance Companies' ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= getVersionNumber() ?>">
    <script src="assets/js/app.js?v=<?= getVersionNumber() ?>"></script>
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
            <a href="index.php?p=admin">Admin</a>
            <a href="index.php?p=companies">Companies</a>
        </nav>

    </div>
</header>

<main class="container">

    <div class="page-header">

        <div>
            <h1><?= $editCompany ? 'Edit Insurance Company' : 'Insurance Companies' ?></h1>
            <p><?= $editCompany ? 'Update the saved insurance company information.' : 'Manage your insurance companies.' ?></p>
        </div>

        <?php if (!$editCompany): ?>
            <a href="index.php?p=dashboard" class="btn btn-secondary">Back to Dashboard</a>
        <?php endif; ?>

    </div>

    <?php if ($success === 'added'): ?>

        <div class="alert-success">Insurance company added successfully.</div>

    <?php elseif ($success === 'updated'): ?>

        <div class="alert-success">Insurance company updated successfully.</div>

    <?php elseif ($success === 'deleted'): ?>

        <div class="alert-success">Insurance company deleted successfully.</div>

    <?php elseif ($success === 'error'): ?>

        <div class="alert-danger">This company cannot be deleted because it is being used by an insurance card.</div>

    <?php endif; ?>

    <section class="form-card">

        <div class="form-card-header">
            <h2><?= $editCompany ? 'Edit Company' : 'Add Company' ?></h2>
        </div>

        <form method="post" enctype="multipart/form-data">

            <input type="hidden" name="action" value="save">
            <input type="hidden" name="company_id" value="<?= (int)($editCompany['id'] ?? 0) ?>">

            <div class="form-grid">

                <div class="field field-full">
                    <label for="name">Company Name</label>
                    <input type="text" id="name" name="name" value="<?= e($editCompany['name'] ?? '') ?>" required>
                </div>

                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= e($editCompany['phone'] ?? '') ?>">
                </div>

                <div class="field full">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?= e($editCompany['address'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?= e($editCompany['city'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" list="state-list" value="<?= e($editCompany['state'] ?? '') ?>" placeholder="Type or select a state" maxlength="50" autocomplete="address-level1">
                    <datalist id="state-list">
                        <?php foreach ($states as $state): ?>
                            <option value="<?= e($state['abbreviation']) ?>"><?= e($state['name']) ?></option>
                            <option value="<?= e($state['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="field">
                    <label for="zip">ZIP Code</label>
                    <input type="text" id="zip" name="zip" value="<?= e(formatZip($editCompany['zip'] ?? '')) ?>" maxlength="10">
                </div>

                <div class="field">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" value="<?= e($editCompany['website'] ?? '') ?>">
                </div>

                <div class="field full">
                    <label for="logo">Company Logo</label>

                    <?php if (!empty($editCompany['logo'])): ?>

                        <div style="margin-bottom:12px;">
                            <img src="<?= e($editCompany['logo']) ?>" alt="<?= e($editCompany['name']) ?> logo" style="max-width:180px;max-height:80px;object-fit:contain;">
                        </div>

                    <?php endif; ?>

                    <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                </div>
                <div class="field full">
                    <select name="card_template">
                        <option value="default">Default</option>
                        <option value="alternate">Alternate</option>
                    </select>
                </div>

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    <?= $editCompany ? 'Save Changes' : 'Add Company' ?>
                </button>

                <?php if ($editCompany): ?>
                    <a href="index.php?p=companies" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>

            </div>

        </form>

    </section>

    <?php if (!$editCompany): ?>

        <section class="form-card" style="margin-top:24px;">

            <div class="form-card-header">
                <h2>Saved Companies</h2>
            </div>

            <?php if (!$companies): ?>

                <div class="empty-state">
                    <h2>No Companies</h2>
                    <p>Add an insurance company above.</p>
                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table>

                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Company</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Website</th>
                                <th>Print</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($companies as $company): ?>

                                <tr>

                                    <td>
                                        <?php if (!empty($company['logo'])): ?>
                                            <img src="<?= e($company['logo']) ?>" alt="<?= e($company['name']) ?> logo" style="width:70px;height:40px;object-fit:contain;">
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>

                                    <td><?= e($company['name']) ?></td>

                                    <td>
                                        <?= e($company['address']) ?><br>
                                        <?= e($company['city']) ?><?php if ($company['city'] && $company['state']): ?>, <?php endif; ?><?= e($company['state']) ?> <?= e(formatZip($company['zip'])) ?>
                                    </td>

                                    <td><?= e($company['phone']) ?></td>

                                    <td><?= e($company['website']) ?></td>

                                    <td><?= ucfirst(e($company['card_template'])) ?></td>

                                    <td>
                                        <a href="index.php?p=companies&edit=<?= (int)$company['id'] ?>" class="btn btn-secondary">Edit</a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>

</main>

</body>
</html>
