<?php
require_once __DIR__ . '/../init.php';

use Includes\Migration;

class Migration006 extends Migration {
    public function up($conn) {
        // Add tags column to books table (JSON encoded array)
        $conn->query("ALTER TABLE books ADD COLUMN tags TEXT DEFAULT '[]'");

        // Add cover image column
        $conn->query("ALTER TABLE books ADD COLUMN cover VARCHAR(255) DEFAULT NULL");

        // Add PDF snippet column
        $conn->query("ALTER TABLE books ADD COLUMN pdf_snippet TEXT DEFAULT NULL");

        // Create book_reviews table
        $conn->query("
            CREATE TABLE IF NOT EXISTS book_reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                book_id INT NOT NULL,
                user_id INT NOT NULL,
                rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
                review TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Add reading_status column to user_books table (if exists)
        $conn->query("ALTER TABLE user_books ADD COLUMN reading_status ENUM('read', 'want_to_read', 'reading') DEFAULT 'want_to_read'");
    }

    public function down($conn) {
        $conn->query("ALTER TABLE books DROP COLUMN tags");
        $conn->query("ALTER TABLE books DROP COLUMN cover");
        $conn->query("ALTER TABLE books DROP COLUMN pdf_snippet");
        $conn->query("DROP TABLE IF EXISTS book_reviews");
        $conn->query("ALTER TABLE user_books DROP COLUMN reading_status");
    }
}
?>
