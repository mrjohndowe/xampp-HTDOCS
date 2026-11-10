<?php

declare(strict_types=1);

/**
 * Interactive questionnaire wizard.
 *
 * config.php defines the questions and can define the section flow. When it
 * does not, flow.php supplies the sections and conditional follow-up rules.
 * Answers stay in the session until the final page is submitted.
 */

require_once __DIR__ . '/extras/functions.php';

session_start();

$config = null;
$flow = null;
$pageTitle = 'Questionnaire';
$fatalError = null;

try {
    $config = loadConfig(__DIR__ . '/config.php');
    $pageTitle = $config['title'];
    $flowDefinition = $config['flow'] ?? null;

    if ($flowDefinition === null) {
        $flowDefinition = loadFlow(__DIR__ . '/flow.php');
    }

    $flow = buildQuestionFlow($config['questions'], $flowDefinition);
} catch (Throwable $exception) {
    $fatalError = $exception->getMessage();
}

if ($fatalError !== null) {
    http_response_code(500);
    require __DIR__ . '/extras/header.php';
    ?>
    <p class="eyebrow">Setup</p>
    <h1>Questionnaire unavailable</h1>
    <p class="lede">The configuration could not be loaded.</p>

    <div class="alert alert-error" role="alert">
        <span class="alert-icon" aria-hidden="true">!</span>
        <div>
            <strong>Configuration error</strong>
            <p><?= e($fatalError) ?></p>
        </div>
    </div>

    <p class="form-note">Fix <code>config.php</code> or <code>flow.php</code> and reload this page.</p>
    <?php
    require __DIR__ . '/extras/footer.php';
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    $pageTitle = 'Method not allowed';
    require __DIR__ . '/extras/header.php';
    ?>
    <h1>Method not allowed</h1>
    <p class="lede">This page accepts GET and POST requests.</p>
    <?php
    require __DIR__ . '/extras/footer.php';
    exit;
}

$token = questionnaireToken();
$questions = $config['questions'];
$formAction = $_SERVER['SCRIPT_NAME'] ?? 'index.php';
$result = null;

if ($method === 'GET' && isset($_GET['done'])) {
    $storedResult = $_SESSION['questionnaire_result'] ?? null;
    unset($_SESSION['questionnaire_result']);

    if (is_array($storedResult)) {
        $result = $storedResult;
    }
}

$errors = [];
$formError = null;
$pages = [];
$pageIndex = 0;
$currentPage = null;

