<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = get_db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { 
  echo '<section class="w-full px-6 py-12 max-w-6xl mx-auto"><p class="text-red-300">Invalid cinema</p></section>'; 
  require_once __DIR__ . '/../includes/footer.php'; 
  exit; 
}
$stmt = $pdo->prepare('SELECT * FROM cinemas WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$cinema = $stmt->fetch();
if (!$cinema) { 
  echo '<section class="w-full px-6 py-12 max-w-6xl mx-auto"><p class="text-red-300">Cinema not found</p></section>'; 
  require_once __DIR__ . '/../includes/footer.php'; 
  exit; 
}
$stmt = $pdo->prepare('SELECT * FROM movies WHERE cinema_id = ? AND show_date >= CURDATE() ORDER BY show_date, show_time');
$stmt->execute([$id]);
$movies = $stmt->fetchAll();
?>

<?php $page_title = 'Cinema'; require_once __DIR__ . '/../includes/header.php'; ?>

<section class="w-full px-6 py-12 max-w-6xl mx-auto">
  <div class="mb-8">
    <a href="<?php echo BASE_URL; ?>/cinema" class="inline-flex items-center gap-2 text-coffee-300 hover:text-coffee-200 transition-colors mb-4">
      <i data-lucide="arrow-left" class="w-5 h-5"></i>
      <span class="text-lg">Back to Cinemas</span>
    </a>
    <h1 class="font-display text-4xl md:text-5xl font-bold text-coffee-100 mb-4"><?php echo esc($cinema['name']); ?></h1>
    <div class="space-y-2 text-coffee-100/60">
      <p class="flex items-center gap-2 text-lg">
        <i data-lucide="map-pin" class="w-5 h-5 text-coffee-400"></i>
        <span><?php echo esc($cinema['city']); ?></span>
      </p>
      <p class="flex items-start gap-2 text-lg">
        <i data-lucide="home" class="w-5 h-5 text-coffee-400 flex-shrink-0 mt-0.5"></i>
        <span><?php echo esc($cinema['address']); ?></span>
      </p>
    </div>
  </div>

  <div class="border-b border-coffee-700/20 pb-8 mb-8"></div>

  <?php if ($movies): ?>
    <div class="mb-8">
      <h2 class="font-display text-2xl font-bold text-coffee-200 mb-6">Upcoming Showings</h2>
      <div class="space-y-3">
        <?php foreach ($movies as $m): ?>
          <div class="card-hover rounded-xl bg-gradient-to-r from-coffee-900/30 to-stone-900/50 border border-coffee-700/20 backdrop-blur-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1">
              <h3 class="font-display text-xl md:text-2xl font-bold text-coffee-200 mb-2"><?php echo esc($m['title']); ?></h3>
              <div class="flex flex-wrap gap-4 text-base text-coffee-100/60">
                <span class="flex items-center gap-2">
                  <i data-lucide="tag" class="w-4 h-4 text-coffee-400"></i>
                  <?php echo esc($m['genre']); ?>
                </span>
                <span class="flex items-center gap-2">
                  <i data-lucide="calendar" class="w-4 h-4 text-coffee-400"></i>
                  <?php echo date('M d, Y', strtotime($m['show_date'])); ?>
                </span>
                <span class="flex items-center gap-2">
                  <i data-lucide="clock" class="w-4 h-4 text-coffee-400"></i>
                  <?php echo esc($m['show_time']); ?>
                </span>
              </div>
            </div>
            <a href="<?php echo BASE_URL; ?>/cinema/movie.php?id=<?php echo $m['id']; ?>" class="px-8 py-3 bg-coffee-500 text-stone-900 font-semibold rounded-lg hover:shadow-lg hover:shadow-coffee-500/30 transition-all text-center whitespace-nowrap">
              <i data-lucide="ticket" class="inline w-5 h-5 mr-2"></i>Book Now
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="rounded-2xl bg-coffee-900/10 border border-coffee-700/20 p-12 text-center">
      <i data-lucide="film" class="w-16 h-16 text-coffee-500/30 mx-auto mb-4"></i>
      <h3 class="font-heading text-xl font-bold text-coffee-200 mb-2">No upcoming movies</h3>
      <p class="text-coffee-100/60 text-lg">Check back soon for new showtimes</p>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/cinema_scripts.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
