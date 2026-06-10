<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();
$errors = [];
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = trim($_POST['setting_key'] ?? '');
    $value = trim($_POST['setting_value'] ?? '');
    
    if ($key && strlen($value) <= 500) {
        // Check if setting exists
        $stmt = $pdo->prepare('SELECT id FROM admin_settings WHERE meta_key = ?');
        $stmt->execute([$key]);
        if ($stmt->fetch()) {
            $pdo->prepare('UPDATE admin_settings SET meta_value = ? WHERE meta_key = ?')->execute([$value, $key]);
        } else {
            $pdo->prepare('INSERT INTO admin_settings (meta_key, meta_value) VALUES (?, ?)')->execute([$key, $value]);
        }
        $success = 'Setting saved successfully';
    } else {
        $errors[] = 'Invalid setting key or value too long';
    }
}

// Fetch settings
$settings = [];
$stmt = $pdo->query('SELECT meta_key, meta_value FROM admin_settings ORDER BY meta_key');
while ($row = $stmt->fetch()) {
    $settings[$row['meta_key']] = $row['meta_value'];
}

$page = 'settings';
include __DIR__ . '/admin_sidebar.php';
?>

<div class="col-md-9">
    <h2 class="mb-3">System Settings</h2>
    <?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo esc($e).'<br>'; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo esc($success); ?></div><?php endif; ?>
    
    <!-- Pricing Settings -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Pricing Rules</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Default Event Ticket Price ($)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="setting_key" value="default_event_price" hidden>
                            <input type="number" name="setting_value" class="form-control" step="0.01" value="<?php echo htmlspecialchars($settings['default_event_price'] ?? '25.00'); ?>" placeholder="0.00">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </div>
            </form>
            
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Cinema Ticket Price ($)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="setting_key" value="cinema_ticket_price" hidden>
                            <input type="number" name="setting_value" class="form-control" step="0.01" value="<?php echo htmlspecialchars($settings['cinema_ticket_price'] ?? '10.00'); ?>" placeholder="0.00">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </div>
            </form>
            
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Service Fee Percentage (%)</label>
                        <div class="input-group">
                            <input type="number" name="setting_key" value="service_fee_percent" hidden>
                            <input type="number" name="setting_value" class="form-control" step="0.1" value="<?php echo htmlspecialchars($settings['service_fee_percent'] ?? '5.0'); ?>" placeholder="0.0">
                            <span class="input-group-text">%</span>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- General Settings -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">General Settings</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="setting_key" value="site_name" hidden>
                    <input type="text" name="setting_value" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'EthioEvents'); ?>">
                    <button type="submit" class="btn btn-primary mt-2">Save</button>
                </div>
            </form>
            
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Support Email</label>
                    <input type="text" name="setting_key" value="support_email" hidden>
                    <input type="email" name="setting_value" class="form-control" value="<?php echo htmlspecialchars($settings['support_email'] ?? 'support@ethioevents.com'); ?>">
                    <button type="submit" class="btn btn-primary mt-2">Save</button>
                </div>
            </form>
            
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="setting_key" value="contact_phone" hidden>
                    <input type="tel" name="setting_value" class="form-control" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? '+251-123-456-7890'); ?>">
                    <button type="submit" class="btn btn-primary mt-2">Save</button>
                </div>
            </form>
            
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Maintenance Mode</label>
                    <input type="text" name="setting_key" value="maintenance_mode" hidden>
                    <select name="setting_value" class="form-control">
                        <option value="0" <?php echo ($settings['maintenance_mode'] === '0' ? 'selected' : ''); ?>>Off</option>
                        <option value="1" <?php echo ($settings['maintenance_mode'] === '1' ? 'selected' : ''); ?>>On</option>
                    </select>
                    <button type="submit" class="btn btn-primary mt-2">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- All Settings -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">All Settings</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr><th>Key</th><th>Value</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settings as $key => $value): ?>
                        <tr>
                            <td><code><?php echo esc($key); ?></code></td>
                            <td><?php echo esc(substr($value, 0, 100)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
