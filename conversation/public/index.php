<?php

    declare (strict_types = 1);
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
    $conversation     = null;
    $conversationMeta = null;

    /*
|--------------------------------------------------------------------------
| Load conversation
|--------------------------------------------------------------------------
*/

    if ($conversationId !== '') {
    if (
        ! preg_match(
            '/^[a-z0-9_-]+$/i',
            $conversationId
        )
    ) {
        http_response_code(400);

        exit(
            'Invalid conversation.'
        );
    }

    $conversationFile =
        $conversationDirectory
        . DIRECTORY_SEPARATOR
        . $conversationId
        . '.php';

    if (! is_file($conversationFile)) {
        http_response_code(404);

        exit(
            'Conversation not found.'
        );
    }

    $conversationData =
    require $conversationFile;

    if (
        ! is_array($conversationData)
        ||
        ! isset(
            $conversationData['conversation']
        )
    ) {
        http_response_code(500);

        exit(
            'Invalid conversation file.'
        );
    }

    $conversation =
        $conversationData['conversation'];

    $conversationMeta = [
        'id'          => $conversationId,

        'name'        =>
        (string) (
            $conversationData['name'] ?? $conversationId
        ),

        'avatar'      =>
        (string) (
            $conversationData['avatar'] ?? '/assets/images/default-avatar.png'
        ),

        'description' =>
        (string) (
            $conversationData['description'] ?? ''
        ),
    ];
    }

    /*
|--------------------------------------------------------------------------
| Normalize participant avatar URLs
|--------------------------------------------------------------------------
*/

    if ($conversation !== null) {
    foreach ( $conversation['participants'] as &$participant ) {
        if (
            ! isset(
                $participant['avatar']
            )
        ) {
            continue;
        }

        $avatar =
            str_replace(
            '//',
            '/',
            (string)
            $participant['avatar']
        );

        if (
            $avatar !== ''
            &&
            ! preg_match(
                '#^[a-z][a-z0-9+.-]*://#i',
                $avatar
            )
        ) {
            $participant['avatar'] =
            $appBasePath
            . '/'
            . ltrim(
                $avatar,
                '/'
            );
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
    JSON_UNESCAPED_UNICODE
     |
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

    <?php
        require dirname(__DIR__)
            . '/app/includes/conversation-list.php';
    ?>

<?php else: ?>

    <div class="app-shell">

        <?php
            require dirname(__DIR__)
                . '/app/includes/phone.php';
        ?>

    </div>

    <script>
        window.APP_BASE_PATH =
            <?php echo json_encode(
    $appBasePath,
    JSON_UNESCAPED_SLASHES
) ?>;

        window.CONVERSATION_DATA =
            <?php echo $conversationJson ?: '{}' ?>;

        window.EMOJI_MAP =
            <?php echo json_encode(
    $emojiMap,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;
    </script>

    <script
        src="/conversation/assets/js/app.js">
    </script>

<?php endif; ?>

</body>
</html>
