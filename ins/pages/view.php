<?php

declare(strict_types=1);

$id = getId();

if (!$id) {
    redirect('index.php?p=dashboard');
}

$stmt = $pdo->prepare("
    SELECT
        cards.*,
        companies.name AS company_name,
        companies.address AS company_address,
        companies.city AS company_city,
        companies.state AS company_state,
        companies.zip AS company_zip,
        companies.phone AS company_phone,
        companies.website AS company_website,
        companies.logo AS company_logo
    FROM insurance_cards AS cards
    INNER JOIN insurance_companies AS companies
        ON companies.id = cards.company_id
    WHERE cards.id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

$card = $stmt->fetch();

if (!$card) {
    http_response_code(404);
    exit('Insurance card not found.');
}

$companyAddress = trim(implode(', ', array_filter([
    $card['company_address'] ?? '',
    $card['company_city'] ?? '',
    $card['company_state'] ?? '',
    formatZip($card['company_zip'] ?? '')
])));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Card</title>
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
            <h1>Insurance Card</h1>
            <p>Policy <?= e($card['policy_number']) ?></p>
        </div>

        <div class="card-actions">
            <a href="index.php?p=edit&id=<?= $id ?>" class="btn btn-primary">Edit</a>
            <a href="index.php?p=print&id=<?= $id ?>" class="btn btn-secondary">Print</a>
            <a href="index.php?p=dashboard" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="insurance-grid">

        <article class="insurance-card">

            <div class="insurance-card-stripe"></div>

            <div class="insurance-card-content">

                <div class="insurance-card-header">

                    <div>

                        <?php if (!empty($card['company_logo'])): ?>

                            <img
                                src="<?= e($card['company_logo']) ?>"
                                alt="<?= e($card['company_name']) ?>"
                                class="insurance-company-logo"
                            >

                        <?php endif; ?>

                        <div class="insurance-company">
                            <?= e($card['company_name']) ?>
                        </div>

                        <?php if ($companyAddress !== ''): ?>

                            <div class="insurance-company-address">
                                <?= e($companyAddress) ?>
                            </div>

                        <?php endif; ?>

                        <?php if (!empty($card['company_phone'])): ?>

                            <div class="insurance-company-address">
                                <?= e($card['company_phone']) ?>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="insurance-type">
                        AUTO INSURANCE
                    </div>

                </div>

                <div class="insurance-title">
                    INSURANCE IDENTIFICATION CARD
                </div>

                <div class="insurance-fields">

                    <div class="insurance-field full">
                        <div class="insurance-label">Named Insured</div>
                        <div class="insurance-value"><?= e($card['insured_name']) ?></div>
                    </div>

                    <div class="insurance-field">
                        <div class="insurance-label">Policy Number</div>
                        <div class="insurance-value"><?= e($card['policy_number']) ?></div>
                    </div>

                    <div class="insurance-field">
                        <div class="insurance-label">Effective</div>
                        <div class="insurance-value"><?= e(formatDate($card['effective_date'])) ?></div>
                    </div>

                    <div class="insurance-field">
                        <div class="insurance-label">Expiration</div>
                        <div class="insurance-value"><?= e(formatDate($card['expiration_date'])) ?></div>
                    </div>

                    <div class="insurance-field">
                        <div class="insurance-label">Vehicle</div>
                        <div class="insurance-value">
                            <?= e(trim(($card['vehicle_year'] ?? '') . ' ' . ($card['vehicle_make'] ?? '') . ' ' . ($card['vehicle_model'] ?? ''))) ?>
                        </div>
                    </div>

                    <div class="insurance-field">
                        <div class="insurance-label">VIN</div>
                        <div class="insurance-value"><?= e($card['vin']) ?></div>
                    </div>

                    <div class="insurance-field">
                        <div class="insurance-label">License Plate</div>
                        <div class="insurance-value"><?= e($card['license_plate']) ?></div>
                    </div>

                    <div class="insurance-field">
                        <div class="insurance-label">Bodily Injury Liability</div>
                        <div class="insurance-value"><?= e($card['liability_bod']) ?></div>
                    </div>

                    <div class="insurance-field">
                        <div class="insurance-label">Property Damage</div>
                        <div class="insurance-value"><?= e($card['property_damage']) ?></div>
                    </div>

                </div>

                <div class="insurance-card-footer">
                    Carry this card in your vehicle as proof of insurance.
                </div>

            </div>

        </article>

    </div>

</main>

</body>
</html>
