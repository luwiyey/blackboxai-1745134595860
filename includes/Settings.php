<?php
require_once 'Database.php';
require_once 'Logger.php';

class Settings {
    private $db;
    private $logger;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    public function get($key) {
        try {
            $result = $this->db->fetchOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
            return $result ? $result['value'] : null;
        } catch (Exception $e) {
            $this->logger->logError(null, 'settings_get_error', ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function set($key, $value) {
        try {
            $exists = $this->db->exists('settings', '`key` = ?', [$key]);
            if ($exists) {
                $this->db->update('settings', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$key]);
            } else {
                $this->db->insert('settings', ['key' => $key, 'value' => $value, 'created_at' => date('Y-m-d H:i:s')]);
            }
            $this->logger->logActivity(null, 'settings_updated', "Setting '{$key}' updated");
            return true;
        } catch (Exception $e) {
            $this->logger->logError(null, 'settings_set_error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    // New methods for user-specific settings

    public function getUserSettings($userId) {
        try {
            $sql = "SELECT * FROM user_settings WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $settings = $result->fetch_assoc();
            if (!$settings) {
                return [
                    'high_contrast' => 0,
                    'font_size' => 16,
                    'screen_reader_enabled' => 0,
                    'voice_navigation_enabled' => 0,
                    'email_notifications' => 1,
                    'two_factor_auth' => 0
                ];
            }
            return $settings;
        } catch (Exception $e) {
            $this->logger->logError($userId, 'get_user_settings_error', ['error' => $e->getMessage()]);
            return [
                'high_contrast' => 0,
                'font_size' => 16,
                'screen_reader_enabled' => 0,
                'voice_navigation_enabled' => 0,
                'email_notifications' => 1,
                'two_factor_auth' => 0
            ];
        }
    }

    public function saveUserSettings($userId, $data) {
        try {
            $sqlCheck = "SELECT id FROM user_settings WHERE user_id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bind_param("i", $userId);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($resultCheck->num_rows > 0) {
                $sqlUpdate = "UPDATE user_settings SET 
                    high_contrast = ?, 
                    font_size = ?, 
                    screen_reader_enabled = ?, 
                    voice_navigation_enabled = ?, 
                    email_notifications = ?, 
                    two_factor_auth = ? 
                    WHERE user_id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->bind_param(
                    "iiiiiii",
                    $data['high_contrast'],
                    $data['font_size'],
                    $data['screen_reader_enabled'],
                    $data['voice_navigation_enabled'],
                    $data['email_notifications'],
                    $data['two_factor_auth'],
                    $userId
                );
                return $stmtUpdate->execute();
            } else {
                $sqlInsert = "INSERT INTO user_settings 
                    (user_id, high_contrast, font_size, screen_reader_enabled, voice_navigation_enabled, email_notifications, two_factor_auth) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtInsert = $this->db->prepare($sqlInsert);
                $stmtInsert->bind_param(
                    "iiiiiii",
                    $userId,
                    $data['high_contrast'],
                    $data['font_size'],
                    $data['screen_reader_enabled'],
                    $data['voice_navigation_enabled'],
                    $data['email_notifications'],
                    $data['two_factor_auth']
                );
                return $stmtInsert->execute();
            }
        } catch (Exception $e) {
            $this->logger->logError($userId, 'save_user_settings_error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
?>
