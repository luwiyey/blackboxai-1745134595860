<?php
require_once '../includes/Auth.php';
require_once '../includes/Validator.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid input');
    }

    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $csrfToken = $input['csrf_token'] ?? '';

    $auth = Auth::getInstance();

    // Validate CSRF token
    $auth->validateToken($csrfToken);

    // Validate input
    $validator = Validator::getInstance();
    $rules = [
        'email' => 'required|email',
        'password' => 'required'
    ];
    if (!$validator->validate(['email' => $email, 'password' => $password], $rules)) {
        throw new Exception($validator->getFirstError());
    }

    // Attempt login
    $user = $auth->login($email, $password);

    // Generate JWT token
    $jwt = $auth->generateJWT($user['id'], $user['role']);

    echo json_encode(['success' => true, 'token' => $jwt, 'user' => $user]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
