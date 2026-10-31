<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

render_header('Contact registration', 'register');
?>
<section>
    <h2>Contact registration</h2>
    <p>This demonstration stores validated contact and academic fields in the protected CSV file. Account passwords are intentionally excluded.</p>
    <?php if (($_GET['registered'] ?? '') === '1'): ?>
        <p class="success-message show" role="status">Registration details were saved successfully in the CSV file.</p>
    <?php endif; ?>
    <form method="post" action="<?= e(app_url('php/register.php')) ?>">
        <?= csrf_field() ?>
        <label for="fullname">Full name</label>
        <input id="fullname" name="fullname" autocomplete="name" maxlength="120" required>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" maxlength="190" required>
        <label for="phone">Mobile number</label>
        <input id="phone" name="phone" type="tel" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" required>
        <label for="dob">Date of birth</label>
        <input id="dob" name="dob" type="date" required>
        <label for="course">Course</label>
        <select id="course" name="course" required>
            <option value="">Select course</option>
            <option value="aiml">B.Tech AIML</option>
            <option value="cse">B.Tech CSE</option>
            <option value="it">B.Tech IT</option>
            <option value="ce">B.Tech CE</option>
        </select>
        <label for="year">Year</label>
        <select id="year" name="year" required>
            <option value="">Select year</option>
            <option value="first">First Year</option>
            <option value="second">Second Year</option>
            <option value="third">Third Year</option>
            <option value="fourth">Fourth Year</option>
        </select>
        <fieldset>
            <legend>Gender</legend>
            <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label): ?>
                <div class="checkbox-group">
                    <input id="gender-<?= e($value) ?>" name="gender" type="radio" value="<?= e($value) ?>" required>
                    <label for="gender-<?= e($value) ?>"><?= e($label) ?></label>
                </div>
            <?php endforeach; ?>
        </fieldset>
        <div class="checkbox-group">
            <input id="terms" name="terms" type="checkbox" required>
            <label for="terms">I accept the terms and conditions.</label>
        </div>
        <button type="submit">Save CSV registration</button>
    </form>
</section>
<?php render_footer(); ?>
