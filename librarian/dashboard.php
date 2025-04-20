<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Book.php';
require_once '../includes/Loan.php';

// Check if user is logged in and is a librarian
Auth::requireRole('librarian');

$db = new Database();
$conn = $db->getConnection();
$book = new Book($conn);
$loan = new Loan($conn);
$userId = $_SESSION['user']['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Librarian Dashboard - PPU Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="../assets/style.css" />
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
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-ppu-blue">PPU Library - Librarian Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                    <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition duration-300">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 gap-8">
            <!-- Book Inventory Management -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6 text-ppu-blue">Book Inventory Management</h2>
                <button id="addBookBtn" class="mb-4 bg-ppu-green text-white px-4 py-2 rounded hover:bg-green-700">Add New Book</button>
                <div id="bookInventory" class="overflow-auto max-h-96">
                    <!-- Book inventory list will be loaded here -->
                </div>
            </div>

            <!-- Loan Management -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6 text-ppu-blue">Loan Management</h2>
                <div id="loanManagement" class="overflow-auto max-h-96">
                    <!-- Loan management list will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for adding/editing book -->
    <div id="bookModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-96">
            <h3 class="text-lg font-bold mb-4" id="modalTitle">Add New Book</h3>
            <form id="bookForm" class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" id="title" name="title" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" required />
                </div>
                <div>
                    <label for="author" class="block text-sm font-medium text-gray-700">Author</label>
                    <input type="text" id="author" name="author" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" required />
                </div>
                <div>
                    <label for="publicationYear" class="block text-sm font-medium text-gray-700">Publication Year</label>
                    <input type="number" id="publicationYear" name="publicationYear" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" required />
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <input type="text" id="category" name="category" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" required />
                </div>
                <div>
                    <label for="isbn" class="block text-sm font-medium text-gray-700">ISBN</label>
                    <input type="text" id="isbn" name="isbn" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" required />
                </div>
                <div>
                    <label for="copies" class="block text-sm font-medium text-gray-700">Number of Copies</label>
                    <input type="number" id="copies" name="copies" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" required />
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" id="cancelBtn" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded bg-ppu-blue text-white hover:bg-ppu-light-blue">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load book inventory
        function loadBookInventory() {
            fetch('../api/get_books.php')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('bookInventory');
                    if (data.length === 0) {
                        container.innerHTML = '<p class="text-gray-500">No books found.</p>';
                        return;
                    }
                    let html = '<table class="min-w-full divide-y divide-gray-200">';
                    html += '<thead><tr data-book-id><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Genre</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ISBN</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Copies</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></tr></thead><tbody class="bg-white divide-y divide-gray-200">';
                    data.forEach(book => {
                        html += `<tr data-book-id="${book.id}">
                            <td class="px-6 py-4 whitespace-nowrap">${book.title}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${book.author}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${book.publication_year}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${book.category}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${book.isbn}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${book.copies}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button class="text-blue-600 hover:text-blue-900 mr-2 edit-btn">Edit</button>
                                <button class="text-red-600 hover:text-red-900 delete-btn">Delete</button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;

                    // Attach event listeners for edit and delete buttons
                    container.querySelectorAll('.edit-btn').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const row = e.target.closest('tr');
                            const book = {
                                id: row.getAttribute('data-book-id'),
                                title: row.children[0].textContent,
                                author: row.children[1].textContent,
                                publication_year: row.children[2].textContent,
                                category: row.children[3].textContent,
                                isbn: row.children[4].textContent,
                                copies: row.children[5].textContent,
                            };
                            openBookModal(book);
                        });
                    });
                    container.querySelectorAll('.delete-btn').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const row = e.target.closest('tr');
                            const bookId = row.getAttribute('data-book-id');
                            if (confirm('Are you sure you want to delete this book?')) {
                                fetch('../api/delete_book.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({ id: bookId }),
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('Book deleted successfully.');
                                        loadBookInventory();
                                    } else {
                                        alert('Failed to delete book.');
                                    }
                                })
                                .catch(err => {
                                    console.error('Error deleting book:', err);
                                    alert('An error occurred.');
                                });
                            }
                        });
                    });
                });
        }

        // Load loan management
        function loadLoanManagement() {
            fetch('../api/get_loans.php')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('loanManagement');
                    if (data.length === 0) {
                        container.innerHTML = '<p class="text-gray-500">No loans found.</p>';
                        return;
                    }
                    let html = '<table class="min-w-full divide-y divide-gray-200">';
                    html += '<thead><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrower</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></tr></thead><tbody class="bg-white divide-y divide-gray-200">';
                    data.forEach(loan => {
                        html += `<tr>
                            <td class="px-6 py-4 whitespace-nowrap">${loan.title}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${loan.borrower_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${loan.loan_date}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${loan.due_date}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${loan.status}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button class="text-green-600 hover:text-green-900 mr-2 approve-btn" data-loan-id="${loan.id}">Approve</button>
                                <button class="text-red-600 hover:text-red-900 reject-btn" data-loan-id="${loan.id}">Reject</button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;

                    // Attach event listeners for approve and reject buttons
                    document.querySelectorAll('.approve-btn').forEach(button => {
                        button.addEventListener('click', () => {
                            const loanId = button.getAttribute('data-loan-id');
                            updateLoanStatus(loanId, 'approved');
                        });
                    });
                    document.querySelectorAll('.reject-btn').forEach(button => {
                        button.addEventListener('click', () => {
                            const loanId = button.getAttribute('data-loan-id');
                            updateLoanStatus(loanId, 'rejected');
                        });
                    });
                });
        }

        // Update loan status
        function updateLoanStatus(loanId, status) {
            fetch('../api/update_loan_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ loanId, status }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`Loan ${status} successfully.`);
                    loadLoanManagement();
                } else {
                    alert(`Failed to ${status} loan.`);
                }
            })
            .catch(err => {
                console.error(`Error updating loan status:`, err);
                alert('An error occurred.');
            });
        }

        // Attach event listeners for Add Book button
        document.getElementById('addBookBtn').addEventListener('click', () => {
            openBookModal();
        });

        // Open modal for adding/editing book
        function openBookModal(book = null) {
            const bookModal = document.getElementById('bookModal');
            const modalTitle = document.getElementById('modalTitle');
            const bookForm = document.getElementById('bookForm');

            bookModal.classList.remove('hidden');
            bookForm.reset();

            if (book) {
                modalTitle.textContent = 'Edit Book';
                bookForm.title.value = book.title;
                bookForm.author.value = book.author;
                bookForm.publicationYear.value = book.publication_year;
                bookForm.category.value = book.category;
                bookForm.isbn.value = book.isbn;
                bookForm.copies.value = book.copies;
                bookForm.dataset.editingId = book.id;
            } else {
                modalTitle.textContent = 'Add New Book';
                delete bookForm.dataset.editingId;
            }
        }

        // Close modal on cancel
        document.getElementById('cancelBtn').addEventListener('click', () => {
            document.getElementById('bookModal').classList.add('hidden');
        });

        // Handle book form submission for add/edit
        document.getElementById('bookForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const bookForm = e.target;
            const formData = new FormData(bookForm);
            const bookData = {};
            formData.forEach((value, key) => {
                bookData[key] = value;
            });

            const isEditing = bookForm.dataset.editingId !== undefined;
            const url = isEditing ? '../api/edit_book.php' : '../api/add_book.php';
            if (isEditing) {
                bookData.id = bookForm.dataset.editingId;
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bookData),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(isEditing ? 'Book updated successfully.' : 'Book added successfully.');
                    document.getElementById('bookModal').classList.add('hidden');
                    loadBookInventory();
                } else {
                    alert(isEditing ? 'Failed to update book.' : 'Failed to add book.');
                }
            })
            .catch(err => {
                console.error('Error saving book:', err);
                alert('An error occurred.');
            });
        });

        // Initial load
        loadBookInventory();
        loadLoanManagement();
    </script>
</body>
</html>
=======
