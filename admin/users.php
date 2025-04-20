<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/User.php';
require_once '../includes/Notification.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();
$user = new User($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = $user->create([
                    'name' => $_POST['name'],
                    'email' => $_POST['email'],
                    'student_id' => $_POST['student_id'],
                    'role' => $_POST['role'],
                    'password' => $_POST['password'], // User class will hash password
                    'status' => $_POST['status']
                ]);
                if ($result) {
                    $_SESSION['success'] = "User added successfully";
                } else {
                    $_SESSION['error'] = "Failed to add user";
                }
                break;

            case 'edit':
                $data = [
                    'name' => $_POST['name'],
                    'email' => $_POST['email'],
                    'student_id' => $_POST['student_id'],
                    'role' => $_POST['role'],
                    'status' => $_POST['status']
                ];
                
                // Only update password if a new one is provided
                if (!empty($_POST['password'])) {
                    $data['password'] = $_POST['password']; // User class will hash password
                }
                
                $result = $user->update($_POST['id'], $data);
                if ($result) {
                    // Send notification email if status changed to active
                    if ($_POST['status'] === 'active') {
                        Notification::sendAccountApprovedEmail($_POST['email']);
                    }
                    $_SESSION['success'] = "User updated successfully";
                } else {
                    $_SESSION['error'] = "Failed to update user";
                }
                break;

            case 'delete':
                // Implement delete method in User class if needed
                // For now, just set status to 'deleted' or remove from DB
                $result = $user->delete($_POST['id']);
                if ($result) {
                    $_SESSION['success'] = "User deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete user";
                }
                break;
        }
        header('Location: users.php');
        exit;
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$order = $_GET['order'] ?? 'asc';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;

// Get users with pagination
$users = $user->getAll($search, $role, $status, $sort, $order, $page, $limit);
$totalUsers = $user->getTotal($search, $role, $status);
$totalPages = ceil($totalUsers / $limit);

// Get all roles and statuses for filters
$roles = ['admin', 'librarian', 'faculty', 'student'];
$statuses = ['active', 'pending', 'suspended'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Management - <?php echo SITE_NAME; ?></title>
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
                    <h3 class="text-gray-700 text-2xl font-medium">User Management</h3>
                    <button onclick="openAddModal()" class="bg-ppu-blue text-white px-4 py-2 rounded-lg hover:bg-ppu-light-blue transition-colors">
                        <i class="fas fa-user-plus mr-2"></i> Add New User
                    </button>
                </div>

                <!-- Search and Filter Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue"
                                   placeholder="Search by name, email, or ID">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                            <select name="role" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                                <option value="">All Roles</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo $role === $r ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($r); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                                <option value="">All Statuses</option>
                                <?php foreach ($statuses as $s): ?>
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

                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student/Staff ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['student_id']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                                        ($user['role'] === 'librarian' ? 'bg-blue-100 text-blue-800' : 
                                                        ($user['role'] === 'faculty' ? 'bg-green-100 text-green-800' : 
                                                        'bg-gray-100 text-gray-800')); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                        ($user['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 
                                                        'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                            class="text-ppu-blue hover:text-ppu-light-blue mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $user['id']; ?>)" 
                                            class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-ppu-blue bg-blue-50 border-ppu-blue z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
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

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add New User</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="userForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="userId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="name" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Student/Staff ID</label>
                    <input type="text" name="student_id" id="student_id" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" id="password" 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500" id="passwordHint">Leave blank to keep current password when editing</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select name="role" id="role" required 
                            class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r; ?>"><?php echo ucfirst($r); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" required 
                            class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="button" onclick="closeModal()" 
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue mr-3">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-ppu-blue py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete User</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to delete this user? This action cannot be undone.
                    </p>
                </div>
                <div class="flex justify-center mt-4">
                    <form id="deleteForm" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteUserId">
                        <button type="button" onclick="closeDeleteModal()" 
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue mr-3">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-red-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('formAction').value = 'add';
            document.getElementById('userForm').reset();
            document.getElementById('password').required = true;
            document.getElementById('passwordHint').style.display = 'none';
            document.getElementById('userModal').classList.remove('hidden');
        }

        function openEditModal(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('userId').value = user.id;
            document.getElementById('name').value = user.name;
            document.getElementById('email').value = user.email;
            document.getElementById('student_id').value = user.student_id;
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;
            document.getElementById('password').required = false;
            document.getElementById('passwordHint').style.display = 'block';
            document.getElementById('userModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        function confirmDelete(id) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            let userModal = document.getElementById('userModal');
            let deleteModal = document.getElementById('deleteModal');
            if (event.target == userModal) {
                closeModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
