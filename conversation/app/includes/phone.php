<?php

$participants = $conversation['participants'];

$other = $participants['other'];
$me = $participants['me'];
error_log('PHONE LOADED FROM: ' . __FILE__);
?>

<section class="phone">
    <div class="phone-frame">

        <div class="dynamic-island"></div>

        <header class="conversation-header">

            <a class="header-button back-button" href="<?= e($appBasePath . '/') ?>" aria-label="Back to conversations">‹</a>

            <div class="contact">

                <label class="contact-avatar-wrap" title="Change avatar">

                    <img id="contact-avatar" class="contact-avatar" src="<?= e($other['avatar']) ?>" alt="">

                    <input id="avatar-upload" type="file" accept="image/*" hidden>

                </label>

                <div class="contact-copy">

                    <strong id="contact-name"><?= e($other['name']) ?></strong>

                    <span class="contact-status">Text Message</span>

                </div>

            </div>

            <button class="header-button info-button" type="button" aria-label="Information">ⓘ</button>

        </header>

        <main id="messages" class="messages" aria-live="polite"></main>

        <?php require __DIR__ . '/composer.php'; ?>

        <div class="home-indicator"></div>

    </div>
</section>
