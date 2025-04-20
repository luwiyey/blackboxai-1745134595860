<?php
    public function generateJWT($userId, $role) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'userId' => $userId,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + 3600 // 1 hour expiration
        ]);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $secret = 'your-secret-key'; // Use a secure key from config
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function validateJWT($jwt) {
        $secret = 'your-secret-key'; // Use a secure key from config
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) !== 3) {
            return false;
        }
        list($header, $payload, $signature) = $tokenParts;
        $signatureCheck = hash_hmac('sha256', $header . "." . $payload, $secret, true);
        $base64UrlSignatureCheck = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signatureCheck));
        if ($base64UrlSignatureCheck !== $signature) {
            return false;
        }
        $payloadDecoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        if ($payloadDecoded['exp'] < time()) {
            return false;
        }
        return $payloadDecoded;
    }

    // Password reset token generation and validation
    public function generatePasswordResetToken($userId) {
        $token = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare("INSERT INTO password_resets (user_id, token, created_at) VALUES (:user_id, :token, NOW())");
        $stmt->execute(['user_id' => $userId, 'token' => $token]);
        return $token;
    }

    public function validatePasswordResetToken($token) {
        $stmt = $this->db->prepare("SELECT user_id, created_at FROM password_resets WHERE token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $createdAt = strtotime($row['created_at']);
        if (time() - $createdAt > 3600) { // 1 hour expiry
            return false;
        }
        return $row['user_id'];
    }

    public function logout() {
        try {
            if (isset($_SESSION['user'])) {
                $userId = $_SESSION['user']['id'];
                
                // Clear session
                session_unset();
                session_destroy();

                $this->logger->logActivity(
                    $userId,
                    'logout',
                    "User logged out"
                );
            }
            return true;
        } catch (Exception $e) {
            $this->logger->logError(
                isset($_SESSION['user']) ? $_SESSION['user']['id'] : null,
                'logout_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user']);
    }

    public function getCurrentUser() {
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    public function requireRole($roles) {
        $this->requireLogin();
        
        $roles = (array)$roles;
        if (!in_array($_SESSION['user']['role'], $roles)) {
            header('Location: /error.php?code=403');
            exit;
        }
    }

    public function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
        }
        return true;
    }

    public function refreshSession() {
        if ($this->isLoggedIn()) {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE id = ?",
                [$_SESSION['user']['id']]
            );

            if ($user) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'student_id' => $user['student_id']
                ];
            }
        }
    }

    public function checkPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $rolePermissions = $this->getRolePermissions($_SESSION['user']['role']);
        return in_array($permission, $rolePermissions);
    }

    private function getRolePermissions($role) {
        $permissions = [
            'admin' => [
                'manage_users',
                'manage_books',
                'manage_loans',
                'manage_fines',
                'view_reports',
                'manage_settings'
            ],
            'librarian' => [
                'manage_books',
                'manage_loans',
                'manage_fines',
                'view_reports'
            ],
            'faculty' => [
                'borrow_books',
                'view_history',
                'create_reading_lists',
                'extended_loan_period'
            ],
            'student' => [
                'borrow_books',
                'view_history',
                'create_reading_lists'
            ]
        ];

        return $permissions[$role] ?? [];
    }

    public function validatePassword($password) {
        $validator = Validator::getInstance();
        return $validator->validate(
            ['password' => $password],
            ['password' => 'required|password']
        );
    }

    public function isPasswordExpired($userId) {
        $user = $this->db->fetchOne(
            "SELECT password_changed_at FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user['password_changed_at']) {
            return true;
        }

        $passwordAge = time() - strtotime($user['password_changed_at']);
        $maxAge = 90 * 24 * 60 * 60; // 90 days

        return $passwordAge > $maxAge;
    }

    public function enforcePasswordPolicy() {
        if ($this->isLoggedIn() && $this->isPasswordExpired($_SESSION['user']['id'])) {
            header('Location: /change-password.php?expired=1');
            exit;
        }
    }

    public function getLoginHistory($userId, $limit = 10) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM activity_logs 
                WHERE user_id = ? AND action = 'login' 
                ORDER BY created_at DESC 
                LIMIT ?",
                [$userId, $limit]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'get_login_history_error',
                ['error' => $e->getMessage()]
            );
            return [];
        }
    }

    private function __clone() {}
    private function __wakeup() {}
}
?>
