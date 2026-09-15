 <div class = "conversation-library" >
    <header class  = "library-header" >
        <h1>Conversations</h1>
            <p>Choose Who You Want to Open .</p>
    </header>
<?php
    if (! $conversationList) : ?>

        <div class="empty-conversations">

            No conversations found.

        </div>

    <?php else: ?>

        <div class="conversation-list">

            <?php
                foreach (
                    $conversationList as $item
                ):
            ?>

                <?php
                    $avatar =
                        $item['avatar'];

                    if (
                        ! preg_match(
                            '#^[a-z][a-z0-9+.-]*://#i',
                            $avatar
                        )
                    ) {
                        $avatar = $appBasePath . '/' . ltrim( $avatar, '/' );
                    }
                ?>

                <a
                    class="conversation-card"
                    href="<?php echo e(
    $appBasePath
    . '/?c='
    . urlencode(
        $item['id']
    )
) ?>"
                >

                    <img
                        class="conversation-card-avatar"
                        src="<?php echo e($avatar) ?>"
                        alt=""
                    >

                    <div
                        class="conversation-card-content"
                    >

                        <div
                            class="conversation-card-top" >

                            <strong>
                                <?php echo e( $item['name'] ) ?>
                            </strong>

                            <span class="conversation-arrow" >› </span>

                        </div>

                        <?php
                            if (
                                $item['description']
                                !== ''
                            ):
                        ?>

                            <p>
                                <?php echo e(
    $item[
        'description'
    ]
) ?>
                            </p>

                        <?php endif; ?>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>
