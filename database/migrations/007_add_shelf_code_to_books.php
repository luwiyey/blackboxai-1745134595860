<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "ALTER TABLE books ADD COLUMN shelf_code VARCHAR(20) DEFAULT NULL";
    $conn->query($sql);
    echo "Migration 007_add_shelf_code_to_books applied successfully.\n";
} catch (Exception $e) {
    echo "Error applying migration 007_add_shelf_code_to_books: " . $e->getMessage() . "\n";
}
?>
