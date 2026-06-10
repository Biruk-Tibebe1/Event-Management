<?php
  $page_title = 'Events';
  $page_has_full_hero = true;
  require_once __DIR__ . '/../includes/header.php';
  // Load events from DB (published only)
  $pdo = get_db();
  $total_events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn();
  $monthStmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE status = 'published' AND MONTH(event_date)=? AND YEAR(event_date)=?");
  $monthStmt->execute([date('n'), date('Y')]);
  $this_month_events = (int)$monthStmt->fetchColumn();
  $categories_count = (int)$pdo->query("SELECT COUNT(DISTINCT category) FROM events WHERE status = 'published'")->fetchColumn();
  $total_all = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
  $confirmed_pct = $total_all > 0 ? round(($total_events / $total_all) * 100) : 100;

  $stmt = $pdo->prepare('SELECT e.*, u.name AS organizer_name FROM events e LEFT JOIN users u ON e.organizer_id = u.id WHERE e.status = ? ORDER BY e.event_date ASC, e.event_time ASC LIMIT 200');
  $stmt->execute(['published']);
  $events = $stmt->fetchAll();
?>
<section class="full-bleed bg-animated" style="position:relative;">
  <div class="floating-orb" style="width:300px;height:300px;background:rgba(99,102,241,0.15);top:8%;left:6%;animation-delay:0s;"></div>
  <div class="floating-orb" style="width:200px;height:200px;background:rgba(168,85,247,0.12);bottom:15%;right:10%;animation-delay:2s;"></div>
  <div id="appRoot" class="app-shell coffee-pattern relative z-10">
    <section class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-[1.08fr_0.92fr] gap-8 items-stretch">
     <div class="glass-panel rounded-[2.5rem] p-7 sm:p-10 lg:p-12 fade-up">
      <!-- Ethiopian coffee theme dashboard box removed -->
      <h1 id="mainTitle" class="title-font text-4xl sm:text-5xl lg:text-6xl leading-tight font-extrabold text-[#fff7ea]">Manage Ethiopian Cultural Events</h1>
      <p id="subtitle" class="mt-5 text-lg sm:text-xl text-[#ffe7c2] max-w-2xl leading-relaxed">Concerts, festivals, public holidays, national days, and community celebrations — all organized in one warm cultural space.</p>
      <div class="mt-8 flex flex-col sm:flex-row gap-4">
        <button id="calendarBtn" class="inline-flex items-center justify-center gap-2 rounded-full border border-[#ffe7c2]/45 text-[#fff7ea] px-6 py-4 font-extrabold hover:bg-white/10 transition"> <i data-lucide="calendar-days" aria-hidden="true"></i> <span id="secondaryButtonText">View Calendar</span> </button>
      </div>
      <p id="toastMessage" class="mt-5 hidden-soft rounded-2xl bg-[#fff7ea] text-[#2b1710] px-5 py-3 font-bold" role="status"></p>
     </div>
     <aside class="cream-card rounded-[2.5rem] p-7 sm:p-8 fade-up delay-1" aria-labelledby="statsTitle">
      <div class="flex items-center justify-between gap-4">
       <div>
        <p class="uppercase tracking-[0.2em] text-[#7a4b2a] text-xs font-extrabold">Live Summary</p>
        <h2 id="statsTitle" class="title-font text-3xl font-extrabold mt-1">Event Overview</h2>
       </div>
       <div class="w-14 h-14 rounded-2xl bg-[#4b2818] text-[#fff7ea] flex items-center justify-center"><i data-lucide="bar-chart-3" aria-hidden="true"></i>
       </div>
      </div>
      <div class="grid grid-cols-2 gap-4 mt-7">
      <div class="rounded-3xl bg-[#f2c879]/35 p-5">
       <p class="text-3xl font-black"><?php echo esc($total_events); ?></p>
       <p class="text-sm font-bold text-[#7a4b2a]">Total Events</p>
      </div>
      <div class="rounded-3xl bg-[#7a4b2a]/12 p-5">
       <p class="text-3xl font-black"><?php echo esc($this_month_events); ?></p>
       <p class="text-sm font-bold text-[#7a4b2a]">This Month</p>
      </div>
      <div class="rounded-3xl bg-[#7a4b2a]/12 p-5">
       <p class="text-3xl font-black"><?php echo esc($categories_count); ?></p>
       <p class="text-sm font-bold text-[#7a4b2a]">Categories</p>
      </div>
      <div class="rounded-3xl bg-[#f2c879]/35 p-5">
       <p class="text-3xl font-black"><?php echo esc($confirmed_pct); ?>%</p>
       <p class="text-sm font-bold text-[#7a4b2a]">Confirmed</p>
      </div>
          </div>
      <!-- Next Highlight block removed per request -->
     </aside>
    </section>
    <section id="events" class="max-w-7xl mx-auto mt-8 fade-up delay-2">
     <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-5">
      <div>
       <p class="uppercase tracking-[0.2em] text-[#f2c879] text-xs font-extrabold">Categories</p>
       <h2 id="featuredTitle" class="title-font text-3xl sm:text-4xl font-extrabold text-[#fff7ea]">Featured Events</h2>
      </div>
      <div class="flex flex-wrap gap-2" aria-label="Event category filters"><button class="filter-btn rounded-full bg-[#d4a45c] text-[#2b1710] px-4 py-2 font-extrabold" data-filter="all">All</button> <button class="filter-btn rounded-full bg-white/10 text-[#fff7ea] px-4 py-2 font-extrabold" data-filter="concert">Concert</button> <button class="filter-btn rounded-full bg-white/10 text-[#fff7ea] px-4 py-2 font-extrabold" data-filter="festival">Festival</button> <button class="filter-btn rounded-full bg-white/10 text-[#fff7ea] px-4 py-2 font-extrabold" data-filter="holiday">Holiday</button> <button class="filter-btn rounded-full bg-white/10 text-[#fff7ea] px-4 py-2 font-extrabold" data-filter="national">National</button>
      </div>
     </div>
     <div id="eventCards" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
      <?php if (empty($events)): ?>
        <div class="p-8 text-center text-[#ffe7c2]">No events available right now.</div>
      <?php else: ?>
        <?php foreach ($events as $e):
          $cat = strtolower(trim($e['category'] ?? 'other'));
          switch ($cat) {
            case 'concert': $icon = 'music-2'; break;
            case 'festival': $icon = 'flame'; break;
            case 'public holiday':
            case 'holiday': $icon = 'calendar-heart'; break;
            case 'national': $icon = 'flag'; break;
            default: $icon = 'sparkles';
          }
          $excerpt = trim(strip_tags($e['description'] ?? ''));
          if (mb_strlen($excerpt) > 140) $excerpt = mb_substr($excerpt, 0, 140) . '...';
          $viewUrl = (defined('BASE_URL') ? BASE_URL : '') . '/events/view.php?id=' . $e['id'];
        ?>
          <article class="event-card cream-card rounded-[2rem] p-6" data-category="<?php echo esc($cat); ?>">
           <div class="w-12 h-12 rounded-2xl bg-[#d4a45c] flex items-center justify-center text-[#2b1710]"><i data-lucide="<?php echo esc($icon); ?>" aria-hidden="true"></i>
           </div>
           <p class="mt-5 text-xs uppercase tracking-[0.18em] font-extrabold text-[#7a4b2a]"><?php echo esc(ucfirst($cat)); ?></p>
           <h3 class="title-font text-2xl font-extrabold mt-1"><?php echo esc($e['title']); ?></h3>
           <p class="mt-3 text-[#4b2818]"><?php echo esc($excerpt); ?></p>
           <a href="<?php echo esc($viewUrl); ?>" class="details-btn mt-5 w-full rounded-full bg-[#4b2818] text-[#fff7ea] px-4 py-3 font-extrabold hover:bg-[#2b1710] transition" data-title="<?php echo esc($e['title']); ?>">View Details</a>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
     </div>
    </section>
    <section id="schedule" class="max-w-7xl mx-auto mt-8 grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-8 fade-up delay-3">
     <div class="cream-card rounded-[2.5rem] p-6 sm:p-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
       <div>
        <p class="uppercase tracking-[0.2em] text-[#7a4b2a] text-xs font-extrabold">Planning Board</p>
        <h2 id="tableTitle" class="title-font text-3xl font-extrabold">Upcoming Event Schedule</h2>
       </div><label class="flex items-center gap-2 rounded-full bg-[#7a4b2a]/10 px-4 py-3"> <span class="sr-only">Search events</span> <i data-lucide="search" aria-hidden="true"></i> <input id="searchInput" type="search" placeholder="Search event..." class="bg-transparent outline-none w-full text-[#2b1710] placeholder:text-[#7a4b2a]"> </label>
      </div>
      <div class="overflow-auto rounded-3xl border border-[#7a4b2a]/15">
       <table class="w-full text-left min-w-[720px]">
        <thead class="bg-[#4b2818] text-[#fff7ea]">
         <tr>
          <th class="px-5 py-4">Event</th>
          <th class="px-5 py-4">Category</th>
          <th class="px-5 py-4">Date</th>
          <th class="px-5 py-4">Venue</th>
          <th class="px-5 py-4">Status</th>
         </tr>
        </thead>
        <tbody id="scheduleBody" class="divide-y divide-[#7a4b2a]/15">
         <?php if (empty($events)): ?>
           <tr><td colspan="5" class="px-5 py-6 text-center text-[#7a4b2a]">No upcoming events.</td></tr>
         <?php else: ?>
           <?php foreach ($events as $e):
             $search = strtolower(trim(($e['title'] ?? '') . ' ' . ($e['category'] ?? '') . ' ' . ($e['venue'] ?? '') . ' ' . ($e['city'] ?? '') . ' ' . ($e['status'] ?? '')));
             $dateStr = !empty($e['event_date']) ? date('M j', strtotime($e['event_date'])) : '-';
             $statusLabel = 'Pending'; $statusClass = 'bg-yellow-100 text-yellow-800';
             if (($e['status'] ?? '') === 'published') { $statusLabel = 'Confirmed'; $statusClass = 'bg-green-100 text-green-800'; }
             elseif (($e['status'] ?? '') === 'cancelled') { $statusLabel = 'Cancelled'; $statusClass = 'bg-red-100 text-red-800'; }
             elseif (($e['status'] ?? '') === 'draft') { $statusLabel = 'Draft'; $statusClass = 'bg-blue-100 text-blue-800'; }
           ?>
             <tr class="event-row" data-search="<?php echo esc($search); ?>">
               <td class="px-5 py-4 font-extrabold"><?php echo esc($e['title']); ?></td>
               <td class="px-5 py-4"><?php echo esc(ucfirst($e['category'] ?? '')); ?></td>
               <td class="px-5 py-4"><?php echo esc($dateStr); ?></td>
               <td class="px-5 py-4"><?php echo esc($e['venue'] ?? '—'); ?></td>
               <td class="px-5 py-4"><span class="rounded-full <?php echo esc($statusClass); ?> px-3 py-1 font-extrabold text-sm"><?php echo esc($statusLabel); ?></span></td>
             </tr>
           <?php endforeach; ?>
         <?php endif; ?>
        </tbody>
       </table>
      </div>
     </div>
    <aside id="manage" class="glass-panel rounded-[2.5rem] p-6 sm:p-7">
     <!-- Quick Manager heading removed -->
     <!-- Helper text removed per request -->
      <div class="mt-6 space-y-3"><button class="quick-action w-full flex items-center justify-between gap-4 rounded-3xl bg-[#fff7ea] text-[#2b1710] px-5 py-4 font-extrabold hover:scale-[1.02] transition" data-message="Venue checklist opened."> Venue Checklist <i data-lucide="map-pin" aria-hidden="true"></i> </button> <button class="quick-action w-full flex items-center justify-between gap-4 rounded-3xl bg-[#fff7ea] text-[#2b1710] px-5 py-4 font-extrabold hover:scale-[1.02] transition" data-message="Volunteer task board opened."> Volunteers <i data-lucide="users" aria-hidden="true"></i> </button> <button class="quick-action w-full flex items-center justify-between gap-4 rounded-3xl bg-[#fff7ea] text-[#2b1710] px-5 py-4 font-extrabold hover:scale-[1.02] transition" data-message="Ticket tracking opened."> Ticket Tracking <i data-lucide="ticket" aria-hidden="true"></i> </button> <button class="quick-action w-full flex items-center justify-between gap-4 rounded-3xl bg-[#fff7ea] text-[#2b1710] px-5 py-4 font-extrabold hover:scale-[1.02] transition" data-message="Sponsor list opened."> Sponsors <i data-lucide="handshake" aria-hidden="true"></i> </button>
      </div>
      <!-- Live Events box removed per request -->
     </aside>
    </section>
  </main>
  <!-- Add New Event modal removed -->
  </div>
  <script>
    const defaultConfig = {
      background_color: "#4b2818",
      surface_color: "#fff7ea",
      text_color: "#fff7ea",
      primary_action_color: "#d4a45c",
      secondary_action_color: "#7a4b2a",
      font_family: "Nunito",
      font_size: 16,
      main_title: "Manage Ethiopian Cultural Events",
      subtitle: "Concerts, festivals, public holidays, national days, and community celebrations — all organized in one warm cultural space.",
      primary_button_text: "Add New Event",
      secondary_button_text: "View Calendar",
      stats_title: "Event Overview",
      featured_title: "Featured Events",
      table_title: "Upcoming Event Schedule"
    };

    function showToast(message) {
      const toast = document.getElementById("toastMessage");
      toast.textContent = message;
      toast.classList.remove("hidden-soft");
      window.setTimeout(() => {
        toast.classList.add("hidden-soft");
      }, 2600);
    }

    function openModal() {
      document.getElementById("eventModal").classList.remove("hidden-soft");
      document.getElementById("eventName").focus();
    }

    function closeModal() {
      document.getElementById("eventModal").classList.add("hidden-soft");
    }

    function applyConfig(config) {
      const finalConfig = Object.assign({}, defaultConfig, config || {});
      const root = document.getElementById("appRoot");
      const fontStack = `${finalConfig.font_family}, Nunito, sans-serif`;
      const titleFontStack = `${finalConfig.font_family}, Fraunces, serif`;
      const baseSize = Number(finalConfig.font_size) || defaultConfig.font_size;

      root.style.background = `
        radial-gradient(circle at 8% 10%, rgba(212, 164, 92, 0.28), transparent 28%),
        radial-gradient(circle at 92% 12%, rgba(68, 36, 22, 0.38), transparent 24%),
        linear-gradient(135deg, #2b1710 0%, ${finalConfig.background_color} 42%, ${finalConfig.secondary_action_color} 100%)
      `;
      root.style.color = finalConfig.text_color;
      root.style.fontFamily = fontStack;

      document.getElementById("mainTitle").textContent = finalConfig.main_title;
      document.getElementById("subtitle").textContent = finalConfig.subtitle;
      document.getElementById("primaryButtonText").textContent = finalConfig.primary_button_text;
      document.getElementById("secondaryButtonText").textContent = finalConfig.secondary_button_text;
      document.getElementById("statsTitle").textContent = finalConfig.stats_title;
      document.getElementById("featuredTitle").textContent = finalConfig.featured_title;
      document.getElementById("tableTitle").textContent = finalConfig.table_title;

      document.querySelectorAll(".cream-card").forEach((card) => {
        card.style.backgroundColor = finalConfig.surface_color;
      });

      document.querySelectorAll(".title-font").forEach((el) => {
        el.style.fontFamily = titleFontStack;
      });

      document.querySelectorAll("p, a, button, input, select, textarea, td, th, label, span").forEach((el) => {
        el.style.fontFamily = fontStack;
      });

      document.querySelectorAll("#openModalBtn, .filter-btn[data-filter='all']").forEach((button) => {
        button.style.backgroundColor = finalConfig.primary_action_color;
        button.style.color = "#2b1710";
      });

      document.querySelectorAll(".details-btn, #eventForm button[type='submit']").forEach((button) => {
        button.style.backgroundColor = finalConfig.secondary_action_color;
      });

      document.getElementById("mainTitle").style.fontSize = `${baseSize * 3}px`;
      document.getElementById("subtitle").style.fontSize = `${baseSize * 1.18}px`;
      document.querySelectorAll("h2").forEach((el) => {
        el.style.fontSize = `${baseSize * 1.9}px`;
      });
      document.querySelectorAll("h3").forEach((el) => {
        el.style.fontSize = `${baseSize * 1.45}px`;
      });
      document.querySelectorAll("p, button, input, select, textarea, td, th, label, a").forEach((el) => {
        el.style.fontSize = `${baseSize}px`;
      });
      document.querySelectorAll(".text-xs").forEach((el) => {
        el.style.fontSize = `${baseSize * 0.75}px`;
      });
      document.querySelectorAll(".text-sm").forEach((el) => {
        el.style.fontSize = `${baseSize * 0.88}px`;
      });
    }

    // Modal handlers removed (Add New Event feature removed)
    const calendarBtnEl = document.getElementById("calendarBtn");
    if (calendarBtnEl) {
      calendarBtnEl.addEventListener("click", () => {
        const sched = document.getElementById("schedule");
        if (sched) sched.scrollIntoView({ behavior: "smooth", block: "start" });
        showToast("Calendar schedule is ready below.");
      });
    }
    // Event form handler removed since modal was removed

    document.querySelectorAll(".quick-action").forEach((button) => {
      button.addEventListener("click", () => {
        showToast(button.dataset.message);
      });
    });

    document.querySelectorAll(".details-btn").forEach((button) => {
      button.addEventListener("click", () => {
        showToast(`${button.dataset.title} details opened.`);
      });
    });

    document.querySelectorAll(".filter-btn").forEach((button) => {
      button.addEventListener("click", () => {
        const filter = button.dataset.filter;

        document.querySelectorAll(".filter-btn").forEach((btn) => {
          btn.classList.remove("bg-[#d4a45c]", "text-[#2b1710]");
          btn.classList.add("bg-white/10", "text-[#fff7ea]");
        });

        button.classList.add("bg-[#d4a45c]", "text-[#2b1710]");
        button.classList.remove("bg-white/10", "text-[#fff7ea]");

        document.querySelectorAll(".event-card").forEach((card) => {
          const cat = (card.dataset.category || '').toLowerCase();
          const shouldShow = filter === "all" || cat === filter || cat.includes(filter);
          card.style.display = shouldShow ? "" : "none";
        });
      });
    });

    document.getElementById("searchInput").addEventListener("input", (event) => {
      const query = event.target.value.trim().toLowerCase();
      document.querySelectorAll("#scheduleBody tr").forEach((row) => {
        row.style.display = (row.dataset.search || '').includes(query) ? "" : "none";
      });
    });

    if (window.elementSdk) {
      window.elementSdk.init({
        defaultConfig,
        onConfigChange: async (config) => {
          applyConfig(config);
        },
        mapToCapabilities: (config) => ({
          recolorables: [
            {
              get: () => config.background_color || defaultConfig.background_color,
              set: (value) => {
                config.background_color = value;
                window.elementSdk.setConfig({ background_color: value });
              }
            },
            {
              get: () => config.surface_color || defaultConfig.surface_color,
              set: (value) => {
                config.surface_color = value;
                window.elementSdk.setConfig({ surface_color: value });
              }
            },
            {
              get: () => config.text_color || defaultConfig.text_color,
              set: (value) => {
                config.text_color = value;
                window.elementSdk.setConfig({ text_color: value });
              }
            },
            {
              get: () => config.primary_action_color || defaultConfig.primary_action_color,
              set: (value) => {
                config.primary_action_color = value;
                window.elementSdk.setConfig({ primary_action_color: value });
              }
            },
            {
              get: () => config.secondary_action_color || defaultConfig.secondary_action_color,
              set: (value) => {
                config.secondary_action_color = value;
                window.elementSdk.setConfig({ secondary_action_color: value });
              }
            }
          ],
          borderables: [],
          fontEditable: {
            get: () => config.font_family || defaultConfig.font_family,
            set: (value) => {
              config.font_family = value;
              window.elementSdk.setConfig({ font_family: value });
            }
          },
          fontSizeable: {
            get: () => config.font_size || defaultConfig.font_size,
            set: (value) => {
              config.font_size = value;
              window.elementSdk.setConfig({ font_size: value });
            }
          }
        }),
        mapToEditPanelValues: (config) => new Map([
          ["main_title", config.main_title || defaultConfig.main_title],
          ["subtitle", config.subtitle || defaultConfig.subtitle],
          ["primary_button_text", config.primary_button_text || defaultConfig.primary_button_text],
          ["secondary_button_text", config.secondary_button_text || defaultConfig.secondary_button_text],
          ["stats_title", config.stats_title || defaultConfig.stats_title],
          ["featured_title", config.featured_title || defaultConfig.featured_title],
          ["table_title", config.table_title || defaultConfig.table_title]
        ])
      });
    } else {
      applyConfig(defaultConfig);
    }
  </script>

  </div>
</section>

<?php include __DIR__ . '/../includes/cinema_scripts.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
