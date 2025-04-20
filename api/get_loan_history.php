<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Loan.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user']['id'];

$db = new Database();
$conn = $db->getConnection();
$loan = new Loan($conn);

$loanHistory = $loan->getUserLoans($userId);

echo json_encode($loanHistory);
?>
