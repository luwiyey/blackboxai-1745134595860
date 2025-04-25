<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Statistics.php';
require_once '../includes/Book.php';
require_once '../includes/Loan.php';
require_once '../includes/Payment.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();
$stats = new Statistics($conn);
$book = new Book($conn);
$loan = new Loan($conn);
$payment = new Payment($conn);

// Handle report generation
if (isset($_GET['generate'])) {
    $reportType = $_GET['type'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';

    if ($reportType === 'payments' && isset($_GET['export'])) {
        $exportType = $_GET['export'];

        $reportData = $payment->getReportData($startDate, $endDate);

        if ($exportType === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="payment_report_' . date('Ymd_His') . '.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Reference ID', 'User Name', 'Student ID', 'Book Title', 'ISBN', 'Amount', 'Method', 'Status', 'Date']);

            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['reference_id'],
                    $row['user_name'],
                    $row['student_id'],
                    $row['book_title'],
                    $row['isbn'],
                    $row['amount'],
                    ucfirst($row['method']),
                    ucfirst($row['status']),
                    $row['created_at']
                ]);
            }
            fclose($output);
            exit;
        }

        if ($exportType === 'pdf') {
            require_once '../vendor/autoload.php'; // Assuming mPDF is installed via Composer

            $mpdf = new \Mpdf\Mpdf();
            $html = '<h1>Payment Report</h1>';
            $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">';
            $html .= '<thead><tr><th>Reference ID</th><th>User Name</th><th>Student ID</th><th>Book Title</th><th>ISBN</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>';

            foreach ($reportData as $row) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($row['reference_id']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['user_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['student_id']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['book_title']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['isbn']) . '</td>';
                $html .= '<td>₱' . number_format($row['amount'], 2) . '</td>';
                $html .= '<td>' . ucfirst($row['method']) . '</td>';
                $html .= '<td>' . ucfirst($row['status']) . '</td>';
                $html .= '<td>' . $row['created_at'] . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            $mpdf->WriteHTML($html);
            $mpdf->Output('payment_report_' . date('Ymd_His') . '.pdf', 'D');
            exit;
        }
    }

    // Existing report generation code for other report types (loans, books) below
    switch ($reportType) {
        case 'loans':
            // Headers
            fputcsv($output, ['Date', 'Book Title', 'ISBN', 'User', 'Due Date', 'Return Date', 'Status', 'Fine']);
            
            // Data
            $loans = $loan->getReportData($startDate, $endDate);
            foreach ($loans as $row) {
                fputcsv($output, [
                    $row['borrow_date'],
                    $row['book_title'],
                    $row['isbn'],
                    $row['user_name'],
                    $row['due_date'],
                    $row['return_date'] ?? 'Not returned',
                    $row['status'],
                    number_format($row['fine_amount'], 2)
                ]);
            }
            break;
            
        case 'books':
            // Headers
            fputcsv($output, ['Title', 'ISBN', 'Author', 'Category', 'Total Copies', 'Available Copies', 'Times Borrowed']);
            
            // Data
            $books = $book->getReportData();
            foreach ($books as $row) {
                fputcsv($output, [
                    $row['title'],
                    $row['isbn'],
                    $row['author'],
                    $row['category'],
                    $row['total_copies'],
                    $row['available_copies'],
                    $row['borrow_count']
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}

// Get summary statistics
$summary = [
    'total_books' => $stats->getTotalBooks(),
    'total_users' => $stats->getTotalUsers(),
    'active_loans' => $stats->getActiveLoanCount(),
    'overdue_loans' => $stats->getOverdueLoanCount(),
    'total_fines' => $stats->getTotalFines(),
    'collected_fines' => $stats->getCollectedFines(),
    'monthly_loans' => $stats->getMonthlyLoanCount(),
    'monthly_registrations' => $stats->getMonthlyRegistrationCount()
];

$popularBooks = $stats->getPopularBooks();

// Get recent activity
$recentActivity = $stats->getRecentActivity();

// Get borrowing behavior by user role
$borrowingBehavior = $stats->getBorrowingBehaviorByUserRole();

// Get reading engagement by course/class
$readingEngagement = $stats->getReadingEngagementByCourse();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo SITE_NAME; ?></title>
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
                        <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
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
                        <a href="reports.php" class="flex items-center px-4 py-3 mt-2 text-gray-700 bg-gray-100 rounded-lg">
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
                <h3 class="text-gray-700 text-2xl font-medium mb-8">Reports & Analytics</h3>

                <!-- Summary Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 bg-opacity-75">
                                <i class="fas fa-book text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm text-gray-500">Total Books</h4>
                                <p class="text-lg font-semibold"><?php echo number_format($summary['total_books']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 bg-opacity-75">
                                <i class="fas fa-users text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm text-gray-500">Total Users</h4>
                                <p class="text-lg font-semibold"><?php echo number_format($summary['total_users']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 bg-opacity-75">
                                <i class="fas fa-hand-holding text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm text-gray-500">Active Loans</h4>
                                <p class="text-lg font-semibold"><?php echo number_format($summary['active_loans']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 bg-opacity-75">
                                <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm text-gray-500">Overdue Loans</h4>
                                <p class="text-lg font-semibold"><?php echo number_format($summary['overdue_loans']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Monthly Loans Chart -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h4 class="text-gray-700 text-lg font-medium mb-4">Monthly Loans</h4>
                        <canvas id="loansChart"></canvas>
                    </div>

                    <!-- Monthly Registrations Chart -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h4 class="text-gray-700 text-lg font-medium mb-4">Monthly Registrations</h4>
                        <canvas id="registrationsChart"></canvas>
                    </div>
                </div>

                <!-- Generate Reports Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h4 class="text-gray-700 text-lg font-medium mb-4">Generate Reports</h4>
                    
                    <form action="" method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                                <select name="type" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                                    <option value="loans">Loans Report</option>
                                    <option value="payments">Payments Report</option>
                                    <option value="books">Books Report</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                                <input type="date" name="start_date" required 
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                                <input type="date" name="end_date" required 
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" name="generate" value="1" 
                                        class="bg-ppu-blue text-white px-6 py-2 rounded-lg hover:bg-ppu-light-blue transition-colors">
                                    <i class="fas fa-download mr-2"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Popular Books -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h4 class="text-gray-700 text-lg font-medium mb-4">Popular Books</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Times Borrowed</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($popularBooks as $book): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($book['title']); ?></div>
                                        <div class="text-sm text-gray-500">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($book['category']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo number_format($book['borrow_count']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h4 class="text-gray-700 text-lg font-medium mb-4">Recent Activity</h4>
                    <div class="space-y-4">
                        <?php foreach ($recentActivity as $activity): ?>
                        <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                            <div class="p-3 rounded-full bg-<?php echo $activity['color']; ?>-100">
                                <i class="fas fa-<?php echo $activity['icon']; ?> text-<?php echo $activity['color']; ?>-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo $activity['time_ago']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Charts -->
    <div class="container mx-auto px-6 py-8">
        <h3 class="text-gray-700 text-2xl font-medium mb-8">Additional Analytics</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Borrowing Behavior by User Role -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h4 class="text-gray-700 text-lg font-medium mb-4">Borrowing Behavior by User Role (Last 12 Months)</h4>
                <canvas id="borrowingBehaviorChart"></canvas>
            </div>

            <!-- Reading Engagement by Course/Class -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h4 class="text-gray-700 text-lg font-medium mb-4">Reading Engagement by Course/Class (Last 12 Months)</h4>
                <canvas id="readingEngagementChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Monthly Loans Chart
        const loansCtx = document.getElementById('loansChart').getContext('2d');
        new Chart(loansCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($summary['monthly_loans'])); ?>,
                datasets: [{
                    label: 'Number of Loans',
                    data: <?php echo json_encode(array_values($summary['monthly_loans'])); ?>,
                    borderColor: '#1E4B87',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Monthly Registrations Chart
        const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
        new Chart(registrationsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($summary['monthly_registrations'])); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_values($summary['monthly_registrations'])); ?>,
                    backgroundColor: '#4F7F3A'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Reading Engagement by Course/Class Chart
        const readingCtx = document.getElementById('readingEngagementChart').getContext('2d');
        const readingLabels = Object.keys(<?php echo json_encode($readingEngagement); ?>[Object.keys(<?php echo json_encode($readingEngagement); ?>)[0]] || {});
        const readingDatasets = Object.entries(<?php echo json_encode($readingEngagement); ?>).map(([course, data]) => ({
            label: course || 'Unknown',
            data: readingLabels.map(month => data[month] || 0),
            fill: false,
            borderColor: '#' + Math.floor(Math.random()*16777215).toString(16),
            tension: 0.1
        }));
        new Chart(readingCtx, {
            type: 'line',
            data: {
                labels: readingLabels,
                datasets: readingDatasets
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Borrowing Behavior by User Role Chart
        const borrowingCtx = document.getElementById('borrowingBehaviorChart').getContext('2d');
        const borrowingLabels = Object.keys(<?php echo json_encode($borrowingBehavior); ?>[Object.keys(<?php echo json_encode($borrowingBehavior); ?>)[0]] || {});
        const borrowingDatasets = Object.entries(<?php echo json_encode($borrowingBehavior); ?>).map(([role, data]) => ({
            label: role.charAt(0).toUpperCase() + role.slice(1),
            data: borrowingLabels.map(month => data[month] || 0),
            fill: false,
            borderColor: '#' + Math.floor(Math.random()*16777215).toString(16),
            tension: 0.1
        }));
        new Chart(borrowingCtx, {
            type: 'line',
            data: {
                labels: borrowingLabels,
                datasets: borrowingDatasets
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Reading Engagement by Course/Class Chart
        const readingCtx = document.getElementById('readingEngagementChart').getContext('2d');
        const readingLabels = Object.keys(<?php echo json_encode($readingEngagement); ?>[Object.keys(<?php echo json_encode($readingEngagement); ?>)[0]] || {});
        const readingDatasets = Object.entries(<?php echo json_encode($readingEngagement); ?>).map(([course, data]) => ({
            label: course || 'Unknown',
            data: readingLabels.map(month => data[month] || 0),
            fill: false,
            borderColor: '#' + Math.floor(Math.random()*16777215).toString(16),
            tension: 0.1
        }));
        new Chart(readingCtx, {
            type: 'line',
            data: {
                labels: readingLabels,
                datasets: readingDatasets
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
