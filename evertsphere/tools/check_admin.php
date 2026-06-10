<?php
// tools/check_admin.php
require __DIR__ . '/../config/database.php';
try {
    $pdo = get_db();
    $email = 'biruktibebesol@gmil.com';
    $stmt = $pdo->prepare('SELECT id,name,email,password,role,is_approved FROM users WHERE email=?');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $password_ok = false;
    if ($row) {
        $password_ok = password_verify('Biruk123', $row['password']);
    }
    echo json_encode(['row' => $row, 'password_ok' => $password_ok], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
