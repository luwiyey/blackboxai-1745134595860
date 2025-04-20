<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Book.php';
require_once '../includes/User.php';
require_once '../includes/Loan.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();
$loan = new Loan($conn);
$book = new Book($conn);
$user = new User($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'issue':
                $result = $loan->issueLoan([
                    'user_id' => $_POST['user_id'],
                    'book_id' => $_POST['book_id'],
                    'due_date' => $_POST['due_date']
                ]);
                if ($result) {
                    $_SESSION['success'] = "Book issued successfully";
                } else {
                    $_SESSION['error'] = "Failed to issue book";
                }
                break;

            case 'return':
                $result = $loan->returnBook($_POST['loan_id']);
                if ($result) {
                    $_SESSION['success'] = "Book returned successfully";
                } else {
                    $_SESSION['error'] = "Failed to return book";
                }
                break;

            case 'extend':
                $result = $loan->extendLoan($_POST['loan_id'], $_POST['new_due_date']);
                if ($result) {
                    $_SESSION['success'] = "Loan extended successfully";
                } else {
                    $_SESSION['error'] = "Failed to extend loan";
                }
                break;

            case 'mark_lost':
                $result = $loan->markAsLost($_POST['loan_id']);
                if ($result) {
                    $_SESSION['success'] = "Book marked as lost";
                } else {
                    $_SESSION['error'] = "Failed to mark book as lost";
                }
                break;
        }
        header('Location: loans.php');
        exit;
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'borrow_date';
$order = $_GET['order'] ?? 'desc';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;

// Get loans with pagination
$loans = $loan->getAll($search, $status, $sort, $order, $page, $limit);
$totalLoans = $loan->getTotal($search, $status);
$totalPages = ceil($totalLoans / $limit);

