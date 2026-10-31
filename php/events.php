<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        csrf_failure();
    }
    require_login();
    $user = current_user();
    $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
    if ($eventId === false || $eventId === null || $eventId < 1) {
        flash('error', 'Select a valid event.');
        redirect('php/events.php');
    }
    $connection = db();
    $eventQuery = $connection->prepare('SELECT status FROM events WHERE id = ?');
    $eventQuery->bind_param('i', $eventId);
    $eventQuery->execute();
    $event = $eventQuery->get_result()->fetch_assoc();
    $eventQuery->close();
    if ($event === null || $event['status'] !== 'open') {
        flash('error', 'This event is unavailable for registration.');
        redirect('php/events.php');
    }
    try {
        $insert = $connection->prepare('INSERT INTO registrations (user_id, event_id) VALUES (?, ?)');
        $insert->bind_param('ii', $user['id'], $eventId);
        $insert->execute();
        $insert->close();
        audit_log($user['id'], 'event.registered', 'event', $eventId, 'Student registered for an event.');
        flash('success', 'You are registered for the event.');
    } catch (mysqli_sql_exception $exception) {
        if ($exception->getCode() === 1062) {
            flash('error', 'You are already registered for this event.');
        } else {
            error_log('StudentHub event registration failed: ' . $exception->getMessage());
            http_response_code(500);
            flash('error', 'Event registration could not be completed.');
        }
    }
    redirect('php/events.php');
}

$category = trim((string) ($_GET['category'] ?? ''));
$categories = ['Technical', 'Academic', 'Sports', 'Cultural', 'Career', 'Other'];
if (!in_array($category, $categories, true)) {
    $category = '';
}
$statement = db()->prepare(
    'SELECT e.id, e.title, e.category, e.description, e.event_date, e.venue, e.status,
            e.poster_path, COUNT(r.id) AS registration_count
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.id
     WHERE (? = "" OR e.category = ?)
     GROUP BY e.id
     ORDER BY e.event_date'
);
$statement->bind_param('ss', $category, $category);
$statement->execute();
$events = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();
$registeredEvents = [];
if (current_user() !== null) {
    $registered = db()->prepare(
        'SELECT event_id FROM registrations WHERE user_id = ?'
    );
    $userId = current_user()['id'];
    $registered->bind_param('i', $userId);
    $registered->execute();
    foreach ($registered->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $registeredEvents[(int) $row['event_id']] = true;
    }
    $registered->close();
}

render_header('Campus events', 'events');
?>
<section>
    <h2>Campus events</h2>
    <form method="get" action="<?= e(app_url('php/events.php')) ?>">
        <label for="category">Filter by category</label>
        <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $option): ?><option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?>
        </select>
        <button type="submit">Apply filter</button>
    </form>
</section>
<section>
    <?php if ($events === []): ?><p>No events match this category.</p><?php else: ?>
    <div class="events-grid">
        <?php foreach ($events as $event): ?>
            <article class="event-card">
                <h3><?= e($event['title']) ?></h3>
                <?php if ($event['poster_path'] !== null): ?><img src="<?= e(app_url($event['poster_path'])) ?>" alt="Poster for <?= e($event['title']) ?>" width="240"><?php endif; ?>
                <p><strong>Category:</strong> <?= e($event['category']) ?></p>
                <p><?= e($event['description']) ?></p>
                <p><strong>Date:</strong> <time datetime="<?= e(date(DATE_ATOM, strtotime($event['event_date']))) ?>"><?= e(date('d M Y, g:i a', strtotime($event['event_date']))) ?></time></p>
                <p><strong>Venue:</strong> <?= e($event['venue']) ?></p>
                <p><strong>Registration:</strong> <?= e(ucfirst($event['status'])) ?> · <?= (int) $event['registration_count'] ?> registered</p>
                <?php if ($event['status'] === 'open' && current_user() !== null): ?>
                    <?php if (isset($registeredEvents[(int) $event['id']])): ?>
                        <p class="event-registration-status" role="status">You are registered for this event.</p>
                    <?php else: ?>
                        <form method="post" action="<?= e(app_url('php/events.php')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                            <button type="submit"><?= current_user()['role'] === 'admin' ? 'Register as attendee' : 'Register for event' ?></button>
                        </form>
                    <?php endif; ?>
                <?php elseif ($event['status'] === 'open'): ?>
                    <p><a href="<?= e(app_url('php/login.php')) ?>">Sign in to register</a></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
