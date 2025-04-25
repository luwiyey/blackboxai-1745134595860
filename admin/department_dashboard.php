<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Statistics.php';

// Check if user is logged in and is a Head of Department (HoD)
Auth::requireRole('hod');

$db = new Database();
$conn = $db->getConnection();
$stats = new Statistics($conn);

// Get department-level analytics
$borrowingTrends = $stats->getBorrowingTrendsByDepartment();
$readingEngagement = $stats->getReadingEngagementByDepartment();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Department Dashboard - PPU Library</title>
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
                <h1 class="text-xl font-bold text-ppu-blue">PPU Library - Department Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800 flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <h2 class="text-2xl font-semibold mb-6 text-ppu-blue">Department Analytics Overview</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-ppu-blue">Borrowing Trends by Department (Last 12 Months)</h3>
                <canvas id="borrowingTrendsChart" height="200"></canvas>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-ppu-blue">Reading Engagement by Department (Last 12 Months)</h3>
                <canvas id="readingEngagementChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <script>
        const borrowingData = <?php echo json_encode($borrowingTrends); ?>;
        const readingData = <?php echo json_encode($readingEngagement); ?>;

        // Prepare labels (months)
        const months = Object.keys(borrowingData[Object.keys(borrowingData)[0]] || {});

        // Prepare datasets for borrowing trends
        const borrowingDatasets = Object.entries(borrowingData).map(([department, data]) => ({
            label: department || 'Unknown',
            data: months.map(month => data[month] || 0),
            fill: false,
            borderColor: '#' + Math.floor(Math.random()*16777215).toString(16),
            tension: 0.1
        }));

        // Prepare datasets for reading engagement
        const readingDatasets = Object.entries(readingData).map(([department, data]) => ({
            label: department || 'Unknown',
            data: months.map(month => data[month] || 0),
            fill: false,
            borderColor: '#' + Math.floor(Math.random()*16777215).toString(16),
            tension: 0.1
        }));

        // Render borrowing trends chart
        const borrowingCtx = document.getElementById('borrowingTrendsChart').getContext('2d');
        new Chart(borrowingCtx, {
            type: 'line',
            data: {
                labels: months,
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

        // Render reading engagement chart
        const readingCtx = document.getElementById('readingEngagementChart').getContext('2d');
        new Chart(readingCtx, {
            type: 'line',
            data: {
                labels: months,
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
