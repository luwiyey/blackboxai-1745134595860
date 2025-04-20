<?php
require_once '../includes/Auth.php';
require_once '../includes/GestureLogin.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid input');
    }

    $userId = $input['userId'] ?? null;
    $gestureToken = $input['gestureToken'] ?? null;

    if (!$userId || !$gestureToken) {
        throw new Exception('Missing required fields');
    }

    $gestureLogin = new GestureLogin();
    $isValid = $gestureLogin->verifyGestureToken($userId, $gestureToken);

    if (!$isValid) {
        throw new Exception('Gesture verification failed');
    }

    $auth = Auth::getInstance();
    $user = $auth->getCurrentUser();

    if (!$user || $user['id'] != $userId) {
        throw new Exception('User session mismatch');
    }

    // Generate JWT token for authenticated session
    $jwt = $auth->generateJWT($userId, $user['role']);

    echo json_encode(['success' => true, 'token' => $jwt, 'user' => $user]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
