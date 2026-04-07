# ConcertHelper

This folder is the separate ConcertHelper app for the McMaster Concert Band project. The group site links here, but app pages have their own PHP includes and stylesheet.

Implemented now:

- `login.php` authenticates a configured email/password account and routes admins to `admin-dashboard.php` and members to `member-dashboard.php`.
- `members.php` loads public ensemble member data from the `members` table.
- `index.php`, `concerts.php`, `member-dashboard.php`, and `admin-dashboard.php` currently show TODO messages for teammates to fill in later.

Ready for teammate expansion:

- `member-dashboard.php` - add member parts and recordings.
- `admin-dashboard.php` - add management forms.

Database notes:

- `includes/connect.php` uses the provided PDO connection with environment variable overrides.
- Import the repo-root `final_project.sql` file into `graydj1_db` before testing login.
- `includes/app.php` reads login accounts from the `users` table. `final_project.sql` seeds two demo logins so routing works now:
  - `admin@mcmaster.ca` / `concerthelper`
  - `macid1@mcmaster.ca` / `concerthelper`
- Member images should be uploaded to `assets/uploads/members/`, with only the generated file name stored in `members.file_name`.
