<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$makeId = (int)($_GET['make_id'] ?? 0);

if ($makeId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        id,
        name
    FROM vehicle_models
    WHERE make_id = :make_id
      AND active = 1
    ORDER BY name ASC
");

$stmt->execute([
    ':make_id' => $makeId
]);

echo json_encode(
    $stmt->fetchAll(),
    JSON_UNESCAPED_UNICODE
);
