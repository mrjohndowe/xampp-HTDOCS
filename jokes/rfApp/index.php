<?php
declare(strict_types=1);

function rfJsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$action = isset($_GET['action']) ? (string) $_GET['action'] : null;
$databaseFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'real_friend_applications.sqlite';

try {
    $databaseDirectory = dirname($databaseFile);
    if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0755, true) && !is_dir($databaseDirectory)) {
        throw new RuntimeException('Could not create the data directory.');
    }

    $db = new PDO('sqlite:' . $databaseFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec("
        CREATE TABLE IF NOT EXISTS real_friend_applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            application_date TEXT NOT NULL,
            form_data TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
    ");

    if ($action !== null) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'history') {
            $stmt = $db->query("
                SELECT id, full_name, application_date, updated_at
                FROM real_friend_applications
                ORDER BY application_date DESC, updated_at DESC, full_name COLLATE NOCASE
            ");
            rfJsonResponse(['success' => true, 'applications' => $stmt->fetchAll()]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'load') {
            $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                rfJsonResponse(['success' => false, 'message' => 'Choose a valid saved application.'], 400);
            }

            $stmt = $db->prepare('SELECT id, full_name, application_date, form_data FROM real_friend_applications WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $application = $stmt->fetch();
            if (!$application) {
                rfJsonResponse(['success' => false, 'message' => 'That saved application was not found.'], 404);
            }

            $application['form_data'] = json_decode($application['form_data'], true, 512, JSON_THROW_ON_ERROR);
            rfJsonResponse(['success' => true, 'application' => $application]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
            $payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload) || !is_array($payload['form_data'] ?? null)) {
                rfJsonResponse(['success' => false, 'message' => 'The submitted application data is invalid.'], 400);
            }

            $formData = $payload['form_data'];
            $fullName = trim((string) ($formData['full_name'] ?? ''));
            if ($fullName === '') {
                rfJsonResponse(['success' => false, 'message' => 'Enter a full name before autosaving.'], 422);
            }

            $applicationDate = trim((string) ($formData['application_date'] ?? ''));
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $applicationDate);
            if (!$date || $date->format('Y-m-d') !== $applicationDate) {
                rfJsonResponse(['success' => false, 'message' => 'Enter a valid application date before autosaving.'], 422);
            }

            $photoData = $formData['photo_data'] ?? '';
            if ($photoData !== '' && (
                !is_string($photoData)
                || !preg_match('/^data:image\/(?:jpeg|png|webp);base64,([A-Za-z0-9+\/=]+)$/', $photoData, $matches)
                || strlen($matches[1]) > 2_800_000
                || base64_decode($matches[1], true) === false
            )) {
                rfJsonResponse(['success' => false, 'message' => 'Choose a valid photo smaller than 2 MB.'], 422);
            }

            $formJson = json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $now = date('Y-m-d H:i:s');
            $id = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT);

            if ($id && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE real_friend_applications
                    SET full_name = :full_name, application_date = :application_date,
                        form_data = :form_data, updated_at = :updated_at
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':full_name' => $fullName,
                    ':application_date' => $applicationDate,
                    ':form_data' => $formJson,
                    ':updated_at' => $now,
                    ':id' => $id,
                ]);

                if ($stmt->rowCount() === 0) {
                    $check = $db->prepare('SELECT 1 FROM real_friend_applications WHERE id = :id');
                    $check->execute([':id' => $id]);
                    if (!$check->fetchColumn()) {
                        rfJsonResponse(['success' => false, 'message' => 'That saved application no longer exists. Reload history and try again.'], 404);
                    }
                }
            } else {
                $stmt = $db->prepare("
                    INSERT INTO real_friend_applications (full_name, application_date, form_data, created_at, updated_at)
                    VALUES (:full_name, :application_date, :form_data, :created_at, :updated_at)
                ");
                $stmt->execute([
                    ':full_name' => $fullName,
                    ':application_date' => $applicationDate,
                    ':form_data' => $formJson,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
                $id = (int) $db->lastInsertId();
            }

            rfJsonResponse([
                'success' => true,
                'id' => $id,
                'full_name' => $fullName,
                'application_date' => $applicationDate,
                'updated_at' => $now,
            ]);
        }

        rfJsonResponse(['success' => false, 'message' => 'The requested operation is not supported.'], 405);
    }
} catch (JsonException $exception) {
    error_log('Real Friend application JSON error: ' . $exception->getMessage());
    if ($action !== null) {
        rfJsonResponse(['success' => false, 'message' => 'The application data could not be processed.'], 400);
    }
    http_response_code(500);
    exit('The application could not be loaded.');
} catch (Throwable $exception) {
    error_log('Real Friend application error: ' . $exception->getMessage());
    if ($action !== null) {
        rfJsonResponse(['success' => false, 'message' => 'The application could not be processed. Check the server database configuration.'], 500);
    }
    http_response_code(500);
    exit('The application could not be loaded.');
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app-base.php'; ?>
    <title>Real Friend Application</title>
    <link rel="stylesheet" href="css/real-friend.css?v=<?= filemtime(__DIR__ . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'real-friend.css') ?>">
    <script src="js/real-friend.js?v=<?= filemtime(__DIR__ . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'real-friend.js') ?>" defer></script>
</head>
<body>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'navbar.php'; ?>
<main class="app">
    <div class="toolbar no-print">
        <label for="historySelect">Saved applications (name — date)</label>
        <select id="historySelect"><option value="">Choose an application to reload or edit</option></select>
        <button type="button" id="newApplication">New application</button>
        <button type="button" id="printApplication">Print</button>
        <span id="saveStatus" role="status" aria-live="polite">Enter a full name to begin autosaving.</span>
    </div>

    <form id="friendForm" class="paper" autocomplete="off">
        <input type="hidden" name="record_id" id="recordId">
        <header class="heading">
            <h1>REAL FRIEND APPLICATION</h1>
            <p>Please answer all questions honestly and truthfully.</p>
        </header>

        <div class="form-table">
            <section class="row identity-row">
                <div class="cell identity-fields">
                    <label class="full-name">Full Name:<input name="full_name" required></label>
                    <label>Age:<input type="number" min="0" max="130" name="age"></label>
                    <label>Gender:<input name="gender"></label>
                    <label>Zodiac Sign:<input name="zodiac"></label>
                    <label class="nickname">Nickname (optional):<input name="nickname"></label>
                    <label>Smokes: Yes/No
                        <select name="smokes"><option value=""></option><option>Yes</option><option>No</option></select>
                    </label>
                    <label>Drinks: Yes/No
                        <select name="drinks"><option value=""></option><option>Yes</option><option>No</option></select>
                    </label>
                    <label>Favorite Color:<input name="favorite_color"></label>
                    <label>Racist or Homophobic: Yes/No
                        <select name="racist_homophobic"><option value=""></option><option>Yes</option><option>No</option></select>
                    </label>
                    <label class="orientation">Sexual Orientation:<input name="sexual_orientation"></label>
                    <label class="qualities">Qualities:<textarea name="qualities" rows="4"></textarea></label>
                </div>
                <label class="photo-cell" for="photoInput">
                    <img id="photoPreview" alt="" hidden>
                    <span id="photoPlaceholder">Your<br>photo</span>
                    <input class="photo-input" type="file" id="photoInput" accept="image/jpeg,image/png,image/webp">
                </label>
            </section>

            <section class="row split">
                <label>Are you a good listener: Yes/No
                    <select name="good_listener"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
                <label>Do you practice sports: Yes/No
                    <select name="practice_sports"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
            </section>
            <section class="row">
                <label class="wide">If yes, which sports:<input name="sports"></label>
            </section>

            <section class="row three">
                <label>Favorite Genre of Music:<textarea name="music_genre" rows="3"></textarea></label>
                <label>Favorite Band:<textarea name="favorite_band" rows="3"></textarea></label>
                <label>Solo Artist:<textarea name="solo_artist" rows="3"></textarea></label>
            </section>
            <section class="row note">Note: If you like Justin Bieber this application loses its effect and you can forget it.</section>
            <section class="row split">
                <label>Do you like Kpop: Yes/No
                    <select name="likes_kpop"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
                <label>Do you like jpop/jrock: Yes/No
                    <select name="likes_jpop_jrock"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
            </section>
            <section class="row split tall">
                <label>If yes, which is your favorite band:<textarea name="kpop_band" rows="3"></textarea></label>
                <label>If yes, which is your favorite band:<textarea name="jpop_band" rows="3"></textarea></label>
            </section>
            <section class="row split">
                <label>Do you like anime: Yes/No
                    <select name="likes_anime"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
                <label>Do you cosplay: Yes/No
                    <select name="cosplay"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
            </section>
            <section class="row">
                <label class="wide">Top 5 Favorite Anime:<textarea name="favorite_anime" rows="3"></textarea></label>
            </section>
            <section class="row three compact">
                <label>Sushi or ramen:<input name="sushi_or_ramen"></label>
                <label>Coke or Pepsi:<input name="coke_or_pepsi"></label>
                <label>Coffee or Tea:<input name="coffee_or_tea"></label>
            </section>
            <section class="row split tall">
                <label>Favorite Foods:<textarea name="favorite_foods" rows="3"></textarea></label>
                <label>Can you cook? If yes, what’s your specialty?<textarea name="cooking_specialty" rows="3"></textarea></label>
            </section>
            <section class="row split">
                <label>Any criminal history: Yes/No
                    <select name="criminal_history"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
                <label>If no, are you ready to make one: Yes/No
                    <select name="ready_to_make_one"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
            </section>
            <section class="row explanation">
                <label class="wide">Explain why you want to be my friend:<textarea name="friendship_reason" rows="3"></textarea></label>
            </section>
            <section class="row split">
                <label>Will you be there when I need: Yes/No
                    <select name="there_when_needed"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
                <label>Will you leave me later: Yes/No
                    <select name="leave_later"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
            </section>
            <section class="row split">
                <label>Do you like hugs: Yes/No
                    <select name="likes_hugs"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
                <label>Do you like cuddles: Yes/No
                    <select name="likes_cuddles"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
            </section>
            <section class="row">
                <label class="wide">Will you hang out with me or at least TRY to: Yes/No
                    <select name="hang_out"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
            </section>
            <section class="row">
                <label class="wide">When we hang out, will you ignore me to talk to other people and forget about me: Yes/No
                    <select name="ignore_me"><option value=""></option><option>Yes</option><option>No</option></select>
                </label>
            </section>
            <section class="row date-row">
                <label>Application date:<input type="date" name="application_date" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>" required></label>
            </section>
        </div>
    </form>
</main>
</body>
</html>
