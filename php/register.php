<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use the registration form to submit your details.');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    csrf_failure();
}

$name = trim((string) ($_POST['fullname'] ?? $_POST['fullName'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$dob = trim((string) ($_POST['dob'] ?? ''));
$course = trim((string) ($_POST['course'] ?? ''));
$year = trim((string) ($_POST['year'] ?? ''));
$gender = trim((string) ($_POST['gender'] ?? ''));
$termsAccepted = isset($_POST['terms']);
$errors = [];
$courses = ['aiml', 'cse', 'it', 'ce'];
$years = [
    'first' => 'First Year',
    'second' => 'Second Year',
    'third' => 'Third Year',
    'fourth' => 'Fourth Year',
];
$genders = [
    'male' => 'Male',
    'female' => 'Female',
    'other' => 'Other',
];

if ($name === '' || !preg_match('/^[\p{L} ]{2,120}$/u', $name)) {
    $errors[] = 'Enter a name containing 2–120 letters and spaces.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    $errors[] = 'Enter a valid email address.';
}
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errors[] = 'Mobile number must contain exactly 10 digits.';
}
$date = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
if ($date === false || $date->format('Y-m-d') !== $dob || $date >= new DateTimeImmutable('today')) {
    $errors[] = 'Enter a valid date of birth in the past.';
}
if (!in_array($course, $courses, true)) {
    $errors[] = 'Select a valid course.';
}
if (!isset($years[$year])) {
    $errors[] = 'Select a valid year.';
}
if (!isset($genders[$gender])) {
    $errors[] = 'Select a valid gender.';
}
if (!$termsAccepted) {
    $errors[] = 'Accept the terms and conditions to continue.';
}

if ($errors !== []) {
    http_response_code(422);
    render_header('Registration error');
    ?>
    <section>
        <h2>Registration could not be saved</h2>
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        <p><a class="btn" href="<?= e(app_url('pages/register.html')) ?>">Return to registration</a></p>
    </section>
    <?php
    render_footer();
    exit;
}

$filePath = APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'registrations.csv';
$handle = fopen($filePath, 'c+');
if ($handle === false) {
    error_log('StudentHub could not open the registration CSV for writing.');
    http_response_code(500);
    exit('Registration storage is unavailable. Please contact the site administrator.');
}

if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    error_log('StudentHub could not acquire the registration CSV lock.');
    http_response_code(500);
    exit('Registration storage is busy. Please try again.');
}

fseek($handle, 0, SEEK_END);
if (ftell($handle) === 0) {
    fputcsv($handle, ['Full Name', 'Email', 'Phone', 'Date of Birth', 'Course', 'Year', 'Gender'], ',', '"', '');
}

$courseNames = [
    'aiml' => 'B.Tech AIML',
    'cse' => 'B.Tech CSE',
    'it' => 'B.Tech IT',
    'ce' => 'B.Tech CE',
];
$writeResult = fputcsv(
    $handle,
    [$name, $email, $phone, $dob, $courseNames[$course], $years[$year], $genders[$gender]],
    ',',
    '"',
    ''
);
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);

if ($writeResult === false) {
    error_log('StudentHub failed to write a registration CSV row.');
    http_response_code(500);
    exit('Registration could not be saved. Please try again.');
}

redirect('php/register-csv.php?registered=1');
