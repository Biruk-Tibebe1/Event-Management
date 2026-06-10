<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = get_db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { 
  echo '<section class="w-full px-6 py-12 max-w-6xl mx-auto"><p class="text-red-300">Invalid movie</p></section>'; 
  require_once __DIR__ . '/../includes/footer.php'; 
  exit; 
}
$stmt = $pdo->prepare('SELECT m.*, c.name as cinema_name FROM movies m JOIN cinemas c ON m.cinema_id = c.id WHERE m.id = ? LIMIT 1');
$stmt->execute([$id]);
$movie = $stmt->fetch();
if (!$movie) { 
  echo '<section class="w-full px-6 py-12 max-w-6xl mx-auto"><p class="text-red-300">Movie not found</p></section>'; 
  require_once __DIR__ . '/../includes/footer.php'; 
  exit; 
}
// Note: booking/seat selection features removed — seats are no longer managed here
?>

<?php $page_title = 'Movie'; require_once __DIR__ . '/../includes/header.php'; ?>

<section class="w-full px-6 py-12 max-w-6xl mx-auto">
  <a href="<?php echo BASE_URL; ?>/cinema" class="inline-flex items-center gap-2 text-coffee-300 hover:text-coffee-200 transition-colors mb-8">
    <i data-lucide="arrow-left" class="w-5 h-5"></i>
    <span class="text-lg">Back to Cinemas</span>
  </a>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Movie Details -->
    <div class="lg:col-span-1">
      <div class="card-hover rounded-2xl bg-white border border-coffee-200/20 backdrop-blur-sm p-6 sticky top-20">
        <h2 class="font-display text-2xl font-bold text-coffee-900 mb-4"><?php echo esc($movie['title']); ?></h2>

        <?php
          $ratingVal = $movie['rating'] ?? '';
          if (empty($ratingVal) && !empty($movie['imdb_url'])) {
            $ratingVal = get_imdb_rating($movie['imdb_url']);
          }
        ?>
        <div class="flex items-center gap-3 mb-3">
          <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-50 text-amber-700 rounded-full text-sm">
            <i data-lucide="star" class="w-4 h-4"></i>
            <?php echo esc($ratingVal ?: '—'); ?>
          </span>
          <?php if (!empty($movie['imdb_url'])): ?>
            <a href="<?php echo esc($movie['imdb_url']); ?>" target="_blank" class="text-sm text-amber-400 hover:text-amber-300">View on IMDb</a>
          <?php endif; ?>
        </div>

        <?php if ($movie['poster']): ?>
          <div class="mb-6 rounded-lg overflow-hidden border border-coffee-200/20">
            <img src="<?php echo esc(get_media_url($movie['poster'])); ?>" alt="<?php echo esc($movie['title']); ?>" class="w-full">
          </div>
        <?php endif; ?>

        <div class="space-y-3 text-coffee-700/60">
          <p class="flex items-center gap-2 text-base">
            <i data-lucide="building" class="w-5 h-5 text-coffee-400"></i>
            <span><?php echo esc($movie['cinema_name']); ?></span>
          </p>
          <p class="flex items-center gap-2 text-base">
            <i data-lucide="calendar" class="w-5 h-5 text-coffee-400"></i>
            <span><?php echo date('M d, Y', strtotime($movie['show_date'])); ?></span>
          </p>
          <p class="flex items-center gap-2 text-base">
            <i data-lucide="clock" class="w-5 h-5 text-coffee-400"></i>
            <span><?php echo esc($movie['show_time']); ?></span>
          </p>
          <p class="flex items-center gap-2 text-base">
            <i data-lucide="tag" class="w-5 h-5 text-coffee-400"></i>
            <span><?php echo esc($movie['genre']); ?></span>
          </p>
        </div>
      </div>
    </div>

    <!-- Booking removed: seat selection and checkout were removed from the product -->
    <div class="lg:col-span-2">
      <div class="rounded-2xl bg-white border border-coffee-200/20 p-8">
        <h3 class="font-display text-2xl font-bold text-coffee-900 mb-4">Booking Disabled</h3>
        <p class="text-coffee-700/60">The seat selection and booking features have been removed from this installation. If you need ticketing re-enabled, please contact the site administrator.</p>
        <div class="mt-6">
          <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">Back to Cinemas</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/cinema_scripts.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
