<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Book.php';
require_once '../includes/Loan.php';

// Check if user is logged in and is a student
Auth::requireRole('student');

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
    <title>Student Dashboard - PPU Library</title>
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
                    <h1 class="text-xl font-bold text-ppu-blue">PPU Library - Student Dashboard</h1>
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
            <!-- Book Search -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6 text-ppu-blue">Search Books</h2>
                <form id="bookSearchForm" class="flex mb-4">
                    <input type="text" id="searchQuery" name="searchQuery" placeholder="Search books by title, author, year, genre, keywords" 
                           class="flex-grow px-4 py-2 border rounded-l" />
                    <button type="submit" class="bg-ppu-blue text-white px-4 rounded-r hover:bg-ppu-light-blue">Search</button>
                </form>
                <div id="searchResults" class="bg-white rounded-lg shadow-lg p-4 max-h-96 overflow-auto"></div>
            </div>

            <!-- Loan History -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6 text-ppu-blue">Loan History</h2>
                <div id="loanHistory" class="overflow-auto max-h-96">
                    <!-- Loan history will be loaded here -->
                </div>
            </div>

            <!-- Recommendations -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6 text-ppu-blue">Recommended Books</h2>
                <div id="recommendations" class="overflow-auto max-h-96">
                    <!-- Recommendations will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
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

        // Load loan history
        function loadLoanHistory() {
            fetch('../api/get_loan_history.php')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('loanHistory');
                    if (data.length === 0) {
                        container.innerHTML = '<p class="text-gray-500">No loan history found.</p>';
                        return;
                    }
                    let html = '<ul class="space-y-4">';
                    data.forEach(loan => {
                        html += `<li class="border border-gray-300 rounded p-4 hover:shadow cursor-pointer">
                            <h3 class="font-semibold text-lg">${loan.title}</h3>
                            <p class="text-sm text-gray-600">Loan Date: ${loan.loan_date}</p>
                            <p class="text-sm text-gray-600">Due Date: ${loan.due_date}</p>
                            <p class="text-sm text-gray-600">Status: ${loan.status}</p>
                        </li>`;
                    });
                    html += '</ul>';
                    container.innerHTML = html;
                });
        }

        // Load recommendations
        function loadRecommendations() {
            fetch('../api/get_recommendations.php')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('recommendations');
                    if (data.length === 0) {
                        container.innerHTML = '<p class="text-gray-500">No recommendations found.</p>';
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
                    container.innerHTML = html;
                });
        }

        // Initialize
        loadLoanHistory();
        loadRecommendations();
    </script>
</body>
</html>
