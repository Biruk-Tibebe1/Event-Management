# EthioEvents

EthioEvents is a lightweight PHP/MySQL web application for discovering and managing cultural events and cinema showings in Ethiopia. It combines organizer tools, cinema manager dashboards, public event listings, and booking capabilities into a small, easy-to-run codebase designed for local development (XAMPP) and simple deployments.

## Key Features

- Event discovery, creation, editing and publishing (organizer dashboard)
- Cinema management (multiple locations per manager, movie listings, ratings)
- Ticket bookings for events and movies with basic seat tracking
- Simple admin panel and seeded admin account support
- File uploads for posters and media (stored under `assets/uploads/`)
- Support for refunds and support tickets (schema included)
- Automatic cleanup of past events (once-per-day cleanup hook)

## Changelog

- 2026-06-11: Workspace cleanup and UI updates
  - Removed runtime/temp files and `.bak` backups from the repository.
  - Consolidated SQL migration scripts into `sql/schema.sql` and removed individual migration files.
  - Added `cinema_managers` mapping table and ensured manager→cinema mappings are persisted when creating locations.
  - Added `movies.rating` and `movies.imdb_url` support and preserved rating UI in manager flows.
  - Replaced the interactive map picker with a single `coords` input and added an "Open in Google Maps" option.
  - Moved the `FAM.jpg` image below the "Technology & Security" section and updated its caption to "Like a Family".
  - Removed the "Heritage Sites We Celebrate" section from `about.php`.
  - Hidden the header `Login` and `Register` buttons visually while keeping the pages accessible by URL.
  - Kept tools like `tools/create_admin.php` and `ajax/validate_registration.php` in place (not removed).

Note: For existing installations, prefer running targeted `ALTER TABLE` migrations rather than re-importing `schema.sql` to avoid data loss.

## Tech Stack & Architecture

- PHP (procedural) with PDO for database access
- MySQL / MariaDB (schema is provided in `sql/schema.sql`)
- Vanilla JavaScript for small interactions (no frontend framework)
- Static assets (CSS, JS, images) under `assets/`

This project favors tiny, readable PHP scripts, aiming for straightforward maintainability over framework complexity.

## Repository Layout (important files)

- `config/database.php` — database connection settings and constants
- `includes/` — shared templates and helper functions (see `includes/functions.php`)
- `cinema/` — cinema manager pages and public cinema listing
- `organizer/` — organizer dashboard and event CRUD
- `events/`, `admin/`, `ajax/`, `tools/` — supporting pages and utilities
- `assets/` — images, CSS, JS, and upload folders
- `sql/schema.sql` — authoritative consolidated schema (run this to create DB)
- `sql/sample_data.sql` — optional sample data for development
- `sql/seed_admin.sql` — seed/insert an initial admin user (replace the password placeholder first)

## Quick Start (local development on Windows + XAMPP)

1. Place the repository into your XAMPP `htdocs` folder (for example `C:\xampp\htdocs\evertsphere`).
2. Start Apache and MySQL via the XAMPP control panel.
3. Edit `config/database.php` and set your DB credentials (`DB_USER`, `DB_PASS`, `DB_HOST`) and `BASE_URL` if needed.
4. Create the database and tables by importing the consolidated schema:

```powershell
mysql -u root -p < sql/schema.sql
```

The `schema.sql` file contains `CREATE DATABASE` and `USE` statements, so importing without specifying a database works.

5. (Optional) Load sample data and admin seed:

```powershell
mysql -u root -p ethioevents < sql/sample_data.sql
mysql -u root -p ethioevents < sql/seed_admin.sql
```

6. Open your browser and visit `http://localhost/evertsphere/` (or the `BASE_URL` you configured).

## Admin / Tools

- Use `tools/create_admin.php` and `tools/check_admin.php` for quick admin management if needed.
- Alternatively edit `sql/seed_admin.sql` to insert a seeded admin (generate a bcrypt hash using PHP before importing).

## Database Notes

- The canonical schema is `sql/schema.sql`. During cleanup the project merged migration files into this single schema file — it is the authoritative starting point for a fresh database.
- The schema includes support for:
  - `cinema_managers` mapping table (allows multiple cinemas per manager)
  - `movies.rating` and `movies.imdb_url`
  - `support_tickets`, `refunds`, `venues`, and `cities`

If you are migrating an existing database, apply only the relevant `ALTER TABLE` statements instead of re-importing schema which may overwrite data.

## Recent Developer Notes

- The cinema manager UI now accepts coordinates as a single `coords` input (no map picker), and an "Open in Google Maps" option redirects to the provided coordinates.
- The project added a session-backed fallback so newly created cinemas appear in a manager's list even when the schema lacks a `manager_id` column.
- Movie `rating` support was added — run the schema to persist ratings.
- The app contains a once-per-day auto-cleanup hook for past events (triggered from `includes/header.php` / `includes/functions.php`).

## Cleanup Performed

- Temporary/runtime files and `.bak` backups were removed from the workspace to keep the repo clean.
- Migration files were consolidated into `sql/schema.sql`. Remaining SQL files are `sql/sample_data.sql` and `sql/seed_admin.sql`.

## Troubleshooting & Common Issues

- "Headers already sent" warnings: check for stray whitespace or `echo`/`print` before headers, and ensure `includes/header.php` is included early. The project enables output buffering in `includes/header.php` as a safety net.
- Database connection failures: update `config/database.php` with correct credentials and ensure MySQL is running.
- File upload problems: ensure `assets/uploads/` and subfolders are writable by the webserver user.

## Contributing / Development

If you plan to modify the app, follow these guidelines:

- Work on feature branches and test locally with the bundled schema and sample data.
- For DB changes, prefer updating `sql/schema.sql` and keep it as the single source of truth.
- Keep frontend JS minimal and avoid adding large third-party frameworks unless necessary.

---

If you want, I can:

- open the `README.md` in the editor for tweaks,
- run a static scan to ensure no code references deleted SQL files,
- or create a small `docs/` page with architecture diagrams.

---
_Generated/updated by automated workspace cleanup and consolidation._
