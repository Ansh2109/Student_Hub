# StudentHub

StudentHub is a responsive student portal built incrementally for the Web Technology practicals. It includes semantic HTML, accessible forms, JavaScript interactions, JSON data rendering, PHP form handling, MySQL-backed accounts, role-based pages, CRUD tools, and a JSON API.

## Requirements

- XAMPP with Apache, PHP 8.2+ and MySQL/MariaDB running
- PHP extensions `mysqli` and `fileinfo`
- A modern browser

## Run locally with XAMPP

1. Keep the project under `A:\xamp\htdocs\xampp\Student_Hub` (or another Apache document-root folder).
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/`, select **Import**, and import [`database/schema.sql`](./database/schema.sql). This creates the `studenthub` database, tables, three sample events, and 20 sample student records. The seed inserts are safe to run again.
   If upgrading an older database, run the matching migration files in order: [`database/migrations/001_student_profile_fields.sql`](./database/migrations/001_student_profile_fields.sql) adds profile fields and [`database/migrations/002_student_id.sql`](./database/migrations/002_student_id.sql) adds the optional unique Student ID. Do not run a migration that is already reflected in your schema.
4. The default local connection uses host `127.0.0.1`, database `studenthub`, user `root`, an empty password, and port `3306`, as in a default XAMPP installation. For a customized MySQL setup, provide `STUDENTHUB_DB_HOST`, `STUDENTHUB_DB_NAME`, `STUDENTHUB_DB_USER`, `STUDENTHUB_DB_PASSWORD`, and optionally `STUDENTHUB_DB_PORT` to Apache/PHP. Shared hosting can instead use a server-only `php/includes/config.local.php` file copied from `config.local.php.example`. Do not commit credentials.
5. Visit `http://localhost/xampp/Student_Hub/`. The PHP homepage adapts its actions to the signed-in state; use Home from the site navigation to return there.

The primary registration form at `pages/register.html` creates a database-backed student account. The separate Practical 7 form at `php/register-csv.php` stores validated profile fields in `storage/registrations.csv`. The directory is denied by Apache so the CSV cannot be downloaded directly. Passwords are never written to that CSV.

