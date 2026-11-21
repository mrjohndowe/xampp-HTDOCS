# Delivery Game

An Android-friendly, offline-capable progressive web app for John and Sierra. No build step or dependencies.

## Install on Android

1. Upload the contents of this folder to a PHP-enabled HTTPS host. Keep the files together.
2. Open the hosted URL in Chrome on Android while online.
3. Open Chrome's menu and choose **Add to home screen**, then **Install**. The app can run offline once it finishes loading.

For a desktop preview, run `php -S localhost:8000` in this folder and open http://localhost:8000. Use HTTPS for the Android install.

## Play

Check **We're safely parked**, tap any penalties or bonuses in **Rules & scoring**, then tap **Complete delivery (+10)**. Completing a delivery gives the driver 10 points and locks scoring again. John starts, and drivers switch every three completed deliveries. Both start at 150. Never penalize necessary emergency braking.

Open **Shift report** for the order history, score edits, the Delivery Champion standings and reset. **Undo** reverses the last score or completed delivery.

Existing shifts from version 2 keep their order and scoring history. Their totals are recalculated from 150 each. Use one device as the shared scoresheet.

