<?php

declare(strict_types=1);

$company = $company ?? [];
$card = $card ?? [];

$esc = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

$companyName = trim((string)($company['name'] ?? 'INSURANCE INFORMATION'));
$companySubtitle = trim((string)($company['subtitle'] ?? 'Mutual Automobile Insurance Co.'));
$companyLogo = trim((string)($company['logo'] ?? ''));
$companyLogo = 'uploads/companies/company_2_9744898b5f475d1c.png';

$insuredName = trim((string)($card['insured_name'] ?? ''));
$secondaryInsured = trim((string)($card['secondary_insured'] ?? ''));
$policyNumber = trim((string)($card['policy_number'] ?? ''));

$effectiveDate = formatDate($card['effective_date'] ?? '');
$expirationDate = formatDate($card['expiration_date'] ?? '');

$vehicleMake = trim((string)($card['vehicle_make'] ?? ''));
$vehicleModel = trim((string)($card['vehicle_model'] ?? ''));
$vehicleYear = trim((string)($card['vehicle_year'] ?? ''));
$vin = trim((string)($card['vin'] ?? ''));

// $agentName = trim((string)($card['agent_name'] ?? ''));

$names = $r->generateNames(1);
$names = $names[0];
$fName = $names['first_name'];
$lName = $names['last_name'];

$agentName = $fName . ' '. $lName;

// $agentPhone = trim((string)($card['agent_phone'] ?? ''));
$agentPhone = generatePhoneNumber('800');

$barcode = '';

if (function_exists('barcodeValue') && function_exists('generateBarcodeSvg')) {
    $barcodeValue = barcodeValue($card);

    if ($barcodeValue !== '') {
        $barcode = generateBarcodeSvg($barcodeValue, 420, 55);
    }
}

// $logoUrl = '';

// if ($companyLogo !== '') {
//     if (filter_var($companyLogo, FILTER_VALIDATE_URL)) {
//         $logoUrl = $companyLogo;
//     } else {
//         $logoPath = __DIR__ . '/../../uploads/companies/' . basename($companyLogo);

//         if (is_file($logoPath)) {
//             $logoUrl = '/ins/uploads/companies/' . basename($companyLogo);
//         }
//     }
// }


?>

<style>
.insurance-company-2 {
    width: 485px;
    max-width: 100%;
    margin: 0 auto;
    font-family: Arial, Helvetica, sans-serif;
    color: #111;
}

.insurance-company-2-card {
    width: 485px;
    max-width: 100%;
    min-height: 344px;
    box-sizing: border-box;
    background: #fff;
    border: 1px solid #cfcfcf;
    border-radius: 13px;
    overflow: hidden;
    box-shadow: 0 2px 7px rgba(0,0,0,.22);
}

.insurance-company-2-header {
    height: 58px;
    box-sizing: border-box;
    background: #c9142b;
    color: #fff;
    display: flex;
    align-items: center;
    padding: 0 12px;
}

.insurance-company-2-logo {
    width: 52px;
    height: 42px;
    object-fit: contain;
    margin-right: 8px;
    flex: 0 0 52px;
}

.insurance-company-2-logo-placeholder {
    width: 52px;
    height: 42px;
    margin-right: 8px;
    flex: 0 0 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-size: 8px;
    font-weight: 700;
    border-radius: 50%;
    border: 2px solid #fff;
    box-sizing: border-box;
}

.insurance-company-2-header-text {
    flex: 1;
    min-width: 0;
    text-align: center;
}

.insurance-company-2-title {
    font-size: 19px;
    line-height: 20px;
    font-weight: 400;
    white-space: nowrap;
}

.insurance-company-2-subtitle {
    margin-top: 2px;
    font-size: 12px;
    line-height: 13px;
    white-space: nowrap;
}

.insurance-company-2-body {
    padding: 12px 20px 10px;
    box-sizing: border-box;
}

.insurance-company-2-info {
    width: 100%;
}

.insurance-company-2-row {
    display: grid;
    grid-template-columns: 112px 1fr 88px 1fr;
    min-height: 19px;
    align-items: center;
    font-size: 13px;
    line-height: 16px;
}

.insurance-company-2-row.single {
    grid-template-columns: 112px 1fr;
}

.insurance-company-2-label {
    font-weight: 400;
    white-space: nowrap;
}

