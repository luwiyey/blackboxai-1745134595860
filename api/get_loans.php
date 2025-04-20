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

$db = new Database();
$conn = $db->getConnection();
$loan = new Loan($conn);

$loans = $loan->getAllLoans();

echo json_encode($loans);
?>
