<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$id = getId();

if (!$id) {
    redirect('index.php');
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

$vehicle = trim(
    ($card['vehicle_year'] ?? '') . ' ' .
    ($card['vehicle_make'] ?? '') . ' ' .
    ($card['vehicle_model'] ?? '')
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Card - <?= e($card['policy_number']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= getVersionNumber() ?>">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">
            <span class="brand-icon">IC</span>
            <span>Insurance Cards</span>
        </a>

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="create.php">Create Card</a>
            <a href="admin/index.php">Admin</a>
        </nav>
    </div>
</header>

<main class="container">

    <div class="page-header no-print">
        <div>
            <h1>Insurance Card</h1>
            <p><?= e($card['policy_number']) ?></p>
        </div>

        <div class="card-actions" style="margin-top:0;">
            <a href="index.php" class="btn btn-secondary">Back</a>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-secondary">Edit</a>
            <a href="print.php?id=<?= $id ?>" class="btn btn-primary">Print Card</a>
        </div>
    </div>

    <div style="max-width:760px;margin:0 auto;">

        <article class="insurance-card">

            <div class="insurance-card-stripe"></div>

            <div class="insurance-card-content">

                <div class="insurance-card-header">

                    <div>

                        <?php if (!empty($card['company_logo'])): ?>
                            <img src="<?= e($card['company_logo']) ?>" alt="<?= e($card['company_name']) ?>" style="max-width:150px;max-height:50px;margin-bottom:8px;">
                        <?php endif; ?>

                        <div class="insurance-company">
                            <?= e($card['company_name']) ?>
                        </div>

                        <div class="insurance-company-address">

                            <?php if ($card['company_address']): ?>
                                <?= e($card['company_address']) ?><br>
                            <?php endif; ?>

                            <?php if ($card['company_city'] || $card['company_state'] || $card['company_zip']): ?>
                                <?= e($card['company_city']) ?><?php if ($card['company_city'] && $card['company_state']): ?>, <?php endif; ?><?= e($card['company_state']) ?> <?= e(formatZip($card['company_zip'])) ?>
                            <?php endif; ?>

                            <?php if ($card['company_phone']): ?>
                                <br><?= e($card['company_phone']) ?>
                            <?php endif; ?>

                        </div>

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

                    <div class="insurance-field full">
                        <div class="insurance-label">Vehicle</div>
                        <div class="insurance-value"><?= e($vehicle) ?></div>
                    </div>

                    <div class="insurance-field full">
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
                    This card is provided as identification of insurance coverage. Coverage is subject to the terms, conditions and exclusions of the applicable insurance policy.
                </div>

            </div>

        </article>

    </div>

</main>

</body>
</html>
