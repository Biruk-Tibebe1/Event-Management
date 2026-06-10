<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();
$errors = [];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($action === 'delete' && $user_id) {
        $pdo->prepare('DELETE FROM users WHERE id = ? AND id != ?')->execute([$user_id, current_user_id()]);
        flash_set('success', 'User deleted');
        header('Location: ' . BASE_URL . '/admin/users.php'); exit;
    } elseif ($action === 'change_role' && $user_id) {
        $role = trim($_POST['role'] ?? '');
        if (in_array($role, ['user', 'organizer', 'admin', 'cinema_manager'])) {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ? AND id != ?')->execute([$role, $user_id, current_user_id()]);
            flash_set('success', 'User role updated');
            header('Location: ' . BASE_URL . '/admin/users.php'); exit;
        }
    } elseif ($action === 'approve_license' && $user_id) {
        $pdo->prepare('UPDATE users SET license_approved = 1 WHERE id = ?')->execute([$user_id]);
        flash_set('success', 'User license approved');
        header('Location: ' . BASE_URL . '/admin/users.php'); exit;
    } elseif ($action === 'approve_user' && $user_id) {
        // Approve the user's account (if DB column exists)
        try {
            $pdo->prepare('UPDATE users SET is_approved = 1 WHERE id = ?')->execute([$user_id]);
            flash_set('success', 'User account approved');
        } catch (Exception $e) {
            flash_set('success', 'Approval failed: ' . $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/admin/users.php'); exit;
    }
}

// Fetch users
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';
$has_license_cols = false;
$has_approval_col = false;
try {
    $pdo->query('SELECT organization_name FROM users LIMIT 1');
    $has_license_cols = true;
} catch (Exception $e) {
    $has_license_cols = false;
}
try {
    $pdo->query('SELECT is_approved FROM users LIMIT 1');
    $has_approval_col = true;
} catch (Exception $e) {
    $has_approval_col = false;
}

if ($has_license_cols) {
    $query = 'SELECT id, name, email, role, phone, city, organization_name, organization_license, cinema_name, cinema_license, license_approved' . ($has_approval_col ? ', is_approved' : '') . ', created_at FROM users WHERE 1=1';
} else {
    $query = 'SELECT id, name, email, role, phone, city' . ($has_approval_col ? ', is_approved' : '') . ', created_at FROM users WHERE 1=1';
}
$params = [];

if ($search) {
    $query .= ' AND (name LIKE ? OR email LIKE ?)';
    $params = ['%'.$search.'%', '%'.$search.'%'];
}
if ($role_filter) {
    $query .= ' AND role = ?';
    $params[] = $role_filter;
}
$query .= ' ORDER BY created_at DESC LIMIT 100';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page = 'users';
include __DIR__ . '/admin_sidebar.php';
?>

<div class="col-md-9">
    <h2 class="title-font text-2xl mb-3">User Management</h2>
    <?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?php echo esc($msg); ?></div><?php endif; ?>
    
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 admin-controls">
                <div class="col-md-6 search-row">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo esc($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="user" <?php echo ($role_filter === 'user' ? 'selected' : ''); ?>>User</option>
                        <option value="organizer" <?php echo ($role_filter === 'organizer' ? 'selected' : ''); ?>>Organizer</option>
                        <option value="cinema_manager" <?php echo ($role_filter === 'cinema_manager' ? 'selected' : ''); ?>>Cinema Manager</option>
                        <option value="admin" <?php echo ($role_filter === 'admin' ? 'selected' : ''); ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Profile / License</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                                <tr>
                                        <td><?php echo esc($u['id']); ?></td>
                                        <td><?php echo esc($u['name']); ?></td>
                                        <td><?php echo esc($u['email']); ?></td>
                                        <td>
                                                <form method="post" style="display:inline;">
                                                        <input type="hidden" name="action" value="change_role">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                        <select name="role" class="form-select form-select-sm" onchange="this.form.submit();" <?php echo ($u['id'] == current_user_id() ? 'disabled' : ''); ?>>
                                                                <option value="user" <?php echo ($u['role'] === 'user' ? 'selected' : ''); ?>>User</option>
                                                                <option value="organizer" <?php echo ($u['role'] === 'organizer' ? 'selected' : ''); ?>>Organizer</option>
                                                                <option value="cinema_manager" <?php echo ($u['role'] === 'cinema_manager' ? 'selected' : ''); ?>>Cinema Manager</option>
                                                                <option value="admin" <?php echo ($u['role'] === 'admin' ? 'selected' : ''); ?>>Admin</option>
                                                        </select>
                                                </form>
                                        </td>
                                        <td>
                                                <?php
                                                    if (($u['role'] ?? '') === 'organizer') {
                                                        $org = esc($u['organization_name'] ?? '-');
                                                        $lic = $u['organization_license'] ?? '';
                                                        if ($lic && file_exists(__DIR__ . '/../assets/uploads/licenses/' . $lic)) {
                                                            $imgUrl = BASE_URL . '/assets/uploads/licenses/' . rawurlencode($lic);
                                                            echo $org . '<br><a href="' . $imgUrl . '" target="_blank"><img src="' . $imgUrl . '" style="height:60px;object-fit:cover;border-radius:6px;margin-top:6px;"></a>';
                                                        } else {
                                                            echo $org . '<br><small class="text-muted">License: ' . esc($lic ?: '-') . '</small>';
                                                        }
                                                    } elseif (($u['role'] ?? '') === 'cinema_manager') {
                                                        $cin = esc($u['cinema_name'] ?? '-');
                                                        $lic = $u['cinema_license'] ?? '';
                                                        if ($lic && file_exists(__DIR__ . '/../assets/uploads/licenses/' . $lic)) {
                                                            $imgUrl = BASE_URL . '/assets/uploads/licenses/' . rawurlencode($lic);
                                                            echo $cin . '<br><a href="' . $imgUrl . '" target="_blank"><img src="' . $imgUrl . '" style="height:60px;object-fit:cover;border-radius:6px;margin-top:6px;"></a>';
                                                        } else {
                                                            echo $cin . '<br><small class="text-muted">License: ' . esc($lic ?: '-') . '</small>';
                                                        }
                                                    } else {
                                                        echo esc($u['city'] ?? '-');
                                                    }
                                                ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                        <td>
                                                <?php if ($u['id'] != current_user_id()): ?>
                                                <form method="post" style="display:inline; margin-right:6px;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-reject admin-action" data-confirm="Delete this user?">Delete</button>
                                                </form>
                                                <?php if (!empty($u['organization_license']) || !empty($u['cinema_license'])): ?>
                                                    <?php if (empty($u['license_approved'])): ?>
                                                        <form method="post" style="display:inline;">
                                                            <input type="hidden" name="action" value="approve_license">
                                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-approve admin-action">Approve License</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">License OK</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if (isset($u['is_approved'])): ?>
                                                    <?php if (empty($u['is_approved'])): ?>
                                                        <form method="post" style="display:inline;margin-left:6px;">
                                                            <input type="hidden" name="action" value="approve_user">
                                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-approve admin-action">Approve Account</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">Account Approved</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                        </td>
                                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
