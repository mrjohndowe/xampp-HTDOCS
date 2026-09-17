<?php
declare(strict_types = 1);

require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');

function tagError(string $message, int $status = 422): never
{
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    tagError('POST is required.', 405);
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$categories = array_filter(array_map('strval', (array) ($payload['categories'] ?? [])));
$productions = array_filter(array_map('strval', (array) ($payload['productions'] ?? [])));

if (empty($categories) && empty($productions)) {
    echo json_encode(['success' => true, 'added' => ['categories' => [], 'productions' => []]]);
    exit;
}

$pdo = db();
$added = ['categories' => [], 'productions' => []];

try {
    $pdo->beginTransaction();
    
    // Add new categories
    foreach ($categories as $category) {
        $categoryId = ensureCategoryExists($pdo, $category);
        if ($categoryId > 0) {
            $added['categories'][] = ['name' => $category, 'id' => $categoryId];
        }
    }
    
    // Add new productions
    foreach ($productions as $production) {
        $productionId = ensureProductionExists($pdo, $production);
        if ($productionId > 0) {
            $added['productions'][] = ['name' => $production, 'id' => $productionId];
        }
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'added' => $added], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    tagError('Failed to add tags: ' . $error->getMessage(), 500);
}
