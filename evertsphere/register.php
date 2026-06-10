<?php
$page_title = 'Register';
$page_has_full_hero = true;
// Start session and load core helpers before processing so redirects work before any output
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Helper: save uploaded license image and return filename on success
function save_license_upload($file) {
  if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    return ['success' => false, 'error' => 'No file uploaded'];
  }
  $maxSize = 10 * 1024 * 1024; // 10MB
  if ($file['size'] > $maxSize) {
    return ['success' => false, 'error' => 'File too large (max 10MB)'];
  }
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($file['tmp_name']);
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/tiff' => 'tif'
  ];
  if (!isset($allowed[$mime])) {
    return ['success' => false, 'error' => 'Unsupported file type. Use JPG/PNG/WEBP/TIFF'];
  }
  $ext = $allowed[$mime];
  $uploadDir = __DIR__ . '/assets/uploads/licenses';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
  try {
    $filename = 'license_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  } catch (Exception $e) {
    $filename = 'license_' . time() . '_' . uniqid() . '.' . $ext;
  }
  $dest = $uploadDir . '/' . $filename;
  if (!move_uploaded_file($file['tmp_name'], $dest)) {
    return ['success' => false, 'error' => 'Failed to save uploaded file'];
  }
  @chmod($dest, 0644);
  return ['success' => true, 'filename' => $filename];
}