if ($result === null) {
    $wizard = wizardState($questions);
    $values = $wizard['values'];
    $pages = buildQuestionPages($flow, $values);

    if ($pages === []) {
        http_response_code(500);
        $pageTitle = 'Questionnaire unavailable';
        require __DIR__ . '/extras/header.php';
        ?>
        <p class="eyebrow">Setup</p>
        <h1>No visible questions</h1>
        <p class="lede">Every question is currently hidden by the flow conditions.</p>
        <p class="form-note">Check <code>flow.php</code> and reload this page.</p>
        <?php
        require __DIR__ . '/extras/footer.php';
        exit;
    }

    $pageIndex = resolvePageIndex($pages, $flow['sections'], $wizard['section']);
    $currentPage = $pages[$pageIndex];
    saveWizardState(['section' => $currentPage['id'], 'values' => $values]);

    if ($method === 'POST') {
        $postedToken = $_POST['_token'] ?? '';

        if (!is_string($postedToken) || !hash_equals($token, $postedToken)) {
            $token = rotateQuestionnaireToken();
            $formError = 'Your session expired or the form was submitted from another page. '
                . 'Please try this section again.';
        } else {
            $postedSection = $_POST['section'] ?? null;

            if (!is_string($postedSection) || $postedSection !== $currentPage['id']) {
                header('Location: ' . $formAction, true, 303);
                exit;
            }

            $submittedSectionId = $currentPage['id'];
            $direction = $_POST['direction'] ?? 'next';
            if (!is_string($direction) || !in_array($direction, ['next', 'back'], true)) {
                $direction = 'next';
            }

            $submittedQuestions = [];
            foreach ($currentPage['items'] as $item) {
                $submittedQuestions[] = $item['question'];
            }

            // Merge the submitted controls (including hidden conditionals) into
            // the draft; validation only applies to questions visible after the
            // trigger values are applied.
            list($postedValues) = readAnswers($submittedQuestions, $_POST);
            foreach ($submittedQuestions as $question) {
                $values[$question['id']] = $postedValues[$question['id']] ?? null;
            }

            $pages = buildQuestionPages($flow, $values);

            if ($direction === 'back') {
                $previousPage = adjacentPage($pages, $flow['sections'], $submittedSectionId, -1);
                saveWizardState([
                    'section' => $previousPage !== null ? $previousPage['id'] : $submittedSectionId,
                    'values' => $values,
                ]);
                header('Location: ' . $formAction, true, 303);
                exit;
            }

            $submittedPageIndex = findPageIndexById($pages, $submittedSectionId);
            $errors = [];

            if ($submittedPageIndex !== null) {
                $visibleQuestions = $pages[$submittedPageIndex]['questions'];
                list($normalizedValues, , $errors) = readAnswers($visibleQuestions, $values);

                foreach ($visibleQuestions as $question) {
                    if (array_key_exists($question['id'], $normalizedValues)) {
                        $values[$question['id']] = $normalizedValues[$question['id']];
                    }
                }

                $pages = buildQuestionPages($flow, $values);
                $submittedPageIndex = findPageIndexById($pages, $submittedSectionId);
            }

            if ($errors === []) {
                $nextPage = adjacentPage($pages, $flow['sections'], $submittedSectionId, 1);

                if ($nextPage !== null) {
                    saveWizardState(['section' => $nextPage['id'], 'values' => $values]);
                    header('Location: ' . $formAction, true, 303);
                    exit;
                }

                $finalQuestions = visibleQuestionsFromPages($pages);
                list($finalValues, $answers, $finalErrors) = readAnswers($finalQuestions, $values);
                $values = $finalValues;
                $pages = buildQuestionPages($flow, $values);
                $finalQuestions = visibleQuestionsFromPages($pages);

                if ($finalErrors !== []) {
                    $errors = $finalErrors;

                    foreach ($finalQuestions as $question) {
                        if (!isset($finalErrors[$question['id']])) {
                            continue;
                        }

                        $errorPageIndex = findPageIndexForQuestion($pages, $question['id']);
                        if ($errorPageIndex !== null) {
                            $pageIndex = $errorPageIndex;
                            $currentPage = $pages[$pageIndex];
                            saveWizardState(['section' => $currentPage['id'], 'values' => $values]);
                        }
                        break;
                    }
                } else {
                    $configPaths = [];
                    foreach ($finalQuestions as $question) {
                        if ($question['config_path'] !== null) {
                            $configPaths[$question['id']] = $question['config_path'];
                        }
                    }

                    try {
                        saveResponse(resolveResponseDir($config['response_dir']), [
                            'title' => $config['title'],
                            'submitted_at' => date('c'),
                            'answers' => $answers,
                            'config_paths' => $configPaths,
                        ]);
                    } catch (Throwable $exception) {
                        http_response_code(500);
                        require __DIR__ . '/extras/header.php';
                        ?>
                        <p class="eyebrow">Storage</p>
                        <h1>Answers could not be saved</h1>

                        <div class="alert alert-error" role="alert">
                            <span class="alert-icon" aria-hidden="true">!</span>
                            <div>
                                <strong>Storage error</strong>
                                <p><?= e($exception->getMessage()) ?></p>
                            </div>
                        </div>

                        <p class="form-note">
                            Check the <code>response_dir</code> setting in <code>config.php</code> and its permissions.
                        </p>
                        <?php
                        require __DIR__ . '/extras/footer.php';
                        exit;
                    }

                    $rows = [];
                    foreach ($finalQuestions as $question) {
                        $rows[] = [
                            'prompt' => $question['prompt'],
                            'value' => $answers[$question['id']],
                            'config_path' => $question['config_path'],
                        ];
                    }

                    clearWizardState();
                    $_SESSION['questionnaire_result'] = [
                        'title' => $config['title'],
                        'rows' => $rows,
                    ];
                    rotateQuestionnaireToken();

                    header('Location: ' . $formAction . '?done=1', true, 303);
                    exit;
                }
            }

            if ($currentPage === null || findPageIndexById($pages, $currentPage['id']) === null) {
                $pageIndex = resolvePageIndex($pages, $flow['sections'], $submittedSectionId);
                $currentPage = $pages[$pageIndex];
            } else {
                $pageIndex = findPageIndexById($pages, $currentPage['id']);
                $currentPage = $pages[$pageIndex];
            }

            saveWizardState(['section' => $currentPage['id'], 'values' => $values]);
        }
    }

    $pageIndex = findPageIndexById($pages, $currentPage['id']);
    if ($pageIndex === null) {
        $pageIndex = resolvePageIndex($pages, $flow['sections'], $currentPage['id']);
        $currentPage = $pages[$pageIndex];
    }

    $sectionCount = count($pages);
    $progressPercent = (int) round((($pageIndex + 1) / $sectionCount) * 100);
    $isLastSection = $pageIndex === $sectionCount - 1;
    $sectionTitleId = 'section-title-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $currentPage['id']);
    $pageTitle = $config['title'] . ($currentPage['title'] !== ''
        ? ' — ' . $currentPage['title']
        : ' — Section ' . ($pageIndex + 1));
}