// Get all books and users for the issue loan form
$allBooks = $book->getAvailableBooks();
$allUsers = $user->getActiveUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                        <a href="loans.php" class="flex items-center px-4 py-3 mt-2 text-gray-700 bg-gray-100 rounded-lg">
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
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-gray-700 text-2xl font-medium">Loan Management</h3>
                    <button onclick="openIssueModal()" class="bg-ppu-blue text-white px-4 py-2 rounded-lg hover:bg-ppu-light-blue transition-colors">
                        <i class="fas fa-plus mr-2"></i> Issue New Loan
                    </button>
                </div>

                <!-- Search and Filter Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue"
                                   placeholder="Search by book title or user name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                                <option value="">All Status</option>
                                <option value="borrowed" <?php echo $status === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                                <option value="returned" <?php echo $status === 'returned' ? 'selected' : ''; ?>>Returned</option>
                                <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                <option value="lost" <?php echo $status === 'lost' ? 'selected' : ''; ?>>Lost</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                            <select name="sort" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                                <option value="borrow_date" <?php echo $sort === 'borrow_date' ? 'selected' : ''; ?>>Borrow Date</option>
                                <option value="due_date" <?php echo $sort === 'due_date' ? 'selected' : ''; ?>>Due Date</option>
                                <option value="return_date" <?php echo $sort === 'return_date' ? 'selected' : ''; ?>>Return Date</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="bg-ppu-green text-white px-6 py-2 rounded-lg hover:bg-opacity-90 transition-colors">
                                <i class="fas fa-search mr-2"></i> Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Loans Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrow Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fine</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($loan['book_title']); ?></div>
                                    <div class="text-sm text-gray-500">ISBN: <?php echo htmlspecialchars($loan['isbn']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($loan['user_name']); ?></div>
                                    <div class="text-sm text-gray-500">ID: <?php echo htmlspecialchars($loan['student_id']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($loan['borrow_date'])); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($loan['due_date'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $loan['status'] === 'borrowed' ? 'bg-blue-100 text-blue-800' : 
                                                        ($loan['status'] === 'returned' ? 'bg-green-100 text-green-800' : 
                                                        ($loan['status'] === 'overdue' ? 'bg-red-100 text-red-800' : 
                                                        'bg-yellow-100 text-yellow-800')); ?>">
                                        <?php echo ucfirst($loan['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php if ($loan['fine_amount'] > 0): ?>
                                        <span class="text-red-600">₱<?php echo number_format($loan['fine_amount'], 2); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <?php if ($loan['status'] === 'borrowed' || $loan['status'] === 'overdue'): ?>
                                        <button onclick="openReturnModal(<?php echo $loan['id']; ?>)" 
                                                class="text-green-600 hover:text-green-800 mr-3">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button onclick="openExtendModal(<?php echo $loan['id']; ?>, '<?php echo $loan['due_date']; ?>')" 
                                                class="text-blue-600 hover:text-blue-800 mr-3">
                                            <i class="fas fa-calendar-plus"></i>
                                        </button>
                                        <button onclick="openLostModal(<?php echo $loan['id']; ?>)" 
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-6">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sort); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sort); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-ppu-blue bg-blue-50 border-ppu-blue z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sort); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Issue Loan Modal -->
    <div id="issueModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Issue New Loan</h3>
                <button onclick="closeIssueModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="issueForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="issue">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">User</label>
                    <select name="user_id" required 
                            class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                        <option value="">Select User</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?php echo $u['id']; ?>">
                                <?php echo htmlspecialchars($u['name'] . ' (' . $u['student_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Book</label>
                    <select name="book_id" required 
                            class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                        <option value="">Select Book</option>
                        <?php foreach ($allBooks as $b): ?>
                            <option value="<?php echo $b['id']; ?>">
                                <?php echo htmlspecialchars($b['title'] . ' (ISBN: ' . $b['isbn'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input type="date" name="due_date" required 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div class="flex justify-end mt-6">
                    <button type="button" onclick="closeIssueModal()" 
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue mr-3">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-ppu-blue py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                        Issue Loan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return Book Modal -->
    <div id="returnModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <i class="fas fa-undo text-green-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Return Book</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to mark this book as returned?
                    </p>
                </div>
                <div class="flex justify-center mt-4">
                    <form id="returnForm" method="POST">
                        <input type="hidden" name="action" value="return">
                        <input type="hidden" name="loan_id" id="returnLoanId">
                        <button type="button" onclick="closeReturnModal()" 
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue mr-3">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-green-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Return Book
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Extend Loan Modal -->
    <div id="extendModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Extend Loan</h3>
                <button onclick="closeExtendModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="extendForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="extend">
                <input type="hidden" name="loan_id" id="extendLoanId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Due Date</label>
                    <input type="date" name="new_due_date" id="newDueDate" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div class="flex justify-end mt-6">
                    <button type="button" onclick="closeExtendModal()" 
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue mr-3">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-ppu-blue py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                        Extend Loan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mark as Lost Modal -->
    <div id="lostModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-times-circle text-red-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Mark Book as Lost</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to mark this book as lost? This will charge the user for the book's price.
                    </p>
                </div>
                <div class="flex justify-center mt-4">
                    <form id="lostForm" method="POST">
                        <input type="hidden" name="action" value="mark_lost">
                        <input type="hidden" name="loan_id" id="lostLoanId">
                        <button type="button" onclick="closeLostModal()" 
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue mr-3">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-red-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Mark as Lost
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openIssueModal() {
            document.getElementById('issueModal').classList.remove('hidden');
        }

        function closeIssueModal() {
            document.getElementById('issueModal').classList.add('hidden');
        }

        function openReturnModal(loanId) {
            document.getElementById('returnLoanId').value = loanId;
            document.getElementById('returnModal').classList.remove('hidden');
        }

        function closeReturnModal() {
            document.getElementById('returnModal').classList.add('hidden');
        }

        function openExtendModal(loanId, currentDueDate) {
            document.getElementById('extendLoanId').value = loanId;
            document.getElementById('newDueDate').min = new Date().toISOString().split('T')[0];
            document.getElementById('newDueDate').value = new Date(currentDueDate).toISOString().split('T')[0];
            document.getElementById('extendModal').classList.remove('hidden');
        }

        function closeExtendModal() {
            document.getElementById('extendModal').classList.add('hidden');
        }

        function openLostModal(loanId) {
            document.getElementById('lostLoanId').value = loanId;
            document.getElementById('lostModal').classList.remove('hidden');
        }

        function closeLostModal() {
            document.getElementById('lostModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            let modals = [
                'issueModal',
                'returnModal',
                'extendModal',
                'lostModal'
            ];

            modals.forEach(function(modalId) {
                let modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>
