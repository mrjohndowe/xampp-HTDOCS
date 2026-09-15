<?php

declare(strict_types=1);

$appBasePath = '/conversation';

$config = require dirname(__DIR__) . '/app/config/config.php';

require dirname(__DIR__) . '/app/helpers/helpers.php';

$emojiMap = require dirname(__DIR__) . '/app/data/emojis.php';

$conversationDirectory = dirname(__DIR__) . '/app/data/conversations';

/*
|--------------------------------------------------------------------------
| Automatically discover conversations
|--------------------------------------------------------------------------
*/

$conversationList = getConversationFiles( $conversationDirectory );

/*
|--------------------------------------------------------------------------
| Requested conversation
|--------------------------------------------------------------------------
*/

$conversationId = isset($_GET['c']) ? trim((string) $_GET['c']) : '';

$conversation = null;
$conversationMeta = null;

/*
|--------------------------------------------------------------------------
| Load conversation
|--------------------------------------------------------------------------
*/

if ($conversationId !== '') {

    if (!preg_match( '/^[a-z0-9_-]+$/i', $conversationId)) {

            http_response_code(400);
            exit('Invalid conversation.');
    }

    $conversationFile = $conversationDirectory . DIRECTORY_SEPARATOR . $conversationId . '.php';

    if (!is_file($conversationFile)) {
        http_response_code(404);
        exit('Conversation not found.');
    }

    $conversationData = require $conversationFile;

    /*
    |--------------------------------------------------------------------------
    | Validate conversation file
    |--------------------------------------------------------------------------
    */

    if (!is_array($conversationData) || !isset($conversationData['conversation']) || !is_array($conversationData['conversation']) ) {
        http_response_code(500);
        exit('Invalid conversation file.');
    }

    /*
    |--------------------------------------------------------------------------
    | Load conversation data
    |--------------------------------------------------------------------------
    */

    $conversation = $conversationData['conversation'];

    /*
    |--------------------------------------------------------------------------
    | Make top-level metadata authoritative
    |--------------------------------------------------------------------------
    |
    | The name/avatar at the top of the conversation file determine who
    | the conversation is with.
    */

    if ( !isset($conversation['participants']) || !is_array($conversation['participants'])) {
        $conversation['participants'] = [];
    }

    if (!isset($conversation['participants']['other']) || !is_array($conversation['participants']['other'])) {
        $conversation['participants']['other'] = [];
    }

    $conversation['participants']['other']['id'] = $conversation['participants']['other']['id'] ?? 'other';

    $conversation['participants']['other']['side'] = $conversation['participants']['other']['side'] ?? 'incoming';

    $conversation['participants']['other']['name'] = (string) ( $conversationData['name'] ?? 'Unknown');

    $conversation['participants']['other']['avatar'] = (string) ( $conversationData['avatar'] ?? '/assets/images/default-avatar.png');

    /*
    |--------------------------------------------------------------------------
    | Conversation metadata
    |--------------------------------------------------------------------------
    */

    $conversationMeta = [
        'id' => $conversationId,
        'name' => (string) ($conversationData['name'] ?? $conversationId ),
        'avatar' => (string) ($conversationData['avatar'] ?? '/assets/images/default-avatar.png'),
        'description' => (string) ($conversationData['description'] ?? ''),
    ];
}

/*
|--------------------------------------------------------------------------
| Normalize participant avatar URLs
|--------------------------------------------------------------------------
*/

if ($conversation !== null && isset($conversation['participants']) && is_array($conversation['participants'])) {
    foreach ($conversation['participants'] as &$participant) {
        if (
            !is_array($participant) || !isset($participant['avatar'])) {
            continue;
        }

        $avatar = str_replace('\\', '/', (string) $participant['avatar']);

        if ($avatar !== '' && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $avatar)) {
            /*
             * Avoid adding /conversation twice.
             */
            if ($avatar !== $appBasePath && !str_starts_with( $avatar, $appBasePath . '/' )) {
                $avatar = $appBasePath . '/' . ltrim($avatar, '/');
            }

            $participant['avatar'] = $avatar;
        }
    }

    unset($participant);
}

/*
|--------------------------------------------------------------------------
| Conversation JSON
|--------------------------------------------------------------------------
*/

$conversationJson =
    $conversation !== null
        ? json_encode(
            $conversation,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        )
        : '{}';

/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require dirname(__DIR__)
    . '/app/includes/header.php';

?>

<?php if ($conversation === null): ?>

    <div class="app-shell">

        <section class="phone">
            <div class="phone-frame">

                <div class="dynamic-island"></div>

                <?php require dirname(__DIR__) . '/app/includes/conversation-list.php'; ?>

                <div class="home-indicator"></div>

            </div>
        </section>

    </div>

<?php else: ?>

    <div class="app-shell">

        <?php
        require dirname(__DIR__)
            . '/app/includes/phone.php';
        ?>

    </div>

    <script>
        window.APP_BASE_PATH =
            <?= json_encode(
                $appBasePath,
                JSON_UNESCAPED_SLASHES
            ) ?>;

        window.CONVERSATION_DATA =
            <?= $conversationJson ?: '{}' ?>;

        window.EMOJI_MAP =
            <?= json_encode(
                $emojiMap,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ) ?>;
    </script>

    <?php $jsVersion = filemtime(__DIR__ . '/assets/js/app.js'); ?>

    <script src="/conversation/assets/js/app.js?v=<?= $jsVersion ?>"></script>

<?php endif; ?>

</body>
</html>
