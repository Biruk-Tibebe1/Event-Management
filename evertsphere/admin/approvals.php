<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();
$page = 'approvals';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($action === 'approve_user' && $user_id) {
        $pdo->prepare('UPDATE users SET is_approved = 1 WHERE id = ?')->execute([$user_id]);
        flash_set('success', 'User account approved');
        header('Location: ' . BASE_URL . '/admin/approvals.php'); exit;
    }
    if ($action === 'approve_license' && $user_id) {
        $pdo->prepare('UPDATE users SET license_approved = 1 WHERE id = ?')->execute([$user_id]);
        flash_set('success', 'User license approved');
        header('Location: ' . BASE_URL . '/admin/approvals.php'); exit;
    }
    if ($action === 'reject_user' && $user_id) {
        // simple rejection: delete user (could be changed to mark rejected)
        $pdo->prepare('DELETE FROM users WHERE id = ? AND id != ?')->execute([$user_id, current_user_id()]);
        flash_set('success', 'User registration rejected and removed');
        header('Location: ' . BASE_URL . '/admin/approvals.php'); exit;
    }
}

// Fetch pending approvals
$stmt = $pdo->prepare("SELECT id, name, email, role, phone, city, organization_name, organization_license, cinema_name, cinema_license, license_approved, is_approved, created_at FROM users WHERE (is_approved = 0 OR license_approved = 0) ORDER BY created_at DESC");
$stmt->execute();
$pending = $stmt->fetchAll();

include __DIR__ . '/admin_sidebar.php';
?>

<div class="col-md-9">
  <h2 class="title-font text-2xl mb-3">Approvals</h2>
  <?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?php echo esc($msg); ?></div><?php endif; ?>

  <?php if (empty($pending)): ?>
    <div class="card"><div class="card-body">No pending approvals.</div></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>License</th><th>Account</th><th>Submitted</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($pending as $u): ?>
            <tr>
              <td><?php echo esc($u['name']); ?></td>
              <td><?php echo esc($u['email']); ?></td>
              <td><?php echo esc($u['role']); ?></td>
              <td>
                <?php if (!empty($u['organization_license']) || !empty($u['cinema_license'])): ?>
                  <?php $lic = $u['organization_license'] ?: $u['cinema_license'];
                    $imgPath = __DIR__ . '/../assets/uploads/licenses/' . ($lic ?: '');
                    if ($lic && file_exists($imgPath)) {
                      $imgUrl = BASE_URL . '/assets/uploads/licenses/' . rawurlencode($lic);
                      echo '<a href="' . $imgUrl . '" target="_blank"><img src="' . $imgUrl . '" style="height:60px;object-fit:cover;border-radius:6px;margin-top:6px;"></a>';
                    } else {
                      echo '<small class="text-muted">No license image</small>';
                    }
                  ?>
                <?php else: ?>
                  <small class="text-muted">N/A</small>
                <?php endif; ?>
              </td>
              <td>
                <?php echo empty($u['is_approved']) ? '<span class="badge bg-warning">Account Pending</span>' : '<span class="badge bg-success">Account OK</span>'; ?>
                <?php echo empty($u['license_approved']) ? '<span class="badge bg-warning ms-2">License Pending</span>' : ''; ?>
              </td>
              <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
              <td>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="approve_user">
                  <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <?php if (empty($u['is_approved'])): ?>
                    <button type="submit" class="btn btn-sm btn-approve admin-action">Approve Account</button>
                  <?php endif; ?>
                </form>

                <?php if (!empty($u['organization_license']) || !empty($u['cinema_license'])): ?>
                  <form method="post" style="display:inline;margin-left:6px;">
                    <input type="hidden" name="action" value="approve_license">
                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                    <?php if (empty($u['license_approved'])): ?>
                      <button type="submit" class="btn btn-sm btn-approve admin-action">Approve License</button>
                    <?php endif; ?>
                  </form>
                <?php endif; ?>

                <form method="post" style="display:inline;margin-left:6px;">
                  <input type="hidden" name="action" value="reject_user">
                  <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-reject admin-action" data-confirm="Reject and remove this registration?">Reject</button>
                </form>

              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
