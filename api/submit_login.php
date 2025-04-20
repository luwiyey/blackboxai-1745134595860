<?php
require_once '../includes/Auth.php';
require_once '../includes/Captcha.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid input');
    }

    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $captchaResponse = $input['captcha'] ?? '';

    $captcha = new Captcha();
    if (!$captcha->verifyResponse($captchaResponse, $_SERVER['REMOTE_ADDR'])) {
        throw new Exception('CAPTCHA verification failed');
    }

    $auth = Auth::getInstance();
    $user = $auth->login($email, $password);

    // Generate JWT token
    $jwt = $auth->generateJWT($user['id'], $user['role']);

    echo json_encode(['success' => true, 'token' => $jwt, 'user' => $user]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
