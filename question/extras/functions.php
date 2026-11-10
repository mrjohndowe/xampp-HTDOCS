<?php

declare(strict_types=1);

/**
 * Shared helpers for the questionnaire.
 *
 * Loaded by index.php before anything is rendered. Session state is expected
 * to be started by the entry point before using the token helpers.
 */

const QUESTION_TYPES = ['text', 'choice', 'multiple', 'confirm', 'number', 'list', 'record', 'records'];

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------------------------------------------------------------------------
// Guided fields (lists, objects and arrays of objects)
// ---------------------------------------------------------------------------

/**
 * Validate the field definitions used by record and records questions.
 *
 * The config may use a shorthand label for a text field, or an array with
 * label, type, required, separator and default keys. Field types are text,
 * boolean, number and list.
 */
function normalizeGuidedFields($rawFields, string $questionId): array
{
    if (!is_array($rawFields) || $rawFields === []) {
        throw new RuntimeException("Question \"{$questionId}\" needs a non-empty \"fields\" array.");
    }

    $fields = [];
    foreach ($rawFields as $key => $rawField) {
        if (!is_string($key) || preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/', $key) !== 1) {
            throw new RuntimeException(
                "Question \"{$questionId}\" has an invalid guided field name. Use letters, numbers and underscores, starting with a letter."
            );
        }

        if (!is_array($rawField)) {
            $rawField = ['label' => $rawField];
        }

        $label = $rawField['label'] ?? ucfirst(str_replace('_', ' ', $key));
        if (!is_string($label) || trim($label) === '') {
            throw new RuntimeException("Question \"{$questionId}\" field \"{$key}\" needs a non-empty name.");
        }

        $fieldType = $rawField['type'] ?? 'text';
        if (!is_string($fieldType) || !in_array($fieldType, ['text', 'boolean', 'number', 'list'], true)) {
            throw new RuntimeException(
                "Question \"{$questionId}\" field \"{$key}\" has an unknown type. Use text, boolean, number or list."
            );
        }

        $required = (bool) ($rawField['required'] ?? false);
        $separator = $rawField['separator'] ?? ';';
        if (!is_string($separator) || $separator === '') {
            throw new RuntimeException("Question \"{$questionId}\" field \"{$key}\" has an empty separator.");
        }

        $default = $rawField['default'] ?? null;

        if ($fieldType === 'text') {
            if ($default !== null && !is_scalar($default)) {
                throw new RuntimeException("Question \"{$questionId}\" field \"{$key}\" has an invalid text default.");
            }
            $default = $default === null ? '' : (string) $default;
        } elseif ($fieldType === 'boolean') {
            $default = $default === null ? false : $default;
            if (!is_bool($default)) {
                throw new RuntimeException("Question \"{$questionId}\" field \"{$key}\" must default to true or false.");
            }
        } elseif ($fieldType === 'number') {
            $default = $default === null ? 0 : $default;
            if (!is_numeric($default)) {
                throw new RuntimeException("Question \"{$questionId}\" field \"{$key}\" must default to a number.");
            }
            $default = strpos((string) $default, '.') !== false ? (float) $default : (int) $default;
        } else {
            $default = $default === null ? [] : $default;
            if (!is_array($default)) {
                throw new RuntimeException("Question \"{$questionId}\" field \"{$key}\" must default to an array.");
            }
            $items = [];
            foreach ($default as $item) {
                if (!is_scalar($item)) {
                    throw new RuntimeException("Question \"{$questionId}\" field \"{$key}\" has an invalid list default.");
                }
                $items[] = (string) $item;
            }
            $default = $items;
        }

        $fields[$key] = [
            'label' => trim($label),
            'type' => $fieldType,
            'required' => $required,
            'separator' => $separator,
            'default' => $default,
        ];
    }

    return $fields;
}

/**
 * Build the one-line guided value used for a record default.
 */
function guidedRecordLine(array $record, array $fields): string
{
    $parts = [];

    foreach ($fields as $key => $field) {
        if (!array_key_exists($key, $record)) {
            continue;
        }

        $value = $record[$key];

        if ($field['type'] === 'boolean') {
            $text = $value ? 'true' : 'false';
        } elseif ($field['type'] === 'list') {
            if (!is_array($value) || $value === []) {
                continue;
            }
            $text = implode($field['separator'] . ' ', array_map('strval', $value));
        } elseif ($field['type'] === 'number') {
            $text = trim((string) $value);
        } else {
            $text = trim((string) $value);
        }

        if ($text === '') {
            continue;
        }

        $parts[] = $key . ': ' . $text;
    }

    return implode(' | ', $parts);
}

/**
 * Parse one guided record line into a properly typed array.
 *
 * @return array{0: ?array, 1: ?string} [record, error]
 */
