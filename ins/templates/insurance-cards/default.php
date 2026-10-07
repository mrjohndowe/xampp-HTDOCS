<?php

declare(strict_types=1);

$companyAddress = trim(implode(', ', array_filter([
    $card['company_address'] ?? '',
    $card['company_city'] ?? '',
    $card['company_state'] ?? '',
    formatZip($card['company_zip'] ?? '')
])));

$vehicle = trim(implode(' ', array_filter([
    $card['vehicle_year'] ?? '',
    $card['vehicle_make'] ?? '',
    $card['vehicle_model'] ?? ''
])));

?>
<style>
    .insurance-template {
        width: 8.5in;
        max-width: 100%;
        min-height: 5.45in;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #8b95a3;
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
        overflow: hidden;
    }

    .insurance-template-header {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 25px;
        padding: 18px 22px 14px;
        border-bottom: 3px solid #111827;
    }

    .insurance-template-logo {
        display: block;
        max-width: 135px;
        max-height: 48px;
        margin-bottom: 5px;
        object-fit: contain;
        object-position: left center;
    }

    .insurance-template-company {
        font-size: 19px;
        font-weight: 800;
        line-height: 1.1;
    }

    .insurance-template-company-details {
        margin-top: 5px;
        color: #374151;
        font-size: 9px;
        line-height: 1.45;
    }

    .insurance-template-title {
        text-align: right;
        font-size: 17px;
        font-weight: 900;
        line-height: 1.05;
        white-space: nowrap;
    }

    .insurance-template-subtitle {
        margin-top: 7px;
        color: #4b5563;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .04em;
        text-align: right;
    }

    .insurance-template-body {
        padding: 15px 22px 12px;
    }

    .insurance-template-section {
        margin-bottom: 14px;
    }

    .insurance-template-heading {
        margin: 0 0 7px;
        padding-bottom: 4px;
        border-bottom: 1px solid #9ca3af;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .08em;
    }

    .insurance-template-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .insurance-template-full {
        grid-column: 1 / -1;
    }

    .insurance-template-field-label {
        margin-bottom: 2px;
        color: #4b5563;
        font-size: 7px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .insurance-template-field-value {
        min-height: 15px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .insurance-template-policy-number {
        font-size: 13px;
        font-weight: 900;
    }

    .insurance-template-vehicle {
        grid-template-columns: .7fr 1fr 1fr 1fr;
    }

    .insurance-template-coverage {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .insurance-template-coverage-box {
        padding: 7px 9px;
        border: 1px solid #aeb6c1;
        background: #f7f8fa;
    }

    .insurance-template-coverage-box .insurance-template-field-value {
        font-size: 10px;
    }

    .insurance-template-footer {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 20px;
        padding: 10px 22px;
        border-top: 2px solid #111827;
        color: #374151;
        font-size: 7px;
        line-height: 1.45;
    }

    .insurance-template-footer-right {
        text-align: right;
        white-space: nowrap;
    }

    @media (max-width: 800px) {
        .insurance-template {
            width: 100%;
        }

        .insurance-template-header {
            grid-template-columns: 1fr;
        }

        .insurance-template-title,
        .insurance-template-subtitle {
            text-align: left;
        }

        .insurance-template-grid,
        .insurance-template-vehicle,
        .insurance-template-coverage {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media print {
        .insurance-template {
            width: 8.5in;
            min-height: 5.45in;
            max-width: none;
            margin: 0;
            border: 1px solid #8b95a3;
        }
    }
</style>

<article class="insurance-template">

    <header class="insurance-template-header">

        <div>

            <?php if (!empty($card['company_logo'])): ?>

                <img
                    src="<?= e($card['company_logo']) ?>"
                    alt="<?= e($card['company_name']) ?>"
                    class="insurance-template-logo"
                >

            <?php endif; ?>

            <div class="insurance-template-company">
                <?= e($card['company_name']) ?>
            </div>

            <div class="insurance-template-company-details">

                <?php if ($companyAddress !== ''): ?>
                    <?= e($companyAddress) ?><br>
                <?php endif; ?>

                <?php if (!empty($card['company_phone'])): ?>
                    <?= e($card['company_phone']) ?>
                <?php endif; ?>

            </div>

        </div>

        <div>

            <div class="insurance-template-title">
                INSURANCE<br>
                IDENTIFICATION CARD
            </div>

            <div class="insurance-template-subtitle">
                AUTOMOBILE LIABILITY INSURANCE
            </div>

        </div>

    </header>

    <div class="insurance-template-body">

        <section class="insurance-template-section">

            <h2 class="insurance-template-heading">
                POLICY INFORMATION
            </h2>

            <div class="insurance-template-grid">

                <div class="insurance-template-full">

                    <div class="insurance-template-field-label">
                        Named Insured
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e($card['insured_name']) ?>
                    </div>

                </div>

                <div>

                    <div class="insurance-template-field-label">
                        Policy Number
                    </div>

                    <div class="insurance-template-field-value insurance-template-policy-number">
                        <?= e($card['policy_number']) ?>
                    </div>

                </div>

                <div>

                    <div class="insurance-template-field-label">
                        Effective Date
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e(formatDate($card['effective_date'])) ?>
                    </div>

                </div>

                <div>

                    <div class="insurance-template-field-label">
                        Expiration Date
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e(formatDate($card['expiration_date'])) ?>
                    </div>

                </div>

            </div>

        </section>

        <section class="insurance-template-section">

            <h2 class="insurance-template-heading">
                VEHICLE INFORMATION
            </h2>

            <div class="insurance-template-grid insurance-template-vehicle">

                <div>

                    <div class="insurance-template-field-label">
                        Year
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e($card['vehicle_year']) ?>
                    </div>

                </div>

                <div>

                    <div class="insurance-template-field-label">
                        Make
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e($card['vehicle_make']) ?>
                    </div>

                </div>

                <div>

                    <div class="insurance-template-field-label">
                        Model
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e($card['vehicle_model']) ?>
                    </div>

                </div>

                <div>

                    <div class="insurance-template-field-label">
                        License Plate
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e($card['license_plate']) ?>
                    </div>

                </div>

                <div class="insurance-template-full">

                    <div class="insurance-template-field-label">
                        Vehicle Identification Number
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e($card['vin']) ?>
                    </div>

                </div>

            </div>

        </section>

        <section class="insurance-template-section">

            <h2 class="insurance-template-heading">
                LIABILITY COVERAGE
            </h2>

            <div class="insurance-template-grid insurance-template-coverage">

                <div class="insurance-template-coverage-box">

                    <div class="insurance-template-field-label">
                        Bodily Injury Liability
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e($card['liability_bod']) ?>
                    </div>

                </div>

                <div class="insurance-template-coverage-box">

                    <div class="insurance-template-field-label">
                        Property Damage Liability
                    </div>

                    <div class="insurance-template-field-value">
                        <?= e($card['property_damage']) ?>
                    </div>

                </div>

                <div class="insurance-template-coverage-box">

                    <div class="insurance-template-field-label">
                        Policy Status
                    </div>

                    <div class="insurance-template-field-value">
                        ACTIVE
                    </div>

                </div>

            </div>

        </section>

    </div>

    <footer class="insurance-template-footer">

        <div>
            This identification card is evidence of insurance coverage
            for the vehicle identified above. Keep this card in the vehicle
            and present it when required by law.
        </div>

        <div class="insurance-template-footer-right">
            POLICY<br>
            <?= e($card['policy_number']) ?>
        </div>

    </footer>

</article>
