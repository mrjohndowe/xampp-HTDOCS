# Test report — Vehicle Tracker 1.0.0

Executed during delivery on November 19, 2026 America/Denver (November 20 UTC).

## Environment actually used

- Linux sandbox, PHP CLI/development server **8.4.28**.
- PDO SQLite and PDO MySQL enabled; GD enabled.
- **MariaDB 11.8.6** local disposable test database, InnoDB.
- Python **3.13.18**, tkinter running under Xvfb for the real GUI smoke test.
- Playwright Chromium headless, 1440×1000 and 390×844 viewport checks.
- No application database, tokens, administrator account or private configuration is included in the source ZIP.

These are not claims of testing Windows XAMPP, Android hardware, Apache restart behavior, a physical printer or an actual GitHub-controlled release. The installer persistence test restarts the PHP server process against the same private directory, exercising the persistent lock/config guards rather than an Apache service.

## Passed executable checks

### HTTP integration — SQLite: 8/8 test methods passed
### HTTP integration — MariaDB: 8/8 test methods passed

`tests/integration.py` exercises:

1. First-run SQLite/MySQL installation, actual private `config.lock`, GET/POST installer denial, missing-lock + existing-config guard, unauthenticated rejection, valid/invalid bearer token behavior.
2. Driver creation/training/deactivation/attribution, idempotent 300-mile trip, **25,300 engine miles** from the example baseline, one linked **$600** invoice expense, **$200** payment → **$400** debt, overpayment rejection, full/partial/full **30 US MPG**, client total override → **$17.00**, fuel edit → recalculated 20 MPG, partial-fill deletion → recalculated 60 MPG, invalid gallons/prices/dates/odometer/trip overlap rejection, engine retirement freeze and replacement accumulation.
3. Incomplete inspection draft, correct Monday/next-week dates, missing results and missing finding notes rejected, explicit acknowledgment, successful immutable completion, independently persisted issue, follow-up resolution preserving the original finding, reason-linked draft revision.
4. Incomplete sale draft, **$4,500** balance, incomplete finalization rejected, valid finalized locked snapshot, direct edit rejected, explicit handwritten-signing confirmation required, Signed metadata without changing price, and a revision preserving the original.
5. CSRF rejection and optimistic-version conflict rejection.
6. Real GD-decoded PNG upload, upload retry idempotency, authenticated photo delivery, unauthorized photo rejection and fake JPEG rejection.
7. Schema-1-style unassigned trip and incomplete legacy fuel fixture upgraded to schema 2 without losing records/amounts or changing the lock; missing fuel detail remains MPG-ineligible.
8. PHP server restart followed by persisted GET/POST setup denial.

Every SQLite run uses a new disposable private directory/ephemeral port; the MySQL runs used a freshly recreated disposable database. The same test file supports both engines.

### PHP units — 28/28 checks passed

`php tests/unit.php` checks semantic 1.10.0 versus 1.9.0, stable tag parsing, prerelease/draft/invalid release filtering, no stable-release case, numeric and date edge rejection, leap date validity, actual catalog counts, bundled Dodge Dart, absence of Dart/F-150 under Ford and per-model permitted type labels.

### Browser GUI — passed

`python tests/browser_smoke.py` launches a fresh temporary website and real Chromium:

- Logs in through the actual browser form.
- Finds Dodge Dart from bundled local data; changing to Ford clears model immediately and removes Dodge suggestions.
- Creates/saves an incomplete weekly inspection draft with no preselected results.
- Uses a 390-pixel-wide sheet, fills the checklist, records a tire finding/note, sees the immediate finding count, acknowledges and submits, then verifies the completed form is locked.
- Saves a bill draft, checks the live 5,000 − 500 balance and document preview.
- Toggles dark theme, reloads and verifies persistence.
- **No uncaught JavaScript page errors.**

### Python GUI — passed

`xvfb-run -a python tests/gui_smoke.py` creates the real tkinter application, authenticates against a disposable PHP website, opens every shared record form (including custom sale templates), navigates every section, verifies blank inspection results and vehicle-odometer prefill, and verifies live agreement balance. No tkinter callback exceptions were observed.

The desktop client keeps requests on worker threads and dispatches completion through the Tk event loop; the smoke check does not simulate every possible packet-loss/concurrent-edit sequence.

### Syntax — passed

- `php -l` for every submitted PHP file.
- `node --check public/app.js`.
- Python compilation for desktop, tools and tests.

## Material gaps / unverified acceptance items

- **Catalog completeness is not achieved.** The provided-in-text 1,889/11,948 snapshot was not attached, and the direct vPIC run was blocked. The real bundled fallback contains **379 makes / 7,543 models**, with exact provenance in CATALOG.md. Type-label checks and Dodge/Ford tests do not establish worldwide coverage or independent source-classification accuracy.
- Windows XAMPP installation and restart, NTFS ACLs, Apache `.htaccess` enforcement and native Windows DPAPI were not executed here. Their implementation/instructions need Windows acceptance testing.
- Chromium's Android-sized viewport was tested, not a physical Android device.
- Browser/desktop HTML print layouts and print CSS are implemented; no physical printer or PDF comparison/visual multi-page regression suite was run. PDF creation requires the browser/OS print-to-PDF workflow.
- GitHub stable-release selection was unit tested. A configured live/controlled repository with equal/older/newer assets, outages and release dismissal was not end-to-end exercised against GitHub. Public-repository settings, cache, error handling and explicit download prompts are implemented; private repositories are unsupported.
- Arbitrary third-party database upgrades are not implemented; migrations cover only this application's documented generic-record schema. The version-1 fixture test checks preservation within that schema.
- Initial-currency support uses two decimal currency precision; zero-/three-decimal currencies require an extension before claiming currency-wide precision support.
- No legal title-transfer template certification, automatic fleet-compliance determination, electronic-signature system, offline desktop database or role-based per-driver access is claimed.

## Reproduce

See README.md. Optional UI commands after installing test dependencies:

```text
python -m pip install requests playwright
python -m playwright install chromium
python tests/browser_smoke.py
python tests/gui_smoke.py
```

Linux headless GUI testing needs Xvfb; browser Linux CI may need `playwright install-deps chromium`. Test packages are not application runtime dependencies.
