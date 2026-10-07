<?php

declare(strict_types=1);

$companyAddress = trim((string)($card['company_address'] ?? ''));

$companyCityStateZip = trim(
    implode(', ', array_filter([
        trim((string)($card['company_city'] ?? '')),
        trim((string)($card['company_state'] ?? ''))
    ]))
);

$companyZip = formatZip($card['company_zip'] ?? '');

if ($companyZip !== '') {
    $companyCityStateZip .= ($companyCityStateZip !== '' ? ' ' : '') . $companyZip;
}

$companyPhone = trim((string)($card['company_phone'] ?? ''));

$companyLogo = trim((string)($card['company_logo'] ?? ''));

$logoPath = '';

if ($companyLogo !== '') {
    $possibleLogo = __DIR__ . '/../../uploads/companies/' . basename($companyLogo);

    if (is_file($possibleLogo)) {
        $logoPath = 'uploads/companies/' . basename($companyLogo);
    }
}

$primaryInsured = trim((string)($card['insured_name'] ?? ''));
$secondaryInsured = trim((string)($card['secondary_insured'] ?? ''));

$coverageBod = trim((string)($card['liability_bod'] ?? ''));
$coveragePd = trim((string)($card['property_damage'] ?? ''));

$vehicle = trim(implode(' ', array_filter([
    trim((string)($card['vehicle_year'] ?? '')),
    trim((string)($card['vehicle_make'] ?? '')),
    trim((string)($card['vehicle_model'] ?? ''))
])));

$effectiveDate = formatDate($card['effective_date'] ?? '');
$expirationDate = formatDate($card['expiration_date'] ?? '');
?>

