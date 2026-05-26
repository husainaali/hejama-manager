# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- **Backend:** PHP with PDO (MySQL) — no framework, no Composer
- **Frontend:** Vanilla HTML5/CSS3/JavaScript — no build step, no bundler, no npm
- **Database:** MySQL hosted on Hostinger
- **UI:** Font Awesome 6.4.0, Google Fonts (Inter), CSS custom properties for theming

## Project Structure

| File/Dir | Purpose |
|---|---|
| `api.php` | All backend API endpoints (GET/POST, JSON responses) |
| `includes/db_connect.php` | PDO connection — **contains hardcoded credentials, do not expose** |
| `database/schema.sql` | Canonical DB schema |
| `setup_db.php` | One-time DB initializer — should not exist in production |
| `upload.sh` | FTP deployment script to Hostinger — contains FTP credentials |
| `style.css` | Global styles with CSS variables (`--primary`, `--secondary`, `--accent`) |
| `app.js` | Main dashboard JS logic |

## Deployment

Deploy to Hostinger via FTP:
```bash
bash upload.sh
```
This uploads changed files directly to the live server. There is no staging environment.

## Database

- Schema lives in `database/schema.sql` — update it when adding/changing tables
- Tables: `patients`, `medical_history`, `appointments`, `specialists`
- All queries in `api.php` must use PDO prepared statements (already the pattern)
- To re-initialize the DB, load `setup_db.php` in a browser, then remove or protect the file

## Security Gotchas

- DB credentials are hardcoded in `includes/db_connect.php` — avoid logging or exposing this file
- FTP credentials are in `upload.sh` — do not commit changes that add new plaintext secrets
- `setup_db.php` should be deleted or access-restricted after initial setup
- No authentication system exists yet — the app is accessible to anyone with the URL

## Coding Conventions

- PHP: PDO prepared statements for all DB queries (no raw string interpolation into SQL)
- JS: Vanilla ES6+, no imports/modules (plain `<script>` tags)
- CSS: Extend the existing CSS variable system (`--primary`, `--secondary`, etc.) for new colors/values
- Arabic/RTL: The UI uses `dir="rtl"` where applicable; maintain RTL-compatible layout when adding UI

## No Build / No Tests

There is no build step, test runner, or linter configured. After making changes, verify by loading the relevant HTML page in a browser and checking the browser console for errors.
