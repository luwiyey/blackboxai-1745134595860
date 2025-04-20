<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Payment.php';

// Require user to be logged in as student
Auth::requireRole('student');

$db = new Database();
$conn = $db->getConnection();
$payment = new Payment($conn);

$user = $_SESSION['user'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $loan_id = $_POST['loan_id'] ?? null;
    $amount = $_POST['amount'] ?? null;

    if (!$loan_id || !$amount) {
        $errors[] = "Loan and amount are required.";
    }

    // Validate file upload
    if (!isset($_FILES['transaction_proof']) || $_FILES['transaction_proof']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Transaction proof image is required.";
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($_FILES['transaction_proof']['type'], $allowed_types)) {
            $errors[] = "Only JPG and PNG images are allowed.";
        }
    }

    if (empty($errors)) {
        // Handle file upload
        $upload_dir = '../uploads/payment_proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = uniqid('proof_') . '_' . basename($_FILES['transaction_proof']['name']);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['transaction_proof']['tmp_name'], $target_file)) {
            // Record payment
            $data = [
                'user_id' => $user['id'],
                'loan_id' => $loan_id,
                'amount' => $amount,
                'method' => 'gcash',
                'student_id' => $user['student_id']
            ];
            $reference = $payment->recordPayment($data, $target_file);
            if ($reference) {
                $success = "Payment recorded successfully. Reference ID: " . htmlspecialchars($reference);
            } else {
                $errors[] = "Failed to record payment. Please try again.";
            }
        } else {
            $errors[] = "Failed to upload transaction proof image.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>GCash Payment - PPU Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="max-w-md w-full bg-white p-6 rounded-lg shadow-lg">
        <h1 class="text-2xl font-semibold mb-4 text-center">GCash Payment</h1>
        <p class="mb-4 text-gray-700">Please scan the QR code below to pay your overdue fines or lost book penalties via GCash.</p>
        <div class="flex justify-center mb-6">
            <img src="<?php echo defined('GCASH_QR_URL') ? GCASH_QR_URL : 'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg'; ?>" alt="GCash QR Code" class="w-48 h-48 object-contain rounded-lg shadow-md" />
        </div>
        <p class="mb-6 text-gray-600 text-sm">After payment, please upload a screenshot of your transaction as proof.</p>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 bg-green-100 text-green-700 p-3 rounded">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label for="loan_id" class="block text-gray-700 font-medium mb-1">Select Loan</label>
                <select name="loan_id" id="loan_id" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                    <option value="">-- Select Loan --</option>
                    <!-- TODO: Populate with user's loans with fines -->
                </select>
            </div>
            <div>
                <label for="amount" class="block text-gray-700 font-medium mb-1">Amount (₱)</label>
                <input type="number" name="amount" id="amount" step="0.01" min="0" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ppu-blue" />
            </div>
            <div>
                <label for="transaction_proof" class="block text-gray-700 font-medium mb-1">Upload Transaction Proof (JPG, PNG)</label>
                <input type="file" name="transaction_proof" id="transaction_proof" accept=".jpg,.jpeg,.png" required class="w-full" />
            </div>
            <button type="submit" class="w-full bg-ppu-blue text-white py-2 rounded hover:bg-ppu-light-blue transition-colors">Submit Payment</button>
        </form>
    </div>
</body>
</html>