require __DIR__ . '/extras/header.php';

if ($result !== null):
    ?>
    <div class="status-badge">Response submitted</div>
    <h1>Thank you!</h1>
    <p class="lede">Your answers have been saved.</p>

    <ul class="summary-list">
        <?php foreach ($result['rows'] as $row):
            $formatted = formatAnswer($row['value']);
            ?>
            <li class="summary-row">
                <span class="summary-key">
                    <?= e($row['prompt']) ?>
                    <?php if ($row['config_path'] !== null): ?>
                        <span class="summary-path"><?= e($row['config_path']) ?></span>
                    <?php endif; ?>
                </span>
                <span class="summary-value<?= $formatted['skipped'] ? ' is-muted' : '' ?>"><?= e($formatted['text']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="form-actions">
        <a class="btn btn-secondary" href="<?= e($formAction) ?>">Submit another response</a>
    </div>
    <?php
else:
    ?>
    <div class="card-head">
        <div>
            <p class="eyebrow">Section <?= $pageIndex + 1 ?> of <?= $sectionCount ?></p>
            <h1><?= e($config['title']) ?></h1>
            <?php if ($pageIndex === 0 && $config['intro'] !== ''): ?>
                <p class="lede"><?= e($config['intro']) ?></p>
            <?php endif; ?>
        </div>
        <span class="count-pill"><?= $progressPercent ?>% complete</span>
    </div>

    <div class="wizard-progress" role="progressbar" aria-label="Questionnaire progress"
        aria-valuemin="1" aria-valuemax="<?= $sectionCount ?>" aria-valuenow="<?= $pageIndex + 1 ?>"
        aria-valuetext="Section <?= $pageIndex + 1 ?> of <?= $sectionCount ?>, <?= $progressPercent ?>% complete">
        <span class="wizard-progress-fill" style="width: <?= $progressPercent ?>%"></span>
    </div>

    <?php if ($formError !== null || $errors !== []): ?>
        <div class="alert alert-error" role="alert" data-error-summary>
            <span class="alert-icon" aria-hidden="true">!</span>
            <div>
                <strong><?= $formError !== null ? 'Please try again.' : 'Please check your answers.' ?></strong>
                <p><?= e($formError ?? 'One or more questions in this section need a valid answer.') ?></p>
            </div>
        </div>
    <?php endif; ?>

    <form class="form" method="post" action="<?= e($formAction) ?>" data-questionnaire>
        <input type="hidden" name="_token" value="<?= e($token) ?>">
        <input type="hidden" name="section" value="<?= e($currentPage['id']) ?>">

        <section class="form-section"<?= $currentPage['title'] !== '' ? ' aria-labelledby="' . e($sectionTitleId) . '"' : '' ?>>
            <?php if ($currentPage['title'] !== '' || $currentPage['description'] !== ''): ?>
                <div class="section-head">
                    <?php if ($currentPage['title'] !== ''): ?>
                        <h2 class="section-title" id="<?= e($sectionTitleId) ?>"><?= e($currentPage['title']) ?></h2>
                    <?php endif; ?>
                    <?php if ($currentPage['description'] !== ''): ?>
                        <p class="section-description"><?= e($currentPage['description']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="section-questions">
                <?php foreach ($currentPage['items'] as $item):
                    $question = $item['question'];
                    $number = ($flow['positions'][$question['id']] ?? 0) + 1;
                    ?>
                    <?= renderQuestion(
                        $question,
                        $number,
                        $values[$question['id']] ?? null,
                        $errors[$question['id']] ?? null,
                        !$item['visible'],
                        $item['condition'],
                        $item['source_question']
                    ) ?>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="form-actions wizard-actions">
            <button class="btn btn-primary wizard-next" type="submit" name="direction" value="next" data-wizard-next>
                <?= $isLastSection ? e($config['submit_label']) : 'Next section' ?>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"></path>
                </svg>
            </button>

            <?php if ($pageIndex > 0): ?>
                <button class="btn btn-secondary wizard-back" type="submit" name="direction" value="back">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M11 18l-6-6 6-6"></path>
                    </svg>
                    Back
                </button>
            <?php endif; ?>
        </div>
    </form>
    <?php
endif;

require __DIR__ . '/extras/footer.php';
