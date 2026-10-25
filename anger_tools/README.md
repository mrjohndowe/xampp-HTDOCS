# Anger Tools — Full Website Revamp

## What this is
A replacement website entry point with one shared layout, stylesheet, and interaction layer. It replaces the routed legacy templates rather than trying to hide their conflicting styles. Includes dashboard, sign-in, registration, guided reflection, monthly logs, full log history, public monthly logs, authorized detail view, POST sign-out, and the old budget route's intensity demo. That old route was a slider demo, not an actual budgeting tool; no budgeting functionality is invented.

## Install on a staging copy first
1. Back up the entire application and database.
2. Keep your existing extra/ directory (connection, helper functions, existing database settings) and storage/database files. Do not replace the database or account data.
3. Copy the included index.php to the application root, replacing the old index.php. Add assets/site.css, assets/site.js, templates/log-table.php, and the included api/anger_data.php compatibility endpoint.
4. The old pages and styles are no longer included by the new router. Do not include extra/header.php or extra/footer.php from this new index: the new entry point renders its own complete document.
5. Check ?p=login, ?p=register, ?p=home, ?p=treatment, ?p=anger, ?p=allLogs, ?p=publicanger, ?p=viewLog&id=VALID_ID, and ?p=budget. Old ?p=logout links are replaced with POST sign-out.
6. Test against the actual schema, deployed PHP version, and connection session behavior. The original users and anger table columns are retained; the anger timestamp default is still required, as in the original application. PHP 7.4+ is needed (a supported PHP release is recommended). No migration or dependencies are included.

## One design system
Every route uses the same header, sidebar, page headings, content width, spacing, buttons, panels, table styles, form fields, alerts, empty states, footer, and dark/light tokens. The palette retains #140a0c, #211719, #e5484d and #b3383e. Narrow screens use a collapsible menu. Focus rings, reduced-motion styling, table overflow, and print styling are included. Theme preference alone is stored in localStorage; reflection answers are not.

## Reflection behavior
Six written answers, intensity, and visibility are required. The final question requires an explicit Private/Public choice; neither choice defaults on. Review/Edit, Back navigation, and server validation are included. New entries preserve UTF-8 text with a plain:v1 keycode marker. Both private and public historical entries with a matching legacy salt are decoded in the replacement detail reader and API. Binary encoding was not encryption; new private entries are not encrypted either. Private means owner-only at the supplied page/API authorization layer, not inaccessible to administrators.

## Security-related improvements and boundaries
- Allowlisted routing replaces arbitrary page-path inclusion.
- Login/registration run before HTML output; successful actions redirect with HTTP 303.
- Passwords are hashed and verified through the existing password hash flow. Login rotates the session id. Registration validates all fields. No password is redisplayed.
- Every write uses a session CSRF token. Visibility updates use POST, require ownership, and confirm public sharing in the browser. Client-side confirmation is an interaction aid; server CSRF/ownership checks enforce authorization.
- The unified detail query authorizes owner OR explicitly public; it handles missing rows and escapes every displayed answer and name.
- Replaces api/anger_data.php with an owner/public checked, escaped-by-consumer JSON compatibility endpoint. Its stats use actual month and dates rather than grouping day numbers across months. No JavaScript chart/CDN dependency is required.
- New detail page displays public UTF-8 content literally rather than interpolating it into modal HTML.

This is not a completed production security audit or a claim of regulatory/clinical compliance. Still review server TLS, login throttling, cookie settings, account recovery, database/backups access, authorization in any other custom endpoints, and data-retention/consent requirements. In particular, the repository includes extra/pma database administration files; restrict or remove such administration tools from public deployment. Do not expose database files, backups, ZIP archives, or configuration files through the web server. Existing PHP files not routed by this index remain on disk; review direct access and remove unused legacy scripts after backup/testing. The patch cannot secure unmodified custom endpoints.

## Dashboard and log semantics
Monthly stats use the current server calendar month; daily trend shows the most recent 14 calendar days with data, not fabricated values. Days without entries are distinguished in the accessible values list. The old server/database timezone must be checked for consistency. The unified public page remains a signed-in, current-month listing, matching the existing app's access pattern. All-my-logs includes the owner's entire history. Each list has in-page search; pagination is not yet implemented, so large datasets need a pagination pass before scale-up.

## Preview
Open Anger-Tools-Full-Site-Preview.html in a browser. Navigation includes overview, guided form, monthly/history/public lists, a sample detail, and account screens. The preview is self-contained, uses fictional records, and never saves, transmits, or publishes form content. Demo login and registration do not authenticate or create accounts. Production sign-in remains connected to the existing database. Do not enter real client data into a design preview.

## Acceptance checklist
- Existing account sign-in succeeds; invalid credentials fail; registration validates and does not redisplay passwords.
- Sign-out requires POST and the session token.
- Dashboard count, intensity averages, daily data, and history agree with known database records.
- Every reflection question blocks empty/whitespace answers; intensity and sharing have no preset option.
- Back/Edit retain answers. Final review handles punctuation, accents, emoji, and HTML-looking strings as literal text.
- Save Private/Public, refresh, and confirm persisted values and correct legacy/new content decoding.
- A second signed-in user cannot read or change another user's private entry via page or API; public entries can be read.
- Missing ids, nonnumeric ids, unknown routes, missing CSRF, arrays in form fields, signed-out requests, and unauthorized visibility updates are rejected.
- Public-sharing updates display a confirmation; cancel sends no update.
- Check all pages on desktop and narrow screens in both themes, with keyboard and a screen reader.
- Test database failure and expired-session behavior. Browser back/refresh can lose unsaved answers; no autosave is claimed.

## Verification status
Packaging and static syntax validation where the local interpreters were available. No access to your live hosting, running database, browser automation, or client records was used. No deployment or full integration testing was performed. Complete the staging checklist before rollout.
