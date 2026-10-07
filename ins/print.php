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
    <title>Print Insurance Card</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= getVersionNumber() ?>">

    <style>
        @page {
            size: auto;
            margin: 0.5in;
        }

        body {
            background: #fff;
        }

        .print-page {
            width: 100%;
            display: flex;
            justify-content: center;
            padding-top: 20px;
        }

        .print-card {
            width: 3.375in;
            min-height: 2.125in;
            box-shadow: none;
            border-radius: 8px;
        }

        .print-card .insurance-card-content {
            padding: 13px;
        }

        .print-card .insurance-card-header {
            padding-bottom: 9px;
            gap: 10px;
        }

        .print-card .insurance-company {
            font-size: 10px;
        }

        .print-card .insurance-company-address {
            font-size: 6px;
            line-height: 1.35;
        }

        .print-card .insurance-type {
            font-size: 6px;
        }

        .print-card .insurance-title {
            margin: 8px 0;
            font-size: 6px;
        }

        .print-card .insurance-fields {
            gap: 7px;
        }

        .print-card .insurance-label {
            margin-bottom: 2px;
            font-size: 5px;
        }

        .print-card .insurance-value {
            font-size: 7px;
        }

        .print-card .insurance-card-footer {
            margin-top: 8px;
            padding-top: 6px;
            font-size: 5px;
        }

        @media print {
            .print-page {
                padding: 0;
            }

            .print-card {
                border: 1px solid #bfc7d2;
            }
        }
    </style>
</head>
<body>

<div class="print-page">

    <article class="insurance-card print-card">

        <div class="insurance-card-stripe"></div>

        <div class="insurance-card-content">

            <div class="insurance-card-header">

                <div>

                    <?php if (!empty($card['company_logo'])): ?>
                        <img src="<?= e($card['company_logo']) ?>" alt="<?= e($card['company_name']) ?>" style="max-width:75px;max-height:25px;margin-bottom:3px;">
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

                <div class="insurance-type">AUTO INSURANCE</div>

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
                    <div class="insurance-label">Effective / Expiration</div>
                    <div class="insurance-value"><?= e(formatDate($card['effective_date'])) ?> - <?= e(formatDate($card['expiration_date'])) ?></div>
                </div>

                <div class="insurance-field full">
                    <div class="insurance-label">Vehicle</div>
                    <div class="insurance-value"><?= e($vehicle) ?></div>
                </div>

                <div class="insurance-field">
                    <div class="insurance-label">VIN</div>
                    <div class="insurance-value"><?= e($card['vin']) ?></div>
                </div>

                <div class="insurance-field">
                    <div class="insurance-label">Plate</div>
                    <div class="insurance-value"><?= e($card['license_plate']) ?></div>
                </div>

                <div class="insurance-field">
                    <div class="insurance-label">Liability</div>
                    <div class="insurance-value"><?= e($card['liability_bod']) ?></div>
                </div>

                <div class="insurance-field">
                    <div class="insurance-label">Property Damage</div>
                    <div class="insurance-value"><?= e($card['property_damage']) ?></div>
                </div>

            </div>

        </div>

    </article>

</div>

<script>
window.addEventListener('load', function () {
    window.print();
});
</script>

</body>
</html>
