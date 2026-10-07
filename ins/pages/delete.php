<?php

declare(strict_types=1);

$id = getId();

if (!$id) {
    redirect('index.php?p=dashboard');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php?p=view&id=' . $id);
}

$stmt = $pdo->prepare("
    SELECT id
    FROM insurance_cards
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

if (!$stmt->fetch()) {
    redirect('index.php?p=dashboard');
}

try {

    $stmt = $pdo->prepare("
        DELETE FROM insurance_cards
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    redirect('index.php?p=dashboard&deleted=1');

} catch (PDOException $e) {

    http_response_code(500);
    exit('Unable to delete the insurance card.');

}
