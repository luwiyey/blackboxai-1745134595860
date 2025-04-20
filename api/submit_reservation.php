<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Reservation.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['book_id'], $data['reservation_date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$userId = $_SESSION['user']['id'];
$bookId = intval($data['book_id']);
$reservationDate = trim($data['reservation_date']);

$db = new Database();
$conn = $db->getConnection();
$reservation = new Reservation($conn);

$success = $reservation->createReservation($userId, $bookId, $reservationDate);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Reservation created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create reservation']);
}
?>
