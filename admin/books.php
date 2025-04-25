<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Book.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

$db = new Database();
$conn = $db->getConnection();
$book = new Book($conn);

require_once '../includes/QRCodeGenerator.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = $book->add([
                    'isbn' => $_POST['isbn'],
                    'title' => $_POST['title'],
                    'author' => $_POST['author'],
                    'publisher' => $_POST['publisher'],
                    'publication_year' => $_POST['publication_year'],
                    'category' => $_POST['category'],
                    'description' => $_POST['description'],
                    'total_copies' => $_POST['total_copies'],
                    'price' => $_POST['price'],
                    'tags' => isset($_POST['tags']) ? $_POST['tags'] : []
                ]);
                if ($result) {
                    $_SESSION['success'] = "Book added successfully";
                } else {
                    $_SESSION['error'] = "Failed to add book";
                }
                break;

            case 'edit':
                $result = $book->update($_POST['id'], [
                    'isbn' => $_POST['isbn'],
                    'title' => $_POST['title'],
                    'author' => $_POST['author'],
                    'publisher' => $_POST['publisher'],
                    'publication_year' => $_POST['publication_year'],
                    'category' => $_POST['category'],
                    'description' => $_POST['description'],
                    'total_copies' => $_POST['total_copies'],
                    'price' => $_POST['price'],
                    'tags' => isset($_POST['tags']) ? $_POST['tags'] : []
                ]);
                if ($result) {
                    $_SESSION['success'] = "Book updated successfully";
                } else {
                    $_SESSION['error'] = "Failed to update book";
                }
                break;

            case 'delete':
                $result = $book->delete($_POST['id']);
                if ($result) {
                    $_SESSION['success'] = "Book deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete book";
                }
                break;
        }
        header('Location: books.php');
        exit;
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'title';
$order = $_GET['order'] ?? 'asc';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;

// Get books with pagination
$books = $book->getAll($search, $category, $sort, $order, $page, $limit);
$totalBooks = $book->getTotal($search, $category);
$totalPages = ceil($totalBooks / $limit);

