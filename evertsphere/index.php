<?php
$page_title = 'Habesh Events For Everyone';
$page_has_full_hero = true;
require_once __DIR__ . '/includes/header.php';
$pdo = get_db();

// Featured / upcoming events for home
$stmt = $pdo->prepare("SELECT id, title, category, event_date, event_time, city, poster, price FROM events WHERE status = 'published' AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 8");
$stmt->execute();
$events = $stmt->fetchAll();

// Site stats from DB
$total_events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn();
$members = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$organizers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'organizer'")->fetchColumn();
$categories = (int)$pdo->query("SELECT COUNT(DISTINCT category) FROM events")->fetchColumn();
?>

<section class="full-bleed bg-animated" style="position:relative;">
  <div class="floating-orb" style="width:300px;height:300px;background:rgba(99,102,241,0.15);top:6%;left:6%;animation-delay:0s;"></div>
  <div class="floating-orb" style="width:220px;height:220px;background:rgba(168,85,247,0.12);bottom:12%;right:8%;animation-delay:2s;"></div>
  <div id="appRoot" class="app-shell coffee-pattern relative z-10">
  <!-- Hero / Home Sections -->
  <section id="homePage" class="site-hero full-bleed">
    <div class="hero-inner">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
      <div class="fade-up">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#f2c879]/15 border border-[#f2c879]/30 text-[#ffe7c2] font-bold mb-6">
          <span class="status-dot bg-[#d4a45c]"></span> Welcome to Habeshan Events
        </div>
        <h1 id="heroTitle" class="hero-title title-font leading-tight font-extrabold">Celebrate Ethiopian Culture</h1>
        <p id="heroSubtitle" class="hero-sub mt-5 text-lg sm:text-xl max-w-2xl leading-relaxed">Discover, manage, and join unforgettable events celebrating Ethiopian heritage. From traditional festivals to modern concerts, all in one place.</p>
        <div class="mt-8 flex flex-col sm:flex-row gap-4">
          <button id="ctaButton" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#d4a45c] text-[#2b1710] px-8 py-4 font-extrabold shadow-xl hover:scale-[1.02] hover:bg-[#f2c879] transition text-lg"> <i data-lucide="sparkles" aria-hidden="true"></i> <span>Explore Events</span> </button>
        </div>
      </div>

      <div class="fade-up delay-1">
        <div class="relative">
          <div class="absolute inset-0 bg-gradient-to-br from-[#d4a45c]/20 to-[#7a4b2a]/20 rounded-[3rem]"></div>
          <div class="cream-card rounded-[3rem] p-8 relative">
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-2xl bg-[#f2c879]/35 p-6 text-center">
                <p class="text-4xl font-black"><?php echo number_format($total_events); ?></p>
                <p class="text-sm font-bold text-[#7a4b2a] mt-2">Events Hosted</p>
              </div>
              <div class="rounded-2xl bg-[#7a4b2a]/12 p-6 text-center">
                <p class="text-4xl font-black"><?php echo number_format($members); ?></p>
                <p class="text-sm font-bold text-[#7a4b2a] mt-2">Members</p>
              </div>
              <div class="rounded-2xl bg-[#7a4b2a]/12 p-6 text-center">
                <p class="text-4xl font-black"><?php echo number_format($organizers); ?></p>
                <p class="text-sm font-bold text-[#7a4b2a] mt-2">Organizers</p>
              </div>
              <div class="rounded-2xl bg-[#f2c879]/35 p-6 text-center">
                <p class="text-4xl font-black"><?php echo number_format($categories); ?></p>
                <p class="text-sm font-bold text-[#7a4b2a] mt-2">Categories</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
  </section>

  <?php echo '<div class="' . $containerClass . '">'; ?>
    <!-- Featured events moved below in the main Events section to avoid duplication -->
  </div>

  <!-- Secondary sections (Events, Schedule, Cinema, About) are left as smaller sections below the main hero -->
  <section id="eventsPage" class="page-section max-w-7xl mx-auto mt-8 px-5 sm:px-8 lg:px-10 fade-up delay-2">
    <div class="flex items-center justify-between mb-6">
      <div>
        <p class="uppercase tracking-[0.2em] text-[#f2c879] text-xs font-extrabold">Categories</p>
        <h2 id="featuredTitle" class="title-font text-3xl sm:text-4xl font-extrabold text-[#fff7ea]">Featured Events</h2>
      </div>
      <div class="flex gap-2">
        <button class="filter-btn rounded-full bg-[#d4a45c] text-[#2b1710] px-4 py-2 font-extrabold" data-filter="all">All</button>
        <button class="filter-btn rounded-full bg-white/10 text-[#fff7ea] px-4 py-2 font-extrabold" data-filter="concert">Concert</button>
        <button class="filter-btn rounded-full bg-white/10 text-[#fff7ea] px-4 py-2 font-extrabold" data-filter="festival">Festival</button>
        <button class="filter-btn rounded-full bg-white/10 text-[#fff7ea] px-4 py-2 font-extrabold" data-filter="holiday">Holiday</button>
        <button class="filter-btn rounded-full bg-white/10 text-[#fff7ea] px-4 py-2 font-extrabold" data-filter="national">National</button>
      </div>
    </div>

    <div id="eventCards" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
      <?php foreach ($events as $ev): ?>
        <article class="event-card cream-card rounded-[2rem] p-6" data-category="<?php echo esc(strtolower($ev['category'])); ?>">
          <div class="w-12 h-12 rounded-2xl bg-[#d4a45c] flex items-center justify-center text-[#2b1710]"><i data-lucide="calendar" aria-hidden="true"></i></div>
          <p class="mt-5 text-xs uppercase tracking-[0.18em] font-extrabold text-[#7a4b2a]"><?php echo esc($ev['category']); ?></p>
          <h3 class="title-font text-2xl font-extrabold mt-1"><?php echo esc($ev['title']); ?></h3>
          <p class="mt-3 text-[#4b2818]"><?php echo esc($ev['city']); ?></p>
          <button class="details-btn mt-5 w-full rounded-full bg-[#4b2818] text-[#fff7ea] px-4 py-3 font-extrabold hover:bg-[#2b1710] transition" data-title="<?php echo esc($ev['title']); ?>">View Details</button>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  </div>
</section>

<script>
  // App state (server-side values exposed to JS)
  window.App = {
    isLoggedIn: <?php echo is_logged_in() ? 'true' : 'false'; ?>,
    isOrganizer: <?php echo is_organizer() ? 'true' : 'false'; ?>,
    baseUrl: '<?php echo BASE_URL; ?>'
  };

  // Simple interactions
  document.getElementById('ctaButton')?.addEventListener('click', () => { window.location.href = App.baseUrl + '/events'; });

  document.querySelectorAll('.details-btn').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      const title = btn.dataset.title || '';
      // Find the event id by matching title (best-effort) and redirect to details — fallback to events page
      const link = App.baseUrl + '/events';
      window.location.href = link;
    });
  });

  // Filter events on the page
  document.querySelectorAll('.filter-btn').forEach((button) => {
    button.addEventListener('click', () => {
      const filter = button.dataset.filter;
      document.querySelectorAll('.filter-btn').forEach((btn) => { btn.classList.remove('bg-[#d4a45c]', 'text-[#2b1710]'); btn.classList.add('bg-white/10', 'text-[#fff7ea]'); });
      button.classList.add('bg-[#d4a45c]', 'text-[#2b1710]');
      button.classList.remove('bg-white/10', 'text-[#fff7ea]');
      document.querySelectorAll('.event-card').forEach((card) => {
        const shouldShow = filter === 'all' || (card.dataset.category || '') === filter;
        card.style.display = shouldShow ? '' : 'none';
      });
    });
  });

  // Setup move to events page if nav uses buttons (header already has links)
  document.getElementById('loginBtn')?.addEventListener('click', () => { window.location.href = App.baseUrl + '/login.php'; });
  document.getElementById('registerBtn')?.addEventListener('click', () => { window.location.href = App.baseUrl + '/register.php'; });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
