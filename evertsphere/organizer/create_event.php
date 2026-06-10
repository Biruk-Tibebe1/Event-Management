<?php
require_once __DIR__ . '/../includes/header.php';
require_organizer();
$pdo = get_db();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $capacity = (int)($_POST['capacity'] ?? 0);

    if ($title === '') $errors[] = 'Title is required';
    if (strlen($title) < 3) $errors[] = 'Title must be at least 3 characters';
    if (strlen($title) > 200) $errors[] = 'Title must not exceed 200 characters';
    if ($event_date === '') $errors[] = 'Event date is required';
    if ($event_time === '') $errors[] = 'Event time is required';
    if (strtotime($event_date) < strtotime('today')) $errors[] = 'Event date cannot be in the past';
    if ($venue === '') $errors[] = 'Venue is required';
    if (strlen($venue) > 150) $errors[] = 'Venue must not exceed 150 characters';
    if ($city === '') $errors[] = 'City is required';
    if (strlen($description) > 5000) $errors[] = 'Description must not exceed 5000 characters';
    if ($price < 0) $errors[] = 'Price cannot be negative';
    if ($capacity <= 0 && $capacity !== 0) $errors[] = 'Capacity must be 0 or a positive number';
    if (strlen($category) > 50) $errors[] = 'Category must not exceed 50 characters';
    if ($slug === '') $slug = preg_replace('/[^a-z0-9]+/','-',strtolower($title));

    // handle poster upload with validation
    $filename = null;
    if (!empty($_FILES['poster']['tmp_name'])) {
      $up = $_FILES['poster'];
      if ($up['error'] === UPLOAD_ERR_OK) {
        if ($up['size'] > 2 * 1024 * 1024) { $errors[] = 'Poster must be <= 2MB'; }
        $info = @getimagesize($up['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/svg+xml'=>'svg'];
        if (!$info && !in_array(mime_content_type($up['tmp_name']), array_keys($allowed))) {
          $errors[] = 'Uploaded file must be an image';
        } else {
          $mime = $info['mime'] ?? mime_content_type($up['tmp_name']);
          $ext = $allowed[$mime] ?? pathinfo($up['name'], PATHINFO_EXTENSION);
          $filename = 'poster_' . time() . '.' . $ext;
          move_uploaded_file($up['tmp_name'], __DIR__ . '/../assets/uploads/' . $filename);
        }
      } else {
        $errors[] = 'Poster upload failed';
      }
    }

    if (empty($errors)) {
    $stmt = $pdo->prepare('INSERT INTO events (title, slug, description, category, event_date, event_time, venue, city, poster, price, capacity, organizer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $slug, $description, $category, $event_date, $event_time, $venue, $city, $filename, $price, $capacity, current_user_id(), 'published']);
    flash_set('success','Event created');
    header('Location: ' . BASE_URL . '/organizer/dashboard.php'); exit;
  }
}
?>
<div class="row">
  <div class="col-md-8">
    <div class="card create-event-card">
      <div class="card-body">
        <h2>Create Event</h2>
        <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>'.esc($e).'</div>';?></div><?php endif; ?>
        <form class="create-event-form" method="post" enctype="multipart/form-data" novalidate>
          <div class="mb-3"><label class="form-label">Title <span class="text-danger">*</span></label><input name="title" class="form-control" value="<?php echo esc($_POST['title'] ?? ''); ?>" required minlength="3" maxlength="200"></div>
          <div class="mb-3"><label class="form-label">Slug (optional)</label><input name="slug" class="form-control" value="<?php echo esc($_POST['slug'] ?? ''); ?>" pattern="[a-z0-9\-]*" maxlength="200"></div>
          <div class="mb-3"><label class="form-label">Category <span class="text-danger">*</span></label><input name="category" class="form-control" value="<?php echo esc($_POST['category'] ?? 'Festival'); ?>" required maxlength="50"></div>
          <div class="mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input name="event_date" type="date" class="form-control" value="<?php echo esc($_POST['event_date'] ?? ''); ?>" required></div>
          <div class="mb-3"><label class="form-label">Time <span class="text-danger">*</span></label><input name="event_time" type="time" class="form-control" value="<?php echo esc($_POST['event_time'] ?? ''); ?>" required></div>
          <div class="mb-3"><label class="form-label">Venue <span class="text-danger">*</span></label><input name="venue" class="form-control" value="<?php echo esc($_POST['venue'] ?? ''); ?>" required maxlength="150"></div>
          <div class="mb-3"><label class="form-label">City <span class="text-danger">*</span></label><input name="city" class="form-control" value="<?php echo esc($_POST['city'] ?? 'Addis Ababa'); ?>" required maxlength="50"></div>
          <div class="mb-3"><label class="form-label">Price <span class="text-danger">*</span></label><input name="price" type="number" step="0.01" class="form-control" value="<?php echo esc($_POST['price'] ?? '0'); ?>" required min="0" max="999999"></div>
          <div class="mb-3"><label class="form-label">Capacity <span class="text-danger">*</span></label><input name="capacity" type="number" class="form-control" value="<?php echo esc($_POST['capacity'] ?? '0'); ?>" required min="0" max="999999"></div>
          <div class="mb-3"><label class="form-label">Poster</label><input name="poster" type="file" class="form-control" accept="image/jpeg,image/png,image/gif"></div>
          <div class="mb-3"><label class="form-label">Description <span class="text-danger">*</span></label><textarea name="description" class="form-control" required minlength="10" maxlength="5000"><?php echo esc($_POST['description'] ?? ''); ?></textarea></div>
          <button class="btn btn-primary">Create</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