function parseGuidedRecord(string $line, array $fields): array
{
    $lookup = [];
    foreach ($fields as $key => $field) {
        $lookup[strtolower($key)] = $key;
    }

    $provided = [];
    foreach (explode('|', $line) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }

        $colon = strpos($part, ':');
        if ($colon === false) {
            return [null, 'Every field must use "name: value".'];
        }

        $givenKey = trim(substr($part, 0, $colon));
        if ($givenKey === '') {
            return [null, 'A field name is missing before ":".'];
        }

        $actualKey = $lookup[strtolower($givenKey)] ?? null;
        if ($actualKey === null) {
            return [null, "Unknown field \"{$givenKey}\"."];
        }
        if (array_key_exists($actualKey, $provided)) {
            return [null, "Field \"{$givenKey}\" appears twice."];
        }

        $provided[$actualKey] = trim(substr($part, $colon + 1));
    }

    $record = [];
    foreach ($fields as $key => $field) {
        $hasValue = array_key_exists($key, $provided) && $provided[$key] !== '';

        if (!$hasValue) {
            if ($field['required']) {
                return [null, "Missing required field \"{$key}\"."];
            }
            $record[$key] = $field['default'];
            continue;
        }

        $raw = $provided[$key];

        if ($field['type'] === 'boolean') {
            $value = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value === null) {
                return [null, "Field \"{$key}\" must be true or false."];
            }
            $record[$key] = $value;
        } elseif ($field['type'] === 'number') {
            if (!is_numeric($raw)) {
                return [null, "Field \"{$key}\" must be a number."];
            }
            $record[$key] = strpos($raw, '.') !== false ? (float) $raw : (int) $raw;
        } elseif ($field['type'] === 'list') {
            $items = [];
            foreach (explode($field['separator'], $raw) as $item) {
                $item = trim($item);
                if ($item !== '') {
                    $items[] = $item;
                }
            }
            $record[$key] = $items;
        } else {
            $record[$key] = $raw;
        }
    }

    return [$record, null];
}

/**
 * Explain the guided syntax shown under list, record and records questions.
 */
function structuredFieldGuide(array $question): ?string
{
    if ($question['type'] === 'list') {
        return 'Enter one item per line.';
    }

    if ($question['type'] !== 'record' && $question['type'] !== 'records') {
        return null;
    }

    $parts = [];
    foreach ($question['fields'] as $key => $field) {
        $part = $key . ($field['required'] ? '*' : '');
        if ($field['type'] === 'boolean') {
            $part .= ' (true/false)';
        } elseif ($field['type'] === 'number') {
            $part .= ' (number)';
        } elseif ($field['type'] === 'list') {
            $part .= ' (separate with "' . $field['separator'] . '")';
        }
        $parts[] = $part;
    }

    $intro = $question['type'] === 'record'
        ? 'Enter one line'
        : 'Enter one item per line';

    return $intro . ', using "field: value" pairs separated by " | ". Fields: '
        . implode(', ', $parts) . '. Required fields are marked *.';
}

// ---------------------------------------------------------------------------
// CSRF token
// ---------------------------------------------------------------------------

