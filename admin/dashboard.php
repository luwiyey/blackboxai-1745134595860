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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-ppu-blue">PPU Library Admin</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-4">Welcome, <?php echo $_SESSION['user']['name']; ?></span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="flex flex-col h-full">
                <div class="flex-1 overflow-y-auto">
                    <nav class="px-2 py-4">
                        <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-700 bg-gray-100 rounded-lg">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                        <a href="books.php" class="flex items-center px-4 py-3 mt-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-book mr-3"></i>
                            Books Management
                        </a>
                        <a href="users.php" class="flex items-center px-4 py-3 mt-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-users mr-3"></i>
                            User Management
                        </a>
                        <a href="loans.php" class="flex items-center px-4 py-3 mt-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-hand-holding mr-3"></i>
                            Loan Management
                        </a>
                        <a href="fines.php" class="flex items-center px-4 py-3 mt-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-money-bill-wave mr-3"></i>
                            Fines & Payments
                        </a>
                        <a href="reports.php" class="flex items-center px-4 py-3 mt-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chart-bar mr-3"></i>
                            Reports
                        </a>
                        <a href="settings.php" class="flex items-center px-4 py-3 mt-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-cog mr-3"></i>
                            Settings
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                <h3 class="text-gray-700 text-2xl font-medium">Dashboard Overview</h3>
                
                <!-- Stats Grid -->
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Books -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-books fa-2x text-ppu-blue"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Books</dt>
                                        <dd class="text-2xl font-semibold text-gray-900"><?php echo $totalBooks; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Users -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users fa-2x text-ppu-green"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Active Users</dt>
                                        <dd class="text-2xl font-semibold text-gray-900"><?php echo $totalUsers; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Loans -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-hand-holding fa-2x text-yellow-500"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Active Loans</dt>
                                        <dd class="text-2xl font-semibold text-gray-900"><?php echo $activeLoans; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overdue Books -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle fa-2x text-red-500"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Overdue Books</dt>
                                        <dd class="text-2xl font-semibold text-gray-900"><?php echo $overdueLoans; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Monthly Revenue Chart -->
                    <div class="bg-white shadow rounded-lg p-4">
                        <h4 class="text-gray-700 text-lg font-medium mb-4">Monthly Revenue</h4>
                        <canvas id="revenueChart" class="w-full" height="200"></canvas>
                    </div>

                    <!-- Popular Books Chart -->
                    <div class="bg-white shadow rounded-lg p-4">
                        <h4 class="text-gray-700 text-lg font-medium mb-4">Popular Books</h4>
                        <canvas id="popularBooksChart" class="w-full" height="200"></canvas>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="mt-8">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:px-6">
                            <h4 class="text-lg font-medium text-gray-900">Recent Activities</h4>
                        </div>
                        <div class="border-t border-gray-200">
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($recentActivities as $activity): ?>
                                <li class="px-4 py-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <?php if ($activity['level'] === 'info'): ?>
                                                <i class="fas fa-info-circle text-blue-500"></i>
                                            <?php elseif ($activity['level'] === 'warning'): ?>
                                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle text-red-500"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                <?php echo htmlspecialchars($activity['action']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($activity['details']); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                <?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
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
