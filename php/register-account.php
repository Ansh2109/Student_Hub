<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

if (current_user() !== null) {
    redirect('php/dashboard.php');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/register.html');
}

$errors = [];
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    csrf_failure();
}

$name = trim((string) ($_POST['fullname'] ?? $_POST['name'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$dob = trim((string) ($_POST['dob'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirmation = (string) ($_POST['confirmPassword'] ?? $_POST['confirm_password'] ?? '');
$courseValues = [
    'aiml' => 'B.Tech AIML',
    'cse' => 'B.Tech CSE',
    'it' => 'B.Tech IT',
    'ce' => 'B.Tech CE',
];
$yearValues = [
    'first' => 'First Year',
    'second' => 'Second Year',
    'third' => 'Third Year',
    'fourth' => 'Fourth Year',
];
$genderValues = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];
$courseInput = trim((string) ($_POST['course'] ?? ''));
$yearInput = trim((string) ($_POST['year'] ?? ''));
$genderInput = trim((string) ($_POST['gender'] ?? ''));
$studentId = strtoupper(trim((string) ($_POST['student_id'] ?? '')));
$course = $courseValues[$courseInput] ?? '';
$year = $yearValues[$yearInput] ?? '';
$gender = $genderValues[$genderInput] ?? '';
$city = trim((string) ($_POST['city'] ?? ''));
$termsAccepted = isset($_POST['terms']);
$dobValue = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);

if (!preg_match('/^[\p{L} ]{2,120}$/u', $name)) {
    $errors[] = 'Enter a name containing 2–120 letters and spaces.';
}
if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
    $errors[] = 'Username must be 3–60 characters using letters, numbers, dots, underscores or hyphens.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    $errors[] = 'Enter a valid email address.';
}
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errors[] = 'Mobile number must contain exactly 10 digits.';
}
if ($dobValue === false || $dobValue->format('Y-m-d') !== $dob || $dobValue >= new DateTimeImmutable('today')) {
    $errors[] = 'Enter a valid date of birth in the past.';
}
if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
    $errors[] = 'Password must be at least 8 characters and contain a letter and a number.';
}
if ($password !== $confirmation) {
    $errors[] = 'Passwords do not match.';
}
if ($course === '') {
    $errors[] = 'Select a valid course.';
}
if ($year === '') {
    $errors[] = 'Select a valid year.';
}
if ($gender === '') {
    $errors[] = 'Select a valid gender.';
}
if (!preg_match('/^[0-9]{2}(AIML|CSE|IT|CE)[0-9]{3}$/', $studentId)) {
    $errors[] = 'Student ID must look like 25AIML002 or 25CSE001.';
}
if (!$termsAccepted) {
    $errors[] = 'Accept the terms and conditions to continue.';
}
if ($city !== '' && !preg_match('/^[\p{L} .-]{2,100}$/u', $city)) {
    $errors[] = 'Enter a valid city.';
}

if ($errors === []) {
    $departments = ['aiml' => 'AIML', 'cse' => 'CSE', 'it' => 'IT', 'ce' => 'CE'];
    $department = $departments[$courseInput];
    $connection = db();
    try {
        $connection->begin_transaction();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insertUser = $connection->prepare(
            'INSERT INTO users (full_name, username, email, password_hash) VALUES (?, ?, ?, ?)'
        );
        $insertUser->bind_param('ssss', $name, $username, $email, $passwordHash);
        $insertUser->execute();
        $userId = (int) $connection->insert_id;
        $insertUser->close();

        $insertStudent = $connection->prepare(
            'INSERT INTO students
             (user_id, student_id, name, email, phone, date_of_birth, gender, course, year, department, city)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertStudent->bind_param(
            'issssssssss',
            $userId,
            $studentId,
            $name,
            $email,
            $phone,
            $dob,
            $gender,
            $course,
            $year,
            $department,
            $city
        );
        $insertStudent->execute();
        $insertStudent->close();
        audit_log($userId, 'account.registered', 'user', $userId, 'Student account created.');
        $connection->commit();

        flash('success', 'Account created. You can now sign in.');
        redirect('php/login.php');
    } catch (mysqli_sql_exception $exception) {
        $connection->rollback();
        if ($exception->getCode() === 1062) {
            $errors[] = 'That email address or username is already registered.';
        } else {
            error_log('StudentHub account registration failed: ' . $exception->getMessage());
            http_response_code(500);
            $errors[] = 'Account registration could not be completed. Please try again later.';
        }
    }
}

render_header('Registration error');
?>
<section>
    <h2>Account could not be created</h2>
    <div class="error-message show" role="alert">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
    <p><a class="btn" href="<?= e(app_url('pages/register.html')) ?>">Return to registration</a></p>
</section>
<?php render_footer(); ?>
