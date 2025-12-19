<?php
// debug.php - Test koneksi dan setup di ByetHost
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Test config
$config = require 'config.php';
echo "Config loaded: " . (is_array($config) ? 'Yes' : 'No') . "<br>";

// Test DB connection
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    echo "DB Connection: Success<br>";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "Users table has $count records<br>";
} catch (PDOException $e) {
    echo "DB Connection Error: " . $e->getMessage() . "<br>";
}

// Test file permissions
$uploadDir = $config['upload_dir'];
echo "Upload dir exists: " . (is_dir($uploadDir) ? 'Yes' : 'No') . "<br>";
echo "Upload dir writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "<br>";

// Test includes
if (file_exists('db.php')) {
    echo "db.php exists: Yes<br>";
    require_once 'db.php';
    echo "db.php included: Success<br>";
} else {
    echo "db.php exists: No<br>";
}
?>