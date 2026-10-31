<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', '0');
require_once __DIR__ . '/db.php';

$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$phpPath = strpos($scriptPath, '/php');
$basePath = $phpPath === false ? $scriptPath : substr($scriptPath, 0, $phpPath);
define('APP_BASE_URL', rtrim($basePath, '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('SESSION_TIMEOUT_SECONDS', 1800);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => APP_BASE_URL . '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (
    isset($_SESSION['user_id'], $_SESSION['last_activity'])
    && time() - (int) $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS
) {
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Your session expired. Please sign in again.',
    ];
}

if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path): string
{
    return APP_BASE_URL . '/' . ltrim($path, '/');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_failure(): never
{
    http_response_code(403);
    exit('The request could not be verified. Reload the page and try again.');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($message) ? $message : null;
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path), true, 303);
    exit;
}

function current_user(): ?array
{
    return isset($_SESSION['user_id'])
        ? [
            'id' => (int) $_SESSION['user_id'],
            'name' => (string) ($_SESSION['user_name'] ?? ''),
            'role' => (string) ($_SESSION['user_role'] ?? 'student'),
        ]
        : null;
}

function require_login(): void
{
    $user = current_user();
    if ($user === null) {
        flash('error', 'Please sign in to continue.');
        redirect('php/login.php');
    }

    $statement = db()->prepare(
        'SELECT full_name, role, status FROM users WHERE id = ? LIMIT 1'
    );
    $statement->bind_param('i', $user['id']);
    $statement->execute();
    $account = $statement->get_result()->fetch_assoc();
    $statement->close();
    if ($account === null || $account['status'] !== 'active') {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('error', 'This account is unavailable. Please contact an administrator.');
        redirect('php/login.php');
    }

    $_SESSION['user_name'] = $account['full_name'];
    $_SESSION['user_role'] = $account['role'];
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo 'Access denied. Administrator privileges are required.';
        exit;
    }
}

