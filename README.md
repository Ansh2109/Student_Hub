# StudentHub

StudentHub is a responsive campus portal built with HTML, CSS, JavaScript, PHP and MySQL. It provides campus information, student accounts, events, profiles and administrator tools in one web application.

## Features

- Responsive website for desktop and mobile devices
- Student registration, login and logout
- Session-based authentication and profile management
- Campus event listing and event management
- Administrator dashboard with student and event management
- JSON data loading with search, filtering, sorting and pagination
- Client-side form validation and CSRF protection
- Dark mode and responsive navigation

## Technologies

- HTML5
- CSS3
- JavaScript
- PHP
- MySQL
- JSON and Fetch API
- Apache through XAMPP
- Git and GitHub

## Project Structure

```text
Student_Hub/
├── css/          Stylesheets
├── data/         JSON data files
├── database/     Database schema and migrations
├── docs/         Planning documents and wireframe
├── js/           JavaScript files and modules
├── pages/        Public HTML pages
├── php/          PHP modules, admin pages and API endpoints
├── storage/      Registration storage
├── uploads/      Event poster uploads
├── index.html    Static homepage
├── index.php     PHP homepage
└── README.md     Project documentation
```

## Run Locally

1. Place the project in the XAMPP `htdocs` directory.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
4. Import `database/schema.sql`.
5. Open `http://localhost/xampp/Student_Hub/` in a browser.

## Live Website

[Open StudentHub](http://studenthub-25aiml043.infinityfree.io/)

StudentHub is permanently hosted on InfinityFree. The website can be accessed online without running XAMPP, Cloudflare Tunnel or the local computer.

## GitHub Repository

[StudentHub Source Code](https://github.com/Ansh2109/Student_Hub)

## Security Notes

- Passwords use secure password hashing.
- Database operations use prepared statements.
- Protected forms use CSRF validation.
- Administrator pages require an authenticated administrator account.
- Store hosting database credentials only in `php/includes/config.local.php`.
- Never upload or commit `config.local.php` to GitHub.
