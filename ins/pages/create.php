<?php

declare(strict_types=1);

$errors = [];

$values = [
    'company_id' => '',
    'insured_name' => '',
    'vehicle_year' => '',
    'vehicle_make' => '',
    'vehicle_model' => '',
    'vin' => '',
    'license_plate' => '',
    'effective_date' => date('Y-m-d'),
    'expiration_date' => '',
    'term' => '1_year',
    'liability_bod' => '',
    'property_damage' => ''
];

$companies = $pdo->query("
    SELECT id, name
    FROM insurance_companies
    WHERE active = 1
    ORDER BY name ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $values['company_id'] = postInt('company_id');
    $values['insured_name'] = postString('insured_name');
    $values['vehicle_year'] = postInt('vehicle_year');
    $values['vehicle_make'] = postString('vehicle_make');
    $values['vehicle_model'] = postString('vehicle_model');
    $values['vin'] = strtoupper(postString('vin'));
    $values['license_plate'] = strtoupper(postString('license_plate'));
    $values['effective_date'] = postString('effective_date');
    $values['term'] = postString('term');
    $values['liability_bod'] = postString('liability_bod');
    $values['property_damage'] = postString('property_damage');

    $termMonths = [
        '1_month' => 1,
        '6_months' => 6,
        '1_year' => 12
    ];

    if (!isset($termMonths[$values['term']])) {
        $errors[] = 'Please select an insurance term.';
    }

    if (!$values['company_id']) {
        $errors[] = 'Please select an insurance company.';
    }

    if ($values['insured_name'] === '') {
        $errors[] = 'Named insured is required.';
    }

    if ($values['effective_date'] === '') {
        $errors[] = 'Effective date is required.';
    }

    if (!$errors) {

        try {
            $effectiveDate = new DateTime($values['effective_date']);

            $expirationDate = clone $effectiveDate;
            $expirationDate->modify('+' . $termMonths[$values['term']] . ' months');

            $values['expiration_date'] = $expirationDate->format('Y-m-d');

        } catch (Exception $e) {
            $errors[] = 'Invalid effective date.';
        }
    }

    if (!$errors) {

        $policyNumber = generatePolicyNumber($pdo);

        try {

            $stmt = $pdo->prepare("
                INSERT INTO insurance_cards
                (
                    company_id,
                    insured_name,
                    policy_number,
                    vehicle_year,
                    vehicle_make,
                    vehicle_model,
                    vin,
                    license_plate,
                    effective_date,
                    expiration_date,
                    liability_bod,
                    property_damage
                )
                VALUES
                (
                    :company_id,
                    :insured_name,
                    :policy_number,
                    :vehicle_year,
                    :vehicle_make,
                    :vehicle_model,
                    :vin,
                    :license_plate,
                    :effective_date,
                    :expiration_date,
                    :liability_bod,
                    :property_damage
                )
            ");

            $stmt->execute([
                ':company_id' => $values['company_id'],
                ':insured_name' => $values['insured_name'],
                ':policy_number' => $policyNumber,
                ':vehicle_year' => $values['vehicle_year'],
                ':vehicle_make' => $values['vehicle_make'],
                ':vehicle_model' => $values['vehicle_model'],
                ':vin' => $values['vin'],
                ':license_plate' => $values['license_plate'],
                ':effective_date' => $values['effective_date'],
                ':expiration_date' => $values['expiration_date'],
                ':liability_bod' => $values['liability_bod'],
                ':property_damage' => $values['property_damage']
            ]);

            redirect('index.php?p=view&id=' . $pdo->lastInsertId());

        } catch (PDOException $e) {
            $errors[] = 'Unable to create the insurance card.';
        }
    }
}

$displayExpirationDate = '';

if ($values['effective_date'] !== '' && $values['term'] !== '') {
    $termMonths = [
        '1_month' => 1,
        '6_months' => 6,
        '1_year' => 12
    ];

    if (isset($termMonths[$values['term']])) {
        try {
            $effectiveDate = new DateTime($values['effective_date']);
            $effectiveDate->modify('+' . $termMonths[$values['term']] . ' months');
            $displayExpirationDate = $effectiveDate->format('Y-m-d');
        } catch (Exception $e) {
            $displayExpirationDate = '';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Insurance Card</title>
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
            <a href="index.php?p=create">Create Card</a>
            <a href="index.php?p=admin">Admin</a>
        </nav>

        <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode" title="Toggle dark mode">🌙</button>

    </div>
</header>

<main class="container">

    <div class="page-header">
        <div>
            <h1>Create Insurance Card</h1>
            <p>Enter the vehicle and policy information for the new card.</p>
        </div>
    </div>

    <?php if ($errors): ?>

        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <div><?= e($error) ?></div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <section class="form-card">

        <div class="form-card-header">
            <h2>Policy Information</h2>
        </div>

        <form method="post">

            <div class="form-grid">

                <div class="field field-full">
                    <label for="company_id">Insurance Company</label>
                    <select id="company_id" name="company_id" required>
                        <option value="">Select insurance company</option>

                        <?php foreach ($companies as $company): ?>

                            <option value="<?= (int)$company['id'] ?>" <?= (string)$values['company_id'] === (string)$company['id'] ? 'selected' : '' ?>>
                                <?= e($company['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="field field-full">
                    <label for="insured_name">Named Insured</label>
                    <input type="text" id="insured_name" name="insured_name" value="<?= e($values['insured_name']) ?>" required>
                </div>

                <div class="field">
                    <label for="effective_date">Effective Date</label>
                    <input type="date" id="effective_date" name="effective_date" value="<?= e($values['effective_date']) ?>" required>
                </div>

                <div class="field">
                    <label for="term">Policy Term</label>
                    <select id="term" name="term" required>
                        <option value="1_month" <?= $values['term'] === '1_month' ? 'selected' : '' ?>>1 Month</option>
                        <option value="6_months" <?= $values['term'] === '6_months' ? 'selected' : '' ?>>6 Months</option>
                        <option value="1_year" <?= $values['term'] === '1_year' ? 'selected' : '' ?>>1 Year</option>
                    </select>
                </div>

                <div class="field">
                    <label for="expiration_date">Expiration Date</label>
                    <input type="date" id="expiration_date" value="<?= e($displayExpirationDate) ?>" readonly>
                </div>

            </div>

            <div class="form-card-header" style="margin:24px -24px 24px;padding-left:24px;padding-right:24px;">
                <h2>Vehicle Information</h2>
            </div>

            <div class="form-grid">

                <div class="field">
                    <label for="vehicle_year">Year</label>
                    <input type="number" id="vehicle_year" name="vehicle_year" value="<?= e($values['vehicle_year']) ?>" min="1900" max="2100">
                </div>

                <div class="field">
                    <label for="vehicle_make">Make</label>
                    <input type="text" id="vehicle_make" name="vehicle_make" value="<?= e($values['vehicle_make']) ?>">
                </div>

                <div class="field">
                    <label for="vehicle_model">Model</label>
                    <input type="text" id="vehicle_model" name="vehicle_model" value="<?= e($values['vehicle_model']) ?>">
                </div>

                <div class="field">
                    <label for="license_plate">License Plate</label>
                    <input type="text" id="license_plate" name="license_plate" value="<?= e($values['license_plate']) ?>">
                </div>

                <div class="field field-full">
                    <label for="vin">VIN</label>
                    <input type="text" id="vin" name="vin" value="<?= e($values['vin']) ?>" maxlength="17">
                </div>

            </div>

            <div class="form-card-header" style="margin:24px -24px 24px;padding-left:24px;padding-right:24px;">
                <h2>Coverage</h2>
            </div>

            <div class="form-grid">

                <div class="field">
                    <label for="liability_bod">Bodily Injury Liability</label>
                    <input type="text" id="liability_bod" name="liability_bod" value="<?= e($values['liability_bod']) ?>" placeholder="$100,000 / $300,000">
                </div>

                <div class="field">
                    <label for="property_damage">Property Damage</label>
                    <input type="text" id="property_damage" name="property_damage" value="<?= e($values['property_damage']) ?>" placeholder="$100,000">
                </div>

            </div>

            <div class="form-actions">
                <a href="index.php?p=dashboard" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Insurance Card</button>
            </div>

        </form>

    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const effectiveDate = document.getElementById('effective_date');
    const term = document.getElementById('term');
    const expirationDate = document.getElementById('expiration_date');

    function calculateExpirationDate() {
        if (!effectiveDate.value || !term.value) {
            expirationDate.value = '';
            return;
        }

        const date = new Date(effectiveDate.value + 'T00:00:00');

        const months = {
            '1_month': 1,
            '6_months': 6,
            '1_year': 12
        };

        date.setMonth(date.getMonth() + months[term.value]);

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        expirationDate.value = `${year}-${month}-${day}`;
    }

    effectiveDate.addEventListener('change', calculateExpirationDate);
    term.addEventListener('change', calculateExpirationDate);

    calculateExpirationDate();
});
</script>

</body>
</html>
