<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

if (current_user() !== null) {
    redirect('php/dashboard.php');
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        csrf_failure();
    }

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if ($email === '' || $password === '') {
        $error = 'Enter your email/username and password.';
    } else {
        $statement = db()->prepare(
            'SELECT id, full_name, password_hash, role, status
             FROM users
             WHERE email = ? OR username = ?
             LIMIT 1'
        );
        $statement->bind_param('ss', $email, $email);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (
            $user === null
            || $user['status'] !== 'active'
            || !password_verify($password, $user['password_hash'])
        ) {
            audit_log(null, 'auth.login_failed', 'user', null, 'Invalid credentials or inactive account.');
            $error = 'The credentials were not accepted.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            $userId = (int) $user['id'];
            $update = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
            $update->bind_param('i', $userId);
            $update->execute();
            $update->close();
            audit_log($userId, 'auth.login', 'user', $userId, 'Successful login.');

            if (isset($_POST['remember'])) {
                $selector = bin2hex(random_bytes(12));
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
                $insert = db()->prepare(
                    'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
                     VALUES (?, ?, ?, ?)'
                );
                $insert->bind_param('isss', $userId, $selector, $tokenHash, $expiresAt);
                $insert->execute();
                $insert->close();
                set_remember_cookie($selector . ':' . $token, strtotime($expiresAt));
            }

            redirect('php/dashboard.php');
        }
    }
}

render_header('Sign in', 'login');
?>
<section>
    <h2>StudentHub sign in</h2>
    <?php if ($error !== ''): ?><p class="error-message show" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="<?= e(app_url('php/login.php')) ?>">
        <?= csrf_field() ?>
        <label for="email">Email or username</label>
        <input id="email" name="email" autocomplete="username" required value="<?= e($email) ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <div class="checkbox-group">
            <input id="remember" name="remember" type="checkbox" value="1">
            <label for="remember">Remember me on this device for 30 days</label>
        </div>
        <button type="submit">Sign in</button>
    </form>
    <p>New student? <a href="<?= e(app_url('php/register-account.php')) ?>">Create an account</a>.</p>
</section>
<?php render_footer(); ?>
