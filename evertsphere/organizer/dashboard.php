<?php
require_once __DIR__ . '/../includes/header.php';
require_organizer();
$pdo = get_db();
$stmt = $pdo->prepare('SELECT * FROM events WHERE organizer_id = ? ORDER BY event_date DESC');
$stmt->execute([current_user_id()]);
$events = $stmt->fetchAll();
?>
<div class="row">
  <div class="col-12">
    <div class="glass-panel rounded-[1.5rem] p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="title-font text-2xl font-extrabold">Organizer Dashboard</h2>
        <a href="create_event.php" class="btn btn-primary">Create Event</a>
      </div>
      <div class="table-responsive">
        <?php if ($events): ?>
          <table class="table table-sm table-hover">
            <thead>
              <tr><th>ID</th><th>Title</th><th>Date</th><th>City</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($events as $e): ?>
              <tr>
                <td><?php echo esc($e['id']); ?></td>
                <td><?php echo esc($e['title']); ?></td>
                <td><?php echo esc($e['event_date']); ?></td>
                <td><?php echo esc($e['city']); ?></td>
                <td><?php echo esc($e['status']); ?></td>
                <td>
                  <a href="edit_event.php?id=<?php echo esc($e['id']); ?>" class="btn btn-sm btn-rounded" style="background:#d4a45c;color:#2b1710;">Edit</a>
                  <form method="post" action="delete_event.php" style="display:inline;margin-left:6px;" onsubmit="return confirm('Delete this event? This cannot be undone.');">
                    <input type="hidden" name="id" value="<?php echo esc($e['id']); ?>">
                    <button type="submit" class="btn btn-sm btn-rounded" style="background:#7a4b2a;color:#fff;">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="text-center py-8 text-coffee-300">No events yet. <a href="create_event.php" class="ml-2 text-coffee-200 underline">Create your first event</a></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
