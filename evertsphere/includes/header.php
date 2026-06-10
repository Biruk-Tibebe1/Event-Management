<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Run daily cleanup for past events (non-blocking)
@auto_delete_past_events_daily();

// Start output buffering to avoid "headers already sent" when files
// or accidental output precede header() calls in other scripts.
if (!headers_sent()) ob_start();
?>
<!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo esc($page_title ?? 'EthioEvents'); ?> | EthioEvents</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700;800&family=Nunito:wght@400;600;700;800&family=Playfair+Display:wght@400;700;900&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="<?php echo BASE_URL; ?>/assets/css/auth.css" rel="stylesheet">
  <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            coffee: {
              50: '#fdf8f3',
              100: '#f5e6d3',
              200: '#e8c9a0',
              300: '#d4a574',
              400: '#b8834f',
              500: '#8B5E3C',
              600: '#6B4226',
              700: '#4A2C17',
              800: '#33200F',
              900: '#1F1409'
            }
          }
        }
      }
    }
  </script>
  <style>
    .font-heading { font-family: 'Playfair Display', serif; }
    .font-display { font-family: 'Playfair Display', serif; }
    .font-body { font-family: 'Source Sans 3', sans-serif; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeUp 0.8s ease forwards; }
    .animate-fade-up-delay-1 { animation: fadeUp 0.8s ease 0.2s forwards; opacity: 0; }
    .card-hover { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    .card-hover:hover { transform: translateY(-8px) scale(1.02); }
    .gold-gradient { background: linear-gradient(135deg, #b8834f, #8B5E3C, #6B4226); }
    .dark-gradient { background: linear-gradient(180deg, #33200F 0%, #4A2C17 50%, #33200F 100%); }

    /* Coffee theme background and utility classes (shared with events page) */
    .app-shell {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background:
        radial-gradient(circle at 8% 10%, rgba(212, 164, 92, 0.28), transparent 28%),
        radial-gradient(circle at 92% 12%, rgba(68, 36, 22, 0.38), transparent 24%),
        linear-gradient(135deg, #2b1710 0%, #4b2818 42%, #7a4b2a 100%);
      color: #fff7ea;
    }

    .coffee-pattern {
      background-image:
        linear-gradient(30deg, rgba(255,255,255,0.04) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,0.04) 87.5%, rgba(255,255,255,0.04)),
        linear-gradient(150deg, rgba(255,255,255,0.04) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,0.04) 87.5%, rgba(255,255,255,0.04)),
        linear-gradient(30deg, rgba(255,255,255,0.04) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,0.04) 87.5%, rgba(255,255,255,0.04)),
        linear-gradient(150deg, rgba(255,255,255,0.04) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,0.04) 87.5%, rgba(255,255,255,0.04));
      background-size: 52px 91px;
      background-position: 0 0, 0 0, 26px 45px, 26px 45px;
    }

    .glass-panel {
      background: rgba(255, 247, 234, 0.11);
      border: 1px solid rgba(255, 231, 194, 0.2);
      box-shadow: 0 24px 80px rgba(25, 12, 7, 0.35);
      backdrop-filter: blur(18px);
    }

    .cream-card {
      background: #fff7ea;
      color: #2b1710;
      box-shadow: 0 18px 45px rgba(25, 12, 7, 0.25);
    }

    .title-font { font-family: 'Fraunces', serif; }

    .fade-up { animation: fadeUp 0.7s ease both; }
    .delay-1 { animation-delay: 0.08s; }
    .delay-2 { animation-delay: 0.16s; }
    .delay-3 { animation-delay: 0.24s; }
    .delay-4 { animation-delay: 0.32s; }

    .status-dot { width: 10px; height: 10px; border-radius: 999px; display: inline-block; }

    .modal-backdrop { background: rgba(25, 12, 7, 0.68); }
    .hidden-soft { display: none; }

    button, input, select, textarea { font: inherit; }
    button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible { outline: 3px solid #f2c879; outline-offset: 3px; }

    .event-row { transition: transform 180ms ease, background 180ms ease; }
    .event-row:hover { transform: translateX(4px); background: rgba(122, 75, 42, 0.08); }
  </style>
  </head>
  <body class="min-h-screen font-body app-shell text-coffee-50">
  <?php
    // Determine whether this is a listing page so pages can choose a wider
    // content container. The container will be opened after the header to
    // allow full-width header/hero sections.
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['REQUEST_URI'] ?? '');
    $isListPage = preg_match('/\/events(\/|$)|\/cinema(\/|$)|\/events.php|\/cinema.php/', $scriptPath);
    $containerClass = $isListPage ? 'site-container site-container-wide' : 'site-container';
  ?>
  <div class="site-root w-full min-h-screen flex flex-col">
   <!-- Header -->
   <header class="bg-coffee-700 text-coffee-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
     <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-coffee-500 rounded-full flex items-center justify-center">
       <i data-lucide="clapperboard" class="w-5 h-5 text-coffee-100"></i>
      </div>
      <a href="<?php echo BASE_URL; ?>" class="font-display text-2xl font-bold">ኢትዮጵያ Events</a>
     </div>
     <nav class="hidden md:flex gap-6 text-coffee-200 text-sm font-medium">
      <a href="<?php echo BASE_URL; ?>" class="hover:text-white transition">Home</a>
      <a href="<?php echo BASE_URL; ?>/events" class="hover:text-white transition">Events</a>
      <a href="<?php echo BASE_URL; ?>/cinema" class="hover:text-white transition">Cinema</a>
      <a href="<?php echo BASE_URL; ?>/about.php" class="hover:text-white transition">About</a>
     </nav>
     <div class="hidden md:flex items-center gap-4">
      <!-- zoom/fullscreen controls removed per design request -->
      <?php if (is_logged_in()): ?>
        <div class="relative group">
          <button class="flex items-center gap-2 hover:text-white transition" aria-haspopup="true" aria-expanded="false"><?php echo esc($_SESSION['name'] ?? 'User'); ?> <i data-lucide="chevron-down" class="w-4 h-4"></i></button>
          <div class="absolute right-0 mt-2 w-48 bg-coffee-900 border border-coffee-700/30 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all group-menu" style="z-index:10000;">
            <a href="<?php echo BASE_URL; ?>/profile.php" class="block px-4 py-2 hover:bg-coffee-700/10 hover:text-white transition first:rounded-t-lg">Profile</a>
            <!-- Organizer/Admin/Cinema dashboard links -->
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
              <a href="<?php echo BASE_URL; ?>/admin/" class="block px-4 py-2 hover:bg-coffee-700/10 hover:text-white transition">Admin Dashboard</a>
            <?php elseif (($_SESSION['role'] ?? '') === 'organizer'): ?>
              <a href="<?php echo BASE_URL; ?>/organizer/dashboard.php" class="block px-4 py-2 hover:bg-coffee-700/10 hover:text-white transition">Organizer Dashboard</a>
            <?php elseif (($_SESSION['role'] ?? '') === 'cinema_manager'): ?>
              <a href="<?php echo BASE_URL; ?>/cinema/manager.php" class="block px-4 py-2 hover:bg-coffee-700/10 hover:text-white transition">Manager Dashboard</a>
            <?php endif; ?>
            <div class="border-t border-coffee-700/20"></div>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="block px-4 py-2 hover:bg-coffee-700/10 hover:text-white transition last:rounded-b-lg">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <!-- Login/Register links are intentionally hidden from the header UI
             They remain in the markup so the pages are still reachable by URL
             (functional), but not visible to users. -->
        <div class="hidden" style="display:none;" aria-hidden="true">
          <a href="<?php echo BASE_URL; ?>/login.php" class="px-4 py-2 border border-coffee-500/50 rounded-full text-coffee-300 hover:bg-coffee-500/10 transition-all">Login</a>
          <a href="<?php echo BASE_URL; ?>/register.php" class="px-4 py-2 bg-coffee-500 text-white font-semibold rounded-full hover:shadow-lg hover:shadow-coffee-500/30 transition-all">Register</a>
        </div>
      <?php endif; ?>
     </div>
     <button id="mobile-menu-btn" class="md:hidden text-coffee-200"> <i data-lucide="menu" class="w-6 h-6"></i> </button>
    </div>
    </header>
    <main class="w-full flex-1">
     <?php
       // If a page needs a full-bleed hero, it can set `$page_has_full_hero = true`
       // before including this header. In that case we delay opening the
       // centered `site-container` so the page can render a full-bleed hero
       // outside the constrained container and then open the container after
       // the hero markup.
       if (empty($page_has_full_hero)) {
         echo '<div class="' . $containerClass . '">';
       }
     ?>
