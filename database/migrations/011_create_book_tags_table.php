<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "CREATE TABLE IF NOT EXISTS book_tags (
        book_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (book_id, tag_id),
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
    echo "Migration 011_create_book_tags_table applied successfully.\n";
} catch (Exception $e) {
    echo "Error applying migration 011_create_book_tags_table: " . $e->getMessage() . "\n";
}
?>
