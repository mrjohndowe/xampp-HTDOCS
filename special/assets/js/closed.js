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