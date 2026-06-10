<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();

// Fetch upcoming showings and group by movie title
$stmt = $pdo->prepare("SELECT m.*, c.name AS cinema_name FROM movies m LEFT JOIN cinemas c ON c.id = m.cinema_id WHERE m.show_date >= CURDATE() ORDER BY m.show_date, m.show_time");
$stmt->execute();
$rows = $stmt->fetchAll();

$moviesByTitle = [];
foreach ($rows as $r) {
    $key = $r['title'] ?? 'Untitled';
    if (!isset($moviesByTitle[$key])) {
        $moviesByTitle[$key] = [
            'title' => $r['title'] ?? 'Untitled',
              'genre' => $r['genre'] ?? '',
              'poster' => $r['poster'] ?? '',
              'rating' => $r['rating'] ?? '',
              'imdb_url' => $r['imdb_url'] ?? '',
            'showings' => [],
            'cinemas' => []
        ];
    }
    $moviesByTitle[$key]['showings'][] = $r;
    if (!empty($r['cinema_name'])) $moviesByTitle[$key]['cinemas'][] = $r['cinema_name'];
}

$stmt = $pdo->query('SELECT * FROM cinemas ORDER BY name');
$cinemas = $stmt->fetchAll();

$base_url = defined('BASE_URL') ? BASE_URL : '';

