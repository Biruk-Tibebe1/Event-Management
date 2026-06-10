<?php
require_once __DIR__ . '/../includes/header.php';
require_organizer();
$pdo = get_db();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    // ensure organizer owns it
    $stmt = $pdo->prepare('DELETE FROM events WHERE id = ? AND organizer_id = ?');
    $stmt->execute([$id, current_user_id()]);
    flash_set('success','Event deleted');
}
header('Location: ' . BASE_URL . '/organizer/events.php'); exit;
?>
