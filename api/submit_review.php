<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Review.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['book_id'], $data['rating'], $data['review_text'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$userId = $_SESSION['user']['id'];
$bookId = intval($data['book_id']);
$rating = intval($data['rating']);
$reviewText = trim($data['review_text']);

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$review = new Review($conn);

$success = $review->submitReview($userId, $bookId, $rating, $reviewText);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}
?>
