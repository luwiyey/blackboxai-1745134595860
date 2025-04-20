<?php
require_once '../includes/Auth.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['token'])) {
        throw new Exception('Invalid input');
    }

    $token = $input['token'];

    $auth = Auth::getInstance();
    $userId = $auth->validatePasswordResetToken($token);

    if (!$userId) {
        throw new Exception('Invalid or expired token');
    }

    echo json_encode(['success' => true, 'userId' => $userId]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
