<?php
// export_database.php - Export database to SQL file for hosting
require_once 'config.php';

$config = require 'config.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['user'], $config['pass']);

$tables = ['users', 'categories', 'listings', 'comments', 'ratings', 'sponsors', 'footer', 'header_settings'];

$sql = "-- Database export for hosting\n-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $table) {
    // Get table structure
    $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
    $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
    $sql .= $createTable['Create Table'] . ";\n\n";

    // Get table data
    $stmt = $pdo->query("SELECT * FROM `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows)) {
        $columns = array_keys($rows[0]);
        $sql .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";

        $values = [];
        foreach ($rows as $row) {
            $rowValues = [];
            foreach ($row as $value) {
                $rowValues[] = $pdo->quote($value);
            }
            $values[] = "(" . implode(', ', $rowValues) . ")";
        }
        $sql .= implode(",\n", $values) . ";\n\n";
    }
}

file_put_contents('database_export.sql', $sql);
echo "Database exported to database_export.sql";
?>