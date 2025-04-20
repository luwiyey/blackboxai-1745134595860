<?php
require_once '../includes/Auth.php';
require_once '../includes/Validator.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid input');
    }

    $userId = $input['userId'] ?? null;
    $token = $input['token'] ?? null;
    $newPassword = $input['newPassword'] ?? null;

    if (!$userId || !$token || !$newPassword) {
        throw new Exception('Missing required fields');
    }

    $auth = Auth::getInstance();

    // Validate reset token
    $validUserId = $auth->validatePasswordResetToken($token);
    if (!$validUserId || $validUserId != $userId) {
        throw new Exception('Invalid or expired token');
    }

    // Validate new password
    $validator = Validator::getInstance();
    if (!$validator->validate(['password' => $newPassword], ['password' => 'required|password'])) {
        throw new Exception($validator->getFirstError());
    }

    // Hash new password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password in database
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("UPDATE users SET password = :password, password_changed_at = NOW() WHERE id = :id");
    $stmt->execute(['password' => $passwordHash, 'id' => $userId]);

    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
