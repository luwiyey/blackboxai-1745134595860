<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../vendor/autoload.php'; // For PhpSpreadsheet

// Check if user is logged in and is admin or librarian
Auth::requireRole(['admin', 'librarian']);

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];

    // Validate file type
    $allowedExtensions = ['xls', 'xlsx'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        $error = 'Invalid file type. Please upload an Excel file (.xls or .xlsx).';
    } else {
        $uploadPath = UPLOAD_PATH . 'temp/';
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $filePath = $uploadPath . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            try {
                $spreadsheet = IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                $db = new Database();
                $conn = $db->getConnection();

                $insertedCount = 0;
                $updatedCount = 0;

                // Assuming first row is header
                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    // Map columns: adjust indexes as per Excel columns
                    $isbn = trim($row[0]);
                    $title = trim($row[1]);
                    $author = trim($row[2]);
                    $publisher = trim($row[3]);
                    $publication_year = (int)$row[4];
                    $category = trim($row[5]);
                    $description = trim($row[6]);
                    $total_copies = (int)$row[7];
                    $price = (float)$row[8];

                    if (empty($isbn) || empty($title)) {
                        continue; // Skip invalid rows
                    }

                    // Check if book exists by ISBN
                    $stmt = $conn->prepare("SELECT id FROM books WHERE isbn = ?");
                    $stmt->bind_param('s', $isbn);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        // Update existing book
                        $book = $result->fetch_assoc();
                        $updateStmt = $conn->prepare("UPDATE books SET title=?, author=?, publisher=?, publication_year=?, category=?, description=?, total_copies=?, available_copies=?, price=? WHERE id=?");
                        $available_copies = $total_copies; // Reset available copies to total copies on update
                        $updateStmt->bind_param('sssssssdis', $title, $author, $publisher, $publication_year, $category, $description, $total_copies, $available_copies, $price, $book['id']);
                        $updateStmt->execute();
                        $updatedCount++;
                    } else {
                        // Insert new book
                        $insertStmt = $conn->prepare("INSERT INTO books (isbn, title, author, publisher, publication_year, category, description, total_copies, available_copies, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $available_copies = $total_copies;
                        $insertStmt->bind_param('sssssssids', $isbn, $title, $author, $publisher, $publication_year, $category, $description, $total_copies, $available_copies, $price);
                        $insertStmt->execute();
                        $insertedCount++;
                    }
                }

                unlink($filePath); // Remove temp file

                $message = "Import completed. Inserted: $insertedCount, Updated: $updatedCount.";
            } catch (Exception $e) {
                $error = 'Error processing Excel file: ' . $e->getMessage();
            }
        } else {
            $error = 'Failed to upload file.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Import Books - Pan Pacific University Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <h1 class="text-3xl font-bold mb-6">Import Books from Excel</h1>
        <?php if ($message): ?>
            <div class="bg-green-100 text-green-800 p-4 rounded mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-800 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="file" name="excel_file" accept=".xls,.xlsx" required class="block w-full border border-gray-300 rounded px-3 py-2" />
            <button type="submit" class="bg-ppu-green text-white px-6 py-3 rounded hover:bg-green-700 transition">Upload</button>
        </form>
        <a href="admin/dashboard.php" class="inline-block mt-6 text-ppu-blue hover:underline">Back to Dashboard</a>
    </div>
</body>
</html>
</create_file>
