<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$userId = $_GET['user_id'] ?? '';
$action = $_GET['action'] ?? '';
$level = $_GET['level'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$where = [];
$params = [];

if ($userId !== '') {
    $where[] = 'user_id = ?';
    $params[] = $userId;
}
if ($action !== '') {
    $where[] = 'action LIKE ?';
    $params[] = "%$action%";
}
if ($level !== '') {
    $where[] = 'level = ?';
    $params[] = $level;
}
if ($startDate !== '') {
    $where[] = 'created_at >= ?';
    $params[] = $startDate . ' 00:00:00';
}
if ($endDate !== '') {
    $where[] = 'created_at <= ?';
    $params[] = $endDate . ' 23:59:59';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalCount = $conn->fetchColumn("SELECT COUNT(*) FROM activity_logs $whereSql", $params);
$totalPages = ceil($totalCount / $limit);

// Get logs
$sql = "SELECT al.*, u.name AS user_name FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $whereSql
        ORDER BY al.created_at DESC
        LIMIT $limit OFFSET $offset";

$logs = $conn->fetchAll($sql, $params);

$levels = ['info', 'warning', 'error'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>System Logs - PPU Library Admin</title>
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

    <div class="max-w-7xl mx-auto mt-10 bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold mb-6">System Logs & Audit Trail</h2>

        <form method="GET" action="logs.php" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <input type="text" name="user_id" placeholder="User ID" value="<?php echo htmlspecialchars($userId); ?>"
                   class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue" />
            <input type="text" name="action" placeholder="Action" value="<?php echo htmlspecialchars($action); ?>"
                   class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue" />
            <select name="level" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue">
                <option value="">All Levels</option>
                <?php foreach ($levels as $lvl): ?>
                    <option value="<?php echo $lvl; ?>" <?php echo $level === $lvl ? 'selected' : ''; ?>><?php echo ucfirst($lvl); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>"
                   class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue" />
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>"
                   class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue" />
            <div class="md:col-span-1 flex items-center">
                <button type="submit" class="bg-ppu-blue text-white px-6 py-2 rounded-md hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                    Filter
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($log['created_at']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($log['action']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($log['details']); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $log['level'] === 'info' ? 'bg-green-100 text-green-800' : 
                                            ($log['level'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 
                                            'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($log['level']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-6">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&user_id=<?php echo urlencode($userId); ?>&action=<?php echo urlencode($action); ?>&level=<?php echo urlencode($level); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&user_id=<?php echo urlencode($userId); ?>&action=<?php echo urlencode($action); ?>&level=<?php echo urlencode($level); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-ppu-blue bg-blue-50 border-ppu-blue z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&user_id=<?php echo urlencode($userId); ?>&action=<?php echo urlencode($action); ?>&level=<?php echo urlencode($level); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
