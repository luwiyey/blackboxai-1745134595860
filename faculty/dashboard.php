<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Book.php';
require_once '../includes/ReadingList.php';

// Check if user is logged in and is a faculty member
Auth::requireRole('faculty');

$db = new Database();
$conn = $db->getConnection();
$book = new Book($conn);
$readingList = new ReadingList($conn);
$userId = $_SESSION['user']['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Faculty Dashboard - PPU Library</title>
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
                    <h1 class="text-xl font-bold text-ppu-blue">PPU Library - Faculty Dashboard</h1>
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Course Reading Lists -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6 text-ppu-blue">Course Reading Lists</h2>
                <div id="readingLists" class="space-y-4">
                    <!-- Reading lists will be loaded here -->
                </div>
                <button id="createListBtn" class="mt-4 bg-ppu-green text-white px-4 py-2 rounded hover:bg-green-700">Create New Reading List</button>
            </div>

            <!-- Book Search & Recommendations -->
            <div>
                <h2 class="text-xl font-bold mb-6 text-ppu-blue">Book Search & Recommendations</h2>
                <form id="bookSearchForm" class="flex mb-4">
                    <input type="text" id="searchQuery" name="searchQuery" placeholder="Search books by title, author, year, genre, keywords" 
                           class="flex-grow px-4 py-2 border rounded-l" />
                    <button type="submit" class="bg-ppu-blue text-white px-4 rounded-r hover:bg-ppu-light-blue">Search</button>
                </form>
                <div id="searchResults" class="bg-white rounded-lg shadow-lg p-4 max-h-96 overflow-auto"></div>
            </div>
        </div>
    </div>

    <!-- Modal for creating reading list -->
    <div id="createListModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-96">
            <h3 class="text-lg font-bold mb-4">Create New Reading List</h3>
            <form id="createListForm" class="space-y-4">
                <div>
                    <label for="listName" class="block text-sm font-medium text-gray-700">List Name</label>
                    <input type="text" id="listName" name="listName" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" required />
                </div>
                <div>
                    <label for="listDescription" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="listDescription" name="listDescription" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" required></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" id="cancelCreateListBtn" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded bg-ppu-blue text-white hover:bg-ppu-light-blue">Create</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load reading lists
        function loadReadingLists() {
            fetch('../api/get_reading_lists.php')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('readingLists');
                    if (data.length === 0) {
                        container.innerHTML = '<p class="text-gray-500">No reading lists found.</p>';
                        return;
                    }
                    let html = '';
                    data.forEach(list => {
                        html += `<div class="border border-gray-300 rounded p-4 hover:shadow cursor-pointer">
                            <h3 class="font-semibold text-lg">${list.name}</h3>
                            <p class="text-sm text-gray-600">${list.description}</p>
                        </div>`;
                    });
                    container.innerHTML = html;
                });
        }

        // Handle book search form submission
        document.getElementById('bookSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const query = document.getElementById('searchQuery').value.trim();
            if (!query) {
                alert('Please enter search terms.');
                return;
            }
            fetch('../api/search_books.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById('searchResults');
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<p class="text-gray-500">No books found.</p>';
                        return;
                    }
                    let html = '<ul class="space-y-4">';
                    data.forEach(book => {
                        html += `<li class="border border-gray-300 rounded p-4 hover:shadow cursor-pointer">
                            <h3 class="font-semibold text-lg">${book.title}</h3>
                            <p class="text-sm text-gray-600">Author: ${book.author}</p>
                            <p class="text-sm text-gray-600">Year: ${book.publication_year}</p>
                            <p class="text-sm text-gray-600">Genre: ${book.category}</p>
                        </li>`;
                    });
                    html += '</ul>';
                    resultsDiv.innerHTML = html;
                })
                .catch(err => {
                    console.error('Error fetching search results:', err);
                });
        });

        // Show create reading list modal
        const createListBtn = document.getElementById('createListBtn');
        const createListModal = document.getElementById('createListModal');
        const cancelCreateListBtn = document.getElementById('cancelCreateListBtn');
        const createListForm = document.getElementById('createListForm');

        createListBtn.addEventListener('click', () => {
            createListModal.classList.remove('hidden');
        });

        cancelCreateListBtn.addEventListener('click', () => {
            createListModal.classList.add('hidden');
        });

        createListForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('listName').value.trim();
            const description = document.getElementById('listDescription').value.trim();

            if (!name || !description) {
                alert('Please fill in all fields.');
                return;
            }

            fetch('../api/create_reading_list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ name, description }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Reading list created successfully.');
                    createListModal.classList.add('hidden');
                    loadReadingLists();
                } else {
                    alert('Failed to create reading list.');
                }
            })
            .catch(err => {
                console.error('Error creating reading list:', err);
                alert('An error occurred.');
            });
        });

        // Initialize
        loadReadingLists();
    </script>
</body>
</html>
