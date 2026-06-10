<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $event_id = (int)($_POST['event_id'] ?? 0);
    
    if ($action === 'approve' && $event_id) {
        $pdo->prepare('UPDATE events SET status = "published" WHERE id = ?')->execute([$event_id]);
        flash_set('success', 'Event approved');
        header('Location: ' . BASE_URL . '/admin/events.php'); exit;
    } elseif ($action === 'reject' && $event_id) {
        $pdo->prepare('UPDATE events SET status = "cancelled" WHERE id = ?')->execute([$event_id]);
        flash_set('success', 'Event rejected');
        header('Location: ' . BASE_URL . '/admin/events.php'); exit;
    } elseif ($action === 'delete' && $event_id) {
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$event_id]);
        flash_set('success', 'Event deleted');
        header('Location: ' . BASE_URL . '/admin/events.php'); exit;
    }
}

// Fetch events
$status_filter = $_GET['status'] ?? '';
$query = 'SELECT e.id, e.title, e.category, e.event_date, e.city, e.status, u.name as organizer FROM events e LEFT JOIN users u ON e.organizer_id = u.id WHERE 1=1';
$params = [];

if ($status_filter) {
    $query .= ' AND e.status = ?';
    $params[] = $status_filter;
}
$query .= ' ORDER BY e.event_date DESC LIMIT 100';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

$page = 'events';
include __DIR__ . '/admin_sidebar.php';
?>

<div class="col-md-9">
    <h2 class="title-font text-2xl mb-3">Event Management</h2>
    <?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?php echo esc($msg); ?></div><?php endif; ?>
    
    <div class="btn-group mb-3" role="group">
        <a href="?status=" class="btn btn-outline-primary <?php echo ($status_filter === '' ? 'active' : ''); ?>">All</a>
        <a href="?status=draft" class="btn btn-outline-primary <?php echo ($status_filter === 'draft' ? 'active' : ''); ?>">Pending</a>
        <a href="?status=published" class="btn btn-outline-primary <?php echo ($status_filter === 'published' ? 'active' : ''); ?>">Published</a>
        <a href="?status=cancelled" class="btn btn-outline-primary <?php echo ($status_filter === 'cancelled' ? 'active' : ''); ?>">Cancelled</a>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Organizer</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                <tr>
                    <td><?php echo esc($ev['title']); ?></td>
                    <td><?php echo esc($ev['organizer'] ?? 'N/A'); ?></td>
                    <td><?php echo esc($ev['category']); ?></td>
                    <td><?php echo esc($ev['event_date']); ?></td>
                    <td><?php echo esc($ev['city']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo ($ev['status'] === 'published' ? 'success' : ($ev['status'] === 'draft' ? 'warning' : 'danger')); ?>">
                            <?php echo ucfirst($ev['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($ev['status'] === 'draft'): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-warning">Reject</button>
                        </form>
                        <?php endif; ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-reject admin-action" data-confirm="Delete this event?">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
