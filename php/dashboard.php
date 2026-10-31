<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
require_login();

$user = current_user();
$connection = db();

if ($user['role'] === 'admin') {
    $studentCount = (int) $connection->query('SELECT COUNT(*) AS total FROM students')->fetch_assoc()['total'];
    $eventCount = (int) $connection->query('SELECT COUNT(*) AS total FROM events WHERE status = "open"')->fetch_assoc()['total'];
    $userCount = (int) $connection->query('SELECT COUNT(*) AS total FROM users WHERE status = "active"')->fetch_assoc()['total'];
    $auditCount = (int) $connection->query('SELECT COUNT(*) AS total FROM audit_logs')->fetch_assoc()['total'];
    $latest = $connection->query('SELECT full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
} else {
    $statement = $connection->prepare('SELECT student_id, course, year, department, city FROM students WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $user['id']);
    $statement->execute();
    $student = $statement->get_result()->fetch_assoc();
    $statement->close();
    $registrations = $connection->prepare(
        'SELECT e.title, e.category, e.event_date, e.venue FROM registrations r JOIN events e ON e.id = r.event_id WHERE r.user_id = ? ORDER BY e.event_date ASC LIMIT 3'
    );
    $registrations->bind_param('i', $user['id']);
    $registrations->execute();
    $registeredEvents = $registrations->get_result()->fetch_all(MYSQLI_ASSOC);
    $registrations->close();
}

render_header('Dashboard', 'dashboard');
?>
<?php if ($user['role'] === 'admin'): ?>
    <section class="portal-welcome admin-welcome">
        <div><p class="portal-kicker">CSPIT · CHARUSAT · AIML</p><h2>Administrative command centre</h2><p>Welcome back, <?= e($user['name']) ?>. Review activity and manage the StudentHub portal.</p></div>
        <div class="portal-actions"><a class="btn secondary-btn" href="<?= e(app_url('php/admin/students.php')) ?>">Student records</a><a class="btn" href="<?= e(app_url('php/admin/events.php')) ?>">Event manager</a></div>
    </section>
    <section class="portal-stat-grid" aria-label="Portal statistics">
        <article><span aria-hidden="true">🎓</span><p>Student records</p><strong><?= $studentCount ?></strong></article><article><span aria-hidden="true">✦</span><p>Open events</p><strong><?= $eventCount ?></strong></article><article><span aria-hidden="true">◉</span><p>Active accounts</p><strong><?= $userCount ?></strong></article><article><span aria-hidden="true">↗</span><p>Audit actions</p><strong><?= $auditCount ?></strong></article>
    </section>
    <section class="portal-panel">
        <div class="section-heading"><div><p class="eyebrow">Recent activity</p><h2>Latest registrations</h2></div><a class="text-link" href="<?= e(app_url('php/admin/audit.php')) ?>">View audit log <span aria-hidden="true">→</span></a></div>
        <?php if ($latest === []): ?><p class="empty-state">No accounts have registered yet.</p><?php else: ?><div class="table-container"><table><caption>Most recent StudentHub accounts</caption><thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Registered</th></tr></thead><tbody><?php foreach ($latest as $row): ?><tr><td><?= e($row['full_name']) ?></td><td><?= e($row['email']) ?></td><td><?= e(date('d M Y', strtotime($row['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>
<?php else: ?>
    <section class="portal-welcome">
        <div><p class="portal-kicker">STUDENT PORTAL · CSPIT CHARUSAT</p><h2>Welcome back, <?= e($user['name']) ?>.</h2><p>Keep your AIML journey, event registrations, and student details in one place.</p><?php if ($student !== null): ?><div class="student-tags"><span>Student ID: <?= e($student['student_id'] ?? 'Pending') ?></span><span><?= e($student['department']) ?></span><span><?= e($student['year']) ?></span></div><?php endif; ?></div>
        <div class="portal-actions"><a class="btn secondary-btn" href="<?= e(app_url('php/profile.php')) ?>">My profile</a><a class="btn" href="<?= e(app_url('php/events.php')) ?>">Explore events</a></div>
    </section>
    <section class="portal-status" aria-label="StudentHub status"><span aria-hidden="true">✦</span><div><strong>StudentHub is ready for your semester.</strong><p>New campus events and announcements appear here as they are published.</p></div><a href="<?= e(app_url('php/events.php')) ?>">Browse events →</a></section>
    <?php if ($student === null): ?>
        <section class="portal-panel"><h2>Your profile is not available yet.</h2><p>Contact StudentHub support for assistance.</p></section>
    <?php else: ?>
        <section class="portal-panel"><div class="section-heading"><div><p class="eyebrow">Academic identity</p><h2>Your academic summary</h2></div><a class="text-link" href="<?= e(app_url('php/profile.php')) ?>">View full profile <span aria-hidden="true">→</span></a></div><div class="academic-grid"><article><span>Course</span><strong><?= e($student['course']) ?></strong></article><article><span>Year</span><strong><?= e($student['year']) ?></strong></article><article><span>Department</span><strong><?= e($student['department']) ?></strong></article><article><span>City</span><strong><?= e($student['city'] !== '' ? $student['city'] : 'Not provided') ?></strong></article></div></section>
        <section class="portal-panel"><div class="section-heading"><div><p class="eyebrow">Your activities</p><h2>Registered events</h2></div><a class="text-link" href="<?= e(app_url('php/events.php')) ?>">All events <span aria-hidden="true">→</span></a></div><?php if ($registeredEvents === []): ?><p class="empty-state">You have not registered for an event yet. Explore what is happening on campus.</p><?php else: ?><div class="registered-event-list"><?php foreach ($registeredEvents as $event): ?><article><span class="event-category"><?= e($event['category']) ?></span><div><h3><?= e($event['title']) ?></h3><p><?= e(date('D, d M · g:i a', strtotime($event['event_date']))) ?> · <?= e($event['venue']) ?></p></div><time datetime="<?= e(date(DATE_ATOM, strtotime($event['event_date']))) ?>"><?= e(date('d M', strtotime($event['event_date']))) ?></time></article><?php endforeach; ?></div><?php endif; ?></section>
    <?php endif; ?>
<?php endif; ?>
<?php render_footer(); ?>