<style>
    .alternate-card-page {
        width: 100%;
        min-height: calc(100vh - 90px);
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 25px;
        box-sizing: border-box;
    }

    .alternate-insurance-card {
        width: 8.5in;
        min-height: 5.45in;
        box-sizing: border-box;
        background: #fff;
        border: 1px solid #111;
        color: #111;
        font-family: Arial, Helvetica, sans-serif;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .15);
    }

    .alternate-card-header {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr;
        align-items: center;
        min-height: 78px;
        padding: 10px 16px;
        border-bottom: 2px solid #111;
        box-sizing: border-box;
    }

    .alternate-company {
        font-size: 16px;
        font-weight: 800;
        line-height: 1.15;
    }

    .alternate-company-logo {
        max-width: 125px;
        max-height: 55px;
        object-fit: contain;
        display: block;
    }

    .alternate-title {
        text-align: center;
        font-size: 18px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .alternate-card-id {
        text-align: right;
        font-size: 9px;
        font-weight: 700;
        line-height: 1.4;
    }

    .alternate-section {
        border-bottom: 1px solid #111;
    }

    .alternate-section-title {
        padding: 5px 9px;
        background: #111;
        color: #fff;
        font-size: 9px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .alternate-grid {
        display: grid;
        grid-template-columns: 1.8fr 1fr 1fr;
    }

    .alternate-grid.vehicle-grid {
        grid-template-columns: 1.7fr 1fr 1fr;
    }

    .alternate-field {
        min-height: 48px;
        padding: 7px 9px;
        border-right: 1px solid #111;
        box-sizing: border-box;
    }

    .alternate-field:last-child {
        border-right: 0;
    }

    .alternate-label {
        display: block;
        margin-bottom: 4px;
        font-size: 7px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .35px;
    }

    .alternate-value {
        display: block;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.25;
        word-break: break-word;
    }

    .alternate-value.small {
        font-size: 10px;
    }

    .alternate-insured {
        min-height: 66px;
    }

    .alternate-insured-name {
        display: block;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.35;
    }

    .alternate-insured-name + .alternate-insured-name {
        margin-top: 2px;
    }

    .alternate-coverage-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .alternate-coverage {
        min-height: 58px;
        padding: 8px 10px;
        border-right: 1px solid #111;
        box-sizing: border-box;
    }

    .alternate-coverage:last-child {
        border-right: 0;
    }

    .alternate-footer {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        min-height: 65px;
    }

    .alternate-company-details {
        padding: 9px 10px;
        border-right: 1px solid #111;
        font-size: 9px;
        line-height: 1.45;
    }

    .alternate-company-details strong {
        font-size: 10px;
    }

    .alternate-notice {
        padding: 9px 10px;
        font-size: 8px;
        line-height: 1.35;
    }

    @media screen and (max-width: 900px) {
        .alternate-card-page {
            padding: 10px;
            overflow-x: auto;
        }

        .alternate-insurance-card {
            flex: 0 0 8.5in;
        }
    }

    @media print {
        @page {
            size: landscape;
            margin: 0.25in;
        }

        body {
            background: #fff !important;
        }

        .alternate-card-page {
            min-height: auto;
            padding: 0;
        }

        .alternate-insurance-card {
            width: 8.5in;
            min-height: 5.45in;
            box-shadow: none;
            page-break-inside: avoid;
        }
    }
</style>

<div class="alternate-card-page">
    <div class="alternate-insurance-card">

        <div class="alternate-card-header">
            <div>
                <?php if ($logoPath !== ''): ?>
                    <img
                        src="<?= e($logoPath) ?>"
                        alt="<?= e($card['company_name']) ?>"
                        class="alternate-company-logo"
                    >
                <?php else: ?>
                    <div class="alternate-company">
                        <?= e($card['company_name']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="alternate-title">
                Insurance Identification Card
            </div>

            <div class="alternate-card-id">
                POLICY<br>
                <?= e($card['policy_number']) ?>
            </div>
        </div>

        <div class="alternate-section">
            <div class="alternate-section-title">
                Named Insured
            </div>

            <div class="alternate-field alternate-insured">
                <?php if ($primaryInsured !== ''): ?>
                    <span class="alternate-insured-name">
                        <?= e($primaryInsured) ?>
                    </span>
                <?php endif; ?>

                <?php if ($secondaryInsured !== ''): ?>
                    <span class="alternate-insured-name">
                        <?= e($secondaryInsured) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="alternate-section">
            <div class="alternate-section-title">
                Policy Information
            </div>

            <div class="alternate-grid">
                <div class="alternate-field">
                    <span class="alternate-label">Policy Number</span>
                    <span class="alternate-value">
                        <?= e($card['policy_number']) ?>
                    </span>
                </div>

                <div class="alternate-field">
                    <span class="alternate-label">Effective</span>
                    <span class="alternate-value">
                        <?= e($effectiveDate) ?>
                    </span>
                </div>

                <div class="alternate-field">
                    <span class="alternate-label">Expiration</span>
                    <span class="alternate-value">
                        <?= e($expirationDate) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="alternate-section">
            <div class="alternate-section-title">
                Vehicle Information
            </div>

            <div class="alternate-grid vehicle-grid">
                <div class="alternate-field">
                    <span class="alternate-label">Vehicle</span>
                    <span class="alternate-value">
                        <?= e($vehicle) ?>
                    </span>
                </div>

                <div class="alternate-field">
                    <span class="alternate-label">VIN</span>
                    <span class="alternate-value small">
                        <?= e($card['vin']) ?>
                    </span>
                </div>

                <div class="alternate-field">
                    <span class="alternate-label">License Plate</span>
                    <span class="alternate-value">
                        <?= e($card['license_plate']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="alternate-section">
            <div class="alternate-section-title">
                Liability Coverage
            </div>

            <div class="alternate-coverage-grid">
                <div class="alternate-coverage">
                    <span class="alternate-label">
                        Bodily Injury Liability
                    </span>

                    <span class="alternate-value">
                        <?= e($coverageBod) ?>
                    </span>
                </div>

                <div class="alternate-coverage">
                    <span class="alternate-label">
                        Property Damage Liability
                    </span>

                    <span class="alternate-value">
                        <?= e($coveragePd) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="alternate-footer">
            <div class="alternate-company-details">
                <strong><?= e($card['company_name']) ?></strong><br>

                <?php if ($companyAddress !== ''): ?>
                    <?= e($companyAddress) ?><br>
                <?php endif; ?>

                <?php if ($companyCityStateZip !== ''): ?>
                    <?= e($companyCityStateZip) ?><br>
                <?php endif; ?>

                <?php if ($companyPhone !== ''): ?>
                    Phone: <?= e($companyPhone) ?>
                <?php endif; ?>
            </div>

            <div class="alternate-notice">
                This identification card is evidence of insurance.
                Keep this card in the insured vehicle and present it
                when requested as permitted by applicable law.
            </div>
        </div>

    </div>
</div>
