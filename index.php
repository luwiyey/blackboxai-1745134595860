<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pan Pacific University Library</title>
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
</head>
<body class="bg-white">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="#" class="flex flex-col">
                        <h1 class="text-2xl font-bold text-ppu-blue">Pan Pacific University</h1>
                        <p class="text-sm text-ppu-green">University Library</p>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-1">
                    <div class="relative group">
                        <a href="#" class="nav-item text-gray-800 hover:text-ppu-blue">About</a>
                        <div class="absolute hidden group-hover:block w-48 bg-white shadow-lg py-2 mt-1">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Mission and Vision</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">History</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Library Hours</a>
                        </div>
                    </div>
                    <div class="relative group">
                        <a href="#" class="nav-item text-gray-800 hover:text-ppu-blue">Policies</a>
                        <div class="absolute hidden group-hover:block w-48 bg-white shadow-lg py-2 mt-1">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Library Guidelines</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Borrowing</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Services</a>
                        </div>
                    </div>
                    <div class="relative group">
                        <a href="#" class="nav-item text-gray-800 hover:text-ppu-blue">Request</a>
                    </div>
                    <div class="relative group">
                        <a href="#" class="nav-item text-gray-800 hover:text-ppu-blue">Search</a>
                    </div>
                    <div class="relative group">
                        <a href="#" class="nav-item text-gray-800 hover:text-ppu-blue">Inquire</a>
                    </div>
                    <div class="relative group">
                        <a href="#" class="nav-item text-gray-800 hover:text-ppu-blue">Publications</a>
                    </div>
                    <div class="ml-4 flex items-center space-x-2">
                        <a href="demo/settings-demo.html" class="nav-item text-white bg-ppu-green hover:bg-green-600 px-4 py-2 rounded-md transition duration-300 ml-2" title="Settings Demo">
                            <i class="fas fa-cog mr-2"></i>Settings
                        </a>
                        <?php if (isset($_SESSION['user'])): ?>
                            <a href="dashboard.php" class="nav-item text-white bg-ppu-blue hover:bg-ppu-light-blue px-4 py-2 rounded-md transition duration-300">
                                <i class="fas fa-user mr-2"></i>Dashboard
                            </a>
                            <a href="logout.php" class="nav-item text-ppu-blue border-2 border-ppu-blue hover:bg-ppu-blue hover:text-white px-4 py-2 rounded-md transition duration-300 ml-2">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="nav-item text-white bg-ppu-blue hover:bg-ppu-light-blue px-4 py-2 rounded-md transition duration-300">
                                <i class="fas fa-sign-in-alt mr-2"></i>Login
                            </a>
                            <a href="register.php" class="nav-item text-ppu-blue border-2 border-ppu-blue hover:bg-ppu-blue hover:text-white px-4 py-2 rounded-md transition duration-300">
                                <i class="fas fa-user-plus mr-2"></i>Register
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button class="text-gray-500 hover:text-ppu-blue focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section flex items-center justify-center mt-20">
        <div class="text-center text-white px-4">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 leading-tight">Welcome to Pan Pacific University Library</h1>
            <p class="text-xl md:text-2xl">Your Gateway to Knowledge and Discovery</p>
        </div>
    </div>

    <!-- Search Section -->
    <div class="bg-ppu-green py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center">
                <h2 class="text-4xl font-bold text-white mb-10">Library Search</h2>
                <div class="flex flex-wrap justify-center gap-4 mb-12">
                    <button class="action-button bg-white text-ppu-green px-6 md:px-8 py-3 rounded-lg transition duration-300 font-medium shadow-md">
                        <i class="fas fa-book mr-2"></i>Books
                    </button>
                    <button class="action-button bg-white text-ppu-green px-6 md:px-8 py-3 rounded-lg transition duration-300 font-medium shadow-md">
                        <i class="fas fa-database mr-2"></i>E-Resources
                    </button>
                    <button class="action-button bg-white text-ppu-green px-6 md:px-8 py-3 rounded-lg transition duration-300 font-medium shadow-md">
                        <i class="fas fa-newspaper mr-2"></i>Journals
                    </button>
                    <button class="action-button bg-white text-ppu-green px-6 md:px-8 py-3 rounded-lg transition duration-300 font-medium shadow-md">
                        <i class="fas fa-globe mr-2"></i>Digital Archive
                    </button>
                </div>
                <div class="max-w-4xl mx-auto px-4">
                    <h3 class="text-2xl text-white mb-6">Search Library Resources</h3>
                    <div class="flex shadow-lg rounded-lg overflow-hidden">
                        <input type="text" 
                               placeholder="Search books, journals, and more..." 
                               class="flex-1 px-6 py-4 text-lg focus:outline-none"
                               aria-label="Search library resources">
                        <button class="search-button text-white px-8 py-4 flex items-center justify-center hover:bg-ppu-light-blue transition-all duration-300">
                            <i class="fas fa-search text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messenger Chat Plugin -->
    <div class="fixed bottom-6 right-6 z-50">
        <div class="messenger-button bg-white rounded-full p-4 shadow-lg cursor-pointer hover:bg-gray-50">
            <i class="fab fa-facebook-messenger text-3xl text-ppu-blue"></i>
        </div>
    </div>

    <!-- Mobile Menu (Hidden by default) -->
    <div class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 z-40">
        <div class="bg-white h-full w-64 p-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-ppu-blue">Menu</h2>
                <button class="text-gray-500 hover:text-ppu-blue">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div class="flex flex-col space-y-2">
                <a href="#" class="py-2 text-gray-800 hover:text-ppu-blue">About</a>
                <a href="#" class="py-2 text-gray-800 hover:text-ppu-blue">Policies</a>
                <a href="#" class="py-2 text-gray-800 hover:text-ppu-blue">Request</a>
                <a href="#" class="py-2 text-gray-800 hover:text-ppu-blue">Search</a>
                <a href="#" class="py-2 text-gray-800 hover:text-ppu-blue">Inquire</a>
                <a href="#" class="py-2 text-gray-800 hover:text-ppu-blue">Publications</a>
                <div class="pt-4 border-t border-gray-200">
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="dashboard.php" class="block py-2 text-ppu-blue hover:text-ppu-light-blue">
                            <i class="fas fa-user mr-2"></i>Dashboard
                        </a>
                        <a href="logout.php" class="block py-2 text-ppu-blue hover:text-ppu-light-blue">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="block py-2 text-ppu-blue hover:text-ppu-light-blue">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                        <a href="register.php" class="block py-2 text-ppu-blue hover:text-ppu-light-blue">
                            <i class="fas fa-user-plus mr-2"></i>Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
