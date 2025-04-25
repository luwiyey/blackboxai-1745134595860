<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Statistics.php';
require_once '../includes/Recommendation.php';

Auth::requireRole('student');

$db = new Database();
$conn = $db->getConnection();
$stats = new Statistics($conn);
$recommendation = new Recommendation($conn);

$totalBooks = $stats->getTotalBooks();
$totalUsers = $stats->getTotalUsers();
$activeLoans = $stats->getActiveLoanCount();
$overdueLoans = $stats->getOverdueLoanCount();

$recommendations = $recommendation->getRecommendationsForUser($_SESSION['user']['id'], 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Dashboard - PPU Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/style.css" />
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16 items-center">
                <h1 class="text-xl font-bold text-ppu-blue">PPU Library - Student Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <a href="../logout.php" class="text-red-600 hover:text-red-800 flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6">
        <h2 class="text-2xl font-semibold mb-6 text-ppu-blue">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-ppu-blue">Your Borrowing Stats</h3>
                <ul class="space-y-2 text-gray-700">
                    <li>Total Books: <?php echo $totalBooks; ?></li>
                    <li>Active Loans: <?php echo $activeLoans; ?></li>
                    <li>Overdue Loans: <?php echo $overdueLoans; ?></li>
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
                <h3 class="text-lg font-semibold mb-4 text-ppu-blue">Recommended Books for You</h3>
                <?php if (empty($recommendations)): ?>
                    <p class="text-gray-600">No recommendations available at this time.</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($recommendations as $book): ?>
                            <li class="border border-gray-300 rounded p-4 hover:shadow-lg transition cursor-pointer">
                                <p class="font-semibold text-lg"><?php echo htmlspecialchars($book['title']); ?></p>
                                <p class="text-gray-600">Author: <?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="text-gray-600">Category: <?php echo htmlspecialchars($book['category']); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
