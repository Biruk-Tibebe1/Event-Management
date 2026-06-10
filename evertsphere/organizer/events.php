<?php
// Organizer events page moved to dashboard. Redirect to dashboard.
require_once __DIR__ . '/../includes/header.php';
require_organizer();
header('Location: ' . BASE_URL . '/organizer/dashboard.php');
exit;
?>