$page_title = 'Cinema';
$page_has_full_hero = true;
require_once __DIR__ . '/../includes/header.php';
?>

  <!-- Hero Banner -->
   <section class="site-hero full-bleed">
    <div class="hero-inner text-center">
     <h2 class="font-display text-4xl md:text-5xl font-bold mb-3">Now Showing</h2>
     <p class="text-coffee-200 text-lg max-w-xl mx-auto">Experience the best of Ethiopian and international cinema at our premium locations.</p>
    </div>
  </section>

  <?php echo '<div class="' . $containerClass . '">'; ?>

  <!-- Tabs -->
  <div class="max-w-7xl mx-auto px-4 mt-8">
    <div class="flex border-b border-coffee-200 gap-8 text-sm font-semibold">
     <button class="tab-btn tab-active pb-3 transition" data-tab="movies"> <i data-lucide="film" class="w-4 h-4 inline mr-1"></i> Movies </button> <button class="tab-btn pb-3 text-coffee-400 hover:text-coffee-600 transition" data-tab="locations"> <i data-lucide="map-pin" class="w-4 h-4 inline mr-1"></i> Locations </button>
    </div>
   </div>

   <!-- Movies Tab -->
   <section id="tab-movies" class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
     <?php if (!empty($moviesByTitle)): ?>
        <?php $delay = 1; foreach ($moviesByTitle as $m): ?>
         <div class="movie-card bg-white rounded-xl shadow-md overflow-hidden animate-fade-up animate-delay-<?php echo min(4, $delay); ?> cursor-pointer">
          <div class="relative h-64 overflow-hidden <?php echo $m['poster'] ? '' : 'bg-coffee-300'; ?>">
           <div class="absolute inset-0 bg-gradient-to-t from-coffee-800 to-transparent flex items-end p-4 movie-overlay opacity-0 transition-opacity z-10">
            <a href="<?php echo esc($base_url . '/cinema/movie.php?id=' . (int)$m['showings'][0]['id']); ?>" class="bg-coffee-500 hover:bg-coffee-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">Book Now</a>
           </div>
           <?php if ($m['poster']): ?>
             <img src="<?php echo esc(get_media_url($m['poster'])); ?>" alt="<?php echo esc($m['title']); ?>" class="w-full h-full object-cover transition-transform duration-500">
           <?php else: ?>
             <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-coffee-600 to-coffee-800">
               <i data-lucide="film" class="w-16 h-16 text-coffee-300"></i>
             </div>
           <?php endif; ?>
          </div>
          <div class="p-4">
           <span class="text-xs bg-coffee-100 text-coffee-600 px-2 py-0.5 rounded-full font-medium"><?php echo esc($m['genre']); ?></span>
           <h3 class="font-display font-semibold text-lg mt-2"><?php echo esc($m['title']); ?></h3>
           <?php $firstShowing = $m['showings'][0] ?? null; ?>
           <p class="text-coffee-400 text-sm mt-1"><?php echo $firstShowing ? date('M d, Y', strtotime($firstShowing['show_date'])) . ' • ' . esc($firstShowing['show_time']) : ''; ?></p>
            <div class="flex items-center gap-2 mt-2 text-coffee-500">
            <?php
              $ratingVal = $m['rating'] ?? '';
              if (empty($ratingVal) && !empty($m['imdb_url'])) {
                $ratingVal = get_imdb_rating($m['imdb_url']);
              }
            ?>
            <i data-lucide="star" class="w-4 h-4 fill-current"></i>
            <span class="text-sm font-medium"><?php echo esc($ratingVal ?: '—'); ?></span>
            <div class="ml-2 p-1 rounded bg-white/5 text-xs text-coffee-200">Rating</div>
            <?php if (!empty($m['imdb_url'])): ?>
              <a href="<?php echo esc($m['imdb_url']); ?>" target="_blank" class="text-xs text-amber-400 ml-2">IMDb</a>
            <?php endif; ?>
           </div>
           <div class="mt-3 pt-3 border-t border-coffee-100">
            <p class="text-xs text-coffee-500 font-medium mb-2">Available at:</p>
            <div class="space-y-1">
             <?php $locations = array_unique($m['cinemas']); $locations = array_values($locations); $showLocations = array_slice($locations, 0, 2); ?>
             <?php if ($showLocations): ?>
               <?php foreach ($showLocations as $loc): ?>
                 <div class="flex items-center gap-1.5 text-xs text-coffee-600">
                  <i data-lucide="map-pin" class="w-3 h-3"></i> <span><?php echo esc($loc); ?></span>
                 </div>
               <?php endforeach; ?>
             <?php else: ?>
               <div class="flex items-center gap-1.5 text-xs text-coffee-600"> <i data-lucide="map-pin" class="w-3 h-3"></i> <span>Unknown</span> </div>
             <?php endif; ?>
            </div>
           </div>
          </div>
         </div>
        <?php $delay++; endforeach; ?>
     <?php else: ?>
        <div class="movie-card bg-white rounded-xl shadow-md overflow-hidden animate-fade-up animate-delay-1 cursor-pointer">
          <div class="relative h-64 overflow-hidden bg-coffee-300">
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-coffee-600 to-coffee-800">
              <i data-lucide="film" class="w-16 h-16 text-coffee-300"></i>
            </div>
          </div>
          <div class="p-4 text-center">
            <h3 class="font-display font-semibold text-lg mt-2">No movies available</h3>
            <p class="text-coffee-400 text-sm mt-1">Check back soon for showtimes</p>
          </div>
        </div>
     <?php endif; ?>
    </div>
   </section>

   <!-- Locations Tab -->
   <section id="tab-locations" class="max-w-7xl mx-auto px-4 py-8 hidden">
    <h2 id="location-heading" class="font-display text-2xl font-bold mb-6">Our Locations</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
     <?php if (!empty($cinemas)): ?>
       <?php $d=1; foreach ($cinemas as $c): ?>
         <div class="bg-white rounded-xl shadow-md overflow-hidden animate-fade-up animate-delay-<?php echo min(4,$d); ?>">
          <div class="h-48 bg-gradient-to-br from-coffee-300 to-coffee-500 flex items-center justify-center relative">
           <i data-lucide="map-pin" class="w-12 h-12 text-white"></i> <span class="absolute top-3 right-3 bg-white/90 text-coffee-700 text-xs font-semibold px-2 py-1 rounded-full"><?php echo esc($c['screens'] ?? ''); ?></span>
          </div>
          <div class="p-5">
           <h3 class="font-display font-bold text-xl"><?php echo esc($c['name']); ?></h3>
           <p class="text-coffee-400 text-sm mt-1 flex items-center gap-1"><i data-lucide="navigation" class="w-3 h-3"></i> <?php echo esc($c['address'] ?: $c['city']); ?></p>
           <div class="flex items-center gap-4 mt-3 text-sm text-coffee-500">
            <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> <?php echo esc($c['hours'] ?? '9AM - 11PM'); ?></span>
            <span class="flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> <?php echo esc($c['contact'] ?? '+251 11 000 0000'); ?></span>
           </div><a href="<?php echo esc($base_url . '/cinema/view.php?id=' . (int)$c['id']); ?>" class="mt-4 block w-full text-center bg-coffee-500 hover:bg-coffee-600 text-white py-2.5 rounded-lg font-medium transition text-sm">View Showtimes</a>
          </div>
         </div>
       <?php $d++; endforeach; ?>
     <?php else: ?>
       <div class="rounded-2xl bg-coffee-100/40 border border-coffee-200 p-12 text-center">
         <i data-lucide="map-pin" class="w-16 h-16 text-coffee-500/30 mx-auto mb-4"></i>
         <h3 class="font-display text-xl font-bold text-coffee-700 mb-2">No locations found</h3>
         <p class="text-coffee-500">Add cinemas in the admin to show locations here.</p>
       </div>
     <?php endif; ?>
    </div>
   </section>

<?php include __DIR__ . '/../includes/cinema_scripts.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
