<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Book.php';

// Check if user is logged in and is admin or librarian
Auth::requireRole(['admin', 'librarian']);

$db = new Database();
$conn = $db->getConnection();
$book = new Book($conn);

// Fetch all books with shelf codes
$books = $book->getAll('', '', 'shelf_code', 'asc', 1, 1000); // Fetch all for layout

// Organize books by shelf code prefix (e.g., A1-S2)
$shelves = [];
foreach ($books as $b) {
    $shelfCode = $b['shelf_code'] ?? 'Unassigned';
    if (!isset($shelves[$shelfCode])) {
        $shelves[$shelfCode] = [];
    }
    $shelves[$shelfCode][] = $b;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Virtual Shelf Layout - PPU Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        .shelf {
            border: 2px solid #4F7F3A;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            background-color: #f9fafb;
        }
        .book-item {
            background-color: #e6f0d4;
            border-radius: 4px;
            padding: 0.5rem;
            margin: 0.25rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .book-item:hover {
            background-color: #c3dca6;
        }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16 items-center">
                <h1 class="text-xl font-bold text-ppu-blue">PPU Library - Virtual Shelf Layout</h1>
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
        <?php foreach ($shelves as $shelfCode => $booksInShelf): ?>
            <section class="shelf">
                <h2 class="text-lg font-semibold mb-4 text-ppu-blue">Shelf: <?php echo htmlspecialchars($shelfCode); ?></h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-5 gap-4">
                    <?php foreach ($booksInShelf as $book): ?>
                        <div class="book-item" title="<?php echo htmlspecialchars($book['title']); ?>">
                            <p class="font-semibold"><?php echo htmlspecialchars($book['title']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($book['author']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </main>
</body>
</html>
