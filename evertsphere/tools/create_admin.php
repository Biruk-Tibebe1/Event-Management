<?php
// tools/create_admin.php
// Usage (from project root):
// php tools/create_admin.php "Full Name" email@example.com "PlainPassword" "phone"
// This script generates a secure password hash (bcrypt/argon2) and inserts/updates an admin user.

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$argv0 = array_shift($argv);
if (count($argv) < 3) {
    echo "Usage: php tools/create_admin.php \"Full Name\" email@example.com \"PlainPassword\" \"phone\"\n";
    exit(1);
}

$name = $argv[0];
$email = $argv[1];
$password = $argv[2];
$phone = $argv[3] ?? '';

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = get_db();
} catch (Exception $e) {
    echo "Failed to connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

// generate a secure hash
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // check if user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row) {
        $id = $row['id'];
        $stmt = $pdo->prepare('UPDATE users SET name = ?, password = ?, role = ?, phone = ?, is_approved = 1 WHERE id = ?');
        $stmt->execute([$name, $hash, 'admin', $phone, $id]);
        echo "Updated existing user (id={$id}) and set role=admin, is_approved=1.\n";
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, phone, is_approved, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())');
        $stmt->execute([$name, $email, $hash, 'admin', $phone]);
        echo "Inserted admin user (id=" . $pdo->lastInsertId() . ") with email={$email}.\n";
    }
    echo "Password hash: {$hash}\n";
    exit(0);
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
