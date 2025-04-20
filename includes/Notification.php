<?php
class Notification {
    private $db;
    private $logger;
    private static $instance = null;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function send($userId, $type, $message, $link = null) {
        try {
            $data = [
                'user_id' => $userId,
                'type' => $type,
                'message' => $message,
                'link' => $link,
                'read' => 0
            ];

            $notificationId = $this->db->insert('notifications', $data);

            // Log notification creation
            $this->logger->logActivity(
                $userId,
                'notification_sent',
                "Type: $type, Message: $message"
            );

            // Send email notification if enabled
            $this->sendEmailNotification($userId, $type, $message);

            return $notificationId;
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'notification_error',
                ['error' => $e->getMessage()]
            );
            return false;
        }
    }

    public function sendBatch($userIds, $type, $message, $link = null) {
        try {
            $this->db->beginTransaction();

            $data = [];
            foreach ($userIds as $userId) {
                $data[] = [
                    'user_id' => $userId,
                    'type' => $type,
                    'message' => $message,
                    'link' => $link,
                    'read' => 0
                ];
            }

            $this->db->insertBatch('notifications', $data);

            // Log batch notification
            $this->logger->logActivity(
                null,
                'batch_notification_sent',
                "Type: $type, Recipients: " . count($userIds)
            );

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->logError(
                null,
                'batch_notification_error',
                ['error' => $e->getMessage()]
            );
            return false;
        }
    }

    public function markAsRead($notificationId, $userId) {
        try {
            return $this->db->update(
                'notifications',
                ['read' => 1],
                'id = ? AND user_id = ?',
                [$notificationId, $userId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'mark_notification_read_error',
                ['error' => $e->getMessage()]
            );
            return false;
        }
    }

    public function markAllAsRead($userId) {
        try {
            return $this->db->update(
                'notifications',
                ['read' => 1],
                'user_id = ? AND read = 0',
                [$userId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'mark_all_notifications_read_error',
                ['error' => $e->getMessage()]
            );
            return false;
        }
    }

    public function getUnreadCount($userId) {
        try {
            return $this->db->count(
                'notifications',
                'user_id = ? AND read = 0',
                [$userId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'get_unread_count_error',
                ['error' => $e->getMessage()]
            );
            return 0;
        }
    }

    public function getUserNotifications($userId, $limit = 20, $offset = 0) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?",
                [$userId, $limit, $offset]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'get_notifications_error',
                ['error' => $e->getMessage()]
            );
            return [];
        }
    }

    public function delete($notificationId, $userId) {
        try {
            return $this->db->delete(
                'notifications',
                'id = ? AND user_id = ?',
                [$notificationId, $userId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'delete_notification_error',
                ['error' => $e->getMessage()]
            );
            return false;
        }
    }

    public function deleteAllForUser($userId) {
        try {
            return $this->db->delete(
                'notifications',
                'user_id = ?',
                [$userId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'delete_all_notifications_error',
                ['error' => $e->getMessage()]
            );
            return false;
        }
    }

    public function cleanupOldNotifications($days = 30) {
        try {
            return $this->db->delete(
                'notifications',
                'created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
                [$days]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                null,
                'cleanup_notifications_error',
                ['error' => $e->getMessage()]
            );
            return false;
        }
    }

    private function sendEmailNotification($userId, $type, $message) {
        try {
            // Get user email
            $user = $this->db->fetchOne(
                "SELECT email, name FROM users WHERE id = ?",
                [$userId]
            );

            if (!$user) {
                return false;
            }

            // Prepare email content based on notification type
            switch ($type) {
                case 'due_date_reminder':
                    $emailContent = get_due_date_reminder_email($user['name'], $message);
                    $subject = "Book Due Date Reminder";
                    break;

                case 'overdue_notice':
                    $emailContent = get_overdue_notice_email($user['name'], $message);
                    $subject = "Overdue Book Notice";
                    break;

                case 'book_available':
                    $emailContent = get_book_available_email($user['name'], $message);
                    $subject = "Book Now Available";
                    break;

                case 'payment_confirmation':
                    $emailContent = get_payment_confirmation_email($user['name'], $message);
                    $subject = "Payment Confirmation";
                    break;

                default:
                    // Generic notification email
                    $emailContent = "
                        <h2>New Notification</h2>
                        <p>Dear {$user['name']},</p>
                        <p>$message</p>
                        <p>Best regards,<br>" . SITE_NAME . " Team</p>
                    ";
                    $subject = "New Notification from " . SITE_NAME;
            }

            // Send email
            return send_email($user['email'], $subject, $emailContent);
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'send_email_notification_error',
                ['error' => $e->getMessage()]
            );
            return false;
        }
    }

    private function __clone() {}
    private function __wakeup() {}
}
?>
