<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Logger.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$logger = new Logger($conn);

$logs = $logger->getAuditLogs();

echo json_encode(['success' => true, 'logs' => $logs]);
?>
