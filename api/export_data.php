<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Export.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing export type']);
    exit;
}

$type = $_GET['type'];

$db = new Database();
$conn = $db->getConnection();
$export = new Export($conn);

switch ($type) {
    case 'books':
        $data = $export->exportBooks();
        break;
    case 'loans':
        $data = $export->exportLoans();
        break;
    case 'users':
        $data = $export->exportUsers();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid export type']);
        exit;
}

echo json_encode(['success' => true, 'data' => $data]);
?>
