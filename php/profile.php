<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
require_login();

$user = current_user();
$statement = db()->prepare(
    'SELECT student_id, name, email, phone, date_of_birth, gender, course, year, department, city
     FROM students
     WHERE user_id = ?
     LIMIT 1'
);
$statement->bind_param('i', $user['id']);
$statement->execute();
$profile = $statement->get_result()->fetch_assoc();
$statement->close();

render_header('My profile', 'profile');
?>
<section class="profile-hero">
    <div class="profile-avatar" aria-hidden="true"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></div>
    <div>
        <p class="eyebrow">StudentHub account</p>
        <h2><?= e($user['name']) ?></h2>
        <p><?= e(ucfirst($user['role'])) ?> account</p>
    </div>
</section>

<?php if ($profile === null): ?>
    <section>
        <h2>Profile details</h2>
        <p class="empty-state">Your student profile details are not available. Please contact StudentHub support.</p>
    </section>
<?php else: ?>
    <section>
        <div class="section-heading">
            <div>
                <p class="eyebrow">Your information</p>
                <h2>Personal details</h2>
            </div>
            <a class="text-link" href="<?= e(app_url('php/dashboard.php')) ?>">Back to dashboard <span aria-hidden="true">→</span></a>
        </div>
        <dl class="profile-details">
            <div><dt>Student ID</dt><dd><?= e($profile['student_id'] ?? 'Not provided') ?></dd></div>
            <div><dt>Full name</dt><dd><?= e($profile['name']) ?></dd></div>
            <div><dt>Email</dt><dd><?= e($profile['email']) ?></dd></div>
            <div><dt>Mobile</dt><dd><?= e($profile['phone'] !== '' ? $profile['phone'] : 'Not provided') ?></dd></div>
            <div><dt>Date of birth</dt><dd><?= e($profile['date_of_birth'] ?? 'Not provided') ?></dd></div>
            <div><dt>Gender</dt><dd><?= e($profile['gender'] !== '' ? $profile['gender'] : 'Not provided') ?></dd></div>
            <div><dt>Course</dt><dd><?= e($profile['course']) ?></dd></div>
            <div><dt>Year</dt><dd><?= e($profile['year']) ?></dd></div>
            <div><dt>Department</dt><dd><?= e($profile['department']) ?></dd></div>
            <div><dt>City</dt><dd><?= e($profile['city'] !== '' ? $profile['city'] : 'Not provided') ?></dd></div>
        </dl>
    </section>
<?php endif; ?>
<?php render_footer(); ?>
