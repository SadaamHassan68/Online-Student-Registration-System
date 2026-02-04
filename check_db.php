<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "Connection successful!<br>";
    
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "The database has no tables.";
    } else {
        echo "Tables found:<br>";
        foreach ($tables as $table) {
            echo "- $table<br>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
