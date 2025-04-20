<?php
require_once '../includes/Auth.php';
require_once '../includes/Validator.php';
require_once '../includes/Notification.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid input');
    }

    $email = $input['email'] ?? '';

    $validator = Validator::getInstance();
    if (!$validator->validate(['email' => $email], ['email' => 'required|email'])) {
        throw new Exception($validator->getFirstError());
    }

    $auth = Auth::getInstance();
    $db = new Database();
    $conn = $db->getConnection();

    $user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    if (!$user) {
        throw new Exception('Email not found');
    }

    $token = $auth->generatePasswordResetToken($user['id']);

    // Send reset email
    $resetLink = "https://yourdomain.com/reset-password.php?token=$token";
    $subject = "Password Reset Request";
    $body = "Hello " . htmlspecialchars($user['name']) . ",\n\n";
    $body .= "You requested a password reset. Click the link below to reset your password:\n";
    $body .= $resetLink . "\n\n";
    $body .= "If you did not request this, please ignore this email.\n\n";
    $body .= "Regards,\nPPU Library";

    Notification::sendEmail($user['email'], $subject, $body);

    echo json_encode(['success' => true, 'message' => 'Password reset email sent']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
