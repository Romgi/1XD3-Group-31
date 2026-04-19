# ConcertHelper

This folder contains the PHP app for the McMaster Concert Band project.

## Structure

- `index.php`, `members.php`, `concerts.php`, `login.php`, `member-dashboard.php`, `admin-dashboard.php`: public app pages and dashboards.
- `actions/`: admin-only form handlers for concerts, parts, recordings, members, assignments, and password updates.
- `includes/`: shared app bootstrap, database connection, header, and footer files.
- `assets/css/`: app stylesheet.
- `assets/js/`: browser JavaScript for admin form submission.
- `assets/images/`: app images.
- `assets/uploads/`: uploaded part files and performance media.
- `sql/legacy_member_examples.sql`: older reference SQL kept for archive purposes.

## Database

- Import `../database/concerthelper_schema.sql` into your MySQL database before testing the app.
- `includes/connect.php` supports `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variable overrides.
- The import file seeds two demo logins:
  - `admin@mcmaster.ca` / `concerthelper`
  - `macid1@mcmaster.ca` / `concerthelper`

## Notes

- `login.php` authenticates against the `users` table and routes admins to `admin-dashboard.php` and members to `member-dashboard.php`.
- `admin-dashboard.php` includes password management for existing user accounts.
