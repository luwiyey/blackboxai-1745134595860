<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user']['id'];

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "SELECT id, book_id, borrow_date, due_date FROM loans WHERE user_id = ? AND status = 'borrowed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => $row['id'],
            'title' => "Book Loan ID: " . $row['id'],
            'start' => $row['borrow_date'],
            'end' => $row['due_date'],
            'allDay' => false,
            'color' => '#1E4B87'
        ];
    }

    echo json_encode($events);
} catch (Exception $e) {
    error_log("Error fetching borrowing events: " . $e->getMessage());
    echo json_encode([]);
}
?>
