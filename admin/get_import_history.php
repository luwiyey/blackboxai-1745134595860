<?php
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

// Initialize auth and ensure user is admin
$auth = new Auth();
if (!$auth->isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $db = new Database();
    
    // Get the last 10 imports
    $sql = "SELECT 
                il.id,
                il.file_name,
                il.import_type,
                il.total_rows,
                il.successful_rows,
                il.failed_rows,
                il.status,
                il.error_log,
                il.started_at,
                il.completed_at,
                u.name as imported_by
            FROM excel_import_logs il
            LEFT JOIN users u ON il.imported_by = u.id
            ORDER BY il.started_at DESC
            LIMIT 10";
    
    $imports = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for display
    $formatted_imports = array_map(function($import) {
        return [
            'id' => $import['id'],
            'file_name' => htmlspecialchars($import['file_name']),
            'import_type' => ucfirst(str_replace('_', ' ', $import['import_type'])),
            'successful_rows' => (int)$import['successful_rows'],
            'failed_rows' => (int)$import['failed_rows'],
            'status' => $import['status'],
            'error_log' => $import['error_log'] ? json_decode($import['error_log'], true) : [],
            'started_at' => $import['started_at'],
            'completed_at' => $import['completed_at'],
            'duration' => $import['completed_at'] 
                ? round((strtotime($import['completed_at']) - strtotime($import['started_at'])) / 60, 2) 
                : null,
            'imported_by' => htmlspecialchars($import['imported_by'])
        ];
    }, $imports);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($formatted_imports);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to fetch import history: ' . $e->getMessage()]);
}
