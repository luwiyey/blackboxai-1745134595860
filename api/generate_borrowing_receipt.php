<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/QRCodeGenerator.php';

require_once '../vendor/autoload.php'; // Assuming composer autoload for TCPDF or similar

use TCPDF;

header('Content-Type: application/pdf');

session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$loanId = $_GET['loan_id'] ?? null;
if (!$loanId) {
    http_response_code(400);
    exit('Missing loan_id parameter');
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Fetch loan and book details
    $sql = "SELECT l.id as loan_id, l.borrow_date, l.due_date, u.name as borrower_name, 
                   b.title, b.author, b.isbn
            FROM loans l
            JOIN users u ON l.user_id = u.id
            JOIN books b ON l.book_id = b.id
            WHERE l.id = ? AND l.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $loanId, $_SESSION['user']['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan = $result->fetch_assoc();

    if (!$loan) {
        http_response_code(404);
        exit('Loan not found');
    }

    // Generate QR code for loan ID URL
    $qrGenerator = new QRCodeGenerator();
    $qrData = "https://yourlibrarydomain.com/loan_details.php?loan_id=" . $loan['loan_id'];
    $qrImage = $qrGenerator->generateBase64($qrData);

    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator('PPU Library');
    $pdf->SetAuthor('PPU Library');
    $pdf->SetTitle('Borrowing Receipt');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    $html = "
    <h1>Borrowing Receipt</h1>
    <p><strong>Borrower:</strong> " . htmlspecialchars($loan['borrower_name']) . "</p>
    <p><strong>Book Title:</strong> " . htmlspecialchars($loan['title']) . "</p>
    <p><strong>Author:</strong> " . htmlspecialchars($loan['author']) . "</p>
    <p><strong>ISBN:</strong> " . htmlspecialchars($loan['isbn']) . "</p>
    <p><strong>Borrow Date:</strong> " . htmlspecialchars($loan['borrow_date']) . "</p>
    <p><strong>Due Date:</strong> " . htmlspecialchars($loan['due_date']) . "</p>
    <p><strong>Loan ID:</strong> " . htmlspecialchars($loan['loan_id']) . "</p>
    <p><strong>QR Code:</strong></p>
    <img src='" . $qrImage . "' alt='QR Code' width='100' height='100' />
    ";

    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Output('borrowing_receipt_' . $loan['loan_id'] . '.pdf', 'I');

} catch (Exception $e) {
    error_log("Error generating borrowing receipt: " . $e->getMessage());
    http_response_code(500);
    exit('Server error');
}
?>
