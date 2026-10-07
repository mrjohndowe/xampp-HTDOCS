<?php

declare(strict_types=1);

$companyCount = (int)$pdo->query("
    SELECT COUNT(*)
    FROM insurance_companies
")->fetchColumn();

$cardCount = (int)$pdo->query("
    SELECT COUNT(*)
    FROM insurance_cards
")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            <h1>Admin Dashboard</h1>
            <p>Manage insurance card settings and companies.</p>
        </div>
    </div>

    <div class="insurance-grid">

        <section class="form-card">

            <div class="form-card-header">
                <h2>Insurance Companies</h2>
            </div>

            <div style="padding:24px;">

                <div style="font-size:32px;font-weight:800;color:var(--navy);">
                    <?= $companyCount ?>
                </div>

                <div style="margin-top:5px;color:var(--muted);font-size:13px;">
                    Companies configured
                </div>

                <div style="margin-top:20px;">
                    <a href="index.php?p=companies" class="btn btn-primary">
                        Manage Companies
                    </a>
                </div>

            </div>

        </section>

        <section class="form-card">

            <div class="form-card-header">
                <h2>Insurance Cards</h2>
            </div>

            <div style="padding:24px;">

                <div style="font-size:32px;font-weight:800;color:var(--navy);">
                    <?= $cardCount ?>
                </div>

                <div style="margin-top:5px;color:var(--muted);font-size:13px;">
                    Cards created
                </div>

                <div style="margin-top:20px;">
                    <a href="index.php?p=create" class="btn btn-primary">
                        Create Card
                    </a>
                </div>

            </div>

        </section>

    </div>

</main>

</body>
</html>