function set_remember_cookie(string $value, int $expires): void
{
    setcookie('studenthub_remember', $value, [
        'expires' => $expires,
        'path' => APP_BASE_URL . '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function restore_remembered_login(): void
{
    if (current_user() !== null || !isset($_COOKIE['studenthub_remember'])) {
        return;
    }

    $parts = explode(':', (string) $_COOKIE['studenthub_remember'], 2);
    if (count($parts) !== 2 || !preg_match('/^[a-f0-9]{24}$/', $parts[0]) || !preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
        set_remember_cookie('', time() - 3600);
        return;
    }

    $statement = db()->prepare(
        'SELECT t.id, t.user_id, t.token_hash, u.full_name, u.role
         FROM remember_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.selector = ? AND t.expires_at > NOW() AND u.status = "active"
         LIMIT 1'
    );
    $statement->bind_param('s', $parts[0]);
    $statement->execute();
    $remembered = $statement->get_result()->fetch_assoc();
    $statement->close();

    if ($remembered === null || !hash_equals($remembered['token_hash'], hash('sha256', $parts[1]))) {
        $delete = db()->prepare('DELETE FROM remember_tokens WHERE selector = ?');
        $delete->bind_param('s', $parts[0]);
        $delete->execute();
        $delete->close();
        set_remember_cookie('', time() - 3600);
        return;
    }

    $userId = (int) $remembered['user_id'];
    $selector = bin2hex(random_bytes(12));
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
    $delete = db()->prepare('DELETE FROM remember_tokens WHERE id = ?');
    $delete->bind_param('i', $remembered['id']);
    $delete->execute();
    $delete->close();
    $insert = db()->prepare(
        'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)'
    );
    $insert->bind_param('isss', $userId, $selector, $tokenHash, $expires);
    $insert->execute();
    $insert->close();

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $remembered['full_name'];
    $_SESSION['user_role'] = $remembered['role'];
    $_SESSION['last_activity'] = time();
    set_remember_cookie($selector . ':' . $token, strtotime($expires));
}

function audit_log(
    ?int $userId,
    string $action,
    string $entityType,
    ?int $entityId = null,
    ?string $details = null
): void {
    $statement = db()->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $statement->bind_param('ississ', $userId, $action, $entityType, $entityId, $details, $ip);
    $statement->execute();
    $statement->close();
}

function render_header(string $title, string $active = ''): void
{
    $user = current_user();
    $isAdmin = $user !== null && $user['role'] === 'admin';
    $flashMessage = take_flash();
    $flashClass = $flashMessage !== null && $flashMessage['type'] === 'success'
        ? 'success-message'
        : 'error-message';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> | StudentHub</title>
        <link rel="stylesheet" href="<?= e(app_url('css/style.css?v=20261031-about')) ?>">
        <link rel="stylesheet" href="<?= e(app_url('css/responsive.css?v=20261031-about')) ?>">
    </head>
    <body>
    <a class="skip-link" href="#mainContent">Skip to content</a>
    <header>
        <div class="header-content">
        <a class="brand" href="<?= e(app_url('index.php')) ?>">StudentHub</a>
            <button id="menuToggle" class="menu-toggle" aria-label="Open navigation menu">☰</button>
            <nav id="mainNav" aria-label="Main Navigation">
                <ul>
                <li><a href="<?= e(app_url('index.php')) ?>" <?= $active === 'home' ? 'aria-current="page"' : '' ?>>Home</a></li>
                <li><a href="<?= e(app_url('php/events.php')) ?>" <?= $active === 'events' ? 'aria-current="page"' : '' ?>>Events</a></li>
                <li class="nav-dropdown">
                    <details>
                        <summary>Explore</summary>
                        <ul class="nav-submenu">
                            <li><a href="<?= e(app_url('php/about.php')) ?>">About</a></li>
                            <li><a href="<?= e(app_url('pages/faq.html')) ?>">FAQ</a></li>
                            <li><a href="<?= e(app_url('pages/contact.html')) ?>">Contact</a></li>
                            <li><a href="<?= e(app_url('pages/feedback.html')) ?>">Feedback</a></li>
                            <li><a href="<?= e(app_url('pages/data.html')) ?>">Campus data</a></li>
                        </ul>
                    </details>
                </li>
                <?php if ($user !== null): ?>
                    <li><a href="<?= e(app_url('php/dashboard.php')) ?>" <?= $active === 'dashboard' ? 'aria-current="page"' : '' ?>>Dashboard</a></li>
                    <li><a href="<?= e(app_url('php/profile.php')) ?>" <?= $active === 'profile' ? 'aria-current="page"' : '' ?>>My profile</a></li>
                    <?php if ($isAdmin): ?>
                        <li class="nav-dropdown">
                            <details>
                                <summary>Manage</summary>
                                <ul class="nav-submenu">
                                    <li><a href="<?= e(app_url('php/admin/students.php')) ?>">Students</a></li>
                                    <li><a href="<?= e(app_url('php/admin/api-students.php')) ?>">Student tools</a></li>
                                    <li><a href="<?= e(app_url('php/admin/events.php')) ?>">Events</a></li>
                                    <li><a href="<?= e(app_url('php/admin/audit.php')) ?>">Audit log</a></li>
                                </ul>
                            </details>
                        </li>
                    <?php endif; ?>
                    <li class="nav-signout">
                        <form action="<?= e(app_url('php/logout.php')) ?>" method="post">
                            <?= csrf_field() ?>
                            <button type="submit">Sign out</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li><a href="<?= e(app_url('pages/register.html')) ?>">Register</a></li>
                    <li><a href="<?= e(app_url('php/login.php')) ?>" <?= $active === 'login' ? 'aria-current="page"' : '' ?>>Sign in</a></li>
                <?php endif; ?>
            </ul>
        </nav>
            <button id="themeToggle" class="theme-toggle" aria-pressed="false">🌙 Dark Mode</button>
        </div>
    </header>
    <main id="mainContent">
        <?php if ($flashMessage !== null): ?>
            <p class="<?= e($flashClass) ?> show" role="status"><?= e($flashMessage['message']) ?></p>
        <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    <footer><p>&copy; <?= date('Y') ?> StudentHub. All Rights Reserved.</p></footer>
    <script src="<?= e(app_url('js/script.js?v=20261031')) ?>"></script>
    </body>
    </html>
    <?php
}

restore_remembered_login();
