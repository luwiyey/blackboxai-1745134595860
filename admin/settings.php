<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Settings.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

$settings = new Settings();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrowing_limit = intval($_POST['borrowing_limit'] ?? 0);
    $fine_rate = floatval($_POST['fine_rate'] ?? 0);

    if ($borrowing_limit <= 0) {
        $errors[] = 'Borrowing limit must be a positive integer.';
    }
    if ($fine_rate < 0) {
        $errors[] = 'Fine rate cannot be negative.';
    }

    if (empty($errors)) {
        $settings->set('borrowing_limit', $borrowing_limit);
        $settings->set('fine_rate', $fine_rate);
        $success = 'Settings updated successfully.';
    }
}

$currentBorrowingLimit = $settings->get('borrowing_limit') ?? 5;
$currentFineRate = $settings->get('fine_rate') ?? 0.5;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>System Settings - PPU Library Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'ppu-green': '#4F7F3A',
                        'ppu-blue': '#1E4B87',
                        'ppu-light-blue': '#3A75D4',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-ppu-blue">PPU Library Admin</h1>
                </div>
                <div class="flex items-center">
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto mt-10 bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold mb-6">System Settings</h2>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="settings.php" class="space-y-6">
            <div>
                <label for="borrowing_limit" class="block text-sm font-medium text-gray-700">Borrowing Limit (number of books)</label>
                <input type="number" name="borrowing_limit" id="borrowing_limit" min="1" required
                       value="<?php echo htmlspecialchars($currentBorrowingLimit); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" />
            </div>
            <div>
                <label for="fine_rate" class="block text-sm font-medium text-gray-700">Fine Rate (per day, in dollars)</label>
                <input type="number" step="0.01" name="fine_rate" id="fine_rate" min="0" required
                       value="<?php echo htmlspecialchars($currentFineRate); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" />
            </div>
            <div>
                <button type="submit"
                        class="bg-ppu-blue text-white px-6 py-2 rounded-md hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</body>
</html>
