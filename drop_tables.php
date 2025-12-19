<?php
// drop_tables.php - Drop database tables
require_once 'config.php';

$tables = ['users', 'categories', 'listings', 'comments', 'ratings', 'sponsors', 'footer', 'header_settings'];

foreach ($tables as $table) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped table: $table<br>";
    } catch (PDOException $e) {
        echo "Error dropping $table: " . $e->getMessage() . "<br>";
    }
}

echo "All tables dropped";
?>