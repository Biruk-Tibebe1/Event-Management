<?php
$page_title = 'My Profile';
$page_has_full_hero = true;
require_once __DIR__ . '/includes/header.php';
require_login();
$pdo = get_db();
$user = get_user_by_id(current_user_id());
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    if ($name === '') $errors[] = 'Name is required';
    if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters';
    if (strlen($name) > 100) $errors[] = 'Name must not exceed 100 characters';
    if (!empty($phone) && !preg_match('/^[0-9+\-\s()]*$/', $phone)) $errors[] = 'Phone number format is invalid';
    if (!empty($city) && strlen($city) > 50) $errors[] = 'City must not exceed 50 characters';
    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, city = ? WHERE id = ?');
        $stmt->execute([$name, $phone, $city, $user['id']]);
        flash_set('success','Profile updated');
        header('Location: ' . BASE_URL . '/profile.php'); exit;
    }
}
?>

<section class="full-bleed bg-animated" style="position:relative;">
  <div id="app-wrapper" class="auth-shell relative z-10 p-4">
    <div class="floating-orb" style="width:300px;height:300px;background:rgba(99,102,241,0.15);top:10%;left:5%;animation-delay:0s;"></div>
    <div class="floating-orb" style="width:200px;height:200px;background:rgba(168,85,247,0.12);bottom:15%;right:10%;animation-delay:2s;"></div>
    <div class="<?php echo $containerClass; ?>">
      <div class="w-full px-6 py-16 max-w-2xl mx-auto">
  <h1 class="font-heading text-4xl font-bold text-coffee-100 mb-12">My Profile</h1>

  <?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30">
      <ul class="space-y-1">
        <?php foreach($errors as $e): ?>
          <li class="text-red-300 text-sm flex items-start gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <span><?php echo esc($e); ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($msg = flash_get('success')): ?>
    <div class="mb-6 p-4 rounded-lg bg-green-500/10 border border-green-500/30 flex items-start gap-3">
      <i data-lucide="check-circle" class="w-5 h-5 text-green-400 flex-shrink-0 mt-0.5"></i>
      <span class="text-green-300"><?php echo esc($msg); ?></span>
    </div>
  <?php endif; ?>

  <div class="card-hover rounded-2xl bg-gradient-to-b from-coffee-900/30 to-stone-900/50 border border-coffee-700/20 backdrop-blur-sm p-8 profile-card">
    <div class="card-body">
      <form method="post" novalidate class="profile-form">
        <div class="grid-row">
          <label class="form-label">Full Name</label>
          <div><input name="name" class="form-control" value="<?php echo esc($user['name']); ?>" required minlength="2" maxlength="100"></div>
        </div>

        <div class="grid-row">
          <label class="form-label">Email Address</label>
          <div>
            <input class="form-control" value="<?php echo esc($user['email']); ?>" disabled>
            <p class="text-coffee-100/40 text-xs mt-1">Email cannot be changed</p>
          </div>
        </div>

        <div class="grid-row">
          <label class="form-label">Phone Number</label>
          <div><input name="phone" class="form-control" value="<?php echo esc($user['phone']); ?>" pattern="[0-9+\-\s()]*" maxlength="20" placeholder="+251..."></div>
        </div>

        <div class="grid-row">
          <label class="form-label">City</label>
          <div><input name="city" class="form-control" value="<?php echo esc($user['city']); ?>" maxlength="50" placeholder="Your city"></div>
        </div>

        <div class="grid-row">
          <label class="form-label"></label>
          <div>
            <button type="submit" class="btn btn-primary">
              <i data-lucide="save" class="inline w-4 h-4 mr-2"></i>Save Changes
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="mt-8 pt-8 border-t border-coffee-700/20">
    <a href="<?php echo BASE_URL; ?>/" class="inline-flex items-center gap-2 text-coffee-300 hover:text-coffee-200 transition-colors">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>Back to Home
    </a>
  </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
