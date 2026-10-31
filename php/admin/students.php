<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app.php';
require_admin();

$connection = db();
$departments = ['AIML', 'CSE', 'IT', 'CE'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        csrf_failure();
    }

    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'delete' && $id !== false && $id !== null && $id > 0) {
        $connection->begin_transaction();
        try {
            $statement = $connection->prepare('DELETE FROM students WHERE id = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $changed = $statement->affected_rows;
            $statement->close();
            if ($changed > 0) {
                audit_log(current_user()['id'], 'student.deleted', 'student', $id, 'Student record deleted.');
            }
            $connection->commit();
            flash($changed > 0 ? 'success' : 'error', $changed > 0 ? 'Student deleted.' : 'Student record was not found.');
        } catch (mysqli_sql_exception $exception) {
            $connection->rollback();
            error_log('StudentHub student delete failed: ' . $exception->getMessage());
            http_response_code(500);
            flash('error', 'The student record could not be deleted.');
        }
        redirect('php/admin/students.php');
    }

    if (in_array($action, ['create', 'update'], true)) {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $course = trim((string) ($_POST['course'] ?? ''));
        $year = trim((string) ($_POST['year'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
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
        if (!in_array($year, ['First Year', 'Second Year', 'Third Year', 'Fourth Year'], true)) {
            $errors[] = 'Select a valid year.';
        }
        if (!in_array($department, $departments, true)) {
            $errors[] = 'Select a valid department.';
        }
        if (strlen($city) > 100) {
            $errors[] = 'City must be 100 characters or fewer.';
        }

        if ($errors === []) {
            if ($action === 'update' && ($id === false || $id === null || $id < 1)) {
                $errors[] = 'A valid student id is required to update a record.';
            }
        }

        if ($errors === []) {
            try {
                $connection->begin_transaction();
                if ($action === 'create') {
                    $statement = $connection->prepare(
                        'INSERT INTO students (name, email, course, year, department, city)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $statement->bind_param('ssssss', $name, $email, $course, $year, $department, $city);
                    $statement->execute();
                    $studentId = (int) $connection->insert_id;
                    $statement->close();
                    audit_log(current_user()['id'], 'student.created', 'student', $studentId, 'Student record created.');
                    flash('success', 'Student added.');
                } elseif ($id !== false && $id !== null && $id > 0) {
                    $statement = $connection->prepare(
                        'UPDATE students SET name = ?, email = ?, course = ?, year = ?, department = ?, city = ?
                         WHERE id = ?'
                    );
                    $statement->bind_param('ssssssi', $name, $email, $course, $year, $department, $city, $id);
                    $statement->execute();
                    $statement->close();
                    audit_log(current_user()['id'], 'student.updated', 'student', $id, 'Student record updated.');
                    flash('success', 'Student updated.');
                }
                $connection->commit();
                redirect('php/admin/students.php');
            } catch (mysqli_sql_exception $exception) {
                $connection->rollback();
                if ($exception->getCode() === 1062) {
                    $errors[] = 'A student with that email address already exists.';
                } else {
                    error_log('StudentHub student save failed: ' . $exception->getMessage());
                    http_response_code(500);
                    $errors[] = 'The student record could not be saved.';
                }
            }
        }
        $_SESSION['student_form_errors'] = $errors;
        $_SESSION['student_form_values'] = compact('name', 'email', 'course', 'year', 'department', 'city');
        if ($id !== false && $id !== null) {
            $_SESSION['student_form_values']['id'] = $id;
        }
        redirect('php/admin/students.php');
    }
}

$errors = $_SESSION['student_form_errors'] ?? [];
$values = $_SESSION['student_form_values'] ?? [];
unset($_SESSION['student_form_errors'], $_SESSION['student_form_values']);
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
$editing = $editId !== false && $editId !== null && $editId > 0;
if ($editing && $values === []) {
    $statement = $connection->prepare(
        'SELECT id, name, email, course, year, department, city FROM students WHERE id = ?'
    );
    $statement->bind_param('i', $editId);
    $statement->execute();
    $values = $statement->get_result()->fetch_assoc() ?: [];
    $statement->close();
}
if ($values !== [] && isset($values['id'])) {
    $editing = true;
    $editId = (int) $values['id'];
}

$query = trim((string) ($_GET['q'] ?? ''));
$departmentFilter = trim((string) ($_GET['department'] ?? ''));
$searchValue = '%' . $query . '%';
if (!in_array($departmentFilter, $departments, true)) {
    $departmentFilter = '';
}
$countStatement = $connection->prepare(
    'SELECT COUNT(*) AS total FROM students
     WHERE (? = "" OR name LIKE ? OR email LIKE ? OR course LIKE ?)
       AND (? = "" OR department = ?)'
);
$countStatement->bind_param(
    'ssssss',
    $query,
    $searchValue,
    $searchValue,
    $searchValue,
    $departmentFilter,
    $departmentFilter
);
$countStatement->execute();
$totalRecords = (int) $countStatement->get_result()->fetch_assoc()['total'];
$countStatement->close();
$perPage = 10;
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$totalPages = max(1, (int) ceil($totalRecords / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$listStatement = $connection->prepare(
    'SELECT id, name, email, course, year, department, city, status
     FROM students
     WHERE (? = "" OR name LIKE ? OR email LIKE ? OR course LIKE ?)
       AND (? = "" OR department = ?)
     ORDER BY name LIMIT ? OFFSET ?'
);
$listStatement->bind_param(
    'ssssssii',
    $query,
    $searchValue,
    $searchValue,
    $searchValue,
    $departmentFilter,
    $departmentFilter,
    $perPage,
    $offset
);
$listStatement->execute();
$students = $listStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$listStatement->close();

render_header('Student management', 'students');
?>
<section>
    <h2>Student management</h2>
    <p><?= $totalRecords ?> student record(s). Search by name, email or course and filter by department.</p>
    <?php if ($errors !== []): ?><div class="error-message show" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="get" action="<?= e(app_url('php/admin/students.php')) ?>">
        <label for="q">Search students</label>
        <input id="q" name="q" value="<?= e($query) ?>" type="search">
        <label for="departmentFilter">Department</label>
        <select id="departmentFilter" name="department">
            <option value="">All departments</option>
            <?php foreach ($departments as $department): ?><option value="<?= e($department) ?>" <?= $departmentFilter === $department ? 'selected' : '' ?>><?= e($department) ?></option><?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
        <a class="btn secondary-btn" href="<?= e(app_url('php/admin/students-export.php')) ?>">Export CSV</a>
    </form>
</section>
<section>
    <h2><?= $editing ? 'Edit student' : 'Add student' ?></h2>
    <form method="post" action="<?= e(app_url('php/admin/students.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editId ?>"><?php endif; ?>
        <label for="name">Name</label><input id="name" name="name" maxlength="120" required value="<?= e($values['name'] ?? '') ?>">
        <label for="email">Email</label><input id="email" name="email" type="email" maxlength="190" required value="<?= e($values['email'] ?? '') ?>">
        <label for="course">Course</label><input id="course" name="course" maxlength="100" required value="<?= e($values['course'] ?? '') ?>">
        <label for="year">Year</label>
        <select id="year" name="year" required><option value="">Select year</option><?php foreach (['First Year', 'Second Year', 'Third Year', 'Fourth Year'] as $option): ?><option value="<?= e($option) ?>" <?= ($values['year'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select>
        <label for="department">Department</label>
        <select id="department" name="department" required><option value="">Select department</option><?php foreach ($departments as $option): ?><option value="<?= e($option) ?>" <?= ($values['department'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select>
        <label for="city">City</label><input id="city" name="city" maxlength="100" value="<?= e($values['city'] ?? '') ?>">
        <button type="submit"><?= $editing ? 'Save changes' : 'Add student' ?></button>
        <?php if ($editing): ?><a class="btn secondary-btn" href="<?= e(app_url('php/admin/students.php')) ?>">Cancel</a><?php endif; ?>
    </form>
</section>
<section>
    <h2>Student records</h2>
    <?php if ($students === []): ?><p>No students match those search criteria.</p><?php else: ?>
    <div class="table-container"><table>
        <caption>Student records, page <?= $page ?> of <?= $totalPages ?></caption>
        <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Course</th><th scope="col">Year</th><th scope="col">Department</th><th scope="col">City</th><th scope="col">Actions</th></tr></thead>
        <tbody><?php foreach ($students as $student): ?><tr>
            <td><?= e($student['name']) ?></td><td><?= e($student['email']) ?></td><td><?= e($student['course']) ?></td><td><?= e($student['year']) ?></td><td><?= e($student['department']) ?></td><td><?= e($student['city']) ?></td>
            <td><a href="<?= e(app_url('php/admin/students.php?edit=' . (int) $student['id'])) ?>">Edit</a>
                <form method="post" action="<?= e(app_url('php/admin/students.php')) ?>" data-confirm="Delete this student record?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $student['id'] ?>"><button type="submit">Delete</button>
                </form>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
    <nav class="pagination" aria-label="Student result pages">
        <?php for ($number = 1; $number <= $totalPages; $number++): ?>
            <a class="btn<?= $number === $page ? ' active-page' : '' ?>" href="<?= e(app_url('php/admin/students.php?' . http_build_query(['q' => $query, 'department' => $departmentFilter, 'page' => $number]))) ?>" <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a>
        <?php endfor; ?>
    </nav>
</section>
<?php render_footer(); ?>