function questionnaireToken(): string
{
    if (empty($_SESSION['questionnaire_token']) || !is_string($_SESSION['questionnaire_token'])) {
        $_SESSION['questionnaire_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['questionnaire_token'];
}

function rotateQuestionnaireToken(): string
{
    $_SESSION['questionnaire_token'] = bin2hex(random_bytes(32));

    return $_SESSION['questionnaire_token'];
}

// ---------------------------------------------------------------------------
// Wizard state
// ---------------------------------------------------------------------------

/**
 * Load the in-progress answers and current section id from the session.
 *
 * @return array{section: ?string, values: array}
 */
function wizardState(array $questions): array
{
    $values = initialValues($questions);
    $section = null;
    $state = $_SESSION['questionnaire_wizard'] ?? null;

    if (is_array($state)) {
        if (isset($state['section']) && is_string($state['section']) && $state['section'] !== '') {
            $section = $state['section'];
        }

        if (isset($state['values']) && is_array($state['values'])) {
            foreach ($values as $id => $default) {
                if (array_key_exists($id, $state['values'])) {
                    $values[$id] = $state['values'][$id];
                }
            }
        }
    }

    return ['section' => $section, 'values' => $values];
}

function saveWizardState(array $state): void
{
    $section = $state['section'] ?? null;

    $_SESSION['questionnaire_wizard'] = [
        'section' => is_string($section) && $section !== '' ? $section : null,
        'values' => $state['values'],
    ];
}

function clearWizardState(): void
{
    unset($_SESSION['questionnaire_wizard']);
}

// ---------------------------------------------------------------------------
// Config loading and validation
// ---------------------------------------------------------------------------

function loadConfig(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Config file not found: ' . $path);
    }

    $config = require $path;

    if (!is_array($config)) {
        throw new RuntimeException('Config file must return an array.');
    }

    $rawQuestions = $config['questions'] ?? null;
    if (!is_array($rawQuestions) || $rawQuestions === []) {
        throw new RuntimeException('Config must contain a non-empty "questions" array.');
    }

    $questions = [];
    $seenIds = [];

    foreach (array_values($rawQuestions) as $index => $rawQuestion) {
        $number = $index + 1;

        if (!is_array($rawQuestion)) {
            throw new RuntimeException("Question {$number} must be an array.");
        }

        $id = $rawQuestion['id'] ?? null;
        if (!is_string($id) || trim($id) === '') {
            throw new RuntimeException("Question {$number} needs a non-empty string \"id\".");
        }
        if (isset($seenIds[$id])) {
            throw new RuntimeException("Duplicate question id \"{$id}\".");
        }
        $seenIds[$id] = true;

        $prompt = $rawQuestion['prompt'] ?? null;
        if (!is_string($prompt) || trim($prompt) === '') {
            throw new RuntimeException("Question \"{$id}\" needs a non-empty \"prompt\".");
        }

        $type = $rawQuestion['type'] ?? 'text';
        if (!is_string($type) || !in_array($type, QUESTION_TYPES, true)) {
            throw new RuntimeException(
                "Question \"{$id}\" has an unknown type. Use one of: " . implode(', ', QUESTION_TYPES) . '.'
            );
        }

        $options = [];
        if ($type === 'choice' || $type === 'multiple') {
            $rawOptions = $rawQuestion['options'] ?? null;
            if (!is_array($rawOptions) || $rawOptions === []) {
                throw new RuntimeException("Question \"{$id}\" of type \"{$type}\" needs a non-empty \"options\" array.");
            }
            foreach ($rawOptions as $option) {
                if (!is_string($option) || trim($option) === '') {
                    throw new RuntimeException("Question \"{$id}\" has an invalid option; options must be non-empty strings.");
                }
                if (in_array($option, $options, true)) {
                    throw new RuntimeException("Question \"{$id}\" has a duplicate option: \"{$option}\".");
                }
                $options[] = $option;
            }
        }

        $pattern = $rawQuestion['pattern'] ?? null;
        if ($pattern !== null && (!is_string($pattern) || @preg_match($pattern, '') === false)) {
            throw new RuntimeException("Question \"{$id}\" has an invalid \"pattern\" regular expression.");
        }

        $help = $rawQuestion['help'] ?? null;
        if ($help !== null && (!is_string($help) || trim($help) === '')) {
            throw new RuntimeException("Question \"{$id}\": \"help\" must be a non-empty string when set.");
        }

        $patternError = $rawQuestion['pattern_error'] ?? null;
        if ($patternError !== null && !is_string($patternError)) {
            throw new RuntimeException("Question \"{$id}\": \"pattern_error\" must be a string.");
        }

        $configPath = $rawQuestion['config_path'] ?? null;
        if ($configPath !== null) {
            if (!is_string($configPath)
                || preg_match('/\A[A-Za-z][A-Za-z0-9_]*(?:\.[A-Za-z][A-Za-z0-9_]*)*\z/', $configPath) !== 1
            ) {
                throw new RuntimeException("Question \"{$id}\": \"config_path\" must look like sections.setting.");
            }
        }

        $fields = [];
        if ($type === 'record' || $type === 'records') {
            $fields = normalizeGuidedFields($rawQuestion['fields'] ?? null, $id);
        }

        $hasDefault = array_key_exists('default', $rawQuestion);

        if ($type === 'confirm') {
            if ($hasDefault && !is_bool($rawQuestion['default'])) {
                throw new RuntimeException("Question \"{$id}\": \"default\" must be true or false for confirm questions.");
            }
            $default = $hasDefault ? $rawQuestion['default'] : true;
        } elseif ($type === 'multiple') {
            $default = $hasDefault ? $rawQuestion['default'] : [];
            if (!is_array($default)) {
                throw new RuntimeException("Question \"{$id}\": \"default\" must be an array of option texts for multiple questions.");
            }
            foreach ($default as $item) {
                if (!is_string($item) || !in_array($item, $options, true)) {
                    throw new RuntimeException("Question \"{$id}\": default value is not one of the options.");
                }
            }
        } elseif ($type === 'choice') {
            $default = $hasDefault ? $rawQuestion['default'] : null;
            if ($default !== null && (!is_string($default) || !in_array($default, $options, true))) {
                throw new RuntimeException("Question \"{$id}\": default value is not one of the options.");
            }
        } elseif ($type === 'number') {
            $default = $hasDefault ? $rawQuestion['default'] : null;
            if ($default !== null && !is_numeric($default)) {
                throw new RuntimeException("Question \"{$id}\": \"default\" must be a number.");
            }
            $default = $default === null ? null : $default + 0;
        } elseif ($type === 'list') {
            $default = $hasDefault ? $rawQuestion['default'] : [];
            if (!is_array($default)) {
                throw new RuntimeException("Question \"{$id}\": \"default\" must be an array of strings for list questions.");
            }
            $items = [];
            foreach ($default as $item) {
                if (!is_scalar($item)) {
                    throw new RuntimeException("Question \"{$id}\": list defaults must be strings.");
                }
                $items[] = (string) $item;
            }
            $default = $items;
        } elseif ($type === 'record' || $type === 'records') {
            $default = $hasDefault ? $rawQuestion['default'] : [];
            if (!is_array($default)) {
                throw new RuntimeException("Question \"{$id}\": \"default\" must be an array for guided questions.");
            }
            if ($type === 'records') {
                if ($default !== [] && array_keys($default) !== range(0, count($default) - 1)) {
                    throw new RuntimeException("Question \"{$id}\": \"default\" must be a list of records.");
                }
                foreach ($default as $item) {
                    if (!is_array($item)) {
                        throw new RuntimeException("Question \"{$id}\": guided record defaults must be arrays.");
                    }
                }
            }
        } else {
            $default = $hasDefault ? $rawQuestion['default'] : null;
            if ($default !== null && !is_scalar($default)) {
                throw new RuntimeException("Question \"{$id}\": \"default\" must be a string for text questions.");
            }
            $default = $default === null ? null : (string) $default;
        }

        $questions[] = [
            'id' => $id,
            'prompt' => $prompt,
            'type' => $type,
            'required' => (bool) ($rawQuestion['required'] ?? false),
            'options' => $options,
            'default' => $default,
            'pattern' => $pattern,
            'pattern_error' => $patternError,
            'help' => $help,
            'multiline' => (bool) ($rawQuestion['multiline'] ?? false),
            'fields' => $fields,
            'config_path' => $configPath,
        ];
    }

    $responseDir = $config['response_dir'] ?? 'responses';
    if (!is_string($responseDir) || trim($responseDir) === '') {
        throw new RuntimeException('"response_dir" must be a non-empty string.');
    }

    $title = $config['title'] ?? 'Questionnaire';
    if (!is_string($title) || trim($title) === '') {
        $title = 'Questionnaire';
    }

    $intro = $config['intro'] ?? '';
    if (!is_string($intro)) {
        $intro = '';
    }

    $submitLabel = $config['submit_label'] ?? 'Submit answers';
    if (!is_string($submitLabel) || trim($submitLabel) === '') {
        $submitLabel = 'Submit answers';
    }

    $flow = null;
    if (array_key_exists('sections', $config) || array_key_exists('conditions', $config)) {
        $inlineSections = $config['sections'] ?? [];
        $inlineConditions = $config['conditions'] ?? [];

        if (!is_array($inlineSections)) {
            throw new RuntimeException('"sections" must be an array when set in the config.');
        }
        if (!is_array($inlineConditions)) {
            throw new RuntimeException('"conditions" must be an array when set in the config.');
        }

        $flow = [
            'sections' => $inlineSections,
            'conditions' => $inlineConditions,
        ];
    }

    return [
        'title' => $title,
        'intro' => $intro,
        'submit_label' => $submitLabel,
        'response_dir' => $responseDir,
        'questions' => $questions,
        'flow' => $flow,
    ];
}

// ---------------------------------------------------------------------------
// Sections and conditional visibility
// ---------------------------------------------------------------------------

/**
 * @return array{sections: array, conditions: array}
 */
function loadFlow(string $path): array
{
    if (!is_file($path)) {
        return ['sections' => [], 'conditions' => []];
    }

    $flow = require $path;
    if (!is_array($flow)) {
        throw new RuntimeException('Flow file must return an array.');
    }

    $sections = $flow['sections'] ?? [];
    $conditions = $flow['conditions'] ?? [];

    if (!is_array($sections)) {
        throw new RuntimeException('Flow "sections" must be an array.');
    }
    if (!is_array($conditions)) {
        throw new RuntimeException('Flow "conditions" must be an array.');
    }

    return ['sections' => $sections, 'conditions' => $conditions];
}

function buildQuestionFlow(array $questions, array $flow): array
{
    $questionsById = [];
    foreach ($questions as $question) {
        $questionsById[$question['id']] = $question;
    }

    $sections = [];
    $assigned = [];
    $rawSections = $flow['sections'];

    if ($rawSections === []) {
        foreach ($questions as $question) {
            $sections[] = [
                'id' => 'section-' . $question['id'],
                'title' => '',
                'description' => '',
                'questions' => [$question],
            ];
        }
    } else {
        $seenSectionIds = [];

        foreach (array_values($rawSections) as $index => $rawSection) {
            $number = $index + 1;

            if (!is_array($rawSection)) {
                throw new RuntimeException("Section {$number} must be an array.");
            }

            $sectionId = $rawSection['id'] ?? null;
            if (!is_string($sectionId) || trim($sectionId) === '') {
                throw new RuntimeException("Section {$number} needs a non-empty string \"id\".");
            }
            $sectionId = trim($sectionId);
            if (isset($seenSectionIds[$sectionId])) {
                throw new RuntimeException("Duplicate section id \"{$sectionId}\".");
            }
            $seenSectionIds[$sectionId] = true;

            $title = $rawSection['title'] ?? null;
            if (!is_string($title) || trim($title) === '') {
                throw new RuntimeException("Section \"{$sectionId}\" needs a non-empty \"title\".");
            }

            $description = $rawSection['description'] ?? '';
            if (!is_string($description)) {
                throw new RuntimeException("Section \"{$sectionId}\": \"description\" must be a string.");
            }

            $questionIds = $rawSection['questions'] ?? null;
            if (!is_array($questionIds) || $questionIds === []) {
                throw new RuntimeException("Section \"{$sectionId}\" needs a non-empty \"questions\" array.");
            }

            $sectionQuestions = [];
            foreach ($questionIds as $questionId) {
                if (!is_string($questionId) || !isset($questionsById[$questionId])) {
                    $label = is_scalar($questionId) ? (string) $questionId : '?';
                    throw new RuntimeException("Section \"{$sectionId}\" references unknown question \"{$label}\".");
                }
                if (isset($assigned[$questionId])) {
                    throw new RuntimeException("Question \"{$questionId}\" appears in more than one section.");
                }

                $assigned[$questionId] = true;
                $sectionQuestions[] = $questionsById[$questionId];
            }

            $sections[] = [
                'id' => $sectionId,
                'title' => trim($title),
                'description' => trim($description),
                'questions' => $sectionQuestions,
            ];
        }

        $remaining = [];
        foreach ($questions as $question) {
            if (!isset($assigned[$question['id']])) {
                $remaining[] = $question;
            }
        }

        if ($remaining !== []) {
            $remainingId = 'more-questions';
            $suffix = 2;
            while (isset($seenSectionIds[$remainingId])) {
                $remainingId = 'more-questions-' . $suffix++;
            }

            $sections[] = [
                'id' => $remainingId,
                'title' => 'More questions',
                'description' => '',
                'questions' => $remaining,
            ];
        }
    }

    $positions = [];
    $position = 0;
    foreach ($sections as $section) {
        foreach ($section['questions'] as $question) {
            $positions[$question['id']] = $position++;
        }
    }

    $conditions = [];
    foreach ($flow['conditions'] as $targetId => $rawCondition) {
        if (!is_string($targetId) || !isset($questionsById[$targetId])) {
            $label = is_scalar($targetId) ? (string) $targetId : '?';
            throw new RuntimeException("Flow condition references unknown question \"{$label}\".");
        }
        if (!is_array($rawCondition)) {
            throw new RuntimeException("Flow condition for \"{$targetId}\" must be an array.");
        }

        $sourceId = $rawCondition['question'] ?? null;
        if (!is_string($sourceId) || !isset($questionsById[$sourceId])) {
            throw new RuntimeException("Flow condition for \"{$targetId}\" needs a valid source \"question\".");
        }
        if (!array_key_exists('equals', $rawCondition)) {
            throw new RuntimeException("Flow condition for \"{$targetId}\" needs an \"equals\" value.");
        }

        $equals = $rawCondition['equals'];
        if (is_array($equals)) {
            if ($equals === []) {
                throw new RuntimeException("Flow condition for \"{$targetId}\": \"equals\" cannot be empty.");
            }
            foreach ($equals as $value) {
                if (!is_scalar($value)) {
                    throw new RuntimeException("Flow condition for \"{$targetId}\": \"equals\" must contain only scalar values.");
                }
            }
        } elseif (!is_scalar($equals)) {
            throw new RuntimeException("Flow condition for \"{$targetId}\": \"equals\" must be a scalar or an array of scalars.");
        }

        if (($positions[$sourceId] ?? -1) >= ($positions[$targetId] ?? -1)) {
            throw new RuntimeException("Flow condition for \"{$targetId}\": \"{$sourceId}\" must appear earlier in the flow.");
        }

        $conditions[$targetId] = [
            'question' => $sourceId,
            'equals' => $equals,
        ];
    }

    return [
        'sections' => $sections,
        'conditions' => $conditions,
        'questions' => $questionsById,
        'positions' => $positions,
    ];
}

function answerForCondition(array $question, $value)
{
    if ($question['type'] === 'choice') {
        if (is_string($value) && ctype_digit($value)) {
            return $question['options'][(int) $value] ?? null;
        }
        return null;
    }

    if ($question['type'] === 'multiple') {
        $selected = [];
        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_string($item) && ctype_digit($item) && isset($question['options'][(int) $item])) {
                    $selected[] = $question['options'][(int) $item];
                }
            }
        }
        return $selected;
    }

    if ($question['type'] === 'confirm') {
        if ($value === '1') {
            return true;
        }
        if ($value === '0') {
            return false;
        }
        return (bool) $question['default'];
    }

    return is_string($value) ? trim($value) : '';
}