$errors = [];
$success_message = '';
$pending = $_SESSION['pending_registration'] ?? null;
$step = $pending ? 'verify' : 'register';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $otp = trim($_POST['otp'] ?? '');

        if (!$pending) {
            $errors[] = 'No registration in progress. Please start again.';
            $step = 'register';
        } else {
            if ($otp === '') {
                $errors[] = 'Enter the OTP sent to your email';
            } elseif (time() > ($pending['expires'] ?? 0)) {
                $errors[] = 'OTP has expired. Please resend the code.';
                $step = 'verify';
            } elseif ($otp !== ($pending['otp'] ?? '')) {
              $errors[] = 'Invalid OTP. Please try again.';
              $step = 'verify';
            } else {
              $res = register_user(
                $pending['name'],
                $pending['email'],
                $pending['password'],
                $pending['role'] ?? 'organizer',
                $pending['phone'],
                $pending['city'],
                $pending['organization_name'] ?? null,
                $pending['organization_license'] ?? null,
                $pending['cinema_name'] ?? null,
                $pending['cinema_license'] ?? null
              );
                if ($res['success']) {
                  unset($_SESSION['pending_registration']);
                  // Do not auto-login: accounts require admin approval before activation.
                  // Show a friendly pending message on this page instead of redirecting (avoids "headers already sent" warnings).
                  $success_message = 'Your registration is under review and pending approval. Please wait for confirmation; you will be notified by email.';
                  $step = 'registered';
                } else {
                  $errors[] = $res['message'];
                  $step = 'register';
                }
            }
        }
    } elseif (isset($_POST['resend_otp']) && $pending) {
        $pending['otp'] = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pending['expires'] = time() + 600;
        $_SESSION['pending_registration'] = $pending;
        send_email_simulate(
            $pending['email'],
            'Your EthioEvents OTP Code',
            '<p>Your EthioEvents verification code is <strong>' . esc($pending['otp']) . '</strong>.</p><p>Use it within 10 minutes.</p>'
        );
        $success_message = 'A new OTP has been sent to your email address.';
        $step = 'verify';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $city = trim($_POST['city'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = trim($_POST['role'] ?? 'organizer');
        $organization_name = trim($_POST['organization_name'] ?? '');
        $organization_license_file = $_FILES['organization_license_file'] ?? null;
        $cinema_name = trim($_POST['cinema_name'] ?? '');
        $cinema_license_file = $_FILES['cinema_license_file'] ?? null;

        if ($name === '') $errors[] = 'Name is required';
        if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters';
        if (strlen($name) > 100) $errors[] = 'Name must not exceed 100 characters';
        // Only allow letters and spaces in name (A-Z, a-z). No numbers or special characters.
        if (!preg_match('/^[A-Za-z ]+$/', $name)) $errors[] = 'Name must contain only letters and spaces (A-Z).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
        if ($phone === '') $errors[] = 'Phone number is required';
        elseif (!preg_match('/^[0-9+\-\s()]*$/', $phone)) $errors[] = 'Phone number format is invalid';
        if ($city === '') $errors[] = 'City is required';
        elseif (strlen($city) > 50) $errors[] = 'City must not exceed 50 characters';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
        if (strlen($password) > 128) $errors[] = 'Password must not exceed 128 characters';
        if ($password !== $password_confirm) $errors[] = 'Passwords do not match';
        if (empty($_POST['terms'])) $errors[] = 'You must agree to the Terms & Conditions';

        // role-specific validation: require organization or cinema info
        if ($role === 'organizer') {
          if ($organization_name === '') $errors[] = 'Organization name is required for organizers';
          $orgFileErr = $organization_license_file['error'] ?? UPLOAD_ERR_NO_FILE;
          if ($orgFileErr !== UPLOAD_ERR_OK) $errors[] = 'Organization license image is required for organizers';
        } elseif ($role === 'cinema_manager') {
          if ($cinema_name === '') $errors[] = 'Cinema name is required for cinema managers';
          $cinFileErr = $cinema_license_file['error'] ?? UPLOAD_ERR_NO_FILE;
          if ($cinFileErr !== UPLOAD_ERR_OK) $errors[] = 'Cinema license image is required for cinema managers';
        }

        if (empty($errors)) {
            if (get_user_by_email($email)) {
                $errors[] = 'Email already registered';
            } else {
              // Only allow organizers and cinema managers to self-register
              $allowed_roles = ['organizer', 'cinema_manager'];
              if (!in_array($role, $allowed_roles)) {
                $role = 'organizer';
              }

              // process uploads (if present) and save filenames
              $uploaded_org_filename = null;
              $uploaded_cinema_filename = null;
              if (($organization_license_file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                  $up = save_license_upload($organization_license_file);
                  if ($up['success']) {
                      $uploaded_org_filename = $up['filename'];
                  } else {
                      $errors[] = 'Organization license upload error: ' . $up['error'];
                  }
              }
              if (($cinema_license_file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                  $up2 = save_license_upload($cinema_license_file);
                  if ($up2['success']) {
                      $uploaded_cinema_filename = $up2['filename'];
                  } else {
                      $errors[] = 'Cinema license upload error: ' . $up2['error'];
                  }
              }

              if (!empty($errors)) {
                // upload or validation failed; fall back to showing errors
              } else {
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['pending_registration'] = [
                  'name' => $name,
                  'email' => $email,
                  'password' => $password,
                  'phone' => $phone,
                  'city' => $city,
                  'role' => $role,
                  'organization_name' => $organization_name,
                  'organization_license' => $uploaded_org_filename,
                  'cinema_name' => $cinema_name,
                  'cinema_license' => $uploaded_cinema_filename,
                  'otp' => $otp,
                  'expires' => time() + 600,
                ];
                send_email_simulate(
                    $email,
                    'Your EthioEvents OTP Code',
                    '<p>Your EthioEvents verification code is <strong>' . esc($otp) . '</strong>.</p><p>Use this code within 10 minutes to complete registration.</p>'
                );
                $success_message = 'An OTP has been sent to your email. Enter it below to complete registration.';
                $pending = $_SESSION['pending_registration'];
                $step = 'verify';

                  }
              }
            }
          }
          }

           $pending = $_SESSION['pending_registration'] ?? null;

          // Include the site header (loads Tailwind, fonts, and site CSS/JS)
          require_once __DIR__ . '/includes/header.php';

          ?>

<!-- Fancy registration UI (reference-inspired) -->
<section class="full-bleed bg-animated" style="position:relative;">
  <div id="app-wrapper" class="auth-shell relative z-10 flex items-center justify-center p-4">
    <div class="floating-orb" style="width:300px;height:300px;background:rgba(99,102,241,0.15);top:10%;left:5%;animation-delay:0s;"></div>
    <div class="floating-orb" style="width:200px;height:200px;background:rgba(168,85,247,0.12);bottom:15%;right:10%;animation-delay:2s;"></div>
    <div class="form-card register-panel rounded-3xl p-8 md:p-10 w-full max-w-md relative z-20">
      <div class="text-center mb-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-amber-700 to-orange-800 flex items-center justify-center pulseGlow">
          <i data-lucide="user-plus" style="width:28px;height:28px;color:white;"></i>
        </div>
        <h1 id="page-title" class="text-3xl font-bold text-white mb-1"><?php echo $step === 'verify' ? 'Verify Your Email' : 'Create Account'; ?></h1>
        <p id="subtitle" class="text-gray-300 text-sm"><?php echo $step === 'verify' ? 'Enter the code we sent to your email' : 'Register as an organizer or cinema manager'; ?></p>
      </div>

      <?php if (!empty($success_message)): ?>
        <div class="mb-4 p-3 rounded-lg bg-green-700/10 border border-green-700/20 text-green-200 text-sm">
          <?php echo esc($success_message); ?>
        </div>
      <?php endif; ?>

      <!-- Server-side validation messages suppressed for cleaner UI -->

      <?php if ($step === 'verify' && $pending): ?>
        <form method="post" id="verifyForm" class="space-y-4" novalidate>
          <div class="text-sm text-gray-300">Registering as: <?php echo esc((($pending['role'] ?? '') === 'cinema_manager') ? 'Cinema Manager' : 'Event Organizer'); ?></div>
          <div class="input-group relative">
            <input id="otp" name="otp" type="text" maxlength="6" required class="input-field w-full px-4 py-3.5 rounded-xl placeholder-transparent outline-none" placeholder="Enter 6-digit code">
            <label for="otp" class="input-label absolute left-4 top-3.5 text-gray-400 pointer-events-none">Verification Code <span class="text-amber-400">*</span></label>
          </div>
          <div class="flex gap-3">
            <button type="submit" name="verify_otp" class="submit-btn w-full py-3 rounded-xl text-white font-semibold">Verify & Create Account</button>
            <button type="submit" name="resend_otp" class="w-28 py-3 rounded-xl border border-gray-600 text-gray-200">Resend</button>
          </div>
        </form>
      <?php elseif ($step === 'registered'): ?>
        <div class="mt-4 p-4 rounded-lg bg-green-700/10 border border-green-700/20 text-green-200 text-sm">
          <?php echo esc($success_message ?: 'Your registration is pending approval.'); ?>
          <div class="mt-3"><a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary">Go to Login</a></div>
        </div>
      <?php else: ?>
        <form method="post" id="registerForm" class="space-y-4" novalidate enctype="multipart/form-data">
          <div class="input-group relative">
            <input id="name" name="name" type="text" required class="input-field w-full px-4 py-3.5 rounded-xl placeholder-transparent outline-none" placeholder="Full name" value="<?php echo esc($_POST['name'] ?? ''); ?>">
            <label for="name" class="input-label absolute left-4 top-3.5">Full Name <span class="text-amber-400">*</span></label>
            <p id="error-name" class="text-red-300 text-sm mt-1 hidden" aria-live="polite"></p>
          </div>

          <div class="input-group relative">
            <input id="email" name="email" type="email" required class="input-field w-full px-4 py-3.5 rounded-xl placeholder-transparent outline-none" placeholder="you@example.com" value="<?php echo esc($_POST['email'] ?? ''); ?>">
            <label for="email" class="input-label absolute left-4 top-3.5">Email <span class="text-amber-400">*</span></label>
            <p id="error-email" class="text-red-300 text-sm mt-1 hidden" aria-live="polite"></p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="input-group relative">
              <input id="phone" name="phone" type="text" required class="input-field w-full px-4 py-3.5 rounded-xl placeholder-transparent outline-none" placeholder="+251..." value="<?php echo esc($_POST['phone'] ?? ''); ?>">
              <label for="phone" class="input-label absolute left-4 top-3.5">Phone <span class="text-amber-400">*</span></label>
              <p id="error-phone" class="text-red-300 text-sm mt-1 hidden" aria-live="polite"></p>
            </div>
            <div class="input-group relative">
              <input id="city" name="city" type="text" required class="input-field w-full px-4 py-3.5 rounded-xl placeholder-transparent outline-none" placeholder="City" value="<?php echo esc($_POST['city'] ?? ''); ?>">
              <label for="city" class="input-label absolute left-4 top-3.5">City <span class="text-amber-400">*</span></label>
              <p id="error-city" class="text-red-300 text-sm mt-1 hidden" aria-live="polite"></p>
            </div>
          </div>

          <div>
            <label class="text-sm text-gray-300 block mb-2">Register As <span class="text-amber-400">*</span></label>
            <div class="flex gap-4">
              <label class="inline-flex items-center gap-2"><input id="role_organizer" type="radio" name="role" value="organizer" <?php echo (($_POST['role'] ?? '') === 'organizer' || empty($_POST['role'])) ? 'checked' : ''; ?> class="form-radio"> <span>Organizer</span></label>
              <label class="inline-flex items-center gap-2"><input id="role_cinema_manager" type="radio" name="role" value="cinema_manager" <?php echo (($_POST['role'] ?? '') === 'cinema_manager') ? 'checked' : ''; ?> class="form-radio"> <span>Cinema Manager</span></label>
            </div>
            <p id="error-role" class="text-red-300 text-sm mt-1 hidden" aria-live="polite"></p>
          </div>

          <div class="org-fields mt-2 <?php echo (($_POST['role'] ?? '') === 'organizer') ? '' : 'hidden'; ?>">
            <div class="input-group relative">
              <input id="organization_name" name="organization_name" type="text" class="input-field w-full px-4 py-3.5 rounded-xl placeholder-transparent outline-none" placeholder="Organization name" value="<?php echo esc($_POST['organization_name'] ?? ''); ?>">
              <label for="organization_name" class="input-label absolute left-4 top-3.5">Organization Name</label>
            </div>
            <div class="input-group relative mt-3">
              <input id="organization_license_file" name="organization_license_file" type="file" accept="image/*" class="input-field w-full px-4 py-3.5 rounded-xl placeholder-transparent outline-none bg-white/5" />
              <label for="organization_license_file" class="input-label absolute left-4 top-3.5">Organization License (upload image)</label>
              <p class="text-xs text-gray-400 mt-2">Upload a high-quality scanned license (JPG/PNG/WEBP). Max 10MB. This will be checked and approved by an admin.</p>
            </div>
          </div>

          <div class="cinema-fields mt-2 <?php echo (($_POST['role'] ?? '') === 'cinema_manager') ? '' : 'hidden'; ?>">
            <div class="input-group relative">
              <input id="cinema_name" name="cinema_name" type="text" class="input-field w-full px-4 py-3.5 rounded-xl placeholder-transparent outline-none" placeholder="Cinema name" value="<?php echo esc($_POST['cinema_name'] ?? ''); ?>">
              <label for="cinema_name" class="input-label absolute left-4 top-3.5">Cinema Name</label>
            </div>
            <div class="input-group relative mt-3">
              <input id="cinema_license_file" name="cinema_license_file" type="file" accept="image/*" class="input-field w-full px-4 py-3.5 rounded-xl placeholder-transparent outline-none bg-white/5" />
              <label for="cinema_license_file" class="input-label absolute left-4 top-3.5">Cinema License (upload image)</label>
              <p class="text-xs text-gray-400 mt-2">Upload a high-quality scanned license (JPG/PNG/WEBP). Max 10MB. This will be checked and approved by an admin.</p>
            </div>
          </div>

          <div class="input-group relative">
            <input id="password" name="password" type="password" required class="input-field show-placeholder w-full px-4 py-3.5 rounded-xl pr-20 outline-none" placeholder="Password *">
            <label for="password" class="input-label absolute left-4 top-3.5" style="display:none;">Password <span class="text-amber-400">*</span></label>
            <button type="button" class="toggle-visibility absolute right-3 top-3.5 text-gray-300" onclick="togglePassword('password', this)"> <i data-lucide="eye" style="width:18px;height:18px;color:rgba(255,255,255,0.75);"></i> </button>
            <!-- Password strength bars removed per UX request -->
          </div>

          <div class="input-group relative">
            <input id="password_confirm" name="password_confirm" type="password" required class="input-field show-placeholder w-full px-4 py-3.5 rounded-xl outline-none" placeholder="Confirm Password *">
            <label for="password_confirm" class="input-label absolute left-4 top-3.5" style="display:none;">Confirm Password <span class="text-amber-400">*</span></label>
            <button type="button" class="toggle-visibility absolute right-3 top-3.5 text-gray-300" onclick="togglePassword('password_confirm', this)"> <i data-lucide="eye" style="width:18px;height:18px;color:rgba(255,255,255,0.75);"></i> </button>
            <p id="match-msg" class="text-xs mt-1 h-4"></p>
          </div>

          <div class="input-group flex items-center gap-3">
            <input id="terms" name="terms" type="checkbox" value="1" required class="w-4 h-4 accent-amber-700"> <label for="terms" class="text-gray-300 text-sm">I agree to the <a href="#" id="terms-link" class="text-amber-400 underline">Terms &amp; Conditions</a></label>
          </div>
          <p id="error-terms" class="text-red-300 text-sm mt-1 hidden" aria-live="polite"></p>

          <p id="error-msg" class="text-red-400 text-sm h-5 text-center"></p>

          <button type="submit" id="submit-btn" class="submit-btn w-full py-3 rounded-xl text-white font-semibold text-lg"> <span id="btn-text">Create Account</span> <span id="btn-loader" class="hidden"><svg class="animate-spin h-5 w-5 mx-auto text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" /></svg></span></button>
        </form>
      <?php endif; ?>

      <div class="flex items-center my-6 input-group" style="animation-delay:0.6s;">
        <div class="flex-1 h-px bg-gray-700"></div><span class="px-3 text-gray-500 text-sm">or sign up with</span>
        <div class="flex-1 h-px bg-gray-700"></div>
      </div>
      <div class="flex gap-3 justify-center input-group" style="animation-delay:0.7s;">
        <button class="social-btn w-12 h-12 rounded-xl flex items-center justify-center">
          <svg width="20" height="20" viewbox="0 0 24 24" fill="white"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" /><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" /><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05" /><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" /></svg>
        </button>
        <button class="social-btn w-12 h-12 rounded-xl flex items-center justify-center">
          <svg width="20" height="20" viewbox="0 0 24 24" fill="white"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.337-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.161 22 16.416 22 12c0-5.523-4.477-10-10-10z" /></svg>
        </button>
        <button class="social-btn w-12 h-12 rounded-xl flex items-center justify-center">
          <svg width="20" height="20" viewbox="0 0 24 24" fill="#1DA1F2"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" /></svg>
        </button>
      </div>

      <p class="text-center text-gray-400 text-sm mt-6 input-group" style="animation-delay:0.8s;">Already have an account? <a href="<?php echo BASE_URL; ?>/login.php" class="text-amber-400 underline">Sign In</a></p>
    </div>
  </div>
</section>
<!-- Removed legacy duplicate container to avoid duplicated UI — keeping the fancy full-bleed registration panel above. -->

<script>
  (function(){
    const orgFields = document.querySelector('.org-fields');
    const cinemaFields = document.querySelector('.cinema-fields');
    const orgName = document.getElementById('organization_name');
    const orgLicenseFile = document.getElementById('organization_license_file');
    const cinemaName = document.getElementById('cinema_name');
    const cinemaLicenseFile = document.getElementById('cinema_license_file');

    function toggleRole() {
      const role = document.querySelector('input[name="role"]:checked')?.value;
      if (role === 'organizer') {
        orgFields?.classList.remove('hidden');
        cinemaFields?.classList.add('hidden');
        if (orgName) orgName.required = true;
        if (orgLicenseFile) orgLicenseFile.required = true;
        if (cinemaName) cinemaName.required = false;
        if (cinemaLicenseFile) cinemaLicenseFile.required = false;
      } else if (role === 'cinema_manager') {
        cinemaFields?.classList.remove('hidden');
        orgFields?.classList.add('hidden');
        if (cinemaName) cinemaName.required = true;
        if (cinemaLicenseFile) cinemaLicenseFile.required = true;
        if (orgName) orgName.required = false;
        if (orgLicenseFile) orgLicenseFile.required = false;
      } else {
        orgFields?.classList.add('hidden');
        cinemaFields?.classList.add('hidden');
        if (orgName) orgName.required = false;
        if (orgLicenseFile) orgLicenseFile.required = false;
        if (cinemaName) cinemaName.required = false;
        if (cinemaLicenseFile) cinemaLicenseFile.required = false;
      }
    }

    document.querySelectorAll('input[name="role"]').forEach((r) => r.addEventListener('change', toggleRole));
    // Initial toggle on page load
    toggleRole();

    // Client-side name validation: letters and spaces only
    const registerForm = document.getElementById('registerForm');
    const nameInput = document.getElementById('name');
    const errorName = document.getElementById('error-name');
    const errorMsg = document.getElementById('error-msg');
    const nameRegex = /^[A-Za-z ]+$/;
    if (registerForm) {
      registerForm.addEventListener('submit', function(e){
        if (!nameInput) return;
        const v = (nameInput.value || '').trim();
        if (v.length === 0) return; // server will handle required
        if (!nameRegex.test(v)) {
          e.preventDefault();
          if (errorName) { errorName.textContent = 'Name must contain only letters and spaces (A-Z).'; errorName.classList.remove('hidden'); }
          if (errorMsg) errorMsg.textContent = 'Please correct the highlighted fields.';
          nameInput.focus();
          return false;
        } else {
          if (errorName) { errorName.textContent = ''; errorName.classList.add('hidden'); }
        }
      });

      nameInput?.addEventListener('input', function(){
        if (errorName && nameRegex.test((nameInput.value||'').trim())) { errorName.textContent = ''; errorName.classList.add('hidden'); }
      });

      // Live validation: email uniqueness and password checks
      const AJAX_VALIDATE_URL = '<?php echo BASE_URL; ?>/ajax/validate_registration.php';
      const emailInput = document.getElementById('email');
      const errorEmail = document.getElementById('error-email');
      const passwordInput = document.getElementById('password');
      const passwordConfirm = document.getElementById('password_confirm');
      const matchMsg = document.getElementById('match-msg');
      let emailTimer = null;
      function debounce(fn, wait) { let t; return function(...args){ clearTimeout(t); t = setTimeout(()=>fn.apply(this,args), wait); }; }

      const checkEmail = debounce(function(){
        const v = (emailInput.value||'').trim();
        if (v === '' || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v)) { if (errorEmail) { errorEmail.textContent = 'Enter a valid email'; errorEmail.classList.remove('hidden'); } return; }
        fetch(AJAX_VALIDATE_URL + '?action=email&email=' + encodeURIComponent(v)).then(r=>r.json()).then(j=>{
          if (!j.success) { if (errorEmail) { errorEmail.textContent = j.message || 'Validation failed'; errorEmail.classList.remove('hidden'); } }
          else if (!j.available) { if (errorEmail) { errorEmail.textContent = 'Email already registered'; errorEmail.classList.remove('hidden'); } }
          else { if (errorEmail) { errorEmail.textContent = ''; errorEmail.classList.add('hidden'); } }
        }).catch(()=>{ if (errorEmail) { errorEmail.textContent = ''; errorEmail.classList.add('hidden'); } });
      }, 500);

      emailInput?.addEventListener('input', function(){ checkEmail(); });

      // Password match and lite strength indicator
      function updatePasswordMatch() {
        const a = passwordInput.value || '';
        const b = passwordConfirm.value || '';
        if (!a && !b) { matchMsg.textContent = ''; return; }
        if (a !== b) { matchMsg.textContent = 'Passwords do not match'; matchMsg.classList.remove('text-green-400'); matchMsg.classList.add('text-red-300'); }
        else { matchMsg.textContent = 'Passwords match'; matchMsg.classList.remove('text-red-300'); matchMsg.classList.add('text-green-400'); }
      }
      passwordInput?.addEventListener('input', updatePasswordMatch);
      passwordConfirm?.addEventListener('input', updatePasswordMatch);
    }
  })();
</script>

<!-- Terms & Conditions Modal -->
<div id="terms-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
  <div id="terms-modal-overlay" class="absolute inset-0 bg-black/60"></div>
  <div role="dialog" aria-modal="true" aria-labelledby="terms-title" class="relative bg-white max-w-2xl w-full mx-4 p-6 rounded-xl z-10 text-black">
    <h2 id="terms-title" class="text-xl font-semibold mb-3">Terms & Conditions</h2>
    <div class="terms-modal-content text-sm max-h-72 overflow-y-auto text-gray-800">
      <p><strong>Introduction</strong></p>
      <p>By creating an account you agree to use EthioEvents in compliance with applicable laws and these Terms. You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account.</p>
      <p><strong>Platform Use</strong></p>
      <p>EthioEvents provides a platform to list and discover events, concerts, and cinema screenings. We strive to keep event information accurate, but we are not responsible for third-party event content, cancellations, or refunds — those are governed by the event organizer's policies.</p>
      <p><strong>Account Conduct</strong></p>
      <p>We may suspend or remove accounts that violate these Terms or applicable laws, including fraudulent activity or misuse of the platform. Personal data is handled per our Privacy Policy.</p>
      <p><strong>Acceptance</strong></p>
      <p>If you do not agree with these Terms, do not create an account. Continued use of the service indicates acceptance of these Terms.</p>
    </div>
    <div class="mt-4 text-right">
      <button id="terms-close" class="px-4 py-2 rounded bg-gray-200">Close</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
