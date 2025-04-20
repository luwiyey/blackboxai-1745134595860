<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Loan.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$loan = new Loan($conn);

try {
    // Get all unpaid loans for the user
    $sql = "SELECT l.*, b.title as book_title, b.isbn,
            (l.fine_amount - l.fine_paid) as remaining_fine
            FROM loans l
            JOIN books b ON l.book_id = b.id
            WHERE l.user_id = ? 
            AND l.fine_amount > l.fine_paid
            ORDER BY l.due_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_GET['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $loans = [];
    while ($row = $result->fetch_assoc()) {
        $loans[] = [
            'id' => $row['id'],
            'book_title' => $row['book_title'],
            'isbn' => $row['isbn'],
            'fine_amount' => $row['remaining_fine'],
            'due_date' => date('M j, Y', strtotime($row['due_date'])),
            'return_date' => $row['return_date'] ? date('M j, Y', strtotime($row['return_date'])) : null,
            'status' => $row['status'],
            'display_text' => sprintf(
                "%s (ISBN: %s) - Fine: ₱%s",
                $row['book_title'],
                $row['isbn'],
                number_format($row['remaining_fine'], 2)
            )
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($loans);
} catch (Exception $e) {
    error_log("Error in get_user_loans.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch loans']);
    exit;
}
