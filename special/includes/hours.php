<?php declare(strict_types=1);

date_default_timezone_set('America/Denver');

$morningTime = 8;
$eveningTime = 23;

$now = new DateTimeImmutable('now');
$todayOpens = $now->setTime($morningTime, 0, 0);
$todayCloses = $now->setTime($eveningTime, 0, 0);

/*
 * The site is available from:
 *
 * 8:00:00 AM through 10:59:59 PM
 */
$isOpen = $now >= $todayOpens && $now < $todayCloses;

if ($isOpen) {
    /*
     * Return control to index.php.
     * No redirect is performed.
     */
    return;
}

/*
 * Before 8:00 AM, the next opening is later today.
 * At or after 11:00 PM, the next opening is tomorrow.
 */
$nextOpening = $now < $todayOpens
    ? $todayOpens
    : $todayOpens->modify('+1 day');

http_response_code(403);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="theme-color"
        content="#232a49"
    >

    <title>Our Three Little Stars — Resting</title>

    <link
        rel="stylesheet"
        href="/assets/css/closed.css?v=1"
    >
</head>

<body>
    <div class="stars" aria-hidden="true"></div>

    <main class="closed-card">
        <div class="moon" aria-hidden="true">☾</div>

        <p class="eyebrow">
            Our three little stars are resting
        </p>

        <h1>
            The story is
            <em>sleeping.</em>
        </h1>

        <p class="message">
            This little corner of the world rests at night.
            Come back when the morning light arrives and
            the story will be ready for you again.
        </p>

        <p class="countdown-label">
            The story opens in
        </p>

        <div
            class="countdown"
            id="countdown"
            aria-live="polite"
        >
            <div class="time-part">
                <strong id="hours">00</strong>
                <span>Hours</span>
            </div>

            <div class="time-part">
                <strong id="minutes">00</strong>
                <span>Minutes</span>
            </div>

            <div class="time-part">
                <strong id="seconds">00</strong>
                <span>Seconds</span>
            </div>
        </div>

        <div class="divider"></div>

        <p class="hours">
            Open every day from 8:00 AM until 11:00 PM ✦
        </p>

        <p class="current-time">
            Current local time:
            <span id="currentTime">
                <?= htmlspecialchars(
                    $now->format('g:i:s A'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </p>
    </main>

    <script>
        const openingTimestamp =
            <?= json_encode(
                $nextOpening->getTimestamp() * 1000,
                JSON_THROW_ON_ERROR
            ) ?>;

        const hoursElement =
            document.getElementById('hours');

        const minutesElement =
            document.getElementById('minutes');

        const secondsElement =
            document.getElementById('seconds');

        const currentTimeElement =
            document.getElementById('currentTime');

        function twoDigits(value) {
            return String(value).padStart(2, '0');
        }

        function updateCountdown() {
            const now = new Date();

            const remaining =
                openingTimestamp - now.getTime();

            currentTimeElement.textContent =
                now.toLocaleTimeString('en-US', {
                    timeZone: 'America/Denver',
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit'
                });

            if (remaining <= 0) {
                /*
                 * Reload the same address at opening time.
                 * This is not a redirect.
                 */
                window.location.reload();
                return;
            }

            const totalSeconds =
                Math.floor(remaining / 1000);

            const hours =
                Math.floor(totalSeconds / 3600);

            const minutes =
                Math.floor((totalSeconds % 3600) / 60);

            const seconds =
                totalSeconds % 60;

            hoursElement.textContent =
                twoDigits(hours);

            minutesElement.textContent =
                twoDigits(minutes);

            secondsElement.textContent =
                twoDigits(seconds);
        }

        updateCountdown();

        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
<?php

/*
 * Prevent index.php from continuing and loading the kids site.
 */
exit;
