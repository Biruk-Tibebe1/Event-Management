<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();
$errors = [];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    
    if ($action === 'update_status' && $ticket_id) {
        $status = $_POST['status'] ?? 'open';
        if (in_array($status, ['open', 'in-progress', 'resolved', 'closed'])) {
            $pdo->prepare('UPDATE support_tickets SET status = ? WHERE id = ?')->execute([$status, $ticket_id]);
            flash_set('success', 'Ticket status updated');
            header('Location: ' . BASE_URL . '/admin/support.php'); exit;
        }
    }
}

// Fetch tickets
$status_filter = $_GET['status'] ?? '';
$query = 'SELECT st.id, st.user_id, st.subject, st.message, st.status, st.priority, st.created_at, st.updated_at, u.name, u.email FROM support_tickets st LEFT JOIN users u ON st.user_id = u.id WHERE 1=1';
$params = [];

if ($status_filter) {
    $query .= ' AND st.status = ?';
    $params[] = $status_filter;
}
$query .= ' ORDER BY st.priority = "urgent" DESC, st.updated_at DESC LIMIT 100';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$page = 'support';
include __DIR__ . '/admin_sidebar.php';
?>

<div class="col-md-9">
    <h2 class="mb-3">Support Ticket Management</h2>
    <?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?php echo esc($msg); ?></div><?php endif; ?>
    
    <div class="btn-group mb-3" role="group">
        <a href="?status=" class="btn btn-outline-primary <?php echo ($status_filter === '' ? 'active' : ''); ?>">All</a>
        <a href="?status=open" class="btn btn-outline-primary <?php echo ($status_filter === 'open' ? 'active' : ''); ?>">Open</a>
        <a href="?status=in-progress" class="btn btn-outline-primary <?php echo ($status_filter === 'in-progress' ? 'active' : ''); ?>">In Progress</a>
        <a href="?status=resolved" class="btn btn-outline-primary <?php echo ($status_filter === 'resolved' ? 'active' : ''); ?>">Resolved</a>
        <a href="?status=closed" class="btn btn-outline-primary <?php echo ($status_filter === 'closed' ? 'active' : ''); ?>">Closed</a>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Subject</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><?php echo esc($t['id']); ?></td>
                    <td><?php echo esc($t['name']); ?><br><small><?php echo esc($t['email']); ?></small></td>
                    <td><strong><?php echo esc($t['subject']); ?></strong><br><small><?php echo substr($t['message'], 0, 50); ?>...</small></td>
                    <td>
                        <span class="badge bg-<?php echo ($t['priority'] === 'urgent' ? 'danger' : ($t['priority'] === 'high' ? 'warning' : 'info')); ?>">
                            <?php echo ucfirst($t['priority']); ?>
                        </span>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit();">
                                <option value="open" <?php echo ($t['status'] === 'open' ? 'selected' : ''); ?>>Open</option>
                                <option value="in-progress" <?php echo ($t['status'] === 'in-progress' ? 'selected' : ''); ?>>In Progress</option>
                                <option value="resolved" <?php echo ($t['status'] === 'resolved' ? 'selected' : ''); ?>>Resolved</option>
                                <option value="closed" <?php echo ($t['status'] === 'closed' ? 'selected' : ''); ?>>Closed</option>
                            </select>
                        </form>
                    </td>
                    <td><?php echo date('M d, Y H:i', strtotime($t['updated_at'])); ?></td>
                    <td>
                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#ticketModal<?php echo $t['id']; ?>">View</button>
                    </td>
                </tr>
                
                <!-- Modal for ticket details -->
                <div class="modal fade" id="ticketModal<?php echo $t['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Ticket #<?php echo $t['id']; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>From:</strong> <?php echo esc($t['name']); ?> (<?php echo esc($t['email']); ?>)</p>
                                <p><strong>Subject:</strong> <?php echo esc($t['subject']); ?></p>
                                <p><strong>Message:</strong></p>
                                <p><?php echo nl2br(esc($t['message'])); ?></p>
                                <p><strong>Priority:</strong> <?php echo esc($t['priority']); ?></p>
                                <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($t['created_at'])); ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
