<?php
require_once '../includes/Auth.php';
require_once '../includes/ExcelImporter.php';
require_once '../includes/Logger.php';

// Initialize auth and ensure user is admin
$auth = new Auth();
if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'details' => []
];

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['import_type']) || empty($_POST['import_type'])) {
        throw new Exception('Import type is required');
    }

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }

    // Validate file type
    $allowed_types = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $_FILES['excel_file']['tmp_name']);
    finfo_close($file_info);

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Invalid file type. Please upload an Excel file (.xlsx, .xls) or CSV file');
    }

    // Initialize ExcelImporter
    $importer = new ExcelImporter($auth->getUserId());
    
    // Process the import
    $result = $importer->import($_FILES['excel_file'], $_POST['import_type']);

    // Log the import
    $logger = new Logger();
    $logger->info('Data import completed', [
        'import_type' => $_POST['import_type'],
        'file_name' => $_FILES['excel_file']['name'],
        'result' => $result
    ]);

    // Set success response
    $response['status'] = 'success';
    $response['message'] = 'Import completed successfully';
    $response['details'] = [
        'successful' => $result['successful'],
        'failed' => $result['failed'],
        'errors' => $result['errors']
    ];

} catch (Exception $e) {
    // Log error
    $logger = new Logger();
    $logger->error('Import failed: ' . $e->getMessage());

    // Set error response
    $response['message'] = 'Import failed: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
