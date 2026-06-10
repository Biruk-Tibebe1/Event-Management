<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();
header('Location: ' . BASE_URL . '/');
exit;
