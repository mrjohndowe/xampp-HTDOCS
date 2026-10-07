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
        companies.logo AS company_logo,
        companies.card_template
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

$template = trim((string)($card['card_template'] ?? 'default'));

$templateFile = __DIR__ . '/../templates/insurance-cards/' . $template . '.php';

if (!is_file($templateFile)) {
    $templateFile = __DIR__ . '/../templates/insurance-cards/default.php';
}

require $templateFile;
