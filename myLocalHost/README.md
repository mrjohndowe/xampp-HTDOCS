# myLocalHost Dashboard v0.2

This package is a repaired dashboard baseline. It deliberately uses the `assets/js` module architecture that exists in this archive; no unused `app/` PHP architecture is included or required.

## Included widgets

- Git Repositories
- Machine
- System Status

## Repair summary

- Removed imports and registrations for the absent Activity and Projects widgets.
- Restored the `Api.action()` method to the `Api` class.
- Passed the action service into `GitWidget` so repository buttons bind correctly.
- Made the MySQL status probe safe when the `mysqli` extension is unavailable.
- Added the missing Git widget styles.

## Run locally

Place the `myLocalHost` folder in the Apache document root and browse to its `index.php`. The API scans the active document root, so repositories must be direct child folders of that document root to appear in the Git card.

The endpoint action buttons open local programs through PHP. They are designed for a trusted local-only development dashboard and should not be exposed publicly.
