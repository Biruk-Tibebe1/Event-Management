<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';
try {
    if ($action === 'email') {
        $email = trim($_GET['email'] ?? $_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email']); exit;
        }
        $u = get_user_by_email($email);
        if ($u) {
            echo json_encode(['success' => true, 'available' => false, 'message' => 'Email already registered']); exit;
        }
        echo json_encode(['success' => true, 'available' => true]); exit;
    } elseif ($action === 'password') {
        $pw = $_GET['password'] ?? $_POST['password'] ?? '';
        $len = strlen($pw);
        $strength = 'weak';
        if ($len >= 12) $strength = 'strong';
        elseif ($len >= 8) $strength = 'good';
        elseif ($len >= 6) $strength = 'weak';
        echo json_encode(['success' => true, 'strength' => $strength, 'length' => $len]); exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
