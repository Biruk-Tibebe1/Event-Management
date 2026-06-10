<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

// Page indicator for the sidebar
$page = 'dashboard';

// Fetch dashboard stats
$counts = [];
$counts['users'] = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$counts['events'] = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$counts['pending_events'] = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'draft'")->fetchColumn();
// Pending approvals (accounts or licenses awaiting review)
$counts['pending_approvals'] = $pdo->query("SELECT COUNT(*) FROM users WHERE (is_approved = 0 OR license_approved = 0)")->fetchColumn();
?>
<style>
/* Dashboard styles: modern stat cards and table */
.stat-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:20px; }
.stat-card {
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
  border: 1px solid rgba(212,175,55,0.10);
  border-radius: 12px;
  padding: 18px;
  display:flex;
  gap:12px;
  align-items:center;
  transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}
.stat-card:hover { transform: translateY(-6px) scale(1.01); box-shadow: 0 18px 40px rgba(15,10,6,0.35); }
.stat-card .icon { width:52px; height:52px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#d4af37,#f4d03f); color:#1a0f0a; flex-shrink:0; }
.stat-card .info { flex:1; }
.stat-card .label { font-size:0.85rem; color:#f3e6c6; margin-bottom:4px; }
.stat-card .count { font-size:1.9rem; font-weight:800; color:#fff; }

@keyframes fadeUp { from { opacity:0; transform: translateY(12px);} to { opacity:1; transform:none; } }
.animate-fade-up { animation: fadeUp 0.7s ease both; }

.admin-table { width:100%; border-collapse:collapse; font-size:0.95rem; color: #f9f6f0; }
.admin-table thead th { text-align:left; padding:10px 12px; font-weight:700; color:#f3e6c6; background: rgba(255,255,255,0.02); }
.admin-table tbody tr { transition: background .12s ease, transform .12s ease; }
.admin-table tbody tr:hover { background: rgba(255,255,255,0.02); transform: translateY(-2px); }
.admin-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.03); }
.stat-small { font-size:0.75rem; color:#e6d9b0; }
.btn-rounded { padding:8px 12px; border-radius:999px; }
</style>

<?php include __DIR__ . '/admin_sidebar.php'; ?>

<div class="col-md-9">
  <h2 class="title-font text-3xl mb-4">Admin Dashboard</h2>

  <div class="stat-grid">
    <div class="stat-card animate-fade-up">
      <div class="icon"><i data-lucide="users"></i></div>
      <div class="info">
        <div class="label">Total Users</div>
        <div class="count"><span class="stat-count" data-target="<?php echo esc($counts['users']); ?>">0</span></div>
      </div>
    </div>

    <div class="stat-card animate-fade-up">
      <div class="icon"><i data-lucide="calendar"></i></div>
      <div class="info">
        <div class="label">Events (<?php echo esc($counts['pending_events']); ?> pending)</div>
        <div class="count"><span class="stat-count" data-target="<?php echo esc($counts['events']); ?>">0</span></div>
      </div>
    </div>


    <div class="stat-card animate-fade-up">
      <div class="icon"><i data-lucide="check-square"></i></div>
      <div class="info">
        <div class="label">Pending Approvals</div>
        <div class="count"><span class="stat-count" data-target="<?php echo esc($counts['pending_approvals']); ?>">0</span></div>
        <div class="stat-small mt-1"><a href="<?php echo BASE_URL; ?>/admin/approvals.php" class="text-amber-200 underline">Review now</a></div>
      </div>
    </div>
  </div>

  <?php
    // Show recent pending registrations for quick action
    $recentPendingStmt = $pdo->prepare("SELECT id, name, email, role, is_approved, license_approved, created_at FROM users WHERE (is_approved = 0 OR license_approved = 0) ORDER BY created_at DESC LIMIT 6");
    $recentPendingStmt->execute();
    $recentPending = $recentPendingStmt->fetchAll();
  ?>

  <?php if (!empty($recentPending)): ?>
    <div class="card mb-4">
      <div class="card-body">
        <h3 class="text-lg font-semibold mb-3">Recent Pending Registrations</h3>
        <div class="table-responsive">
          <table class="admin-table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Account</th><th>License</th><th>Submitted</th></tr></thead>
            <tbody>
              <?php foreach ($recentPending as $rp): ?>
                <tr>
                  <td><?php echo esc($rp['name']); ?></td>
                  <td><?php echo esc($rp['email']); ?></td>
                  <td><?php echo esc($rp['role']); ?></td>
                  <td><?php echo empty($rp['is_approved']) ? '<span class="badge bg-warning">Pending</span>' : '<span class="badge bg-success">Approved</span>'; ?></td>
                  <td><?php echo empty($rp['license_approved']) ? '<span class="badge bg-warning">Pending</span>' : '<span class="badge bg-success">Approved</span>'; ?></td>
                  <td><?php echo date('M d, Y', strtotime($rp['created_at'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>

<script>
  (function(){
    // Animate numeric counters in stat cards
    document.querySelectorAll('.stat-count').forEach(function(el){
      var target = parseInt(el.dataset.target || el.textContent,10) || 0;
      var duration = 1200;
      var startTime = null;
      function step(ts){
        if (!startTime) startTime = ts;
        var progress = Math.min((ts - startTime) / duration, 1);
        el.textContent = Math.floor(progress * target);
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = target;
      }
      requestAnimationFrame(step);
    });
  })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
