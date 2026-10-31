# Practical 1: Project Planning

## Problem scope

StudentHub is a responsive student portal for discovering campus information and events, creating a student account, and managing student and event records. Public visitors can browse general information and JSON-backed content. Signed-in students can access their dashboard and event information. Administrators can manage student and event records and review the audit log.

### User roles

| Role | Main capabilities |
| --- | --- |
| Visitor | View Home, About, Events, FAQ, Contact, Feedback and JSON Data; register or sign in. |
| Student | Use the authenticated dashboard and view student-facing portal information. |
| Administrator | Use the dashboard, manage students and events, use the student API tools, export student data and review audit history. |

### Key modules

- Public information: Home, About, Events, FAQ, Contact and Feedback.
- Accounts: registration, login, logout and session-backed dashboard.
- Student records: registration workflow, administrator CRUD, CSV export and JSON API.
- Event management: public event listing and administrator CRUD with optional poster upload.
- Shared UI: responsive navigation, accessibility affordances and persistent light/dark theme.
- Data and support: Practical 6 JSON datasets, search/filter/sort/pagination and audit log.

## Sitemap and navigation flow

```text
StudentHub
├── Home (index.html)
│   ├── About (pages/about.html)
│   ├── Events (pages/events.html -> php/events.php)
│   ├── FAQ (pages/faq.html)
│   ├── Contact (pages/contact.html)
│   ├── Feedback (pages/feedback.html)
│   ├── JSON Data (pages/data.html)
│   ├── Register (pages/register.html -> php/register-account.php)
│   └── Login (pages/login.html -> php/login.php)
│       └── Authenticated Dashboard (php/dashboard.php)
│           ├── Student: dashboard and event information
│           └── Administrator
│               ├── Student Records (php/admin/students.php)
│               ├── Student API (php/admin/api-students.php)
│               ├── Event Management (php/admin/events.php)
│               ├── Audit Log (php/admin/audit.php)
│               └── Student CSV Export (php/admin/students-export.php)
├── Profile (pages/profile.html; static practical page)
└── Practical 7 CSV Registration (php/register-csv.php)
```

Visitors navigate through the shared header and page links. Registration and login submit to PHP routes; successful authentication leads to the protected dashboard. The server checks the role before serving administrator routes. Sign-out is a protected POST action and returns the visitor to the public experience. The legacy static dashboard/admin pages redirect to their protected PHP destinations.

## Low-fidelity wireframe

The SVG is a scalable, low-fidelity desktop home-page layout. It shows the shared header/navigation, notification, sidebar, hero actions, featured content and footer. Open [`wireframe.svg`](./wireframe.svg) in a browser or design tool to view it.

## Key questions

1. **What is a URL?** A Uniform Resource Locator identifies how and where to retrieve a resource. For `https://example.edu/students?year=2#list`, the scheme is `https`, host is `example.edu`, path is `/students`, query is `year=2`, and fragment is `list`. A port may follow the host, for example `:8080`.
2. **How does a browser process HTML?** The browser requests the document, parses HTML into the DOM, discovers and loads linked resources such as CSS, JavaScript and images, applies CSS to form the render tree, calculates layout, and paints pixels. JavaScript can update the DOM and trigger rendering again.
3. **How is page navigation managed?** Ordinary links use relative URLs between static pages. Forms and authenticated operations use PHP endpoints; protected routes enforce session and role checks. Shared navigation provides the common entry points, while redirects preserve the intended route flow.
4. **How are GitHub commits maintained?** Keep changes focused on one practical or coherent feature, use descriptive commit messages (for example, `Practical 6: add JSON filtering and pagination`), review the diff, run relevant checks, then commit and push. Do not commit credentials, generated uploads, or private data.

## Project structure

The source is organized by responsibility: `pages/` and `index.html` for the HTML UI, `css/` for styles, `js/` for interactions, `data/` for JSON fixtures, `php/` for server routes and shared helpers, `database/` for schema and migrations, `storage/` for protected CSV submissions, and `uploads/` for event images.
