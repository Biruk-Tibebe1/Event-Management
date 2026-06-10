<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$theme = $_POST['theme'] ?? '';
$allowed = ['', 'dark', 'cultural'];
if (!in_array($theme, $allowed)) $theme = '';
if (!empty($_SESSION['user_id'])) {
    set_user_theme(current_user_id(), $theme);
} else {
    $_SESSION['theme'] = $theme;
}
$redirect = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/';
if (strpos($redirect, BASE_URL) !== 0) {
    $redirect = BASE_URL . '/';
}
header('Location: ' . $redirect);
exit;
?>
