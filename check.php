<?php
// check.php - Check system status
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection OK";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>