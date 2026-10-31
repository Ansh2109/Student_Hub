<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app.php';
require_admin();

$connection = db();
$categories = ['Technical', 'Academic', 'Sports', 'Cultural', 'Career', 'Other'];
$errors = [];
$values = [];

function store_event_poster(array $upload): ?string
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The poster upload did not complete successfully.');
    }
    if (($upload['size'] ?? 0) <= 0 || $upload['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Poster images must be smaller than 2 MB.');
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fileInfo->file($upload['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime]) || getimagesize($upload['tmp_name']) === false) {
        throw new RuntimeException('Upload a valid JPG, PNG or WebP image.');
    }

    $directory = APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'events';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('The poster upload directory could not be created.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($upload['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('The poster image could not be saved.');
    }
    return 'uploads/events/' . $filename;
}

function remove_event_poster(?string $relativePath): void
{
    if ($relativePath === null || !str_starts_with($relativePath, 'uploads/events/')) {
        return;
    }
    $filename = basename($relativePath);
    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'events' . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path) && !unlink($path)) {
        error_log('StudentHub could not remove old event poster: ' . $path);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        csrf_failure();
    }

    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'delete' && $id !== false && $id !== null && $id > 0) {
        $connection->begin_transaction();
        try {
            $statement = $connection->prepare('SELECT poster_path FROM events WHERE id = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $event = $statement->get_result()->fetch_assoc();
            $statement->close();
            if ($event !== null) {
                $delete = $connection->prepare('DELETE FROM events WHERE id = ?');
                $delete->bind_param('i', $id);
                $delete->execute();
                $delete->close();
                audit_log(current_user()['id'], 'event.deleted', 'event', $id, 'Event deleted.');
            }
            $connection->commit();
            if ($event !== null) {
                remove_event_poster($event['poster_path']);
                flash('success', 'Event deleted.');
            } else {
                flash('error', 'Event was not found.');
            }
        } catch (mysqli_sql_exception $exception) {
            $connection->rollback();
            error_log('StudentHub event deletion failed: ' . $exception->getMessage());
            http_response_code(500);
            flash('error', 'The event could not be deleted.');
        }
        redirect('php/admin/events.php');
    }

    if (in_array($action, ['create', 'update'], true)) {
        $values = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'event_date' => trim((string) ($_POST['event_date'] ?? '')),
            'venue' => trim((string) ($_POST['venue'] ?? '')),
            'status' => trim((string) ($_POST['status'] ?? 'open')),
        ];
        if ($values['title'] === '' || strlen($values['title']) > 160) {
            $errors[] = 'Enter an event title no longer than 160 characters.';
        }
        if (!in_array($values['category'], $categories, true)) {
            $errors[] = 'Select an event category.';
        }
        if ($values['description'] === '' || strlen($values['description']) > 10000) {
            $errors[] = 'Enter an event description (maximum 10,000 characters).';
        }
        $eventDate = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $values['event_date']);
        if ($eventDate === false || $eventDate->format('Y-m-d\TH:i') !== $values['event_date']) {
            $errors[] = 'Enter a valid event date and time.';
        }
        if ($values['venue'] === '' || strlen($values['venue']) > 160) {
            $errors[] = 'Enter a venue no longer than 160 characters.';
        }
        if (!in_array($values['status'], ['open', 'closed'], true)) {
            $errors[] = 'Select a valid event status.';
        }

        $existingPoster = null;
        if ($action === 'update' && $id !== false && $id !== null && $id > 0) {
            $find = $connection->prepare('SELECT poster_path FROM events WHERE id = ?');
            $find->bind_param('i', $id);
            $find->execute();
            $existing = $find->get_result()->fetch_assoc();
            $find->close();
            if ($existing === null) {
                $errors[] = 'The event to update no longer exists.';
            } else {
                $existingPoster = $existing['poster_path'];
            }
        }

        $newPoster = null;
        if ($errors === []) {
            try {
                $newPoster = store_event_poster($_FILES['poster'] ?? []);
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($errors === []) {
            $eventDateSql = $eventDate->format('Y-m-d H:i:s');
            $posterPath = $newPoster ?? $existingPoster;
            $connection->begin_transaction();
            try {
                if ($action === 'create') {
                    $creatorId = current_user()['id'];
                    $insert = $connection->prepare(
                        'INSERT INTO events (title, category, description, event_date, venue, status, poster_path, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insert->bind_param(
                        'sssssssi',
                        $values['title'],
                        $values['category'],
                        $values['description'],
                        $eventDateSql,
                        $values['venue'],
                        $values['status'],
                        $posterPath,
                        $creatorId
                    );
                    $insert->execute();
                    $eventId = (int) $connection->insert_id;
                    $insert->close();
                    audit_log($creatorId, 'event.created', 'event', $eventId, 'Event created.');
                } else {
                    $update = $connection->prepare(
                        'UPDATE events
                         SET title = ?, category = ?, description = ?, event_date = ?, venue = ?, status = ?, poster_path = ?
                         WHERE id = ?'
                    );
                    $update->bind_param(
                        'sssssssi',
                        $values['title'],
                        $values['category'],
                        $values['description'],
                        $eventDateSql,
                        $values['venue'],
                        $values['status'],
                        $posterPath,
                        $id
                    );
                    $update->execute();
                    $update->close();
                    audit_log(current_user()['id'], 'event.updated', 'event', $id, 'Event updated.');
                }
                $connection->commit();
            } catch (mysqli_sql_exception $exception) {
                $connection->rollback();
                remove_event_poster($newPoster);
                error_log('StudentHub event save failed: ' . $exception->getMessage());
                http_response_code(500);
                $errors[] = 'The event could not be saved. Please try again.';
            }

            if ($errors === []) {
                if ($action === 'update' && $newPoster !== null) {
                    remove_event_poster($existingPoster);
                }
                flash('success', $action === 'create' ? 'Event added.' : 'Event updated.');
                redirect('php/admin/events.php');
            }
        }
    }
}

