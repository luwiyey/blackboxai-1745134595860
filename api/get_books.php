<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Book.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$book = new Book($conn);

$books = $book->getAllBooks();

echo json_encode($books);
?>