Database-backed account registration is available from the login page or at `http://localhost/xampp/Student_Hub/php/register-account.php`. Accounts have the `student` role by default. To grant administrator access to an account you own, first register it, then run this in phpMyAdmin:

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'your-account@example.com';
```

Sign out and sign back in for the role change to appear in the session menu. Do not use a shared or publicly exposed account as an administrator.

## Project layout

```text
Student_Hub/
├── index.php               Session-aware StudentHub homepage
├── css/                    Shared styles and responsive rules
├── data/                   Practical 6 JSON datasets
├── database/
│   └── schema.sql          Database, tables and seed data
├── docs/
│   ├── practical-1-planning.md  Scope, roles, sitemap and key concepts
│   └── wireframe.svg       Low-fidelity home-page wireframe
├── js/
│   ├── modules/            Fetch and rendering modules
│   ├── campus-data.js      Search, filter, sort and pagination
│   ├── script.js           Shared interactions and saved theme
│   └── student-api.js      Fetch-based asynchronous CRUD
├── pages/                  Static semantic HTML practical pages
├── php/
│   ├── admin/              Admin-only CRUD, dashboard tools and audit log
│   ├── api/                JSON API endpoints
│   ├── includes/           Session, database, CSRF and shared navigation
│   ├── about.php           Public about page using shared navigation
│   ├── home.php            Dynamic homepage and upcoming events
│   ├── profile.php         Signed-in student's database-backed profile
│   └── ...                 Registration, login, logout and public pages
├── storage/                Protected CSV form submissions
└── uploads/events/         Validated event poster images
```

## Database and security notes

- All application SQL that includes user input uses MySQLi prepared statements.
- Account passwords use `password_hash()` and are checked with `password_verify()`.
- Login rotates the session ID; sessions time out after 30 minutes of inactivity. Optional remember-me uses a random cookie token whose hash is stored in the database and rotated on use.
- POST forms and state-changing API requests validate a session CSRF token.
- Admin pages and API operations enforce the `admin` role on the server.
- Student and event values are validated on the server and escaped on output. Event poster uploads accept JPG, PNG and WebP images up to 2 MB, use random file names, and block script execution in the upload folder.
- Admin student/event/account actions are recorded in `audit_logs`.
- Keep production credentials outside source control. Use HTTPS and a least-privilege database account when deploying beyond local XAMPP.

The ER relationships are: `users` has an optional one-to-one `students` profile; `users` and `events` each have one-to-many relationships with `registrations`; an event optionally references its creating user; `remember_tokens` belong to a user; and `audit_logs` optionally reference the acting user. Foreign keys enforce these relationships and cascade or null references where appropriate.

## Practical coverage

- **Practical 1:** project scope, user roles, navigation flow, sitemap, low-fidelity wireframe, project structure, README and GitHub repository.
- **Practicals 2–3:** semantic HTML pages, accessible structure and responsive CSS.
- **Practical 4:** DOM interactions, menu, modal, slider, notification and theme control.
- **Practical 5:** browser-side registration validation.
- **Practical 6:** `pages/data.html` fetches events, students and FAQs from JSON; each file has 15 records, with search, category/department filtering, sorting, loading/error messages and pagination.
- **Practical 7:** `php/register-csv.php` and `php/register.php` validate POST data and write a locked CSV record.
- **Practical 8:** normalized MySQL schema and shared MySQLi connection.
- **Practical 9:** unique email/username account registration and password hashing.
- **Practical 10:** login/logout, session ID regeneration, role checks, timeout and optional remember-me token.
- **Practical 11:** admin student create/read/update/delete, search/filter, pagination and CSV export.
- **Practical 12:** admin event CRUD, category/open-closed status and validated poster upload.
- **Practical 13:** `php/api/students.php` provides JSON GET/POST/PUT/DELETE operations consumed asynchronously by the student API page.
- **Practical 14:** role-based admin dashboard, dynamic navigation, database counts and audit log.
- **Practical 15:** this setup guide, local deployment notes and test checklist below.

Practical 1 planning details and the wireframe are available in [`docs/practical-1-planning.md`](./docs/practical-1-planning.md) and [`docs/wireframe.svg`](./docs/wireframe.svg).

## Verification checklist

1. **Theme/navigation:** toggle dark mode, move to another page, reload, and confirm the preference remains; test the menu with keyboard and touch/mobile width.
2. **Practical 6:** switch among Events, Students and FAQs; test empty/non-empty search, filter, A–Z/Z–A sort and Previous/Next pagination.
3. **Practical 7:** submit valid and invalid registration values; confirm errors are visible and `storage/registrations.csv` receives one row only for valid input.
4. **Practical 9:** create a student account, try the same email and username again, and confirm the duplicate is rejected. Verify the stored password is a hash, not plaintext.
5. **Practical 10:** test a valid and invalid login, sign out, access the dashboard without signing in, use Remember Me, and wait 30 minutes to test timeout.
6. **Practical 11:** as an admin, add/edit/delete a student, search by name/email/course, filter department, navigate pages and export CSV.
7. **Practical 12:** add/edit/delete events; upload valid posters, then test an unsupported file and a file over 2 MB.
8. **Practical 13:** use the API student manager to list/search/add/edit/delete without a page reload; inspect JSON responses and HTTP status codes in browser developer tools.
9. **Practical 14:** verify a student cannot access admin routes, an administrator sees live counts/latest accounts, and actions appear in the audit log.
10. **Accessibility/security:** check labels, keyboard focus, skip link, status/error announcements, mobile layout, prepared SQL, CSRF rejections and upload-folder protections.

## Deployment

Local deployment uses Apache from the XAMPP document root. For a local Apache virtual host, point `DocumentRoot` to the project folder, grant access to that directory, enable `.htaccess` overrides for the protected storage/upload rules, and restart Apache. Update the database environment variables for the target instance. The application is not configured for direct public deployment; set HTTPS, secure database credentials, backups and production error logging before exposing it to a network.