// Get all categories for filter
$categories = $book->getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Books Management - <?php echo SITE_NAME; ?></title>
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
                        <a href="books.php" class="flex items-center px-4 py-3 mt-2 text-gray-700 bg-gray-100 rounded-lg">
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
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-gray-700 text-2xl font-medium">Books Management</h3>
                    <button onclick="openAddModal()" class="bg-ppu-blue text-white px-4 py-2 rounded-lg hover:bg-ppu-light-blue transition-colors">
                        <i class="fas fa-plus mr-2"></i> Add New Book
                    </button>
                </div>

                <!-- Search and Filter Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue"
                                   placeholder="Search by title, author, or ISBN">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                            <select name="sort" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ppu-blue">
                                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title</option>
                                <option value="author" <?php echo $sort === 'author' ? 'selected' : ''; ?>>Author</option>
                                <option value="publication_year" <?php echo $sort === 'publication_year' ? 'selected' : ''; ?>>Publication Year</option>
                                <option value="available_copies" <?php echo $sort === 'available_copies' ? 'selected' : ''; ?>>Available Copies</option>
                            </select>
                        </div>
                        <div class="md:col-span-3 flex justify-end">
                            <button type="submit" class="bg-ppu-green text-white px-6 py-2 rounded-lg hover:bg-opacity-90 transition-colors">
                                <i class="fas fa-search mr-2"></i> Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Books Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ISBN</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Copies</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($books as $book): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($book['title']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($book['publisher']); ?> (<?php echo $book['publication_year']; ?>)</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($book['author']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($book['category']); ?></td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $book['available_copies']; ?> / <?php echo $book['total_copies']; ?></div>
                                    <div class="text-sm text-gray-500">Available</div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($book['status'] === 'available'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Available
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Unavailable
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($book)); ?>)" 
                                            class="text-ppu-blue hover:text-ppu-light-blue mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $book['id']; ?>)" 
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-ppu-blue bg-blue-50 border-ppu-blue z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" 
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

    <!-- Add/Edit Book Modal -->
    <div id="bookModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add New Book</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="bookForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="bookId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">ISBN</label>
                    <input type="text" name="isbn" id="isbn" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" id="title" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Author</label>
                    <input type="text" name="author" id="author" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Publisher</label>
                    <input type="text" name="publisher" id="publisher" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Publication Year</label>
                    <input type="number" name="publication_year" id="publication_year" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" min="1000" max="9999">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category" id="category" required
                            class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Tags</label>
                    <input type="text" id="tagsInput" placeholder="Enter tags separated by commas"
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" />
                    <div id="tagsSuggestions" class="border border-gray-300 rounded mt-1 max-h-40 overflow-y-auto hidden bg-white z-10 absolute w-full"></div>
                    <input type="hidden" name="tags" id="tagsHidden" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3" 
                              class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Copies</label>
                    <input type="number" name="total_copies" id="total_copies" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" min="1">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Price</label>
                    <input type="number" name="price" id="price" step="0.01" required 
                           class="mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" min="0">
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
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Book</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to delete this book? This action cannot be undone.
                    </p>
                </div>
                <div class="flex justify-center mt-4">
                    <form id="deleteForm" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteBookId">
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
        const tagsInput = document.getElementById('tagsInput');
        const tagsHidden = document.getElementById('tagsHidden');
        const tagsSuggestions = document.getElementById('tagsSuggestions');

        let allTags = [];
        let filteredTags = [];
        let selectedTags = [];

        async function fetchAllTags() {
            const res = await fetch('/api/tags.php');
            const data = await res.json();
            if (data.success) {
                allTags = data.data;
            }
        }

        function renderSuggestions() {
            if (filteredTags.length === 0) {
                tagsSuggestions.classList.add('hidden');
                tagsSuggestions.innerHTML = '';
                return;
            }
            tagsSuggestions.classList.remove('hidden');
            tagsSuggestions.innerHTML = '';
            filteredTags.forEach(tag => {
                const div = document.createElement('div');
                div.textContent = tag.name;
                div.className = 'px-3 py-1 cursor-pointer hover:bg-gray-200';
                div.onclick = () => {
                    addTag(tag);
                };
                tagsSuggestions.appendChild(div);
            });
        }

        function addTag(tag) {
            if (!selectedTags.find(t => t.id === tag.id)) {
                selectedTags.push(tag);
                updateTagsInput();
            }
            tagsInput.value = '';
            filteredTags = [];
            renderSuggestions();
        }

        function removeTag(tagId) {
            selectedTags = selectedTags.filter(t => t.id !== tagId);
            updateTagsInput();
        }

        function updateTagsInput() {
            tagsHidden.value = JSON.stringify(selectedTags.map(t => t.id));
            renderSelectedTags();
        }

        function renderSelectedTags() {
            let container = document.getElementById('selectedTagsContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'selectedTagsContainer';
                tagsInput.parentNode.insertBefore(container, tagsInput.nextSibling);
                container.className = 'flex flex-wrap gap-2 mt-2';
            }
            container.innerHTML = '';
            selectedTags.forEach(tag => {
                const tagElem = document.createElement('span');
                tagElem.className = 'bg-ppu-blue text-white px-2 py-1 rounded flex items-center gap-1';
                tagElem.textContent = tag.name;
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.innerHTML = '&times;';
                removeBtn.className = 'ml-1 text-white hover:text-gray-300';
                removeBtn.onclick = () => removeTag(tag.id);
                tagElem.appendChild(removeBtn);
                container.appendChild(tagElem);
            });
        }

        tagsInput.addEventListener('input', () => {
            const query = tagsInput.value.toLowerCase();
            if (query.length === 0) {
                filteredTags = [];
                renderSuggestions();
                return;
            }
            filteredTags = allTags.filter(tag => tag.name.toLowerCase().includes(query) && !selectedTags.find(t => t.id === tag.id));
            renderSuggestions();
        });

        // Initialize
        fetchAllTags();

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Book';
            document.getElementById('formAction').value = 'add';
            document.getElementById('bookForm').reset();
            document.getElementById('bookId').value = '';
            selectedTags = [];
            updateTagsInput();
            document.getElementById('bookModal').classList.remove('hidden');
        }

        function openEditModal(book) {
            document.getElementById('modalTitle').textContent = 'Edit Book';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('bookId').value = book.id;
            document.getElementById('isbn').value = book.isbn;
            document.getElementById('title').value = book.title;
            document.getElementById('author').value = book.author;
            document.getElementById('publisher').value = book.publisher;
            document.getElementById('publication_year').value = book.publication_year;
            document.getElementById('category').value = book.category;
            document.getElementById('description').value = book.description;
            document.getElementById('total_copies').value = book.total_copies;
            document.getElementById('price').value = book.price;

            // Set tags input values
            selectedTags = [];
            if (book.tags) {
                try {
                    const tagsArray = JSON.parse(book.tags);
                    tagsArray.forEach(tagId => {
                        const tag = allTags.find(t => t.id == tagId);
                        if (tag) selectedTags.push(tag);
                    });
                } catch {}
            }
            updateTagsInput();

            document.getElementById('bookModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('bookModal').classList.add('hidden');
        }

        function confirmDelete(id) {
            document.getElementById('deleteBookId').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            let bookModal = document.getElementById('bookModal');
            let deleteModal = document.getElementById('deleteModal');
            if (event.target == bookModal) {
                closeModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