$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
$editing = $editId !== false && $editId !== null && $editId > 0;
if ($editing && $values === []) {
    $find = $connection->prepare(
        'SELECT id, title, category, description, event_date, venue, status, poster_path
         FROM events WHERE id = ?'
    );
    $find->bind_param('i', $editId);
    $find->execute();
    $values = $find->get_result()->fetch_assoc() ?: [];
    $find->close();
}
$events = $connection->query(
    'SELECT id, title, category, event_date, venue, status, poster_path
     FROM events ORDER BY event_date DESC'
)->fetch_all(MYSQLI_ASSOC);

render_header('Event management', 'events');
?>
<section>
    <h2>Event management</h2>
    <p>Manage events and posters. Supported image formats: JPG, PNG and WebP (maximum 2 MB).</p>
    <?php if ($errors !== []): ?><div class="error-message show" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="<?= e(app_url('php/admin/events.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editId ?>"><?php endif; ?>
        <label for="title">Title</label><input id="title" name="title" maxlength="160" required value="<?= e($values['title'] ?? '') ?>">
        <label for="category">Category</label>
        <select id="category" name="category" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= ($values['category'] ?? '') === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select>
        <label for="description">Description</label><textarea id="description" name="description" maxlength="10000" required><?= e($values['description'] ?? '') ?></textarea>
        <label for="event_date">Date and time</label><input id="event_date" name="event_date" type="datetime-local" required value="<?= isset($values['event_date']) ? e(str_replace(' ', 'T', substr($values['event_date'], 0, 16))) : '' ?>">
        <label for="venue">Venue</label><input id="venue" name="venue" maxlength="160" required value="<?= e($values['venue'] ?? '') ?>">
        <label for="status">Registration status</label>
        <select id="status" name="status"><option value="open" <?= ($values['status'] ?? 'open') === 'open' ? 'selected' : '' ?>>Open</option><option value="closed" <?= ($values['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option></select>
        <label for="poster">Poster image</label><input id="poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($values['poster_path'])): ?><p>Current poster: <img src="<?= e(app_url($values['poster_path'])) ?>" alt="Current event poster" width="160"></p><?php endif; ?>
        <button type="submit"><?= $editing ? 'Save event' : 'Add event' ?></button>
        <?php if ($editing): ?><a class="btn secondary-btn" href="<?= e(app_url('php/admin/events.php')) ?>">Cancel</a><?php endif; ?>
    </form>
</section>
<section>
    <h2>Current events</h2>
    <?php if ($events === []): ?><p>No events are available.</p><?php else: ?>
    <div class="table-container"><table>
        <caption>Events managed in StudentHub</caption>
        <thead><tr><th scope="col">Title</th><th scope="col">Category</th><th scope="col">Date</th><th scope="col">Venue</th><th scope="col">Status</th><th scope="col">Poster</th><th scope="col">Actions</th></tr></thead>
        <tbody><?php foreach ($events as $event): ?><tr>
            <td><?= e($event['title']) ?></td><td><?= e($event['category']) ?></td><td><?= e($event['event_date']) ?></td><td><?= e($event['venue']) ?></td><td><?= e($event['status']) ?></td>
            <td><?php if ($event['poster_path'] !== null): ?><img src="<?= e(app_url($event['poster_path'])) ?>" alt="Poster for <?= e($event['title']) ?>" width="90"><?php else: ?>—<?php endif; ?></td>
            <td><a href="<?= e(app_url('php/admin/events.php?edit=' . (int) $event['id'])) ?>">Edit</a>
                <form method="post" action="<?= e(app_url('php/admin/events.php')) ?>" data-confirm="Delete this event and its registrations?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $event['id'] ?>"><button type="submit">Delete</button>
                </form>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
