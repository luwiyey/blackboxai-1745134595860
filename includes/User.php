<?php
class User {
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

    public function create($data) {
        try {
            $validator = Validator::getInstance();
            $rules = [
                'name' => 'required|min:2',
                'email' => 'required|email',
                'password' => 'required|password',
                'student_id' => 'required|student_id',
                'role' => 'required|in:student,faculty,admin'
            ];

            if (!$validator->validate($data, $rules)) {
                throw new Exception($validator->getFirstError());
            }

            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                throw new Exception("Email already registered");
            }

            // Check if student ID already exists
            if ($this->studentIdExists($data['student_id'])) {
                throw new Exception("Student ID already registered");
            }

            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'student_id' => $data['student_id'],
                'role' => $data['role'],
                'status' => 'pending',
                'verification_token' => bin2hex(random_bytes(32)),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $userId = $this->db->insert('users', $userData);

            // Send verification email
            $emailContent = get_verification_email(
                $userData['name'],
                $userData['verification_token']
            );
            send_email($userData['email'], "Verify Your Email", $emailContent);

            $this->logger->logActivity(
                $userId,
                'user_registered',
                "New user registration: {$userData['email']}"
            );

            return $userId;
        } catch (Exception $e) {
            $this->logger->logError(
                null,
                'user_registration_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function update($userId, $data) {
        try {
            $validator = Validator::getInstance();
            $rules = [
                'name' => 'min:2',
                'email' => 'email',
                'student_id' => 'student_id'
            ];

            if (!$validator->validate($data, $rules)) {
                throw new Exception($validator->getFirstError());
            }

            // Check if email is being changed and if new email exists
            if (isset($data['email'])) {
                $existingUser = $this->getByEmail($data['email']);
                if ($existingUser && $existingUser['id'] != $userId) {
                    throw new Exception("Email already registered");
                }
            }

            // Check if student ID is being changed and if new ID exists
            if (isset($data['student_id'])) {
                $existingUser = $this->getByStudentId($data['student_id']);
                if ($existingUser && $existingUser['id'] != $userId) {
                    throw new Exception("Student ID already registered");
                }
            }

            $data['updated_at'] = date('Y-m-d H:i:s');

            $result = $this->db->update('users', $data, 'id = ?', [$userId]);

            $this->logger->logActivity(
                $userId,
                'profile_updated',
                "Profile updated"
            );

            return $result;
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'profile_update_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->get($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Validate new password
            $validator = Validator::getInstance();
            if (!$validator->validate(['password' => $newPassword], ['password' => 'required|password'])) {
                throw new Exception($validator->getFirstError());
            }

            $data = [
                'password' => password_hash($newPassword, PASSWORD_BCRYPT),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->db->update('users', $data, 'id = ?', [$userId]);

            $this->logger->logActivity(
                $userId,
                'password_changed',
                "Password updated"
            );

            return $result;
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'password_update_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function verifyEmail($token) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE verification_token = ?",
                [$token]
            );

            if (!$user) {
                throw new Exception("Invalid verification token");
            }

            if ($user['email_verified']) {
                throw new Exception("Email already verified");
            }

            $data = [
                'email_verified' => 1,
                'verification_token' => null,
                'status' => 'active',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->db->update('users', $data, 'id = ?', [$user['id']]);

            $this->logger->logActivity(
                $user['id'],
                'email_verified',
                "Email verified"
            );

            return $result;
        } catch (Exception $e) {
            $this->logger->logError(
                null,
                'email_verification_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function requestPasswordReset($email) {
        try {
            $user = $this->getByEmail($email);
            if (!$user) {
                throw new Exception("Email not found");
            }

            $token = bin2hex(random_bytes(32));
            $data = [
                'reset_token' => $token,
                'reset_token_expiry' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->update('users', $data, 'id = ?', [$user['id']]);

            // Send reset email
            $emailContent = get_password_reset_email($user['name'], $token);
            send_email($user['email'], "Reset Your Password", $emailContent);

            $this->logger->logActivity(
                $user['id'],
                'password_reset_requested',
                "Password reset requested"
            );

            return true;
        } catch (Exception $e) {
            $this->logger->logError(
                null,
                'password_reset_request_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function resetPassword($token, $newPassword) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM users 
                WHERE reset_token = ? 
                AND reset_token_expiry > NOW() 
                AND reset_token IS NOT NULL",
                [$token]
            );

            if (!$user) {
                throw new Exception("Invalid or expired reset token");
            }

            // Validate new password
            $validator = Validator::getInstance();
            if (!$validator->validate(['password' => $newPassword], ['password' => 'required|password'])) {
                throw new Exception($validator->getFirstError());
            }

            $data = [
                'password' => password_hash($newPassword, PASSWORD_BCRYPT),
                'reset_token' => null,
                'reset_token_expiry' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->db->update('users', $data, 'id = ?', [$user['id']]);

            $this->logger->logActivity(
                $user['id'],
                'password_reset_completed',
                "Password reset completed"
            );

            return $result;
        } catch (Exception $e) {
            $this->logger->logError(
                null,
                'password_reset_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function get($userId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM users WHERE id = ?",
                [$userId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                null,
                'get_user_error',
                ['error' => $e->getMessage()]
            );
            return null;
        }
    }

    public function getByEmail($email) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM users WHERE email = ?",
                [$email]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                null,
                'get_user_by_email_error',
                ['error' => $e->getMessage()]
            );
            return null;
        }
    }

    public function getByStudentId($studentId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM users WHERE student_id = ?",
                [$studentId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                null,
                'get_user_by_student_id_error',
                ['error' => $e->getMessage()]
            );
            return null;
        }
    }

    private function emailExists($email) {
        return $this->db->exists('users', 'email = ?', [$email]);
    }

    private function studentIdExists($studentId) {
        return $this->db->exists('users', 'student_id = ?', [$studentId]);
    }

    private function __clone() {}
    private function __wakeup() {}
}
?>
