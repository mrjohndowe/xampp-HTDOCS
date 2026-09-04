APPLICATION FORM PHP APP

Requirements: PHP 8+ with PDO SQLite enabled.

Install:
1. Copy this folder into your web root, e.g. C:\xampp\htdocs\piece_of_ass_app\
2. Open http://localhost/piece_of_ass_app/
3. The database is created automatically at data/applications.sqlite.

Features:
- Form recreated from the supplied image.
- Autosaves about 700ms after changes.
- Saved records can be searched by name and selected from the dropdown.
- New button starts a fresh record.

Security note: The source image contains an S.S. Number field. If this is exposed beyond localhost, add authentication, HTTPS, access controls, and appropriate encryption/data-retention safeguards before storing real sensitive information.
