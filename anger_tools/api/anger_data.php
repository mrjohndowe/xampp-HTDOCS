<?php
require_once '../extra/liteConnect.php';
require_once '../extra/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userID = getUserID($_SESSION['user']);
if (!$userID) {
    echo json_encode(['error' => 'Invalid user']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_anger_stats') {
    try {
        // Get anger count for current month
        $month = date('m');
        $year = date('Y');

        $q = 'SELECT count(id) as angerCount FROM anger WHERE month = :month AND year = :year AND username = :name';
        $p = [':month' => $month, ':year' => $year, ':name' => $userID];
        $stmt = $db->prepare($q);
        $stmt->execute($p);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['angerCount'];

        // Get average anger level for current month (SQLite compatible)
        $q = 'SELECT AVG(CASE angerScale
            WHEN "option1" THEN 1
            WHEN "option2" THEN 2
            WHEN "option3" THEN 3
            WHEN "option4" THEN 4
            WHEN "option5" THEN 5
            WHEN "option6" THEN 6
            WHEN "option7" THEN 7
            WHEN "option8" THEN 8
            WHEN "option9" THEN 9
            WHEN "option10" THEN 10
            END) as avgLevel FROM anger WHERE month = :month AND year = :year AND username = :name';
        $p = [':month' => $month, ':year' => $year, ':name' => $userID];
        $stmt = $db->prepare($q);
        $stmt->execute($p);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $avgLevel = $result['avgLevel'] ? round($result['avgLevel'], 1) : 0;

        echo json_encode([
            'count' => $count,
            'avgLevel' => $avgLevel ?: 0,
            'month' => date('F'),
            'year' => $year
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_anger_trend') {
    try {
        // Get daily anger counts for the last 30 days (SQLite compatible)
        $q = 'SELECT strftime("%d", timestamp) as day, COUNT(id) as count,
            AVG(CASE angerScale
                WHEN "option1" THEN 1
                WHEN "option2" THEN 2
                WHEN "option3" THEN 3
                WHEN "option4" THEN 4
                WHEN "option5" THEN 5
                WHEN "option6" THEN 6
                WHEN "option7" THEN 7
                WHEN "option8" THEN 8
                WHEN "option9" THEN 9
                WHEN "option10" THEN 10
            END) as avgLevel
            FROM anger
            WHERE username = :name
            AND date(timestamp) >= date("now", "-30 days")
            GROUP BY strftime("%d", timestamp)
            ORDER BY day ASC';
        $p = [':name' => $userID];
        $stmt = $db->prepare($q);
        $stmt->execute($p);
        $trendData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($trendData);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_anger_distribution') {
    try {
        // Get distribution of anger levels for current month
        $q = 'SELECT angerScale, COUNT(id) as count
            FROM anger
            WHERE username = :name
            AND month = :month AND year = :year
            GROUP BY angerScale
            ORDER BY angerScale';
        $p = [':name' => $userID, ':month' => date('m'), ':year' => date('Y')];
        $stmt = $db->prepare($q);
        $stmt->execute($p);
        $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Initialize all levels with 0 count
        $allLevels = [];
        for ($i = 1; $i <= 10; $i++) {
            $allLevels['option' . $i] = 0;
        }

        // Fill in actual counts
        foreach ($distribution as $item) {
            $allLevels[$item['angerScale']] = $item['count'];
        }

        echo json_encode($allLevels);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_log_details') {
    $logID = intval($_GET['id']);
    $q = 'SELECT * FROM anger WHERE id = :id';
    $p = [':id' => $logID];
    $stmt = $db->prepare($q);
    $stmt->execute($p);
    $anger = $stmt->fetch(PDO::FETCH_ASSOC);

    $public = $anger['public'] == 1 ? true : false;
    if (!$public && $userID !== $anger['username']) {
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    if (!$public && $userID === $anger['username']) {
        if (angerCode($anger['id']) === getCode($userID)) {
            $anger['madAbout'] = binary_decoded($anger['madAbout']);
            $anger['express'] = binary_decoded($anger['express']);
            $anger['thoughts'] = binary_decoded($anger['thoughts']);
            $anger['feelings'] = binary_decoded($anger['feelings']);
            $anger['act'] = binary_decoded($anger['act']);
            $anger['better'] = binary_decoded($anger['better']);
        }
    }

    $anger['userName'] = getName($anger['username']);
    $anger['angerLogID'] = date('ynjHis', strtotime($anger['timestamp']));
    $anger['angerDate'] = date('F jS, Y', strtotime($anger['timestamp']));
    $anger['angerTime'] = date('H:i:s', strtotime($anger['timestamp']));
    $anger['levelColor'] = getAngerLevel($anger['angerScale']);
    $anger['levelScale'] = getAngerLevelScale($anger['angerScale']);

    echo json_encode($anger);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
