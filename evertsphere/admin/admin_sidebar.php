<?php
// Shared admin sidebar component
?>
<style>
/* Admin sidebar: improved transitions and responsive auto-collapse */
.admin-sidebar {
  background: rgba(28, 25, 23, 0.95);
  padding: 20px;
  border-radius: 18px;
  position: sticky;
  top: 90px;
  height: fit-content;
  border: 1px solid rgba(212, 175, 55, 0.2);
  box-sizing: border-box;
  width: 220px;
  transition: width 280ms cubic-bezier(.2,.9,.3,1), padding 200ms ease, border-radius 200ms ease, background-color 200ms ease;
  overflow: hidden;
}

/* Shared admin utilities */
.admin-table { width:100%; border-collapse:collapse; font-size:0.95rem; color: #f9f6f0; }
.admin-table thead th { text-align:left; padding:10px 12px; font-weight:700; color:#f3e6c6; background: rgba(255,255,255,0.02); }
.admin-table tbody tr { transition: background .12s ease, transform .12s ease; }
.admin-table tbody tr:hover { background: rgba(255,255,255,0.02); transform: translateY(-2px); }
.admin-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.03); }
.admin-action { padding:6px 10px; border-radius:8px; display:inline-block; transition: transform .12s ease, box-shadow .12s ease; }
.admin-action:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.18); }
.btn-approve {
  background: linear-gradient(135deg,#16a34a 0%,#bbf7d0 100%);
  color: #04240e;
  border: none;
  padding: 6px 10px;
  border-radius: 8px;
  box-shadow: 0 6px 18px rgba(16,185,129,0.12);
  transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
}
.btn-approve:hover { transform: translateY(-3px); box-shadow: 0 18px 40px rgba(16,185,129,0.16); }
.btn-approve:active { transform: scale(.98); }

.btn-reject {
  background: linear-gradient(135deg,#ef4444 0%,#fb7185 100%);
  color: #fff;
  border: none;
  padding: 6px 10px;
  border-radius: 8px;
  box-shadow: 0 6px 18px rgba(239,68,68,0.12);
  transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
}
.btn-reject:hover { transform: translateY(-3px); box-shadow: 0 18px 40px rgba(239,68,68,0.14); }
.btn-reject:active { transform: scale(.98); }

@keyframes popIn { 0% { opacity:0; transform: scale(.96);} 60% { opacity:1; transform: scale(1.02);} 100% { transform: none; } }
.btn.clicked { animation: popIn .28s ease both; }

.admin-sidebar .nav-link {
  color: var(--text);
  text-decoration: none;
  display: flex;
  gap: 10px;
  align-items: center;
  border-left: 3px solid transparent;
  padding: 10px 6px;
  font-size: 0.95rem;
  transition: background 180ms ease, color 180ms ease, padding 180ms ease;
}
.admin-sidebar .nav-link i { width: 18px; height: 18px; opacity: 0.95; display:inline-flex; align-items:center; justify-content:center; }
.admin-sidebar .nav-link:hover { border-left-color: var(--primary); background: rgba(212, 175, 55, 0.08); }
.admin-sidebar .nav-link.active { border-left-color: var(--primary); font-weight: 700; color: #fff; }
.admin-sidebar h6 { color: #fef3c7; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.admin-sidebar .toggle-btn { background: transparent; border: 1px solid rgba(255,255,255,0.04); color: var(--text); padding: 6px 8px; border-radius: 8px; cursor: pointer; transition: opacity 180ms ease, transform 180ms ease; }
.admin-sidebar .link-text { transition: opacity 220ms ease, transform 220ms ease; display: inline-block; white-space: nowrap; }

/* Admin page form controls and filters (visible, text-box like) */
.admin-controls .form-control,
.admin-controls input[type="text"],
.admin-controls .form-select,
.admin-table .form-select {
  background: rgba(255,255,255,0.96);
  color: #111827;
  border: 1px solid rgba(0,0,0,0.08);
  padding: 0.45rem 0.6rem;
  border-radius: 8px;
  box-shadow: 0 6px 18px rgba(15,23,42,0.06);
  width: 100%;
  max-width: 100%;
  appearance: auto;
}
.admin-controls .form-control::placeholder { color: #9ca3af; }
.admin-table .form-select[disabled] { background: rgba(250,250,250,0.9); color: #6b7280; cursor: not-allowed; }
.admin-controls .search-row { display:flex; gap:8px; align-items:center; }
/* Make the search input compact on wide screens, but fluid on small screens */
.admin-controls .search-row .form-control { flex:0 0 200px; max-width:200px; }
.admin-controls .search-row .btn { flex:0 0 120px; }
@media (max-width: 767px) {
  .admin-controls .search-row .form-control { flex:1 1 auto; max-width:100%; }
}

/* Collapsed state — compact vertical icon bar */
#adminLayoutRow.sidebar-collapsed > .col-md-3 { flex: 0 0 72px !important; max-width: 72px !important; }
#adminLayoutRow.sidebar-collapsed > .col-md-9 { flex: 1 1 calc(100% - 72px) !important; max-width: calc(100% - 72px) !important; }
#adminLayoutRow.sidebar-collapsed .admin-sidebar { width: 72px !important; padding: 10px !important; border-radius: 12px !important; }
/* legacy fallback in case a page doesn't use the admin layout row id */
body.sidebar-collapsed .row > .col-md-3 { flex: 0 0 72px !important; max-width: 72px !important; }
body.sidebar-collapsed .row > .col-md-9 { flex: 1 1 calc(100% - 72px) !important; max-width: calc(100% - 72px) !important; }
body.sidebar-collapsed .admin-sidebar { width: 72px !important; padding: 10px !important; border-radius: 12px !important; }
.sidebar-collapsed .admin-sidebar h6 { display: block; position: relative; padding: 8px 0; }
.sidebar-collapsed .admin-sidebar h6 span { display: none; }
.sidebar-collapsed .admin-sidebar .toggle-btn { display: inline-block; position: absolute; right: 8px; top: 6px; z-index: 10; }
.sidebar-collapsed .admin-sidebar .nav-link { justify-content: center; padding: 8px 0; border-left: none; }
.sidebar-collapsed .admin-sidebar .nav-link .link-text { opacity: 0; transform: translateX(-6px); pointer-events: none; }
.sidebar-collapsed .admin-sidebar .nav-link i { margin: 0; }

/* Mobile: auto-collapse and hide the toggle control to save space */
@media (max-width: 767px) {
  .admin-sidebar { width: 56px; padding: 8px; border-radius: 10px; }
  .admin-sidebar .toggle-btn { display: none !important; }
  .admin-sidebar h6 { display: none; }
  .admin-sidebar .link-text { display: none; }
  /* reduce overall column size further when collapsed on small screens */
  .sidebar-collapsed .col-md-3 { flex-basis: 56px; max-width: 56px; }
  .sidebar-collapsed .admin-sidebar { width: 56px; padding: 6px; }
}
</style>

<div id="adminLayoutRow" class="row mb-4">
  <div class="col-md-3">
    <div class="admin-sidebar" id="adminSidebar">
      <h6 class="mb-3 fw-bold">
        <span>Admin Menu</span>
        <button id="sidebarToggle" class="toggle-btn" aria-pressed="false" title="Collapse menu" type="button"><i data-lucide="chevrons-up"></i></button>
      </h6>
      <a href="<?php echo BASE_URL; ?>/admin/" class="nav-link <?php echo ($page === 'dashboard' ? 'active' : ''); ?>"><i data-lucide="grid"></i><span class="link-text">Dashboard</span></a>
      <a href="<?php echo BASE_URL; ?>/admin/users.php" class="nav-link <?php echo ($page === 'users' ? 'active' : ''); ?>"><i data-lucide="users"></i><span class="link-text">Users</span></a>
      <a href="<?php echo BASE_URL; ?>/admin/approvals.php" class="nav-link <?php echo ($page === 'approvals' ? 'active' : ''); ?>"><i data-lucide="check-square"></i><span class="link-text">Approvals</span></a>
      <a href="<?php echo BASE_URL; ?>/admin/events.php" class="nav-link <?php echo ($page === 'events' ? 'active' : ''); ?>"><i data-lucide="calendar"></i><span class="link-text">Events</span></a>
      <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="nav-link <?php echo ($page === 'reports' ? 'active' : ''); ?>"><i data-lucide="bar-chart-3"></i><span class="link-text">Reports</span></a>
    </div>
  </div>
  <script>
    (function(){
      try{
        var key = 'adminSidebarCollapsed';
        var btn = document.getElementById('sidebarToggle');
        var body = document.body;
        var layoutRow = document.getElementById('adminLayoutRow');
        var isAutoMobile = function(){ return window.innerWidth <= 767; };

        function setState(collapsed, save){
          // Toggle the layout row first (most specific), then body/html fallbacks
          if(collapsed){
            if(layoutRow) layoutRow.classList.add('sidebar-collapsed');
            body.classList.add('sidebar-collapsed');
            try{ document.documentElement.classList.add('sidebar-collapsed'); }catch(e){}
            if(btn){ btn.setAttribute('aria-pressed','true'); btn.innerHTML = '<i data-lucide="chevrons-down"></i>'; btn.title = 'Expand menu'; }
          } else {
            if(layoutRow) layoutRow.classList.remove('sidebar-collapsed');
            body.classList.remove('sidebar-collapsed');
            try{ document.documentElement.classList.remove('sidebar-collapsed'); }catch(e){}
            if(btn){ btn.setAttribute('aria-pressed','false'); btn.innerHTML = '<i data-lucide="chevrons-up"></i>'; btn.title = 'Collapse menu'; }
          }
          try{ lucide.createIcons(); }catch(e){}
          if(save){ try{ localStorage.setItem(key, collapsed ? '1' : '0'); }catch(e){} }
        }

        function updateForViewport(){
          var mobile = isAutoMobile();
          if(btn){ btn.style.display = mobile ? 'none' : ''; }
          if(mobile){
            // On small screens force collapsed state (don't overwrite saved desktop preference)
            setState(true, false);
          } else {
            // On larger screens respect saved preference
            var stored = null;
            try{ stored = localStorage.getItem(key); }catch(e){}
            if(stored === '1') setState(true, false); else setState(false, false);
          }
        }

        if(btn){
          btn.addEventListener('click', function(e){
            e.preventDefault();
            var collapsed = layoutRow ? !layoutRow.classList.contains('sidebar-collapsed') : !body.classList.contains('sidebar-collapsed');
            setState(collapsed, true);
          });
        }

        // Initialize
        updateForViewport();

        // React to resize but debounce to avoid thrash
        var resizeTimer = null;
        window.addEventListener('resize', function(){ clearTimeout(resizeTimer); resizeTimer = setTimeout(updateForViewport, 160); });

        // Convenience: clicking the collapsed sidebar (empty area) will expand it
        var sidebarEl = document.getElementById('adminSidebar');
        if (sidebarEl) {
          sidebarEl.addEventListener('click', function(e){
            var isCollapsed = body.classList.contains('sidebar-collapsed') || (layoutRow && layoutRow.classList.contains('sidebar-collapsed'));
            if (isCollapsed) {
              var clickedLink = e.target.closest && e.target.closest('.nav-link');
              var clickedToggle = e.target.closest && e.target.closest('#sidebarToggle');
              if (!clickedLink && !clickedToggle) {
                setState(false, true);
              }
            }
          });
        }
      }catch(e){ console.error(e); }
    })();
  </script>

  <script>
    (function(){
      try{
        function animateAndSubmit(btn){
          var form = btn.closest('form');
          if(!form) return;
          btn.disabled = true;
          btn.classList.add('clicked');
          setTimeout(function(){ try{ form.submit(); }catch(e){} }, 320);
        }

        document.addEventListener('click', function(e){
          var btn = e.target.closest && e.target.closest('button');
          if(!btn) return;
          if(btn.classList.contains('btn-approve') || btn.classList.contains('btn-reject')){
            var confirmMsg = btn.getAttribute('data-confirm');
            if(confirmMsg && !window.confirm(confirmMsg)) return;
            e.preventDefault();
            animateAndSubmit(btn);
          }
        }, true);
      }catch(e){ console.error(e); }
    })();
  </script>
