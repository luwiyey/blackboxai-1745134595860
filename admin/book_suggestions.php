<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

// Check if user is logged in and is admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "SELECT bs.*, u.name as user_name, u.email 
            FROM book_suggestions bs
            JOIN users u ON bs.user_id = u.id
            ORDER BY bs.created_at DESC";
    $result = $conn->query($sql);
    $suggestions = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching book suggestions: " . $e->getMessage());
    $suggestions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Book Suggestions - Admin - PPU Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="../assets/style.css" />
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16 items-center">
                <h1 class="text-xl font-bold text-ppu-blue">PPU Library - Book Suggestions</h1>
                <div class="flex items-center space-x-4">
                    <a href="admin_dashboard.php" class="text-ppu-green hover:text-ppu-light-blue flex items-center">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                    </a>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800 flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6">
        <h2 class="text-2xl font-semibold mb-6 text-ppu-blue">Book Suggestions</h2>
        <?php if (empty($suggestions)): ?>
            <p class="text-gray-600">No book suggestions found.</p>
        <?php else: ?>
            <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
                <thead class="bg-ppu-green text-white">
                    <tr>
                        <th class="py-3 px-6 text-left">User</th>
                        <th class="py-3 px-6 text-left">Title</th>
                        <th class="py-3 px-6 text-left">Author</th>
                        <th class="py-3 px-6 text-left">Reason</th>
                        <th class="py-3 px-6 text-left">Status</th>
                        <th class="py-3 px-6 text-left">Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suggestions as $s): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6"><?php echo htmlspecialchars($s['user_name']); ?><br/><small class="text-gray-500"><?php echo htmlspecialchars($s['email']); ?></small></td>
                            <td class="py-3 px-6"><?php echo htmlspecialchars($s['title']); ?></td>
                            <td class="py-3 px-6"><?php echo htmlspecialchars($s['author']); ?></td>
                            <td class="py-3 px-6"><?php echo nl2br(htmlspecialchars($s['reason'])); ?></td>
                            <td class="py-3 px-6"><?php echo ucfirst($s['status']); ?></td>
                            <td class="py-3 px-6"><?php echo htmlspecialchars($s['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>
</html>
