<?php
// make_admin.php - Create admin user
require_once 'config.php';

$name = 'Administrator';
$email = 'admin@example.com';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$role = 'admin';

$stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
try {
    $stmt->execute([$name, $email, $password, $role]);
    echo "Admin user created";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>