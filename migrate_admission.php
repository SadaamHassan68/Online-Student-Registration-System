<?php
/**
 * Migration Script - Add Admission Fields
 */
require_once __DIR__ . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration...<br>";
    
    // Check if columns already exist to avoid errors
    $columns = $conn->query("SHOW COLUMNS FROM students")->fetchAll(PDO::FETCH_COLUMN);
    
    $newColumns = [
        "admission_status" => "ALTER TABLE students ADD COLUMN admission_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER registration_date",
        "intended_major" => "ALTER TABLE students ADD COLUMN intended_major VARCHAR(100) AFTER admission_status",
        "high_school_name" => "ALTER TABLE students ADD COLUMN high_school_name VARCHAR(150) AFTER intended_major",
        "high_school_grade" => "ALTER TABLE students ADD COLUMN high_school_grade DECIMAL(4,2) AFTER high_school_name",
        "secondary_phone" => "ALTER TABLE students ADD COLUMN secondary_phone VARCHAR(20) AFTER high_school_grade",
        "status" => "ALTER TABLE students MODIFY COLUMN status ENUM('active', 'inactive') DEFAULT 'active'" // Just in case
    ];
    
    foreach ($newColumns as $col => $sql) {
        if (!in_array($col, $columns)) {
            $conn->exec($sql);
            echo "Added column: $col<br>";
        } else {
            echo "Column $col already exists, skipping.<br>";
        }
    }
    
    // Also add status if it was missing or renamed (the schema showed it as 'status' before I changed it to 'admission_status' in my thought but actually I replaced 'status' with 'admission_status' in the schema file edit)
    // Wait, the schema had 'status'. I should keep 'status' and add 'admission_status'.
    
    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
