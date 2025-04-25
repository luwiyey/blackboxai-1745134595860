<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "CREATE TABLE IF NOT EXISTS book_suggestions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(255) DEFAULT NULL,
        reason TEXT DEFAULT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
    echo "Migration 008_create_book_suggestions applied successfully.\n";
} catch (Exception $e) {
    echo "Error applying migration 008_create_book_suggestions: " . $e->getMessage() . "\n";
}
?>
