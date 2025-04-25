<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');
$author = trim($data['author'] ?? '');
$reason = trim($data['reason'] ?? '');

if (!$title) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "INSERT INTO book_suggestions (user_id, title, author, reason, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $_SESSION['user']['id'], $title, $author, $reason);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Suggestion submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit suggestion']);
    }
} catch (Exception $e) {
    error_log("Error submitting book suggestion: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
