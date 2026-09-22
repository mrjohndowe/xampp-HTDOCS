<?php
$settings = [
    'discord' => 'cMUCpBuGyM',
    'community' => 'Dowe Game Services',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $settings['community'] ?> | Developer Community Invite</title>
    <link rel="stylesheet" href="assets/css/styles.css" />
</head>

<body>

    <div class="invite-card">
        <div class="header">
            <div class="server-icon">&lt;/&gt;</div>
            <h1 class="server-title"><?= $settings['community'] ?></h1>
            <p class="server-subtitle"><strong><?= $settings['community'] ?></strong> — your new HQ for chill convoys, space builds, eco utopias, and open-road legends.

            </p>
        </div>

        <!-- Active Community Events -->
        <div class="events-section">
            <div class="section-title">📅 Upcoming Community Events</div>

            <!-- Event 1 -->
            <div class="event-card">
                <div class="event-header">
                    <span class="event-title">🎙️ Post-Election Review & Planning</span>
                    <span class="event-tag">Stage Event</span>
                </div>
                <p class="event-details">
                    Share your feedback on the recent server election and help plan the rules, roles, and setup for the next one!
                </p>
                <span class="event-time">🗓️ Sep 29th, 2026 • 5:00 PM</span>
            </div>

            <!-- Event 2 -->
            <div class="event-card">
                <div class="event-header">
                    <span class="event-title">⚔️ SoloLearn Coding Challenge</span>
                    <span class="event-tag">Weekly</span>
                </div>
                <p class="event-details">
                    Test your Python and Web Development skills in 1v1 speed battles and earn XP on the server leaderboard!
                </p>
                <span class="event-time">💬 Active in #sololearn-challenge</span>
            </div>
        </div>

        <a href="https://discord.gg/<?= $settings['discord'] ?>" target="_blank" class="join-btn">
            Accept Invite & Join Event
        </a>

        <p class="footer-text">Powered by Discord &bull; ><?= $settings['community'] ?></p>
    </div>

</body>

</html>
