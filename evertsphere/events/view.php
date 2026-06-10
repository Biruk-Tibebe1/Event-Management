<?php
require_once __DIR__ . '/../includes/header.php';
$pdo = get_db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo '<p>Invalid event</p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
$stmt = $pdo->prepare('SELECT e.*, u.name as organizer_name FROM events e LEFT JOIN users u ON e.organizer_id = u.id WHERE e.id = ? LIMIT 1');
$stmt->execute([$id]);
$ev = $stmt->fetch();
if (!$ev) {
    echo '<p>Event not found</p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
// Determine poster URL (use BASE_URL so paths work when site is not at webroot)
$posterUrl = BASE_URL . '/assets/images/event1.svg';
$posterPath = __DIR__ . '/../assets/uploads/' . ($ev['poster'] ?? '');
if (!empty($ev['poster']) && file_exists($posterPath)) {
  $posterUrl = BASE_URL . '/assets/uploads/' . rawurlencode($ev['poster']);
}

?>
<div class="row">
  <div class="col-12 mb-3">
    <div class="hero event-hero" style="background-image: url('<?php echo esc($posterUrl); ?>');">
      <h1 class="event-title title-font"><?php echo esc($ev['title']); ?></h1>
      <p class="event-meta"><?php echo esc($ev['category']); ?> — <?php echo esc($ev['city']); ?> — <?php echo esc($ev['event_date']); ?></p>
    </div>
  </div>
  <div class="col-md-8">
    <div class="mb-3">
      <div class="event-details-box">
        <h3>About</h3>
        <p><?php echo nl2br(esc($ev['description'])); ?></p>
        <p><strong>Venue:</strong> <?php echo esc($ev['venue']); ?> — <?php echo esc($ev['city']); ?></p>
        <p><strong>Organizer:</strong> <?php echo esc($ev['organizer_name'] ?? '—'); ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3">
      <h4>Tickets</h4>
      <p>Price: <?php echo $ev['price'] > 0 ? '$' . esc($ev['price']) : 'Free'; ?></p>
      <p class="text-coffee-700/60 mt-3">Ticketing and booking features have been disabled on this site. Please contact the administrator for assistance.</p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
