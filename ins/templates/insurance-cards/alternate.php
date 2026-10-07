<?php

declare(strict_types=1);

$companyName = trim((string)($card['company_name'] ?? ''));
$companyAddress = trim((string)($card['company_address'] ?? ''));
$companyCity = trim((string)($card['company_city'] ?? ''));
$companyState = trim((string)($card['company_state'] ?? ''));
$companyZip = formatZip($card['company_zip'] ?? '');
$companyPhone = trim((string)($card['company_phone'] ?? ''));
$companyLogo = trim((string)($card['logo'] ?? ''));

$insuredName = trim((string)($card['insured_name'] ?? ''));
$secondaryInsured = trim((string)($card['secondary_insured'] ?? ''));

$policyNumber = trim((string)($card['policy_number'] ?? ''));

$effectiveDate = formatDate($card['effective_date'] ?? '');
$expirationDate = formatDate($card['expiration_date'] ?? '');

$vehicleYear = trim((string)($card['vehicle_year'] ?? ''));
$vehicleMake = trim((string)($card['vehicle_make'] ?? ''));
$vehicleModel = trim((string)($card['vehicle_model'] ?? ''));
$vin = trim((string)($card['vin'] ?? ''));
$licensePlate = trim((string)($card['license_plate'] ?? ''));

$bodilyInjury = trim((string)($card['liability_bod'] ?? ''));
$propertyDamage = trim((string)($card['property_damage'] ?? ''));

$vehicle = trim(implode(' ', array_filter([
    $vehicleYear,
    $vehicleMake,
    $vehicleModel
])));

$companyLocation = trim(implode(', ', array_filter([
    $companyCity,
    $companyState
])));

if ($companyZip !== '') {
    $companyLocation .= ($companyLocation !== '' ? ' ' : '') . $companyZip;
}

$barcodeData = barcodeValue($card);
$barcodeUrl = generateBarcodeSvg($barcodeData);

$logoPath = '';

