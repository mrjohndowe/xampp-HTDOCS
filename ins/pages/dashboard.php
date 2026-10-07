<?php

declare(strict_types=1);

$stmt = $pdo->query("
    SELECT
        cards.*,
        companies.name AS company_name,
        companies.address AS company_address,
        companies.city AS company_city,
        companies.state AS company_state,
        companies.zip AS company_zip,
        companies.phone AS company_phone
    FROM insurance_cards AS cards
    INNER JOIN insurance_companies AS companies
        ON companies.id = cards.company_id
    ORDER BY cards.created_at DESC
");

$cards = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Cards</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= getVersionNumber() ?>">
    <script src="assets/js/app.js?v=<?= getVersionNumber() ?>"></script>
</head>


<body>

<header class="topbar">
    <div class="topbar-inner">
        <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode">🌙</button>

        <a href="index.php?p=dashboard" class="brand">
            <span class="brand-icon">IC</span>
            <span>Insurance Cards</span>
        </a>

        <nav>
            <a href="index.php?p=dashboard">Dashboard</a>
            <a href="index.php?p=create">Create Card</a>
            <a href="index.php?p=admin">Admin</a>
        </nav>

    </div>
</header>

<main class="container">

    <div class="page-header">

        <div>
            <h1>Insurance Cards</h1>
            <p>Manage your vehicle insurance identification cards.</p>
        </div>

        <a href="index.php?p=create" class="btn btn-primary">Create Insurance Card</a>

    </div>

    <?php if (!$cards): ?>

        <section class="form-card">

            <div class="empty-state">
                <h2>No Insurance Cards</h2>
                <p>Create your first insurance card to get started.</p>
                <a href="index.php?p=create" class="btn btn-primary">Create Insurance Card</a>
            </div>

        </section>

    <?php else: ?>

        <div class="insurance-grid">

            <?php foreach ($cards as $card): ?>

                <article class="insurance-card">

                    <div class="insurance-card-stripe"></div>

                    <div class="insurance-card-content">

                        <div class="insurance-card-header">

                            <div>

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
                                <div class="insurance-label">Coverage Period</div>
                                <div class="insurance-value"><?= e(formatDate($card['effective_date'])) ?> - <?= e(formatDate($card['expiration_date'])) ?></div>
                            </div>

                            <div class="insurance-field full">
                                <div class="insurance-label">Vehicle</div>
                                <div class="insurance-value"><?= e(trim(($card['vehicle_year'] ?? '') . ' ' . ($card['vehicle_make'] ?? '') . ' ' . ($card['vehicle_model'] ?? ''))) ?></div>
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
                                <div class="insurance-label">Liability</div>
                                <div class="insurance-value"><?= e($card['liability_bod']) ?></div>
                            </div>

                            <div class="insurance-field">
                                <div class="insurance-label">Property Damage</div>
                                <div class="insurance-value"><?= e($card['property_damage']) ?></div>
                            </div>

                        </div>

                        <div class="insurance-card-footer">

                            <?php if ($card['company_phone']): ?>
                                Insurance Company: <?= e($card['company_phone']) ?>
                            <?php endif; ?>

                        </div>

                        <div class="card-actions">
                            <a href="index.php?p=view&id=<?= (int)$card['id'] ?>" class="btn btn-secondary">View</a>
                            <a href="index.php?p=edit&id=<?= (int)$card['id'] ?>" class="btn btn-secondary">Edit</a>
                            <a href="index.php?p=print&id=<?= (int)$card['id'] ?>" class="btn btn-primary">Print</a>
                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
