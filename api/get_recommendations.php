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

// For demonstration, return top popular books as recommendations
$recommendations = $book->getPopularBooks(6);

echo json_encode($recommendations);
?>
