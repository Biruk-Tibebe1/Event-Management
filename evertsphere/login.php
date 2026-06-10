<?php
$page_title = 'Login';
$page_has_full_hero = true;

// Start session and include helpers before sending any output so AJAX responses
// can return JSON without header/body conflicts.
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] === '1');

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
  if ($password === '') $errors[] = 'Password required';

  // If AJAX and validation failed, return JSON immediately
  if ($isAjax && !empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $errors[0]]);
    exit;
  }

  if (empty($errors)) {
    $res = authenticate_user($email, $password);

    if (!empty($isAjax)) {
      header('Content-Type: application/json');
      if ($res['success']) {
        echo json_encode(['success' => true, 'redirect' => BASE_URL . '/']);
      } else {
        echo json_encode(['success' => false, 'message' => $res['message']]);
      }
      exit;
    }

    if ($res['success']) {
      header('Location: ' . BASE_URL . '/');
      exit;
    } else {
      $errors[] = $res['message'];
    }
  }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="full-bleed bg-animated" style="position:relative;">
  <div id="app-wrapper" class="auth-shell relative z-10 flex items-center justify-center p-4">
    <div class="floating-orb" style="width:300px;height:300px;background:rgba(99,102,241,0.15);top:10%;left:5%;animation-delay:0s;"></div>
    <div class="floating-orb" style="width:200px;height:200px;background:rgba(168,85,247,0.12);bottom:15%;right:10%;animation-delay:2s;"></div>

    <div class="form-card rounded-3xl p-8 md:p-10 w-full max-w-md relative z-20">
      <div class="text-center mb-6">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-amber-700 to-orange-800 flex items-center justify-center" style="animation: pulseGlow 3s ease infinite;">
          <i data-lucide="user" style="width:28px;height:28px;color:white;"></i>
        </div>
        <h1 id="page-title" class="text-3xl font-bold mb-1">Welcome Back</h1>
        <p id="subtitle" class="text-gray-300 text-sm">Sign in to your Evertsphere account</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="mb-4 p-3 rounded-lg bg-red-700/10 border border-red-700/20 text-red-200 text-sm">
          <ul class="list-disc ml-4">
            <?php foreach ($errors as $e): ?>
              <li><?php echo esc($e); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

          <?php if ($msg = flash_get('success')): ?><div class="mb-4 p-3 rounded-lg bg-green-700/10 border border-green-700/20 text-green-200 text-sm"><?php echo esc($msg); ?></div><?php endif; ?>

      <form id="loginForm" method="post" novalidate class="space-y-4">
        <div>
          <label for="email" class="text-sm text-gray-300 block mb-2">Email <span class="text-amber-400">*</span></label>
          <input id="email" name="email" type="email" required class="input-field w-full px-4 py-3 rounded-xl" value="<?php echo esc($_POST['email'] ?? ''); ?>" placeholder="you@example.com">
          <p id="error-email" class="text-red-300 text-sm mt-1 hidden" aria-live="polite"></p>
        </div>

        <div>
          <label for="password" class="text-sm text-gray-300 block mb-2">Password <span class="text-amber-400">*</span></label>
          <input id="password" name="password" type="password" required class="input-field w-full px-4 py-3 rounded-xl" placeholder="••••••••">
          <p id="error-password" class="text-red-300 text-sm mt-1 hidden" aria-live="polite"></p>
        </div>

        <div class="flex gap-3">
          <button type="submit" id="submit-btn" class="submit-btn w-full py-3 rounded-xl text-white font-semibold"> <span id="btn-text">Sign In</span> <span id="btn-loader" class="hidden"><svg class="animate-spin h-5 w-5 mx-auto text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" /></svg></span></button>
        </div>
      </form>

      <div class="mt-6 text-center text-gray-300 text-sm">Don't have an account? <a href="<?php echo BASE_URL; ?>/register.php" class="text-amber-400 underline">Create Account</a></div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