function conditionMatches(array $condition, array $sourceQuestion, $sourceValue): bool
{
    $actual = answerForCondition($sourceQuestion, $sourceValue);
    $expectedValues = is_array($condition['equals'])
        ? array_values($condition['equals'])
        : [$condition['equals']];

    foreach ($expectedValues as $expected) {
        if (is_bool($actual)) {
            if (is_bool($expected) && $actual === $expected) {
                return true;
            }
            continue;
        }

        if (is_array($actual)) {
            foreach ($actual as $item) {
                if (strcasecmp(trim((string) $item), trim((string) $expected)) === 0) {
                    return true;
                }
            }
            continue;
        }

        if (strcasecmp(trim((string) $actual), trim((string) $expected)) === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Build the visible section pages for a set of draft values.
 * Hidden follow-ups whose trigger is on the same page are kept in the markup
 * so JavaScript can reveal them without a round trip.
 */
function buildQuestionPages(array $flow, array $values): array
{
    $conditions = $flow['conditions'];
    $questionsById = $flow['questions'];
    $visibility = [];
    $pages = [];

    foreach ($flow['sections'] as $sectionIndex => $section) {
        $sectionQuestionIds = [];
        foreach ($section['questions'] as $question) {
            $sectionQuestionIds[$question['id']] = true;
        }

        $items = [];
        $visibleQuestions = [];

        foreach ($section['questions'] as $question) {
            $id = $question['id'];
            $condition = $conditions[$id] ?? null;
            $isVisible = true;

            if ($condition !== null) {
                $sourceId = $condition['question'];
                $sourceVisible = $visibility[$sourceId] ?? false;
                $isVisible = $sourceVisible
                    && conditionMatches($condition, $questionsById[$sourceId], $values[$sourceId] ?? null);
            }

            $visibility[$id] = $isVisible;
            $sourceOnPage = $condition !== null && isset($sectionQuestionIds[$condition['question']]);

            if (!$isVisible && !$sourceOnPage) {
                continue;
            }

            $items[] = [
                'question' => $question,
                'visible' => $isVisible,
                'condition' => $sourceOnPage ? $condition : null,
                'source_question' => $sourceOnPage ? $questionsById[$condition['question']] : null,
            ];

            if ($isVisible) {
                $visibleQuestions[] = $question;
            }
        }

        if ($visibleQuestions === []) {
            continue;
        }

        $pages[] = [
            'section_index' => $sectionIndex,
            'id' => $section['id'],
            'title' => $section['title'],
            'description' => $section['description'],
            'items' => $items,
            'questions' => $visibleQuestions,
        ];
    }

    return $pages;
}

function findPageIndexById(array $pages, string $sectionId): ?int
{
    foreach ($pages as $index => $page) {
        if ($page['id'] === $sectionId) {
            return $index;
        }
    }

    return null;
}

function resolvePageIndex(array $pages, array $sections, ?string $sectionId): int
{
    if ($pages === []) {
        return 0;
    }

    if ($sectionId !== null) {
        $found = findPageIndexById($pages, $sectionId);
        if ($found !== null) {
            return $found;
        }

        $position = null;
        foreach ($sections as $index => $section) {
            if ($section['id'] === $sectionId) {
                $position = $index;
                break;
            }
        }

        if ($position !== null) {
            foreach ($pages as $index => $page) {
                if ($page['section_index'] >= $position) {
                    return $index;
                }
            }
            return count($pages) - 1;
        }
    }

    return 0;
}

function adjacentPage(array $pages, array $sections, string $currentSectionId, int $direction): ?array
{
    $currentIndex = findPageIndexById($pages, $currentSectionId);
    if ($currentIndex !== null) {
        return $pages[$currentIndex + $direction] ?? null;
    }

    $position = null;
    foreach ($sections as $index => $section) {
        if ($section['id'] === $currentSectionId) {
            $position = $index;
            break;
        }
    }

    if ($position === null) {
        return null;
    }

    if ($direction > 0) {
        foreach ($pages as $page) {
            if ($page['section_index'] > $position) {
                return $page;
            }
        }
        return null;
    }

    for ($index = count($pages) - 1; $index >= 0; $index--) {
        if ($pages[$index]['section_index'] < $position) {
            return $pages[$index];
        }
    }

    return null;
}

function visibleQuestionsFromPages(array $pages): array
{
    $questions = [];
    foreach ($pages as $page) {
        foreach ($page['questions'] as $question) {
            $questions[] = $question;
        }
    }

    return $questions;
}

function findPageIndexForQuestion(array $pages, string $questionId): ?int
{
    foreach ($pages as $index => $page) {
        foreach ($page['questions'] as $question) {
            if ($question['id'] === $questionId) {
                return $index;
            }
        }
    }

    return null;
}

// ---------------------------------------------------------------------------
// Form values and validation
// ---------------------------------------------------------------------------

function initialValues(array $questions): array
{
    $values = [];

    foreach ($questions as $question) {
        $id = $question['id'];

        if ($question['type'] === 'choice') {
            $index = $question['default'] !== null
                ? array_search($question['default'], $question['options'], true)
                : false;
            $values[$id] = $index === false ? '' : (string) $index;
            continue;
        }

        if ($question['type'] === 'multiple') {
            $indexes = [];
            foreach ($question['default'] as $default) {
                $index = array_search($default, $question['options'], true);
                if ($index !== false) {
                    $indexes[] = (string) $index;
                }
            }
            $values[$id] = $indexes;
            continue;
        }

        if ($question['type'] === 'confirm') {
            $values[$id] = $question['default'] ? '1' : '0';
            continue;
        }

        if ($question['type'] === 'number') {
            $values[$id] = $question['default'] === null ? '' : (string) $question['default'];
            continue;
        }

        if ($question['type'] === 'list') {
            $values[$id] = implode("\n", $question['default']);
            continue;
        }

        if ($question['type'] === 'record') {
            $values[$id] = guidedRecordLine($question['default'], $question['fields']);
            continue;
        }

        if ($question['type'] === 'records') {
            $lines = [];
            foreach ($question['default'] as $record) {
                if (!is_array($record)) {
                    continue;
                }
                $line = guidedRecordLine($record, $question['fields']);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
            $values[$id] = implode("\n", $lines);
            continue;
        }

        $values[$id] = $question['default'] ?? '';
    }

    return $values;
}

/**
 * @return array{0: array, 1: array, 2: array} [form values, answers, errors]
 */
function readAnswers(array $questions, array $post): array
{
    $values = [];
    $answers = [];
    $errors = [];

    foreach ($questions as $question) {
        $id = $question['id'];

        if ($question['type'] === 'list') {
            $raw = $post[$id] ?? '';
            $raw = is_string($raw) ? trim(str_replace(["\r\n", "\r"], "\n", $raw)) : '';
            $values[$id] = $raw;
            $items = [];

            if ($raw !== '') {
                foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $items[] = $line;
                    }
                }
            }

            $answers[$id] = $items;
            if ($items === [] && $question['required']) {
                $errors[$id] = 'Please add at least one item.';
            }
            continue;
        }

        if ($question['type'] === 'number') {
            $raw = $post[$id] ?? '';
            $value = is_string($raw) ? trim($raw) : '';
            $values[$id] = $value;

            if ($value === '') {
                $answers[$id] = null;
                if ($question['required']) {
                    $errors[$id] = 'Please enter a number.';
                }
            } elseif (!is_numeric($value)) {
                $answers[$id] = null;
                $errors[$id] = 'Please enter a number.';
            } else {
                $answers[$id] = strpos($value, '.') !== false
                    ? (float) $value
                    : (int) $value;
            }
            continue;
        }

        if ($question['type'] === 'record' || $question['type'] === 'records') {
            $raw = $post[$id] ?? '';
            $raw = is_string($raw) ? trim(str_replace(["\r\n", "\r"], "\n", $raw)) : '';
            $values[$id] = $raw;
            $lines = [];

            if ($raw !== '') {
                foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $lines[] = $line;
                    }
                }
            }

            if ($question['type'] === 'record') {
                if ($lines === []) {
                    $answers[$id] = null;
                    if ($question['required']) {
                        $errors[$id] = 'Please complete this line.';
                    }
                    continue;
                }
                if (count($lines) > 1) {
                    $answers[$id] = null;
                    $errors[$id] = 'Enter this as one line.';
                    continue;
                }

                list($record, $recordError) = parseGuidedRecord($lines[0], $question['fields']);
                $answers[$id] = $record;
                if ($recordError !== null) {
                    $errors[$id] = $recordError;
                }
                continue;
            }

            if ($lines === []) {
                $answers[$id] = [];
                if ($question['required']) {
                    $errors[$id] = 'Please add at least one item.';
                }
                continue;
            }

            $records = [];
            $lineErrors = [];
            foreach ($lines as $index => $line) {
                list($record, $recordError) = parseGuidedRecord($line, $question['fields']);
                if ($recordError !== null) {
                    $lineErrors[] = 'Line ' . ($index + 1) . ': ' . $recordError;
                    continue;
                }
                $records[] = $record;
            }

            $answers[$id] = $records;
            if ($lineErrors !== []) {
                $errors[$id] = implode(' ', $lineErrors);
            }
            continue;
        }

        if ($question['type'] === 'text') {
            $raw = $post[$id] ?? '';
            $value = is_string($raw) ? trim($raw) : '';
            $values[$id] = $value;

            if ($value === '') {
                $answers[$id] = null;
                if ($question['required']) {
                    $errors[$id] = 'Please answer this question.';
                }
            } elseif ($question['pattern'] !== null && preg_match($question['pattern'], $value) !== 1) {
                $answers[$id] = $value;
                $errors[$id] = $question['pattern_error'] ?? 'That answer is not in the expected format.';
            } else {
                $answers[$id] = $value;
            }
            continue;
        }

        if ($question['type'] === 'choice') {
            $raw = $post[$id] ?? '';
            $index = null;

            if (is_string($raw) && ctype_digit($raw)) {
                $candidate = (int) $raw;
                if ($candidate >= 0 && $candidate < count($question['options'])) {
                    $index = $candidate;
                }
            }

            if ($index === null) {
                $values[$id] = '';
                $answers[$id] = null;
                if (is_string($raw) && $raw !== '') {
                    $errors[$id] = 'Please choose one of the available options.';
                } elseif ($question['required']) {
                    $errors[$id] = 'Please choose one of the options.';
                }
            } else {
                $values[$id] = (string) $index;
                $answers[$id] = $question['options'][$index];
            }
            continue;
        }

        if ($question['type'] === 'multiple') {
            $raw = $post[$id] ?? [];
            $selected = [];
            $invalid = false;

            if (is_array($raw)) {
                foreach ($raw as $item) {
                    if (is_string($item) && ctype_digit($item)) {
                        $candidate = (int) $item;
                        if ($candidate >= 0 && $candidate < count($question['options'])) {
                            $selected[$candidate] = true;
                            continue;
                        }
                    }
                    $invalid = true;
                }
            } else {
                $invalid = $raw !== '';
            }

            ksort($selected);
            $indexes = array_keys($selected);
            $values[$id] = array_map('strval', $indexes);
            $answers[$id] = [];
            foreach ($indexes as $index) {
                $answers[$id][] = $question['options'][$index];
            }

            if ($invalid) {
                $errors[$id] = 'Please choose from the available options.';
            } elseif ($indexes === [] && $question['required']) {
                $errors[$id] = 'Please choose at least one option.';
            }
            continue;
        }

        // confirm
        $raw = $post[$id] ?? '';
        if ($raw === '0') {
            $answers[$id] = false;
            $values[$id] = '0';
        } elseif ($raw === '1') {
            $answers[$id] = true;
            $values[$id] = '1';
        } else {
            $answers[$id] = $question['default'];
            $values[$id] = $question['default'] ? '1' : '0';
        }
    }

    return [$values, $answers, $errors];
}

