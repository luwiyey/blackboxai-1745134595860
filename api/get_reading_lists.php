<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/ReadingList.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user']['id'];

$db = new Database();
$conn = $db->getConnection();
$readingList = new ReadingList($conn);

$lists = $readingList->getReadingListsByUser($userId);

echo json_encode($lists);
?>
