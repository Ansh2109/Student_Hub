<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app.php';
require_admin();

$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 50;
$offset = ($page - 1) * $perPage;
$statement = db()->prepare(
    'SELECT a.action, a.entity_type, a.entity_id, a.details, a.ip_address, a.created_at,
            u.full_name, u.email
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT ? OFFSET ?'
);
$statement->bind_param('ii', $perPage, $offset);
$statement->execute();
$logs = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();

render_header('Audit log', 'audit');
?>
<section>
    <h2>Administrator audit log</h2>
    <p>Recent account, student and event actions are recorded for operational traceability.</p>
    <?php if ($logs === []): ?><p>No audit activity has been recorded.</p><?php else: ?>
    <div class="table-container"><table>
        <caption>Audit events, newest first</caption>
        <thead><tr><th scope="col">Time</th><th scope="col">User</th><th scope="col">Action</th><th scope="col">Entity</th><th scope="col">Details</th><th scope="col">IP address</th></tr></thead>
        <tbody><?php foreach ($logs as $log): ?><tr>
            <td><?= e($log['created_at']) ?></td>
            <td><?= e($log['full_name'] ?? 'Unknown user') ?><?= $log['email'] !== null ? ' (' . e($log['email']) . ')' : '' ?></td>
            <td><?= e($log['action']) ?></td>
            <td><?= e($log['entity_type']) ?><?= $log['entity_id'] !== null ? ' #' . (int) $log['entity_id'] : '' ?></td>
            <td><?= e($log['details'] ?? '') ?></td>
            <td><?= e($log['ip_address'] ?? '') ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
    <p><a class="btn" href="<?= e(app_url('php/admin/audit.php?page=' . ($page + 1))) ?>">Next page</a></p>
</section>
<?php render_footer(); ?>
