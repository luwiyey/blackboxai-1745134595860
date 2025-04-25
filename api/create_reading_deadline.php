<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = $_POST;
$class = trim($data['class'] ?? '');
$deadline = trim($data['deadline'] ?? '');

if (!$class || !$deadline) {
    echo json_encode(['success' => false, 'message' => 'Class and deadline date are required']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "INSERT INTO reading_deadlines (faculty_id, class, deadline_date, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $_SESSION['user']['id'], $class, $deadline);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Reading deadline created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create reading deadline']);
    }
} catch (Exception $e) {
    error_log("Error creating reading deadline: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