.insurance-company-2-value {
    min-width: 0;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.insurance-company-2-value.right {
    text-align: right;
}
.insurance-company-2-value.right.vin {
    font-size: 11px;
}

.insurance-company-2-secondary {
    display: block;
    margin-left: 112px;
    margin-top: -1px;
    font-size: 13px;
    line-height: 15px;
    font-weight: 700;
}

.insurance-company-2-coverage {
    margin-top: 17px;
    font-size: 13px;
    line-height: 16px;
}

.insurance-company-2-coverage div {
    margin: 0;
    padding: 0;
}

.insurance-company-2-barcode {
    margin-top: 10px;
    text-align: center;
}

.insurance-company-2-barcode img {
    width: 300px;
    max-width: 100%;
    height: 43px;
    object-fit: contain;
}

.insurance-company-2-disclaimer {
    margin-top: 8px;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    line-height: 14px;
    color: #111;
}

@media print {
    .insurance-company-2 {
        width: 485px;
    }

    .insurance-company-2-card {
        box-shadow: none;
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>

<div class="insurance-company-2">

    <div class="insurance-company-2-card">

        <div class="insurance-company-2-header">

           <?php if ($companyLogo !== ''): ?>
                <img class="insurance-company-2-logo" src="<?= $esc($companyLogo) ?>" alt="<?= $esc($companyName) ?> logo">
            <?php else: ?>
                <div class="insurance-company-2-logo-placeholder">
                    INSURANCE
                </div>
            <?php endif; ?>

            <div class="insurance-company-2-header-text">
                <div class="insurance-company-2-title">
                    <?= $esc($companyName) ?>
                </div>

                <div class="insurance-company-2-subtitle">
                    <?= $esc($companySubtitle) ?>
                </div>
            </div>

        </div>

        <div class="insurance-company-2-body">

            <div class="insurance-company-2-info">

                <div class="insurance-company-2-row single">
                    <div class="insurance-company-2-label">INSURED</div>
                    <div class="insurance-company-2-value right">
                        <?= $esc($insuredName) ?>
                    </div>
                </div>

                <?php if ($secondaryInsured !== ''): ?>
                    <div class="insurance-company-2-secondary">
                        <?= $esc($secondaryInsured) ?>
                    </div>
                <?php endif; ?>

                <div class="insurance-company-2-row single">
                    <div class="insurance-company-2-label">POLICY NUMBER</div>
                    <div class="insurance-company-2-value right">
                        <?= $esc($policyNumber) ?>
                    </div>
                </div>

                <div class="insurance-company-2-row single">
                    <div class="insurance-company-2-label">EFFECTIVE</div>
                    <div class="insurance-company-2-value right">
                        <?= $esc($effectiveDate) ?> TO <?= $esc($expirationDate) ?>
                    </div>
                </div>

                <div style="height:8px"></div>

                <div class="insurance-company-2-row">
                    <div class="insurance-company-2-label">MAKE</div>
                    <div class="insurance-company-2-value">
                        <?= $esc($vehicleMake) ?>
                    </div>

                    <div class="insurance-company-2-label">MODEL</div>
                    <div class="insurance-company-2-value right">
                        <?= $esc($vehicleModel) ?>
                    </div>
                </div>

                <div class="insurance-company-2-row">
                    <div class="insurance-company-2-label">YR</div>
                    <div class="insurance-company-2-value">
                        <?= $esc($vehicleYear) ?>
                    </div>

                    <div class="insurance-company-2-label">VIN</div>
                    <div class="insurance-company-2-value right vin">
                        <?= $esc($vin) ?>
                    </div>
                </div>

                <div class="insurance-company-2-row">
                    <div class="insurance-company-2-label">AGENT</div>
                    <div class="insurance-company-2-value">
                        <?= $esc($agentName) ?>
                    </div>

                    <div class="insurance-company-2-label">PHONE</div>
                    <div class="insurance-company-2-value right">
                        <?= $esc($agentPhone) ?>
                    </div>
                </div>

            </div>

            <div class="insurance-company-2-coverage">
                <div>Bodily Injury &amp; Property Damage</div>
                <div>Medical Payments</div>
                <div>Comprehensive Coverage</div>
                <div>Collision Coverage</div>
                <div>Emergency Road Service</div>
                <div>Uninsured/Underinsured</div>
            </div>

            <?php if ($barcode !== ''): ?>
                <div class="insurance-company-2-barcode">
                    <img
                        src="<?= $esc($barcode) ?>"
                        alt="Policy barcode"
                    >
                </div>
            <?php endif; ?>

        </div>

    </div>

    <div class="insurance-company-2-disclaimer">
        This card may or may not be accepted by law enforcement officials as an Insurance ID card in your state.
    </div>

</div>
