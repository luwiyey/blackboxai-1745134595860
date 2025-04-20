<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/ReadingList.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name'], $data['description'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$userId = $_SESSION['user']['id'];
$name = trim($data['name']);
$description = trim($data['description']);

$db = new Database();
$conn = $db->getConnection();
$readingList = new ReadingList($conn);

$success = $readingList->createReadingList($userId, $name, $description);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Reading list created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create reading list']);
}
?>
