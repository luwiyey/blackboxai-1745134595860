<?php
// Migration to create user_settings table for storing user-specific accessibility and privacy settings

require_once __DIR__ . '/../init.php';

use Includes\Migration;

class Migration005 extends Migration {
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS user_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            high_contrast BOOLEAN NOT NULL DEFAULT FALSE,
            font_size INT NOT NULL DEFAULT 16,
            screen_reader_enabled BOOLEAN NOT NULL DEFAULT FALSE,
            voice_navigation_enabled BOOLEAN NOT NULL DEFAULT FALSE,
            email_notifications BOOLEAN NOT NULL DEFAULT TRUE,
            two_factor_auth BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->execute($sql);
    }

    public function down() {
        $sql = "DROP TABLE IF EXISTS user_settings;";
        $this->execute($sql);
    }
}

$migration = new Migration005();
$migration->migrate();
?>
