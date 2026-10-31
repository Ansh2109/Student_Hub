<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app.php';
require_admin();
render_header('Student API management', 'students');
?>
<section>
    <h2>Student management (Fetch API)</h2>
    <p>List, search, add, update and delete student records asynchronously without reloading the page.</p>
    <p id="apiStatus" role="status" aria-live="polite"></p>
    <div id="studentApi"
         data-endpoint="<?= e(app_url('php/api/students.php')) ?>"
         data-csrf="<?= e(csrf_token()) ?>">
        <label for="studentSearch">Search by name, email or course</label>
        <input id="studentSearch" type="search">
        <label for="studentDepartment">Department</label>
        <select id="studentDepartment">
            <option value="">All departments</option>
            <option value="AIML">AIML</option>
            <option value="CSE">CSE</option>
            <option value="IT">IT</option>
            <option value="CE">CE</option>
        </select>
        <form id="studentApiForm">
            <input id="studentId" name="id" type="hidden">
            <label for="studentName">Name</label><input id="studentName" name="name" required maxlength="120">
            <label for="studentEmail">Email</label><input id="studentEmail" name="email" type="email" required maxlength="190">
            <label for="studentCourse">Course</label><input id="studentCourse" name="course" required maxlength="100">
            <label for="studentYear">Year</label>
            <select id="studentYear" name="year" required>
                <option value="">Select year</option>
                <option>First Year</option><option>Second Year</option><option>Third Year</option><option>Fourth Year</option>
            </select>
            <label for="studentDepartmentField">Department</label>
            <select id="studentDepartmentField" name="department" required>
                <option value="">Select department</option>
                <option>AIML</option><option>CSE</option><option>IT</option><option>CE</option>
            </select>
            <label for="studentCity">City</label><input id="studentCity" name="city" maxlength="100">
            <button type="submit">Save student</button>
            <button id="cancelStudentEdit" type="button" class="secondary-button">Clear form</button>
        </form>
        <p id="studentLoading" hidden>Loading student records…</p>
        <div class="table-container">
            <table>
                <caption>Student records loaded from the PHP JSON API</caption>
                <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Course</th><th scope="col">Year</th><th scope="col">Department</th><th scope="col">Actions</th></tr></thead>
                <tbody id="studentApiRows"></tbody>
            </table>
        </div>
        <nav id="studentApiPagination" class="pagination" aria-label="Student API result pages"></nav>
    </div>
</section>
<script src="<?= e(app_url('js/student-api.js')) ?>" defer></script>
<?php render_footer(); ?>
