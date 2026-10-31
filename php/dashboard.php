<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
require_login();

$user = current_user();
render_header('Dashboard', 'dashboard');
?>
<section>
    <h2>Welcome, <?= e($user['name']) ?></h2>
    <p>Your StudentHub account role is <strong><?= e(ucfirst($user['role'])) ?></strong>.</p>
</section>
<?php if ($user['role'] === 'admin'): ?>
    <?php
    $connection = db();
    $studentCount = (int) $connection->query('SELECT COUNT(*) AS total FROM students')->fetch_assoc()['total'];
    $eventCount = (int) $connection->query('SELECT COUNT(*) AS total FROM events')->fetch_assoc()['total'];
    $userCount = (int) $connection->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'];
    $latest = $connection->query(
        'SELECT full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 8'
    )->fetch_all(MYSQLI_ASSOC);
    ?>
    <section>
        <h2>Administrator overview</h2>
        <div class="dashboard-grid">
            <article class="card"><h3>Students</h3><p class="big-number"><?= $studentCount ?></p></article>
            <article class="card"><h3>Events</h3><p class="big-number"><?= $eventCount ?></p></article>
            <article class="card"><h3>Accounts</h3><p class="big-number"><?= $userCount ?></p></article>
        </div>
        <p>
            <a class="btn" href="<?= e(app_url('php/admin/students.php')) ?>">Manage students</a>
            <a class="btn" href="<?= e(app_url('php/admin/events.php')) ?>">Manage events</a>
            <a class="btn" href="<?= e(app_url('php/admin/audit.php')) ?>">View audit log</a>
        </p>
    </section>
    <section>
        <h2>Latest registrations</h2>
        <?php if ($latest === []): ?>
            <p>No accounts have registered yet.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <caption>Most recent StudentHub accounts</caption>
                    <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Registered</th></tr></thead>
                    <tbody><?php foreach ($latest as $row): ?>
                        <tr><td><?= e($row['full_name']) ?></td><td><?= e($row['email']) ?></td><td><?= e($row['created_at']) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php else: ?>
    <?php
    $statement = db()->prepare(
        'SELECT course, year, department, city FROM students WHERE user_id = ? LIMIT 1'
    );
    $statement->bind_param('i', $user['id']);
    $statement->execute();
    $student = $statement->get_result()->fetch_assoc();
    $statement->close();
    ?>
    <section>
        <h2>Your academic summary</h2>
        <?php if ($student === null): ?>
            <p>Your profile is not available yet. Contact StudentHub support for assistance.</p>
        <?php else: ?>
            <dl>
                <dt>Course</dt><dd><?= e($student['course']) ?></dd>
                <dt>Year</dt><dd><?= e($student['year']) ?></dd>
                <dt>Department</dt><dd><?= e($student['department']) ?></dd>
                <dt>City</dt><dd><?= e($student['city']) ?></dd>
            </dl>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php render_footer(); ?>
