<header class="conversation-header conversation-list-header">

    <div class="conversation-list-heading">Messages</div>

</header>

<main class="conversation-list-screen">

    <div class="conversation-list-title">
        <h1>Messages</h1>
    </div>

    <?php if (!$conversationList): ?>

        <div class="empty-conversations">No conversations found.</div>

    <?php else: ?>

        <div class="conversation-list">

            <?php foreach ($conversationList as $item): ?>

                <?php
                $avatar = (string) ($item['avatar'] ?? '/assets/images/avatar/default-avatar.png');

                if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $avatar)) {
                    $avatar = $appBasePath . '/' . ltrim($avatar, '/');
                }
                ?>

                <a class="conversation-card" href="<?= e($appBasePath . '/?c=' . urlencode($item['id'])) ?>">

                    <img class="conversation-card-avatar" src="<?= e($avatar) ?>" alt="">

                    <div class="conversation-card-content">

                        <div class="conversation-card-top">

                            <strong><?= e($item['name']) ?></strong>

                            <span class="conversation-arrow">›</span>

                        </div>

                        <?php if (($item['description'] ?? '') !== ''): ?>

                            <div class="conversation-preview">
                                <?= e($item['description']) ?>
                            </div>

                        <?php endif; ?>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>
