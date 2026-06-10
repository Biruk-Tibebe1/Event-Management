<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();
$errors = [];
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // VENUES
    if ($action === 'add_venue') {
        $name = trim($_POST['venue_name'] ?? '');
        $city = trim($_POST['venue_city'] ?? '');
        $capacity = (int)($_POST['venue_capacity'] ?? 0);
        if ($name && $city && $capacity > 0) {
            $pdo->prepare('INSERT INTO venues (name, city, capacity) VALUES (?, ?, ?)')->execute([$name, $city, $capacity]);
            $success = 'Venue added successfully';
        } else {
            $errors[] = 'Please fill all venue fields';
        }
    } elseif ($action === 'delete_venue') {
        $venue_id = (int)($_POST['venue_id'] ?? 0);
        $pdo->prepare('DELETE FROM venues WHERE id = ?')->execute([$venue_id]);
        $success = 'Venue deleted';
    }
    
    // CITIES
    elseif ($action === 'add_city') {
        $name = trim($_POST['city_name'] ?? '');
        $region = trim($_POST['city_region'] ?? '');
        if ($name) {
            $pdo->prepare('INSERT IGNORE INTO cities (name, region) VALUES (?, ?)')->execute([$name, $region]);
            $success = 'City added successfully';
        } else {
            $errors[] = 'City name is required';
        }
    } elseif ($action === 'delete_city') {
        $city_id = (int)($_POST['city_id'] ?? 0);
        $pdo->prepare('DELETE FROM cities WHERE id = ?')->execute([$city_id]);
        $success = 'City deleted';
    }
}

// Fetch data
$venues = $pdo->query('SELECT * FROM venues ORDER BY name')->fetchAll();
$cities = $pdo->query('SELECT * FROM cities ORDER BY name')->fetchAll();
$cinemas = $pdo->query('SELECT * FROM cinemas ORDER BY name')->fetchAll();

$page = 'content';
include __DIR__ . '/admin_sidebar.php';
?>

<div class="col-md-9">
    <h2 class="mb-3">Content Management</h2>
    <?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo esc($e).'<br>'; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo esc($success); ?></div><?php endif; ?>
    
    <!-- VENUES -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Manage Venues</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="venue_name" class="form-control" placeholder="Venue name" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="venue_city" class="form-control" placeholder="City" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="venue_capacity" class="form-control" placeholder="Capacity" required min="1">
                </div>
                <div class="col-md-2">
                    <input type="hidden" name="action" value="add_venue">
                    <button class="btn btn-primary w-100">Add</button>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead class="table-light">
                        <tr><th>Name</th><th>City</th><th>Capacity</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($venues as $v): ?>
                        <tr>
                            <td><?php echo esc($v['name']); ?></td>
                            <td><?php echo esc($v['city']); ?></td>
                            <td><?php echo esc($v['capacity']); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_venue">
                                    <input type="hidden" name="venue_id" value="<?php echo $v['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-reject admin-action" data-confirm="Delete?">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- CITIES -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Manage Cities</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input type="text" name="city_name" class="form-control" placeholder="City name" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="city_region" class="form-control" placeholder="Region (optional)">
                </div>
                <div class="col-md-3">
                    <input type="hidden" name="action" value="add_city">
                    <button class="btn btn-primary w-100">Add</button>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Region</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cities as $c): ?>
                        <tr>
                            <td><?php echo esc($c['name']); ?></td>
                            <td><?php echo esc($c['region'] ?? '-'); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_city">
                                    <input type="hidden" name="city_id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-reject admin-action" data-confirm="Delete?">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
