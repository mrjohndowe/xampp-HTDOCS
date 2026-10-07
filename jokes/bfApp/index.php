<?php
declare(strict_types=1);
$submitted = false;

/*
|--------------------------------------------------------------------------
| Boyfriend Application - Autosave
|--------------------------------------------------------------------------
| Storage:
|   data/boyfriend_applications.sqlite
|
| Autosave requires:
|   Applicant Name
|   Age
|
| Records are identified by applicant name.
|--------------------------------------------------------------------------
*/
$dataDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$databaseFile  = $dataDirectory . DIRECTORY_SEPARATOR . 'boyfriend_applications.sqlite';

if (!is_dir($dataDirectory)) {
    mkdir($dataDirectory, 0755, true);
}

$db = new PDO('sqlite:' . $databaseFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$db->exec("
    CREATE TABLE IF NOT EXISTS boyfriend_form_applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        applicant_name TEXT NOT NULL,
        applicant_name_key TEXT NOT NULL UNIQUE,
        age TEXT NOT NULL,
        form_data TEXT NOT NULL,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )
");

/*
|--------------------------------------------------------------------------
| LOAD SAVED APPLICATION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['autosave'])) {

    header('Content-Type: application/json; charset=utf-8');

    $name = trim((string)($_GET['name'] ?? ''));

    if ($name === '') {
        echo json_encode([
            'success' => false,
            'exists' => false
        ]);
        exit;
    }

    $nameKey = mb_strtolower($name);

    $stmt = $db->prepare("
        SELECT form_data
        FROM boyfriend_form_applications
        WHERE applicant_name_key = :name_key
        LIMIT 1
    ");

    $stmt->execute([
        ':name_key' => $nameKey
    ]);

    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'application' => null
        ]);
        exit;
    }

    $application = json_decode($row['form_data'], true);

    echo json_encode([
        'success' => true,
        'exists' => true,
        'application' => $application
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| SAVE APPLICATION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['autosave'])) {

    header('Content-Type: application/json; charset=utf-8');

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!is_array($payload)) {
        echo json_encode([
            'success' => false,
            'saved' => false,
            'message' => 'Invalid request.'
        ]);
        exit;
    }

    $name = trim((string)($payload['applicant_name'] ?? ''));
    $age  = trim((string)($payload['age'] ?? ''));

    /*
     * ABSOLUTELY NO SAVE WITHOUT BOTH.
     */
    if ($name === '' || $age === '') {
        echo json_encode([
            'success' => false,
            'saved' => false,
            'message' => 'Enter your name and age to enable autosave.'
        ]);
        exit;
    }

    $nameKey = mb_strtolower($name);

    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        echo json_encode([
            'success' => false,
            'saved' => false,
            'message' => 'Unable to save application.'
        ]);
        exit;
    }

    $now = date('Y-m-d H:i:s');

    $stmt = $db->prepare("
        INSERT INTO boyfriend_form_applications (
            applicant_name,
            applicant_name_key,
            age,
            form_data,
            created_at,
            updated_at
        )
        VALUES (
            :applicant_name,
            :applicant_name_key,
            :age,
            :form_data,
            :created_at,
            :updated_at
        )
        ON CONFLICT(applicant_name_key)
        DO UPDATE SET
            applicant_name = excluded.applicant_name,
            age = excluded.age,
            form_data = excluded.form_data,
            updated_at = excluded.updated_at
    ");

    $stmt->execute([
        ':applicant_name'     => $name,
        ':applicant_name_key' => $nameKey,
        ':age'                => $age,
        ':form_data'          => $json,
        ':created_at'         => $now,
        ':updated_at'         => $now
    ]);

    echo json_encode([
        'success' => true,
        'saved' => true,
        'message' => 'Autosaved',
        'updated_at' => $now
    ]);

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Boyfriend Application Form</title>
<link rel="stylesheet" href="css/styles.css?v=<?= filemtime(__DIR__ . '/css/styles.css') ?>"/>
</head>

<body>

<div class="page">

    <div class="header">
        <div class="title">▤ BOYFRIEND APPLICATION FORM™</div>
        <div class="subtitle">“For quality assurance, emotional auditing, and future soft-life planning.” 💕 👑</div>
    </div>

    <?php if ($submitted): ?>
        <div class="success">APPLICATION RECEIVED • UNDER REVIEW</div>
    <?php endif; ?>

    <form method="post">
        <div id="autosaveStatus" class="autosave-status">
            <span class="autosave-dot"></span>
            <span id="autosaveText">Enter name and age to enable autosave</span>
        </div>

        <div class="application-meta">

            <div class="meta-row">
                <div class="field">
                    <strong>Applicant Name:</strong>
                    <input type="text" name="applicant_name" style="width:100%;">
                </div>

                <div class="field">
                    <strong>Date Submitted:</strong>
                    <input type="date" name="date_submitted" style="width:100%;">
                </div>

                <div></div>
                <div></div>
                <div></div>
            </div>

            <div class="meta-row">
                <div class="field">
                    <strong>Nickname(s):</strong>
                    <input type="text" name="nickname" style="width:100%;">
                </div>

                <div class="field">
                    <strong>Age:</strong>
                    <input type="text" name="age" style="width:100%;">
                </div>

                <div class="field">
                    <strong>Height:</strong>
                    <input type="text" name="height" style="width:100%;">
                </div>

                <div></div>

                <div class="field">
                    <strong>Emergency Snack Preference:</strong>
                    <input type="text" name="snack" style="width:100%;">
                </div>
            </div>

        </div>

        <div class="sections">

            <!-- SECTION A -->
            <div class="section">
                <div class="section-title">SECTION A: BASIC IDENTIFICATION 🪪</div>
                <div class="section-body">

                    <div class="question">
                        <strong>1.</strong> Full government name
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>2.</strong> Please list all names your mother calls you when angry:
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>3.</strong> State of origin / hometown:
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>4.</strong> Do you snore?
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Yes</div>
                            <div class="check"><span class="box"></span>No</div>
                            <div class="check"><span class="box"></span>I deny all allegations</div>
                        </div>
                    </div>

                    <div class="question">
                        <strong>5.</strong> Relationship status before this application:
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Single</div>
                            <div class="check"><span class="box"></span>Situationship survivor</div>
                            <div class="check"><span class="box"></span>Recovering heartbreaker</div>
                            <div class="check"><span class="box"></span>Classified information</div>
                        </div>
                    </div>

                    <div class="question">
                        <strong>6.</strong> What are your top 3 love languages?
                        <div class="checks">
                            <div class="check"><span class="box"></span>Food</div>
                            <div class="check"><span class="box"></span>Quality time</div>
                            <div class="check"><span class="box"></span>Physical touch</div>
                            <div class="check"><span class="box"></span>Words of affirmation</div>
                            <div class="check"><span class="box"></span>Money</div>
                            <div class="check"><span class="box"></span>Sending memes at 2am</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- SECTION B -->
            <div class="section">
                <div class="section-title">SECTION B: FAMILY BACKGROUND 👨‍👩‍👧</div>
                <div class="section-body">

                    <div class="question">
                        <strong>1.</strong> Father's full name:
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>2.</strong> Mother's full name:
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>3.</strong> Number of siblings:
                        <input class="inline-answer" type="text" name="siblings" aria-label="Number of siblings">
                    </div>

                    <div class="question">
                        <strong>4.</strong> Which sibling causes the most drama?<br>
                        Explain carefully.
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>5.</strong> Which parent do you resemble most?
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Mum</div>
                            <div class="check"><span class="box"></span>Dad</div>
                            <div class="check"><span class="box"></span>Family WhatsApp admin</div>
                        </div>
                    </div>

                    <div class="question">
                        <strong>6.</strong> Describe your family in 3 words:
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>7.</strong> If I attend your family gathering, what should I prepare for?
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Loud aunties</div>
                            <div class="check"><span class="box"></span>Endless food</div>
                            <div class="check"><span class="box"></span>Unexpected marriage questions</div>
                            <div class="check"><span class="box"></span>Family dance competition</div>
                            <div class="check"><span class="box"></span>Prayer marathon</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- SECTION C -->
            <div class="section">
                <div class="section-title">SECTION C: FRIENDSHIP &amp; SOCIAL LIFE 🦋</div>
                <div class="section-body">

                    <div class="question">
                        <strong>1.</strong> Name your closest friends and what they contribute to society:
                        <table class="mini-table">
                            <tr>
                                <th>Friend's Name</th>
                                <th>Their Vibe</th>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                    </div>

                    <div class="question">
                        <strong>2.</strong> Which friend is the bad influence?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>3.</strong> Are your friends aware you are dating a queen?
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Yes</div>
                            <div class="check"><span class="box"></span>Absolutely</div>
                            <div class="check"><span class="box"></span>They are about to learn</div>
                        </div>
                    </div>

                    <div class="question">
                        <strong>4.</strong> Who knows your deepest secrets?
                        <span class="answer-line"></span>
                    </div>

                </div>
            </div>

            <!-- SECTION D -->
            <div class="section">
                <div class="section-title">SECTION D: DREAMS, GOALS &amp; ASPIRATIONS 🚀</div>
                <div class="section-body">

                    <div class="question">
                        <strong>1.</strong> What is your biggest dream in life?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>2.</strong> What kind of life do you want in 10 years?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>3.</strong> What motivates you when life gets difficult?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>4.</strong> What legacy do you want to leave behind?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>5.</strong> Describe your future home briefly:
                        <div class="checks">
                            <div class="check"><span class="box"></span>Mansion</div>
                            <div class="check"><span class="box"></span>Beach house</div>
                            <div class="check"><span class="box"></span>Farmhouse</div>
                            <div class="check"><span class="box"></span>Anywhere with peace and food</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- SECTION E -->
            <div class="section">
                <div class="section-title">SECTION E: LOVE &amp; RELATIONSHIP INVESTIGATION 💘</div>
                <div class="section-body">

                    <div class="question">
                        <strong>1.</strong> What made you interested in me?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>2.</strong> What is your biggest green flag?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>3.</strong> Biggest red flag you're working on?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>4.</strong> How do you handle conflict?
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Communicate calmly</div>
                            <div class="check"><span class="box"></span>Go silent like a Netflix villain</div>
                            <div class="check"><span class="box"></span>Buy food and apologize</div>
                            <div class="check"><span class="box"></span>Need 2 business days to process emotions</div>
                        </div>
                    </div>

                    <div class="question">
                        <strong>5.</strong> What's your ideal date night?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>6.</strong> Define loyalty in your own words:
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>7.</strong> Are you romantic naturally or by force?
                        <div class="checks">
                            <div class="check"><span class="box"></span>Naturally</div>
                            <div class="check"><span class="box"></span>Still under</div>
                            <div class="check"><span class="box"></span>By prayer</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- SECTION F -->
            <div class="section">
                <div class="section-title">SECTION F: VALUES &amp; BELIEFS 🕊️</div>
                <div class="section-body">

                    <div class="question">
                        <strong>1.</strong> What values are non-negotiable for you?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>2.</strong> What do you believe makes a relationship last?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>3.</strong> What does success mean to you?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>4.</strong> Are you spiritual/religious?<br>
                        Explain briefly:
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>5.</strong> What type of father/husband do you hope to become someday?
                        <span class="answer-line"></span>
                    </div>

                </div>
            </div>

            <!-- SECTION G -->
            <div class="section">
                <div class="section-title">SECTION G: MONEY &amp; BUSINESS MATTERS 💰💼</div>
                <div class="section-body">

                    <div class="question">
                        <strong>1.</strong> What's your dream business idea?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>2.</strong> If you received £1 million today, what's the first thing you'd do?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>3.</strong> Are you a saver or spender?
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Saver</div>
                            <div class="check"><span class="box"></span>Spender</div>
                            <div class="check"><span class="box"></span>“Money comes and goes” motivational speaker</div>
                        </div>
                    </div>

                    <div class="question">
                        <strong>4.</strong> Financial red flags you cannot tolerate:
                        <span class="answer-line"></span>
                    </div>

                </div>
            </div>

            <!-- SECTION H -->
            <div class="section">
                <div class="section-title">SECTION H: DREAM VACATIONS 🏖️✈️</div>
                <div class="section-body">

                    <div class="question">
                        <strong>1.</strong> Top 5 dream destinations:
                        <span class="answer-line"></span>
                        <span class="answer-line"></span>
                        <span class="answer-line"></span>
                        <span class="answer-line"></span>
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>2.</strong> Vacation personality:
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Adventure explorer</div>
                            <div class="check"><span class="box"></span>Luxury soft life ambassador</div>
                            <div class="check"><span class="box"></span>Sleep and eat professionally</div>
                            <div class="check"><span class="box"></span>Content creator</div>
                        </div>
                    </div>

                    <div class="question">
                        <strong>3.</strong> Would you survive a couple's road trip without a GPS?
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Yes</div>
                            <div class="check"><span class="box"></span>No</div>
                            <div class="check"><span class="box"></span>Depends on playlist control</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- SECTION I -->
            <div class="section">
                <div class="section-title">SECTION I: RANDOM BUT IMPORTANT QUESTIONS 🌚</div>
                <div class="section-body">

                    <div class="question">
                        <strong>1.</strong> Can you cook?
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Yes</div>
                            <div class="check"><span class="box"></span>No</div>
                        </div>
                    </div>

                    <div class="question">
                        <strong>2.</strong> I can boil water with confidence
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>3.</strong> What's your most embarrassing moment?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>4.</strong> Which song describes your life currently?
                        <span class="answer-line"></span>
                    </div>

                    <div class="question">
                        <strong>5.</strong> Have you ever stalked me online? Be honest.
                        <div class="checks single">
                            <div class="check"><span class="box"></span>Yes</div>
                            <div class="check"><span class="box"></span>No</div>
                            <div class="check"><span class="box"></span>Prefer not to say for legal reasons</div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <div class="bottom">

            <div class="declaration">
                <div class="bottom-title">FINAL DECLARATION</div>

                <div class="bottom-body">
                    <div class="declaration-text">
                        I, <input class="declaration-name" type="text" name="declaration_name" aria-label="Declaration name">,
                        hereby confirm that all information
                        provided above is true and accurate to the best of my knowledge.
                        Any lies discovered later may result in:
                    </div>

                    <div class="checks">
                        <div class="check"><span class="box"></span>Excessive questioning</div>
                        <div class="check"><span class="box"></span>Temporary cuddling suspension</div>
                        <div class="check"><span class="box"></span>Side-eye punishment</div>
                        <div class="check"><span class="box"></span>Immediate relationship tribunal</div>
                    </div>

                    <div class="signature-row">
                        <div class="signature-field">
                            <strong>Signature:</strong>
                            <span class="line"></span>
                        </div>

                        <div class="signature-field">
                            <strong>Date:</strong>
                            <span class="line"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="official">
                <div class="bottom-title">FOR OFFICIAL GIRLFRIEND USE ONLY 👑</div>

                <div class="bottom-body">

                    <div class="official-grid">
                        <div class="check"><span class="box"></span>Approved</div>
                        <div class="check"><span class="box"></span>Needs further investigation</div>
                        <div class="check"><span class="box"></span>Approved with conditions</div>
                        <div class="check"><span class="box"></span>Fine boy but suspicious</div>
                    </div>

                    <div class="notes">
                        <strong>Interviewer's Notes:</strong>
                        <div class="notes-line"></div>
                        <div class="notes-line"></div>
                        <div class="notes-line"></div>
                        <div class="notes-line"></div>
                    </div>

                </div>
            </div>

        </div>

        <div class="submit-area">
            <button type="submit" class="submit-button">Submit Application</button>
            <button type="button" class="submit-button" onclick="window.print()">Print Form</button>
        </div>

    </form>

</div>

<script src="js/app.js?v=<?= filemtime(__DIR__ . '/js/app.js') ?>" defer></script>
</body>
</html>
