<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Book.php';
require_once '../includes/User.php';
require_once '../includes/Statistics.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();
$stats = new Statistics($conn);

// Get dashboard statistics
$totalBooks = $stats->getTotalBooks();
$totalUsers = $stats->getTotalUsers();
$activeLoans = $stats->getActiveLoans();
$overdueLoans = $stats->getOverdueLoans();
$todaysFines = $stats->getTodaysFines();
$monthlyRevenue = $stats->getMonthlyRevenue();
$popularBooks = $stats->getPopularBooks();
$recentActivities = $stats->getRecentActivities();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - PPU Library (Enhanced)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <div class="flex justify-between h-16 items-center">
                <h1 class="text-xl font-bold text-ppu-blue">PPU Library Admin - Enhanced Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo $_SESSION['user']['name']; ?></span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800 flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

        <div class="max-w-7xl mx-auto px-6 py-8">
            <h2 class="text-2xl font-semibold mb-6 text-ppu-blue">Dashboard Overview</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow flex items-center space-x-4">
                    <i class="fas fa-books fa-3x text-ppu-blue"></i>
                    <div>
                        <p class="text-gray-500">Total Books</p>
                        <p class="text-3xl font-semibold"><?php echo $totalBooks; ?></p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow flex items-center space-x-4">
                    <i class="fas fa-users fa-3x text-ppu-green"></i>
                    <div>
                        <p class="text-gray-500">Active Users</p>
                        <p class="text-3xl font-semibold"><?php echo $totalUsers; ?></p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow flex items-center space-x-4">
                    <i class="fas fa-hand-holding fa-3x text-yellow-500"></i>
                    <div>
                        <p class="text-gray-500">Active Loans</p>
                        <p class="text-3xl font-semibold"><?php echo $activeLoans; ?></p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow flex items-center space-x-4">
                    <i class="fas fa-exclamation-circle fa-3x text-red-500"></i>
                    <div>
                        <p class="text-gray-500">Overdue Books</p>
                        <p class="text-3xl font-semibold"><?php echo $overdueLoans; ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <a href="department_dashboard.php" class="inline-block bg-ppu-blue text-white px-6 py-3 rounded-md hover:bg-ppu-light-blue transition duration-300">
                    <i class="fas fa-chart-line mr-2"></i> Department Dashboard
                </a>
            </div>

        <div class="mt-10 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-ppu-blue">Monthly Revenue</h3>
                <canvas id="revenueChart" height="200"></canvas>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-ppu-blue">Popular Books</h3>
                <canvas id="popularBooksChart" height="200"></canvas>
            </div>
        </div>

        <div class="mt-10 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 text-ppu-blue">Recent Activities</h3>
            <ul class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                <?php foreach ($recentActivities as $activity): ?>
                <li class="py-3 flex items-center space-x-4">
                    <div>
                        <?php if ($activity['level'] === 'info'): ?>
                            <i class="fas fa-info-circle text-blue-500"></i>
                        <?php elseif ($activity['level'] === 'warning'): ?>
                            <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle text-red-500"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($activity['action']); ?></p>
                        <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($activity['details']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></p>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
        // Monthly Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($monthlyRevenue)); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_values($monthlyRevenue)); ?>,
                    borderColor: '#1E4B87',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Popular Books Chart
        const booksCtx = document.getElementById('popularBooksChart').getContext('2d');
        new Chart(booksCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($popularBooks, 'title')); ?>,
                datasets: [{
                    label: 'Times Borrowed',
                    data: <?php echo json_encode(array_column($popularBooks, 'borrow_count')); ?>,
                    backgroundColor: '#4F7F3A'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>
