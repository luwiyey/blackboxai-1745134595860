<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Book.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['q']);

$db = new Database();
$conn = $db->getConnection();
$book = new Book($conn);

$results = $book->searchBooks($query);

echo json_encode($results);
?>
