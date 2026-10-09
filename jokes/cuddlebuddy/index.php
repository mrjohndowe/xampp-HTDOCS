<?php
declare(strict_types=1);

$databaseFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cuddlebuddy_applications.sqlite';
$databaseDirectory = dirname($databaseFile);

if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0755, true) && !is_dir($databaseDirectory)) {
    http_response_code(500);
    exit('Unable to access the application database directory.');
}

$db = new PDO('sqlite:' . $databaseFile, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$db->exec("
    CREATE TABLE IF NOT EXISTS cuddle_buddy_agreements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        application_date TEXT NOT NULL,
        form_data TEXT NOT NULL,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )
");

function cuddleJsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (isset($_GET['action'])) {
    try {
        $action = (string) $_GET['action'];

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'history') {
            $stmt = $db->query("
                SELECT id, full_name, application_date, updated_at
                FROM cuddle_buddy_agreements
                ORDER BY application_date DESC, updated_at DESC, full_name COLLATE NOCASE
            ");
            cuddleJsonResponse(['success' => true, 'applications' => $stmt->fetchAll()]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'load') {
            $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                cuddleJsonResponse(['success' => false, 'message' => 'Choose a valid saved application.'], 400);
            }

            $stmt = $db->prepare('SELECT id, full_name, application_date, form_data FROM cuddle_buddy_agreements WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $application = $stmt->fetch();

            if (!$application) {
                cuddleJsonResponse(['success' => false, 'message' => 'That saved application was not found.'], 404);
            }

            $application['form_data'] = json_decode($application['form_data'], true, 512, JSON_THROW_ON_ERROR);
            cuddleJsonResponse(['success' => true, 'application' => $application]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
            $payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload) || !is_array($payload['form_data'] ?? null)) {
                cuddleJsonResponse(['success' => false, 'message' => 'The submitted application data is invalid.'], 400);
            }

            $formData = $payload['form_data'];
            $first = trim((string) ($formData['first_name'] ?? ''));
            $middle = trim((string) ($formData['middle_name'] ?? ''));
            $last = trim((string) ($formData['last_name'] ?? ''));
            $fullName = trim(implode(' ', array_filter([$first, $middle, $last], static fn(string $part): bool => $part !== '')));

            if ($fullName === '') {
                cuddleJsonResponse(['success' => false, 'message' => 'Enter a name before autosaving.'], 422);
            }

            $applicationDate = trim((string) ($formData['application_date'] ?? ''));
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $applicationDate);
            if (!$date || $date->format('Y-m-d') !== $applicationDate) {
                cuddleJsonResponse(['success' => false, 'message' => 'Enter a valid application date before autosaving.'], 422);
            }

            $formJson = json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $now = date('Y-m-d H:i:s');
            $id = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT);

            if ($id && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE cuddle_buddy_agreements
                    SET full_name = :full_name,
                        application_date = :application_date,
                        form_data = :form_data,
                        updated_at = :updated_at
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
                    $check = $db->prepare('SELECT 1 FROM cuddle_buddy_agreements WHERE id = :id');
                    $check->execute([':id' => $id]);
                    if (!$check->fetchColumn()) {
                        cuddleJsonResponse(['success' => false, 'message' => 'That saved application no longer exists. Reload the history list and try again.'], 404);
                    }
                }
            } else {
                $stmt = $db->prepare("
                    INSERT INTO cuddle_buddy_agreements (full_name, application_date, form_data, created_at, updated_at)
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

            cuddleJsonResponse([
                'success' => true,
                'id' => $id,
                'full_name' => $fullName,
                'application_date' => $applicationDate,
                'updated_at' => $now,
            ]);
        }

        cuddleJsonResponse(['success' => false, 'message' => 'The requested operation is not supported.'], 405);
    } catch (JsonException $exception) {
        error_log('CuddleBuddy JSON error: ' . $exception->getMessage());
        cuddleJsonResponse(['success' => false, 'message' => 'The application data could not be processed. Check the form values and try again.'], 400);
    } catch (PDOException $exception) {
        error_log('CuddleBuddy database error: ' . $exception->getMessage());
        cuddleJsonResponse(['success' => false, 'message' => 'The application could not be saved or retrieved. Check the server database configuration.'], 500);
    } catch (Throwable $exception) {
        error_log('CuddleBuddy request error: ' . $exception->getMessage());
        cuddleJsonResponse(['success' => false, 'message' => 'The application could not be processed. Please try again.'], 500);
    }
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app-base.php'; ?>
    <title>Cuddle Buddy Administration — Application for a Cuddy Buddy Agreement</title>
    <link rel="stylesheet" href="css/cuddlebuddy-form.css?v=<?= filemtime(__DIR__ . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'cuddlebuddy-form.css') ?>">
    <script src="js/cuddlebuddy-form.js?v=<?= filemtime(__DIR__ . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'cuddlebuddy-form.js') ?>" defer></script>
</head>
<body>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'navbar.php'; ?>
<main class="sheet-wrap">
    <div class="toolbar no-print">
        <label for="historySelect">Saved applications (name — date)</label>
        <select id="historySelect">
            <option value="">Choose an application to reload or edit</option>
        </select>
        <button type="button" id="newApplication">New application</button>
        <button type="button" id="printApplication">Print</button>
        <span id="saveStatus" role="status" aria-live="polite">Enter a name to begin autosaving.</span>
    </div>

    <form id="cuddleForm" class="sheet" autocomplete="off">
        <input type="hidden" name="record_id" id="recordId">

        <header class="form-header">
            <div>
                <h1>CUDDLE BUDDY ADMINISTRATION</h1>
                <h2>Application for a Cuddy Buddy Agreement</h2>
            </div>
            <div class="approval">Form Approved<br>OFS No. 0960-0066</div>
        </header>

        <section class="numbered-row name-row">
            <div class="number">1</div>
            <div class="row-content">
                <div class="subheading">NAME <span>TO BE SHOWN ON AGREEMENT</span></div>
                <div class="name-fields">
                    <label>First<input name="first_name" aria-label="First name"></label>
                    <label>Full Middle Name<input name="middle_name" aria-label="Full middle name"></label>
                    <label>Last<input name="last_name" aria-label="Last name"></label>
                </div>
                <label class="inline-line">OTHER NAMES <span>GONE BY</span><input name="other_names" aria-label="Other names gone by"></label>
                <label class="inline-line">PREFERRED PET NAMES<input name="pet_names" aria-label="Preferred pet names"></label>
            </div>
        </section>

        <section class="numbered-row reason-row">
            <div class="number">2</div>
            <div class="row-content">
                <label class="prompt">Reason(s) why you are submitting<br>this Cuddy Buddy Application<textarea name="reasons" rows="2" aria-label="Reasons for submitting this Cuddy Buddy application"></textarea></label>
            </div>
        </section>

        <section class="split-row">
            <div class="numbered-row place-row">
                <div class="number">3</div>
                <div class="row-content">
                    <div class="subheading">PLACE<br>OF BIRTH <span>Do Not Abbreviate</span></div>
                    <div class="place-fields">
                        <label>City<input name="birth_city"></label>
                        <label>State or Foreign Country<input name="birth_state_country"></label>
                    </div>
                </div>
            </div>
            <div class="fci-box"><span>Office Use<br>Only</span><input name="fci" aria-label="FCI office use only"></div>
            <div class="numbered-row age-row">
                <div class="number">4</div>
                <div class="age-label">AGE</div>
                <label class="age-input"><input type="number" name="age" min="0" max="130" aria-label="Age"><span>In Years</span></label>
            </div>
        </section>

        <section class="numbered-row allowance-row">
            <div class="number">5</div>
            <div class="row-content">
                <div class="subheading">Cuddle Allowances <span>(Check All That Apply)</span></div>
                <div class="choice-grid allowance-options">
                    <label><input type="checkbox" name="allowances[]" value="Falling Asleep"> Falling Asleep</label>
                    <label><input type="checkbox" name="allowances[]" value="Hand Holding"> Hand Holding</label>
                    <label><input type="checkbox" name="allowances[]" value="Kissing"> Kissing</label>
                    <label><input type="checkbox" name="allowances[]" value="Tickle or Pillow Fights"> Tickle or Pillow Fights</label>
                    <label><input type="checkbox" name="allowances[]" value="Playing With Your Hair"> Playing With Your Hair</label>
                </div>
                <div class="total-line">Allowances selected: <output id="allowanceTotal">0</output></div>
            </div>
        </section>

        <section class="numbered-row preferences-row">
            <div class="number">6</div>
            <div class="row-content spoon">
                <div class="subheading">SPOON</div>
                <div class="tiny-note">Are You Little or Big Spoon?<br>(Your Response is Mandatory)</div>
                <div class="choice-list">
                    <label><input type="radio" name="spoon" value="Little" required> Little</label>
                    <label><input type="radio" name="spoon" value="Big"> Big</label>
                </div>
            </div>
            <div class="row-content cuddle-type">
                <div class="subheading">CUDDLE TYPE</div>
                <div class="tiny-note">Select One or More<br>(Your Response is Voluntary)</div>
                <div class="choice-grid">
                    <label><input type="checkbox" name="cuddle_types[]" value="Cuddle Puddle"> Cuddle Puddle</label>
                    <label><input type="checkbox" name="cuddle_types[]" value="Flop"> Flop</label>
                    <label><input type="checkbox" name="cuddle_types[]" value="Sporking"> Sporking</label>
                    <label><input type="checkbox" name="cuddle_types[]" value="Death Grip"> Death Grip</label>
                    <label><input type="checkbox" name="cuddle_types[]" value="Chest Pillow"> Chest Pillow</label>
                    <label><input type="checkbox" name="cuddle_types[]" value="Lap Pillow"> Lap Pillow</label>
                    <label><input type="checkbox" name="cuddle_types[]" value="Octopi"> Octopi</label>
                </div>
                <div class="total-line">Cuddle types selected: <output id="cuddleTypeTotal">0</output></div>
            </div>
        </section>

        <section class="numbered-row sex-row">
            <div class="number">8</div>
            <div class="row-content">
                <div class="subheading sex-label">SEX</div>
                <div class="choice-list sex-options">
                    <label><input type="radio" name="sex" value="Male"> Male</label>
                    <label><input type="radio" name="sex" value="Female"> Female</label>
                </div>
            </div>
        </section>

        <section class="numbered-row">
            <div class="number">9</div>
            <div class="row-content preference-fields">
                <div class="section-heading">TELEVISION SHOW<br>PREFERENCES</div>
                <label>Favorite Shows<input name="tv_favorite"></label>
                <label>Second Tier<input name="tv_second_tier"></label>
                <label>HATED<input name="tv_hated"></label>
            </div>
        </section>

        <section class="numbered-row">
            <div class="number">10</div>
            <div class="row-content preference-fields">
                <div class="section-heading">FAVORITE<br>MOVIE GENRES</div>
                <label>Favorite<input name="movie_favorite"></label>
                <label>Also Good<input name="movie_also_good"></label>
                <label>Please No<input name="movie_please_no"></label>
            </div>
        </section>

        <section class="numbered-row clothing-row">
            <div class="number">11</div>
            <div class="row-content">
                <div class="prompt">Do you see clothing as mandatory or an evil fabric that reduces the surface area skin for cuddles?</div>
                <div class="choice-list">
                    <label><input type="checkbox" name="clothing[]" value="Mandatory"> Mandatory</label>
                    <label><input type="checkbox" name="clothing[]" value="Evil"> Evil</label>
                    <label><input type="checkbox" name="clothing[]" value="0/0"> 0/0</label>
                </div>
            </div>
        </section>

        <section class="numbered-row reference-row">
            <div class="number">12</div>
            <div class="row-content endorsement-row">
                <div>Three Character Reference endorsements<br><span>(Jerk-Buts will AUTOMATICALLY be rejected)</span></div>
                <label>First<input name="reference_first"></label>
                <label>Second<input name="reference_second"></label>
                <label>Third<input name="reference_third"></label>
            </div>
        </section>

        <section class="numbered-row days-row">
            <div class="number">13</div>
            <div class="row-content">
                <div class="prompt">Enter any preferred day(s) for cuddles to<br>ensure compatibility</div>
                <div class="choice-list day-options">
                    <label><input type="checkbox" name="days[]" value="Monday"> M</label>
                    <label><input type="checkbox" name="days[]" value="Tuesday"> T</label>
                    <label><input type="checkbox" name="days[]" value="Wednesday"> W</label>
                    <label><input type="checkbox" name="days[]" value="Thursday"> Th</label>
                    <label><input type="checkbox" name="days[]" value="Friday"> F</label>
                    <label><input type="checkbox" name="days[]" value="Saturday"> S</label>
                    <label><input type="checkbox" name="days[]" value="Sunday"> Sn</label>
                </div>
            </div>
        </section>

        <section class="split-row date-contact-row">
            <div class="numbered-row date-row">
                <div class="number">14</div>
                <label class="row-content date-label"><strong>TODAY'S<br>DATE</strong><input type="date" name="application_date" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>" required></label>
            </div>
            <div class="numbered-row contact-row">
                <div class="number">15</div>
                <div class="row-content">
                    <div class="subheading">CONTACT<br>INFORMATION</div>
                    <div class="contact-fields">
                        <label>Tumblr<input name="contact_tumblr"></label>
                        <label>Email<input type="email" name="contact_email"></label>
                    </div>
                </div>
            </div>
        </section>

        <section class="numbered-row misc-row">
            <div class="number">16</div>
            <label class="row-content">MISCELLANEOUS INFORMATION<textarea name="miscellaneous" rows="2"></textarea></label>
        </section>

        <section class="numbered-row declaration-row">
            <div class="number">17</div>
            <div class="row-content">
                <div class="declaration-text">I declare under penalty of being jerk-butt that I have examined all the information on this form, and on any accompanying statements or cuddles, and it is true and correct to the best of my knowledge.</div>
                <label class="signature-label">YOUR SIGNATURE<input name="signature"></label>
            </div>
            <div class="number">18</div>
            <div class="row-content initiated-by">
                <div>Which party or situations initiate the cuddle procedure?</div>
                <div class="choice-list">
                    <label><input type="checkbox" name="initiated_by[]" value="Recipient"> Recipient</label>
                    <label><input type="checkbox" name="initiated_by[]" value="Applicant"> Applicant</label>
                    <label><input type="checkbox" name="initiated_by[]" value="Stress"> Stress</label>
                    <label><input type="checkbox" name="initiated_by[]" value="Other"> Other</label>
                    <label>Specify <input class="specify-input" name="initiated_by_other"></label>
                </div>
            </div>
        </section>

        <section class="official-use">
            <div class="official-title">DO NOT WRITE BELOW THIS LINE (FOR NCA USE ONLY)</div>
            <div class="official-codes">
                <span>CBS</span><span>PCP</span><span>IAL</span><span>NRH</span><span>CAP</span>
                <span>CTV</span><span>FTS</span><span>FMG</span><span>FWB</span><span>PRA</span><span>ILI</span><span>WRI</span><span>PCAC</span>
            </div>
            <div class="official-bottom">
                <div>EVIDENCE SUBMITTED<textarea name="evidence_submitted" aria-label="Evidence submitted"></textarea></div>
                <div>
                    <div>SIGNATURE AND TITLE OF EMPLOYEE(S) REVIEWING<br>EVIDENCE AND/OR CONDUCTING CUDDLES</div>
                    <input name="reviewer_signature">
                    <div class="official-date">DATE <input type="date" name="reviewer_date"></div>
                    <div>DCL <input name="dcl"></div>
                    <div>DATE <input type="date" name="dcl_date"></div>
                </div>
            </div>
        </section>
        <footer class="form-footer"><span>Form CB-5 (08-2011) ef (08-2011)</span><span>Destroy Prior Editions</span><span>Page 3</span></footer>
    </form>
</main>
</body>
</html>
