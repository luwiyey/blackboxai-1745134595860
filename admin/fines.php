<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Payment.php';
require_once '../includes/Loan.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();
$payment = new Payment($conn);
$loan = new Loan($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'verify':
                $result = $payment->verifyPayment($_POST['payment_id']);
                if ($result) {
                    $_SESSION['success'] = "Payment verified successfully";
                } else {
                    $_SESSION['error'] = "Failed to verify payment";
                }
                break;

            case 'reject':
                $result = $payment->rejectPayment($_POST['payment_id'], $_POST['rejection_reason']);
                if ($result) {
                    $_SESSION['success'] = "Payment rejected successfully";
                } else {
                    $_SESSION['error'] = "Failed to reject payment";
                }
                break;

            case 'record_cash':
                $result = $payment->recordCashPayment([
                    'user_id' => $_POST['user_id'],
                    'loan_id' => $_POST['loan_id'],
                    'amount' => $_POST['amount']
                ]);
                if ($result) {
                    $_SESSION['success'] = "Cash payment recorded successfully";
                } else {
                    $_SESSION['error'] = "Failed to record cash payment";
                }
                break;
        }
        header('Location: fines.php');
        exit;
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$method = $_GET['method'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'desc';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;

// Get payments with pagination
$payments = $payment->getAll($search, $status, $method, $sort, $order, $page, $limit);
$totalPayments = $payment->getTotal($search, $status, $method);
$totalPages = ceil($totalPayments / $limit);

// Get statistics
$stats = [
    'total_fines' => $payment->getTotalFines(),
    'collected_fines' => $payment->getCollectedFines(),
    'pending_fines' => $payment->getPendingFines(),
    'today_collections' => $payment->getTodayCollections()
];

// Get all payment methods and statuses for filters
$paymentMethods = ['gcash', 'cash', 'credit_card'];
$paymentStatuses = ['pending', 'verified', 'rejected'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fines & Payments - <?php echo SITE_NAME; ?></title>
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
                        <a href="loans.php" class="flex items-center px-4 py-3 mt-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-hand-holding mr-3"></i>
                            Loan Management
                        </a>
                        <a href="fines.php" class="flex items-center px-4 py-3 mt-2 text-gray-700 bg-gray-100 rounded-lg">
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
                    <h3 class="text-gray-700 text-2xl font-medium">Fines & Payments</h3>
                    <button onclick="openCashModal()" class="bg-ppu-blue text-white px-4 py-2 rounded-lg hover:bg-ppu-light-blue transition-colors">
                        <i class="fas fa-money-bill mr-2"></i> Record Cash Payment
                    </button>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <!-- Total Fines -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 bg-opacity-75">
                                <i class="fas fa-money-bill-wave text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm text-gray-500">Total Fines</h4>
                                <p class="text-lg font-semibold">₱<?php echo number_format($stats['total_fines'], 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Collected Fines -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 bg-opacity-75">
                                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm text-gray-500">Collected</h4>
                                <p class="text-lg font-semibold">₱<?php echo number_format($stats['collected_fines'], 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Fines -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 bg-opacity-75">
                                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm text-gray-500">Pending</h4>
                                <p class="text-lg font-semibold">₱<?php echo number_format($stats['pending_fines'], 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Collections -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 bg-opacity-75">
                                <i class="fas fa-calendar-day text-purple-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm text-gray-500">Today's Collections</h4>
                                <p class="text-lg font-semibold">₱<?php echo number_format($stats['today_collections'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue"
                                   placeholder="Search by reference number or user">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                            <select name="method" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                                <option value="">All Methods</option>
                                <?php foreach ($paymentMethods as $m): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $method === $m ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($m); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                                <option value="">All Status</option>
                                <?php foreach ($paymentStatuses as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($s); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="bg-ppu-green text-white px-6 py-2 rounded-lg hover:bg-opacity-90 transition-colors">
                                <i class="fas fa-search mr-2"></i> Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Payments Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($payment['reference_number']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($payment['user_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($payment['student_id']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        ₱<?php echo number_format($payment['amount'], 2); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $payment['method'] === 'cash' ? 'bg-green-100 text-green-800' : 
                                                        ($payment['method'] === 'gcash' ? 'bg-blue-100 text-blue-800' : 
                                                        'bg-purple-100 text-purple-800'); ?>">
                                        <?php echo ucfirst($payment['method']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $payment['status'] === 'verified' ? 'bg-green-100 text-green-800' : 
                                                        ($payment['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                                        'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <?php if ($payment['status'] === 'pending'): ?>
                                        <button onclick="openVerifyModal(<?php echo $payment['id']; ?>)" 
                                                class="text-green-600 hover:text-green-800 mr-3">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="openRejectModal(<?php echo $payment['id']; ?>)" 
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($payment['method'] === 'gcash' && !empty($payment['transaction_proof'])): ?>
                                        <button onclick="openProofModal('<?php echo htmlspecialchars($payment['transaction_proof']); ?>')" 
                                                class="text-blue-600 hover:text-blue-800 ml-3" title="View Payment Proof">
                                            <i class="fas fa-image"></i>
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&method=<?php echo urlencode($method); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&method=<?php echo urlencode($method); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-ppu-blue bg-blue-50 border-ppu-blue z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&method=<?php echo urlencode($method); ?>" 
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

    <!-- Record Cash Payment Modal -->
    <div id="cashModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Record Cash Payment</h3>
                <button onclick="closeCashModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="cashForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="record_cash">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">User</label>
                    <select name="user_id" required 
                            class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                        <option value="">Select User</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>">
                                <?php echo htmlspecialchars($u['name'] . ' (' . $u['student_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Loan</label>
                    <select name="loan_id" required 
                            class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                        <option value="">Select Loan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                    <input type="number" name="amount" required step="0.01" min="0" 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div class="flex justify-end mt-6">
                    <button type="button" onclick="closeCashModal()" 
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue mr-3">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-ppu-blue py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                        Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Verify Payment Modal -->
    <div id="verifyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Verify Payment</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to verify this payment?
                    </p>
                </div>
                <div class="flex justify-center mt-4">
                    <form id="verifyForm" method="POST">
                        <input type="hidden" name="action" value="verify">
                        <input type="hidden" name="payment_id" id="verifyPaymentId">
                        <button type="button" onclick="closeVerifyModal()" 
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue mr-3">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-green-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Verify
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Payment Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Reject Payment</h3>
                <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="rejectForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="payment_id" id="rejectPaymentId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reason for Rejection</label>
                    <textarea name="rejection_reason" required rows="3" 
                              class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm"></textarea>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="button" onclick="closeRejectModal()" 
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue mr-3">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-red-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Reject
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Dynamic loan selection based on user
        document.querySelector('select[name="user_id"]').addEventListener('change', function() {
            const userId = this.value;
            const loanSelect = document.querySelector('select[name="loan_id"]');
            loanSelect.innerHTML = '<option value="">Select Loan</option>';
            
            if (userId) {
                fetch(`get_user_loans.php?user_id=${userId}`)
                    .then(response => response.json())
                    .then(loans => {
                        loans.forEach(loan => {
                            const option = document.createElement('option');
                            option.value = loan.id;
                            option.textContent = `Book: ${loan.book_title} - Fine: ₱${loan.fine_amount}`;
                            loanSelect.appendChild(option);
                        });
                    });
            }
        });

        function openCashModal() {
            document.getElementById('cashModal').classList.remove('hidden');
        }

        function closeCashModal() {
            document.getElementById('cashModal').classList.add('hidden');
        }

        function openVerifyModal(paymentId) {
            document.getElementById('verifyPaymentId').value = paymentId;
            document.getElementById('verifyModal').classList.remove('hidden');
        }

        function closeVerifyModal() {
            document.getElementById('verifyModal').classList.add('hidden');
        }

        function openRejectModal(paymentId) {
            document.getElementById('rejectPaymentId').value = paymentId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            let modals = [
                'cashModal',
                'verifyModal',
                'rejectModal'
            ];

            modals.forEach(function(modalId) {
                let modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.classList.add('hidden');
                }
            });
        }
    </script>

    <!-- Payment Proof Modal -->
    <div id="proofModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Payment Proof</h3>
                <button onclick="closeProofModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex justify-center">
                <img id="proofImage" src="" alt="Payment Proof" class="max-w-full max-h-96 rounded-md" />
            </div>
        </div>
    </div>

    <script>
        function openProofModal(imageUrl) {
            document.getElementById('proofImage').src = imageUrl;
            document.getElementById('proofModal').classList.remove('hidden');
        }

        function closeProofModal() {
            document.getElementById('proofModal').classList.add('hidden');
            document.getElementById('proofImage').src = '';
        }
    </script>
</body>
</html>