// ---------------------------------------------------------------------------
// Question markup
// ---------------------------------------------------------------------------

function renderQuestion(
    array $question,
    int $number,
    $value,
    ?string $error,
    bool $hidden = false,
    ?array $condition = null,
    ?array $sourceQuestion = null
): string {
    $id = $question['id'];
    $type = $question['type'];
    $fieldId = 'q-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $id);
    $labelId = 'label-' . $fieldId;
    $errorId = 'error-' . $fieldId;
    $hasError = $error !== null;

    $marker = $question['required']
        ? ' <span class="req" aria-hidden="true">*</span><span class="sr-only"> (required)</span>'
        : '';

    $help = $question['help'];
    $fieldGuide = structuredFieldGuide($question);
    if ($fieldGuide !== null) {
        $help = $help === null ? $fieldGuide : trim($help . ' ' . $fieldGuide);
    }

    $wrapperAttributes = ' data-question-id="' . e($id) . '" data-question-type="' . e($type) . '"';
    if ($condition !== null && $sourceQuestion !== null) {
        $expected = is_array($condition['equals'])
            ? array_values($condition['equals'])
            : [$condition['equals']];
        $expectedJson = json_encode($expected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $wrapperAttributes .= ' data-show-if-question="' . e($condition['question']) . '"';
        $wrapperAttributes .= ' data-show-if-values="' . e($expectedJson === false ? '[]' : $expectedJson) . '"';
    }

    $html = '<div class="question' . ($hasError ? ' has-error' : '') . '"' . $wrapperAttributes
        . ($hidden ? ' hidden' : '') . '>';
    $html .= '<span class="q-index" aria-hidden="true">' . str_pad((string) $number, 2, '0', STR_PAD_LEFT) . '</span>';
    $html .= '<div class="q-body">';

    if ($type === 'text' || $type === 'number' || in_array($type, ['list', 'record', 'records'], true)) {
        $attributes = ' id="' . e($fieldId) . '" name="' . e($id) . '"';
        if ($hasError) {
            $attributes .= ' aria-invalid="true" aria-describedby="' . e($errorId) . '"';
        }

        $html .= '<label class="q-prompt" for="' . e($fieldId) . '">' . e($question['prompt']) . $marker . '</label>';
        if ($help !== null) {
            $html .= '<p class="help">' . e($help) . '</p>';
        }

        if ($type === 'number') {
            $html .= '<input class="field" type="number" step="1"' . $attributes . ' value="' . e((string) $value) . '">';
        } elseif ($type === 'text' && !$question['multiline']) {
            $html .= '<input class="field" type="text"' . $attributes . ' value="' . e((string) $value) . '">';
        } else {
            $rows = 4;
            if ($type === 'list') {
                $rows = 6;
            } elseif ($type === 'record') {
                $rows = 3;
            } elseif ($type === 'records') {
                $rows = 8;
            }

            $html .= '<textarea class="field" rows="' . $rows . '"' . $attributes . '>' . e((string) $value) . '</textarea>';
        }
    } else {
        $groupAttributes = ' role="group" aria-labelledby="' . e($labelId) . '"';
        if ($hasError) {
            $groupAttributes .= ' aria-describedby="' . e($errorId) . '"';
        }

        $html .= '<div class="q-prompt" id="' . e($labelId) . '">' . e($question['prompt']) . $marker . '</div>';
        if ($help !== null) {
            $html .= '<p class="help">' . e($help) . '</p>';
        }
        $html .= '<div class="options"' . $groupAttributes . '>';

        if ($type === 'choice') {
            foreach ($question['options'] as $index => $option) {
                $checked = (string) $index === (string) $value ? ' checked' : '';
                $html .= '<label class="option"><input type="radio" name="' . e($id) . '" value="'
                    . $index . '" data-answer-value="' . e($option) . '"' . $checked
                    . '><span>' . e($option) . '</span></label>';
            }
        } elseif ($type === 'multiple') {
            $selected = is_array($value) ? array_map('strval', $value) : [];
            foreach ($question['options'] as $index => $option) {
                $checked = in_array((string) $index, $selected, true) ? ' checked' : '';
                $html .= '<label class="option"><input type="checkbox" name="' . e($id) . '[]" value="'
                    . $index . '" data-answer-value="' . e($option) . '"' . $checked
                    . '><span>' . e($option) . '</span></label>';
            }
        } else {
            foreach (['1' => 'Yes', '0' => 'No'] as $key => $text) {
                $checked = (string) $key === (string) $value ? ' checked' : '';
                $html .= '<label class="option"><input type="radio" name="' . e($id) . '" value="'
                    . $key . '" data-answer-value="' . ($key === '1' ? 'true' : 'false') . '"' . $checked
                    . '><span>' . $text . '</span></label>';
            }
        }

        $html .= '</div>';
    }

    if ($hasError) {
        $html .= '<p class="field-error" id="' . e($errorId) . '" role="alert">' . e($error) . '</p>';
    }

    $html .= '</div></div>';

    return $html;
}

/**
 * @return array{text: string, skipped: bool}
 */
function formatAnswer($value): array
{
    if ($value === null) {
        return ['text' => 'Skipped', 'skipped' => true];
    }
    if (is_bool($value)) {
        return ['text' => $value ? 'Yes' : 'No', 'skipped' => false];
    }
    if (is_array($value)) {
        if ($value === []) {
            return ['text' => 'None selected', 'skipped' => false];
        }

        $isList = array_keys($value) === range(0, count($value) - 1);
        $isFlat = true;
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                $isFlat = false;
                break;
            }
        }

        if ($isList && $isFlat) {
            return ['text' => implode(', ', array_map('strval', $value)), 'skipped' => false];
        }

        $json = json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return [
            'text' => $json === false ? 'Unreadable answer' : $json,
            'skipped' => false,
        ];
    }

    $text = trim((string) $value);

    return $text === ''
        ? ['text' => 'Skipped', 'skipped' => true]
        : ['text' => $text, 'skipped' => false];
}

// ---------------------------------------------------------------------------
// Saving responses
// ---------------------------------------------------------------------------

function resolveResponseDir(string $dir): string
{
    $isAbsolute = preg_match('#^(?:[A-Za-z]:[\\\\/]|[\\\\/])#', $dir) === 1;

    return $isAbsolute ? $dir : __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $dir;
}

function saveResponse(string $dir, array $payload): string
{
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the response directory: ' . $dir);
    }

    $guard = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($guard)) {
        @file_put_contents($guard, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
    }

    $index = $dir . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($index)) {
        @file_put_contents($index, '');
    }

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Could not encode the answers as JSON.');
    }

    $file = $dir . DIRECTORY_SEPARATOR . 'response-' . date('Ymd-His') . '-'
        . bin2hex(random_bytes(3)) . '.json';

    if (@file_put_contents($file, $json . PHP_EOL) === false) {
        throw new RuntimeException('Could not write to the response directory. Check its permissions.');
    }

    return $file;
}
