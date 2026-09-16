<?php

declare(strict_types=1);

function renderMessage(
    array $participant,
    string $message
): void {
    $side = $participant['side'] ?? 'incoming';
    ?>

    <div class="message-row <?= e($side) ?>">

        <?php if ($side === 'incoming'): ?>

            <img
                class="message-avatar"
                src="<?= e($participant['avatar'] ?? '') ?>"
                alt=""
            >

        <?php endif; ?>

        <div class="message-column">

            <div class="sender-name">
                <?= e($participant['name'] ?? '') ?>
            </div>

            <div class="message-bubble">
                <?= formatMessage($message) ?>
            </div>

        </div>

    </div>

    <?php
}
