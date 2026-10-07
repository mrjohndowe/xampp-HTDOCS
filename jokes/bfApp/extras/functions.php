<?php
declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalizeApplicantName(string $name): string
{
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    return function_exists('mb_strtolower')
        ? mb_strtolower($name, 'UTF-8')
        : strtolower($name);
}

function appDatabase(): PDO
{
    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';

    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the data directory.');
    }

    $db = new PDO(
        'sqlite:' . $directory . DIRECTORY_SEPARATOR . 'boyfriend_applications.sqlite',
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $db->exec('
        CREATE TABLE IF NOT EXISTS applications (
            applicant_key TEXT PRIMARY KEY,
            applicant_name TEXT NOT NULL,
            age TEXT NOT NULL,
            form_data TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
    ');

    return $db;
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function renderField(array $field): void
{
    [$type, $name, $label] = $field;
    $id = 'field_' . $name;

    echo '<div class="question">';
    echo '<label class="question-label" for="' . h($id) . '">' . h($label) . '</label>';

    if ($type === 'text') {
        echo '<input class="line-input" id="' . h($id) . '" type="text" name="' . h($name) . '" autocomplete="off">';
    } elseif ($type === 'textarea') {
        echo '<textarea class="line-input textarea" id="' . h($id) . '" name="' . h($name) . '" rows="2"></textarea>';
    } elseif ($type === 'radio' || $type === 'checkboxes') {
        $inputType = $type === 'radio' ? 'radio' : 'checkbox';
        $inputName = $type === 'radio' ? $name : $name . '[]';

        echo '<div class="choices">';
        foreach ($field[3] as $index => $option) {
            $optionId = $id . '_' . $index;
            echo '<label class="choice" for="' . h($optionId) . '">';
            echo '<input id="' . h($optionId) . '" type="' . $inputType . '" name="' . h($inputName) . '" value="' . h($option) . '">';
            echo '<span class="control-mark" aria-hidden="true"></span>';
            echo '<span>' . h($option) . '</span></label>';
        }
        echo '</div>';
    }

    echo '</div>';
}
