<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function api_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$user = current_user();
if ($user === null) {
    api_response(401, ['error' => 'Sign in is required.']);
}
$accountStatement = db()->prepare('SELECT role, status FROM users WHERE id = ? LIMIT 1');
$accountStatement->bind_param('i', $user['id']);
$accountStatement->execute();
$account = $accountStatement->get_result()->fetch_assoc();
$accountStatement->close();
if ($account === null || $account['status'] !== 'active') {
    api_response(401, ['error' => 'This account is unavailable.']);
}
if ($account['role'] !== 'admin') {
    api_response(403, ['error' => 'Administrator access is required.']);
}

$method = $_SERVER['REQUEST_METHOD'];
$connection = db();
$departments = ['AIML', 'CSE', 'IT', 'CE'];
$years = ['First Year', 'Second Year', 'Third Year', 'Fourth Year'];

if ($method === 'GET') {
    $query = trim((string) ($_GET['q'] ?? ''));
    $department = trim((string) ($_GET['department'] ?? ''));
    if (!in_array($department, $departments, true)) {
        $department = '';
    }
    $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
    $perPage = 10;
    $searchValue = '%' . $query . '%';
    $count = $connection->prepare(
        'SELECT COUNT(*) AS total FROM students
         WHERE (? = "" OR name LIKE ? OR email LIKE ? OR course LIKE ?)
           AND (? = "" OR department = ?)'
    );
    $count->bind_param('ssssss', $query, $searchValue, $searchValue, $searchValue, $department, $department);
    $count->execute();
    $total = (int) $count->get_result()->fetch_assoc()['total'];
    $count->close();
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;
    $list = $connection->prepare(
        'SELECT id, name, email, course, year, department, city, status
         FROM students
         WHERE (? = "" OR name LIKE ? OR email LIKE ? OR course LIKE ?)
           AND (? = "" OR department = ?)
         ORDER BY name LIMIT ? OFFSET ?'
    );
    $list->bind_param(
        'ssssssii',
        $query,
        $searchValue,
        $searchValue,
        $searchValue,
        $department,
        $department,
        $perPage,
        $offset
    );
    $list->execute();
    $rows = $list->get_result()->fetch_all(MYSQLI_ASSOC);
    $list->close();
    api_response(200, ['data' => $rows, 'pagination' => ['page' => $page, 'pages' => $pages, 'total' => $total]]);
}

if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, DELETE');
    api_response(405, ['error' => 'Method not allowed.']);
}

$csrf = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf($csrf)) {
    api_response(403, ['error' => 'The request could not be verified. Reload the page and try again.']);
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    api_response(400, ['error' => 'Expected a JSON request body.']);
}

if ($method === 'DELETE') {
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id < 1) {
        api_response(422, ['error' => 'A valid student id is required.']);
    }
    $connection->begin_transaction();
    try {
        $statement = $connection->prepare('DELETE FROM students WHERE id = ?');
        $statement->bind_param('i', $id);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        if (!$deleted) {
            $connection->rollback();
            api_response(404, ['error' => 'Student record was not found.']);
        }
        audit_log($user['id'], 'student.deleted', 'student', $id, 'Student deleted through JSON API.');
        $connection->commit();
        api_response(200, ['message' => 'Student deleted.']);
    } catch (mysqli_sql_exception $exception) {
        $connection->rollback();
        error_log('StudentHub student API delete failed: ' . $exception->getMessage());
        api_response(500, ['error' => 'The student operation could not be completed.']);
    }
}

$name = trim((string) ($input['name'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$course = trim((string) ($input['course'] ?? ''));
$year = trim((string) ($input['year'] ?? ''));
$department = trim((string) ($input['department'] ?? ''));
$city = trim((string) ($input['city'] ?? ''));
$errors = [];
if (!preg_match('/^[\p{L} ]{2,120}$/u', $name)) {
    $errors[] = 'Enter a valid student name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    $errors[] = 'Enter a valid email address.';
}
if ($course === '' || strlen($course) > 100) {
    $errors[] = 'Enter a valid course.';
}
if (!in_array($year, $years, true)) {
    $errors[] = 'Select a valid year.';
}
if (!in_array($department, $departments, true)) {
    $errors[] = 'Select a valid department.';
}
if (strlen($city) > 100) {
    $errors[] = 'City must be 100 characters or fewer.';
}
if ($errors !== []) {
    api_response(422, ['error' => implode(' ', $errors)]);
}

try {
    $connection->begin_transaction();
    if ($method === 'POST') {
        $statement = $connection->prepare(
            'INSERT INTO students (name, email, course, year, department, city)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param('ssssss', $name, $email, $course, $year, $department, $city);
        $statement->execute();
        $id = (int) $connection->insert_id;
        $statement->close();
        audit_log($user['id'], 'student.created', 'student', $id, 'Student added through JSON API.');
        $connection->commit();
        api_response(201, ['message' => 'Student added.', 'id' => $id]);
    }

    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id < 1) {
        api_response(422, ['error' => 'A valid student id is required for updates.']);
    }
    $statement = $connection->prepare(
        'UPDATE students SET name = ?, email = ?, course = ?, year = ?, department = ?, city = ?
         WHERE id = ?'
    );
    $statement->bind_param('ssssssi', $name, $email, $course, $year, $department, $city, $id);
    $statement->execute();
    $updated = $statement->affected_rows > 0;
    $statement->close();
    if (!$updated) {
        $check = $connection->prepare('SELECT id FROM students WHERE id = ?');
        $check->bind_param('i', $id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();
        if (!$exists) {
            $connection->rollback();
            api_response(404, ['error' => 'Student record was not found.']);
        }
    }
    audit_log($user['id'], 'student.updated', 'student', $id, 'Student updated through JSON API.');
    $connection->commit();
    api_response(200, ['message' => 'Student updated.']);
} catch (mysqli_sql_exception $exception) {
    $connection->rollback();
    if ($exception->getCode() === 1062) {
        api_response(409, ['error' => 'A student with that email address already exists.']);
    }
    error_log('StudentHub student API failed: ' . $exception->getMessage());
    api_response(500, ['error' => 'The student operation could not be completed.']);
}
