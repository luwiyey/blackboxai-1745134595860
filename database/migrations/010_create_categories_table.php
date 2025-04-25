<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
    echo "Migration 010_create_categories_table applied successfully.\n";
} catch (Exception $e) {
    echo "Error applying migration 010_create_categories_table: " . $e->getMessage() . "\n";
}
?>
