<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pan Pacific University Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
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
    <style>
        .task-manager {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 0.3px 2.2px rgba(0, 0, 0, 0.011), 0 0.7px 5.3px rgba(0, 0, 0, 0.016), 0 1.3px 10px rgba(0, 0, 0, 0.02), 0 2.2px 17.9px rgba(0, 0, 0, 0.024), 0 4.2px 33.4px rgba(0, 0, 0, 0.029), 0 10px 80px rgba(0, 0, 0, 0.04);
        }

        .left-bar {
            background-color: #f5f8ff;
            border-right: 1px solid #e3e7f7;
        }

        .task-box {
            position: relative;
            border-radius: 12px;
            margin: 20px 0;
            padding: 16px;
            cursor: pointer;
            box-shadow: 2px 2px 4px 0px rgba(235, 235, 235, 1);
            transition: transform 0.2s ease;
        }

        .task-box:hover {
            transform: scale(1.02);
        }

        .yellow { background-color: #fff1d6; }
        .blue { background-color: #d3e6ff; }
        .red { background-color: #ffd9d9; }
        .green { background-color: #daffe5; }

        .tag {
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 20px;
        }

        .tag.completed { background-color: #d4edda; color: #155724; }
        .tag.in-progress { background-color: #fff3cd; color: #856404; }
        .tag.needs-review { background-color: #cce5ff; color: #004085; }
        .tag.overdue { background-color: #f8d7da; color: #721c24; }

        .priority {
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 20px;
            margin-left: 10px;
        }

        .priority.urgent { background-color: #dc3545; color: white; }
        .priority.high { background-color: #ff8c00; color: white; }
        .priority.normal { background-color: #28a745; color: white; }
        .priority.low { background-color: #d3d3d3; color: black; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Top Navigation Bar -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <img src="photos/PUlogo.png" alt="University Logo" class="h-12 w-auto mr-4">
                        <div>
                            <h1 class="text-xl font-bold text-ppu-blue">Pan Pacific University</h1>
                            <p class="text-sm text-ppu-green">Library Dashboard</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                        <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition duration-300">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Left Sidebar -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-6 text-ppu-blue">Book Search</h2>
                    <form id="bookSearchForm" class="space-y-4">
                        <div>
                            <label for="searchQuery" class="block text-sm font-medium text-gray-700">Search by Title, Author, Year, Genre, Keywords</label>
                            <input type="text" id="searchQuery" name="searchQuery" placeholder="Enter search terms" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm" />
                        </div>
                        <div>
                            <button type="button" id="voiceSearchBtn" 
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                                <i class="fas fa-microphone mr-2"></i> Voice Search
                            </button>
                        </div>
                        <div>
                            <button type="submit" 
                                    class="w-full bg-ppu-blue text-white px-4 py-2 rounded-md hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                                Search
                            </button>
                        </div>
                    </form>
                    <div id="searchResults" class="mt-4"></div>
                </div>

                <!-- Main Content -->
                <div class="md:col-span-2">
                    <h2 class="text-xl font-bold mb-6 text-ppu-blue">Recommended Books</h2>
                    <div id="recommendations" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <!-- Recommendations will be loaded here -->
                    </div>

                    <h2 class="text-xl font-bold mb-6 text-ppu-blue">Your Loan History</h2>
                    <div id="loanHistory" class="bg-white rounded-lg shadow-lg p-6 overflow-auto max-h-96">
                        <!-- Loan history will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Voice search using Web Speech API
            const voiceSearchBtn = document.getElementById('voiceSearchBtn');
            const searchQueryInput = document.getElementById('searchQuery');

            if ('webkitSpeechRecognition' in window) {
                const recognition = new webkitSpeechRecognition();
                recognition.continuous = false;
                recognition.interimResults = false;
                recognition.lang = 'en-US';

                voiceSearchBtn.addEventListener('click', () => {
                    recognition.start();
                });

                recognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript;
                    searchQueryInput.value = transcript;
                    recognition.stop();
                    document.getElementById('bookSearchForm').dispatchEvent(new Event('submit'));
                };

                recognition.onerror = (event) => {
                    console.error('Speech recognition error', event.error);
                    recognition.stop();
                };
            } else {
                voiceSearchBtn.disabled = true;
                voiceSearchBtn.title = 'Voice search not supported in this browser.';
            }

            // Handle book search form submission
            document.getElementById('bookSearchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const query = searchQueryInput.value.trim();
                if (!query) {
                    alert('Please enter search terms.');
                    return;
                }
                fetch('search_books.php?q=' + encodeURIComponent(query))
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

            // Load recommendations and loan history on page load
            document.addEventListener('DOMContentLoaded', () => {
                fetch('get_recommendations.php')
                    .then(res => res.json())
                    .then(data => {
                        const recDiv = document.getElementById('recommendations');
                        if (data.length === 0) {
                            recDiv.innerHTML = '<p class="text-gray-500">No recommendations available.</p>';
                            return;
                        }
                        let html = '';
                        data.forEach(book => {
                            html += `<div class="border border-gray-300 rounded p-4 hover:shadow cursor-pointer">
                                <h3 class="font-semibold text-lg">${book.title}</h3>
                                <p class="text-sm text-gray-600">Author: ${book.author}</p>
                                <p class="text-sm text-gray-600">Year: ${book.publication_year}</p>
                            </div>`;
                        });
                        recDiv.innerHTML = html;
                    });

                fetch('get_loan_history.php')
                    .then(res => res.json())
                    .then(data => {
                        const loanDiv = document.getElementById('loanHistory');
                        if (data.length === 0) {
                            loanDiv.innerHTML = '<p class="text-gray-500">No loan history found.</p>';
                            return;
                        }
                        let html = '<table class="min-w-full divide-y divide-gray-200">';
                        html += '<thead><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrow Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th></tr></thead><tbody class="bg-white divide-y divide-gray-200">';
                        data.forEach(loan => {
                            html += `<tr>
                                <td class="px-6 py-4 whitespace-nowrap">${loan.title}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${loan.borrow_date}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${loan.due_date}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${loan.status}</td>
                            </tr>`;
                        });
                        html += '</tbody></table>';
                        loanDiv.innerHTML = html;
                    });
            });
        </script>
    </div>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Add click handlers for task boxes
            const taskBoxes = document.querySelectorAll('.task-box');
            taskBoxes.forEach(box => {
                box.addEventListener('click', () => {
                    // Handle task box click
                    console.log('Task clicked:', box.querySelector('h3').textContent);
                });
            });
        });
    </script>
</body>
</html>
