<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use the sign-out button to end your session.');
}
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    csrf_failure();
}

$user = current_user();
if (isset($_COOKIE['studenthub_remember'])) {
    $parts = explode(':', (string) $_COOKIE['studenthub_remember'], 2);
    if (isset($parts[0]) && preg_match('/^[a-f0-9]{24}$/', $parts[0])) {
        $delete = db()->prepare('DELETE FROM remember_tokens WHERE selector = ?');
        $delete->bind_param('s', $parts[0]);
        $delete->execute();
        $delete->close();
    }
}
set_remember_cookie('', time() - 3600);
if ($user !== null) {
    audit_log($user['id'], 'auth.logout', 'user', $user['id'], 'User signed out.');
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $parameters = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $parameters['path'], '', $parameters['secure'], $parameters['httponly']);
}
session_destroy();
redirect('php/login.php');