if ($companyLogo !== '') {
    if (preg_match('/^https?:\/\//i', $companyLogo)) {
        $logoPath = $companyLogo;
    } else {
        $logoPath = '../../uploads/companies/' . ltrim(basename($companyLogo), '/\\');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Insurance Identification Card</title>

<style>
* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
    background: #ffffff;
    color: #000000;
    font-family: Arial, Helvetica, sans-serif;
}

.print-page {
    width: 8.5in;
    height: 11in;
    margin: 0 auto;
    padding: .35in .45in;
    background: #ffffff;
}

.insurance-card {
    width: 7.6in;
    height: 4.72in;
    border: 1.2px solid #000000;
    background: #ffffff;
    position: relative;
    overflow: hidden;
}

.insurance-card:first-child {
    margin-bottom: .32in;
}

.card-header {
    height: .72in;
    border-bottom: 1px solid #000000;
    display: grid;
    grid-template-columns: 1.05in 1fr;
}

.company-box {
    border-right: 1px solid #000000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: .08in;
}

.company-logo {
    max-width: .85in;
    max-height: .52in;
    object-fit: contain;
}

.company-logo-placeholder {
    font-size: 9px;
    font-weight: 700;
    text-align: center;
}

.company-box-information {
    padding: .09in .14in;
}

.company-name {
    font-size: 16px;
    font-weight: 800;
    line-height: 1.1;
    text-transform: uppercase;
}

.company-address {
    margin-top: 4px;
    font-size: 8px;
    line-height: 1.35;
}

.card-heading {
    height: .34in;
    border-bottom: 1px solid #000000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .3px;
    text-transform: uppercase;
}

.policy-row {
    height: .68in;
    display: grid;
    grid-template-columns: 2.1fr 1fr 1fr;
    border-bottom: 1px solid #000000;
}

.policy-cell {
    padding: .08in .11in;
}

.policy-cell + .policy-cell {
    border-left: 1px solid #000000;
}

.label {
    font-size: 7px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .35px;
    margin-bottom: 3px;
}

.value {
    font-size: 10px;
    font-weight: 700;
    line-height: 1.2;
}

.policy-number {
    font-size: 15px;
    letter-spacing: .8px;
}

.insured-row {
    height: .66in;
    display: grid;
    grid-template-columns: 1fr 2.15fr;
    border-bottom: 1px solid #000000;
}

.insured-label {
    padding: .09in .11in;
    border-right: 1px solid #000000;
}

.insured-information {
    padding: .08in .12in;
}

.insured-name {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    line-height: 1.35;
}

.secondary-name {
    margin-top: 2px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
}

.vehicle-row {
    height: .85in;
    display: grid;
    grid-template-columns: 1.7fr 1fr 1.15fr;
    border-bottom: 1px solid #000000;
}

.vehicle-cell {
    padding: .08in .11in;
}

.vehicle-cell + .vehicle-cell {
    border-left: 1px solid #000000;
}

.vehicle-description {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    line-height: 1.3;
}

.vin {
    font-size: 8px;
    font-weight: 700;
    word-break: break-all;
}

.coverage-row {
    height: .55in;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    border-bottom: 1px solid #000000;
}

.coverage-cell {
    padding: .07in .11in;
}

.coverage-cell + .coverage-cell {
    border-left: 1px solid #000000;
}

.barcode-row {
    height: .75in;
    display: grid;
    grid-template-columns: 1fr 2.25fr;
    border-bottom: 1px solid #000000;
}

.barcode-information {
    padding: .08in .11in;
    border-right: 1px solid #000000;
    font-size: 7px;
    line-height: 1.4;
}

.barcode-box {
    padding: .05in .1in;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.barcode-box img {
    display: block;
    width: 4.55in;
    height: .62in;
    object-fit: contain;
}

.footer {
    height: .47in;
    padding: .07in .11in;
    font-size: 6.5px;
    line-height: 1.35;
}

.cut-line {
    height: .32in;
    position: relative;
}

.cut-line::before {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    border-top: 1px dashed #555555;
}

.cut-line span {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 0 8px;
    background: #ffffff;
    color: #555555;
    font-size: 7px;
    letter-spacing: 1px;
}

@media screen {
    body {
        background: #eeeeee;
    }

    .print-page {
        margin: 20px auto;
        box-shadow: 0 0 12px rgba(0, 0, 0, .15);
    }
}

@media print {
    @page {
        size: Letter portrait;
        margin: 0;
    }

    html,
    body {
        width: 8.5in;
        height: 11in;
        margin: 0;
        padding: 0;
    }

    .print-page {
        margin: 0;
        box-shadow: none;
    }
}
</style>
</head>

<body>

<div class="print-page">

    <?php for ($copy = 0; $copy < 2; $copy++): ?>

        <div class="insurance-card">

            <div class="card-header">

                <div class="company-box">

                    <?php if ($logoPath !== ''): ?>

                        <img
                            src="<?= e($logoPath) ?>"
                            alt="<?= e($companyName) ?>"
                            class="company-logo"
                        >

                    <?php else: ?>

                        <div class="company-logo-placeholder">
                            INSURANCE
                        </div>

                    <?php endif; ?>

                </div>

                <div class="company-box-information">

                    <div class="company-name">
                        <?= e($companyName) ?>
                    </div>

                    <div class="company-address">

                        <?php if ($companyAddress !== ''): ?>
                            <?= e($companyAddress) ?><br>
                        <?php endif; ?>

                        <?php if ($companyLocation !== ''): ?>
                            <?= e($companyLocation) ?><br>
                        <?php endif; ?>

                        <?php if ($companyPhone !== ''): ?>
                            <?= e($companyPhone) ?>
                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <div class="card-heading">
                Automobile Insurance Identification Card
            </div>

            <div class="policy-row">

                <div class="policy-cell">

                    <div class="label">
                        Policy Number
                    </div>

                    <div class="value policy-number">
                        <?= e($policyNumber) ?>
                    </div>

                </div>

                <div class="policy-cell">

                    <div class="label">
                        Effective
                    </div>

                    <div class="value">
                        <?= e($effectiveDate) ?>
                    </div>

                </div>

                <div class="policy-cell">

                    <div class="label">
                        Expiration
                    </div>

                    <div class="value">
                        <?= e($expirationDate) ?>
                    </div>

                </div>

            </div>

            <div class="insured-row">

                <div class="insured-label">

                    <div class="label">
                        Named Insured
                    </div>

                </div>

                <div class="insured-information">

                    <?php if ($insuredName !== ''): ?>

                        <div class="insured-name">
                            <?= e($insuredName) ?>
                        </div>

                    <?php endif; ?>

                    <?php if ($secondaryInsured !== ''): ?>

                        <div class="secondary-name">
                            <?= e($secondaryInsured) ?>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

            <div class="vehicle-row">

                <div class="vehicle-cell">

                    <div class="label">
                        Covered Vehicle
                    </div>

                    <div class="vehicle-description">
                        <?= e($vehicle) ?>
                    </div>

                </div>

                <div class="vehicle-cell">

                    <div class="label">
                        VIN
                    </div>

                    <div class="vin">
                        <?= e($vin) ?>
                    </div>

                </div>

                <div class="vehicle-cell">

                    <div class="label">
                        License Plate
                    </div>

                    <div class="value">
                        <?= e($licensePlate) ?>
                    </div>

                </div>

            </div>

            <div class="coverage-row">

                <div class="coverage-cell">

                    <div class="label">
                        Year
                    </div>

                    <div class="value">
                        <?= e($vehicleYear) ?>
                    </div>

                </div>

                <div class="coverage-cell">

                    <div class="label">
                        Make / Model
                    </div>

                    <div class="value">
                        <?= e(trim($vehicleMake . ' ' . $vehicleModel)) ?>
                    </div>

                </div>

                <div class="coverage-cell">

                    <div class="label">
                        Property Damage
                    </div>

                    <div class="value">
                        <?= e($propertyDamage) ?>
                    </div>

                </div>

            </div>

            <div class="barcode-row">

                <div class="barcode-information">

                    <strong>LIABILITY COVERAGE</strong><br>

                    Bodily Injury:
                    <?= e($bodilyInjury) ?><br>

                    Property Damage:
                    <?= e($propertyDamage) ?><br>

                    Policy:
                    <?= e($policyNumber) ?>

                </div>

                <div class="barcode-box">

                    <?php if ($barcodeUrl !== ''): ?>

                        <img
                            src="<?= e($barcodeUrl) ?>"
                            alt="Insurance information barcode"
                        >

                    <?php endif; ?>

                </div>

            </div>

            <div class="footer">

                Keep this card with the insured vehicle. The information
                displayed above represents the insurance information entered
                into this system. Coverage is subject to the applicable policy
                terms, conditions, limits and exclusions.

            </div>

        </div>

        <?php if ($copy === 0): ?>

            <div class="cut-line">
                <span>CUT HERE</span>
            </div>

        <?php endif; ?>

    <?php endfor; ?>

</div>

</body>
</html>
