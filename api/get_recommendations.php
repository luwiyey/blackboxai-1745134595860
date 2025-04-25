<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Book.php';
require_once '../includes/User.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user']['id'];

$db = new Database();
$conn = $db->getConnection();
$book = new Book($conn);
$user = new User($conn);

$userId = $_SESSION['user']['id'];

// Get user course
$userSql = "SELECT course FROM users WHERE id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userRow = $userResult->fetch_assoc();
$userCourse = $userRow['course'] ?? null;

if ($userCourse) {
    // Get popular books in user's course category or related tags
    $sql = "SELECT b.*, COUNT(l.id) as borrow_count
            FROM books b
            LEFT JOIN loans l ON b.id = l.book_id
            WHERE b.category = ? OR JSON_CONTAINS(b.tags, JSON_QUOTE(?))
            GROUP BY b.id
            ORDER BY borrow_count DESC
            LIMIT 6";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $userCourse, $userCourse);
    $stmt->execute();
    $result = $stmt->get_result();
    $recommendations = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Fallback to top popular books
    $recommendations = $book->getPopularBooks(6);
}

echo json_encode($recommendations);
?>
