<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_db();

// Revenue and booking features were removed; reports focus on events and users now

// User stats
$users_total = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$users_organizers = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "organizer"')->fetchColumn();
$users_this_month = $pdo->query('SELECT COUNT(*) FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())')->fetchColumn();

// Event stats
$events_total = $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$events_published = $pdo->query('SELECT COUNT(*) FROM events WHERE status = "published"')->fetchColumn();
$events_by_city = $pdo->query('SELECT city, COUNT(*) as count FROM events WHERE status = "published" GROUP BY city ORDER BY count DESC LIMIT 10')->fetchAll();

// Top events by attendance (approx) - uses events only
$top_events = $pdo->query('SELECT e.title, COUNT(ev.id) as event_count FROM events e LEFT JOIN events ev ON ev.id = e.id GROUP BY e.id ORDER BY event_count DESC LIMIT 10')->fetchAll();

$page = 'reports';
include __DIR__ . '/admin_sidebar.php';
?>

<div class="col-md-9">
        <h2 class="mb-4">Reports & Analytics</h2>

        <style>
            .report-stats .stat-grid { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
            .report-stats .stat-card { flex:1 1 200px; background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); border:1px solid rgba(255,255,255,0.03); border-radius:10px; padding:14px; display:flex; gap:12px; align-items:center; box-shadow:0 6px 20px rgba(0,0,0,0.06); transition: transform .15s ease, box-shadow .15s ease; }
            .report-stats .stat-card:hover { transform: translateY(-6px); box-shadow:0 20px 50px rgba(0,0,0,0.12); }
            .report-stats .icon { width:44px; height:44px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#d4af37,#f4d03f); color:#1a0f0a; font-size:18px; }
            .report-stats .label { font-size:0.85rem; color:#f3e6c6; margin-bottom:4px; }
            .report-stats .count { font-size:1.5rem; font-weight:700; color:#fff; }
            .report-tables { display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px; }
            @media (max-width: 900px) { .report-tables { grid-template-columns: 1fr; } }
        </style>

        <!-- User Activity (attractive stat cards) -->
        <div class="card mb-4 report-stats">
            <div class="card-header bg-light">
                <h5 class="mb-0">User Activity</h5>
            </div>
            <div class="card-body">
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="icon"><i data-lucide="users"></i></div>
                        <div>
                            <div class="label">Total Users</div>
                            <div class="count"><span class="stat-count" data-target="<?php echo esc($users_total); ?>">0</span></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i data-lucide="user-check"></i></div>
                        <div>
                            <div class="label">Organizers</div>
                            <div class="count"><span class="stat-count" data-target="<?php echo esc($users_organizers); ?>">0</span></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i data-lucide="calendar-plus"></i></div>
                        <div>
                            <div class="label">New This Month</div>
                            <div class="count"><span class="stat-count" data-target="<?php echo esc($users_this_month); ?>">0</span></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i data-lucide="user"></i></div>
                        <div>
                            <div class="label">Regular Users</div>
                            <div class="count"><span class="stat-count" data-target="<?php echo esc($users_total - $users_organizers); ?>">0</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Statistics: stats + city & top event tables side-by-side -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Event Statistics</h5>
            </div>
            <div class="card-body">
                <div class="stat-grid" style="margin-bottom:16px;">
                    <div class="stat-card">
                        <div class="icon"><i data-lucide="film"></i></div>
                        <div>
                            <div class="label">Total Events</div>
                            <div class="count"><span class="stat-count" data-target="<?php echo esc($events_total); ?>">0</span></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i data-lucide="check-circle"></i></div>
                        <div>
                            <div class="label">Published</div>
                            <div class="count"><span class="stat-count" data-target="<?php echo esc($events_published); ?>">0</span></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i data-lucide="clock"></i></div>
                        <div>
                            <div class="label">Pending Approval</div>
                            <div class="count"><span class="stat-count" data-target="<?php echo esc($events_total - $events_published); ?>">0</span></div>
                        </div>
                    </div>
                </div>

                <div class="report-tables">
                    <div>
                        <h6>Events by City (Top 10)</h6>
                        <div class="table-responsive"><table class="admin-table"><thead><tr><th>City</th><th>Count</th></tr></thead><tbody><?php foreach ($events_by_city as $city): ?><tr><td><?php echo esc($city['city']); ?></td><td><?php echo esc($city['count']); ?></td></tr><?php endforeach; ?></tbody></table></div>
                    </div>
                    <div>
                        <h6>Top Events</h6>
                        <div class="table-responsive"><table class="admin-table"><thead><tr><th>Event Title</th><th>Count</th></tr></thead><tbody><?php foreach ($top_events as $ev): ?><tr><td><?php echo esc($ev['title']); ?></td><td><?php echo esc($ev['event_count'] ?? 0); ?></td></tr><?php endforeach; ?></tbody></table></div>
                    </div>
                </div>

            </div>
        </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
