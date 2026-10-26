# Anger Management Fillable PHP App

This app displays all pages of the original worksheets plus seven newly added PDFs as their original page images. Use the On-page tool selector to place either text boxes or checkmark boxes directly over a worksheet page. Click a page to place an item, drag it to reposition it, type into text boxes, toggle checkmarks, and use × to remove an item. Overlay items autosave to the database and print over the original page. Workbook questions open a saved answer box in a popup. Text boxes and checkmarks can still be placed over page images, and each page also has an additional notes field.

## Included new sections
- Anger Coping Strategies (1 page)
- What Is Anger (1 page)
- Getting Along and Keeping Cool — Therapist Manual (79 pages)
- Self Help for Anger (28 pages)
- Anger Management & Conflict Resolution Workbook (IMH) (38 pages)
- Anger Management Workbook (SWMPT) (48 pages)
- Anger Management for Substance Use and Mental Health Clients (SAMHSA) (64 pages)

The original PDFs are preserved in `assets/`, and their page images are stored in `assets/pages/` for on-page annotation and printing.

## Requirements
- PHP 8.1+ with PDO and PDO_SQLITE enabled (SQLite is the default)
- A web server with write permission to this directory so it can create `data/anger_records.sqlite`

## Run locally
From this directory:
```bash
php -S 127.0.0.1:8080
```
Open http://127.0.0.1:8080

## Features
- On-page overlay tools for all 307 pages across the original worksheets and seven added PDFs: choose text box or checkmark box
- Click to place overlay items, drag to move, and remove with ×
- Popup answer boxes for questions in workbook PDFs, saved with the record\n- Optional response area under each page
- Automatic save after a short pause and a Save now button
- History dropdown and clickable history list showing name, date and worksheet
- Reload and edit saved records
- Print original pages with saved text boxes and checkmarks overlaid, plus page-level responses
- Auto-calculated Anger Styles total/average (10 ratings, 1–5) and Dirty Fighting total (5 ratings, 0–3)
- Reload and edit saved page-level responses from earlier records

## MySQL option
Create a database and table permissions for the app's database user, then set environment variables:
```bash
DB_DSN='mysql:host=127.0.0.1;dbname=anger_records;charset=utf8mb4' DB_USER='your_user' DB_PASS='your_password' php -S 127.0.0.1:8080
```
The app detects the PDO driver and creates a compatible schema for SQLite or MySQL automatically.

## Deployment/security notes
- Put the app behind HTTPS and restrict access to authorized staff. It currently has no login/roles, CSRF protection or audit log; add these before using it with confidential client records.
- Back up `data/anger_records.sqlite` regularly.
- On-page text boxes and checkmarks are saved with percentage-based coordinates relative to each page image, so their positions scale with the page. They are not pre-mapped to each original blank line; use the on-page tool to place them where needed.
