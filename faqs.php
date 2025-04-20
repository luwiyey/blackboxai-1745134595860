<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - Pan Pacific University Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .floating-home {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 50;
        }
        .faq-item.hidden {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="flex justify-between items-center py-1.5 px-4 border-b bg-white">
        <div>
            <a href="/" class="no-underline">
                <h1 class="text-[20px] font-bold text-blue-900 leading-tight">Pan Pacific<br>University</h1>
                <p class="text-gray-600 text-[12px]">University Library</p>
            </a>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/" class="text-gray-600 hover:text-blue-900 py-1 text-sm inline-block">
                <i class="fas fa-home mr-1"></i>
                Home
            </a>
            <a href="login.html" class="bg-blue-900 hover:bg-blue-800 text-white px-3 py-1.5 rounded text-sm flex items-center gap-1.5">
                <i class="fas fa-sign-in-alt text-[13px]"></i>
                <span>Login</span>
            </a>
            <a href="register.html" class="bg-white hover:bg-gray-50 text-blue-900 px-3 py-1.5 rounded text-sm flex items-center gap-1.5 border border-blue-900">
                <i class="fas fa-user-plus text-[13px]"></i>
                <span>Register</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold mb-8">Frequently Asked Questions</h2>

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <div class="mb-6">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search FAQs</label>
                    <div class="relative">
                        <input type="text" id="search" 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Type to search...">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button class="category-btn bg-blue-900 text-white px-4 py-2 rounded-md text-sm" data-category="all">
                        All
                    </button>
                    <button class="category-btn bg-white text-blue-900 px-4 py-2 rounded-md text-sm border border-blue-900" data-category="borrowing">
                        Borrowing
                    </button>
                    <button class="category-btn bg-white text-blue-900 px-4 py-2 rounded-md text-sm border border-blue-900" data-category="resources">
                        Resources
                    </button>
                    <button class="category-btn bg-white text-blue-900 px-4 py-2 rounded-md text-sm border border-blue-900" data-category="services">
                        Services
                    </button>
                    <button class="category-btn bg-white text-blue-900 px-4 py-2 rounded-md text-sm border border-blue-900" data-category="facilities">
                        Facilities
                    </button>
                </div>
            </div>

            <!-- FAQs -->
            <div class="space-y-4">
                <!-- Borrowing -->
                <div class="faq-item" data-category="borrowing">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-3">How many books can I borrow?</h3>
                        <p class="text-gray-600">Undergraduate students can borrow up to 5 books, while graduate students and faculty members can borrow up to 10 books at a time.</p>
                    </div>
                </div>

                <div class="faq-item" data-category="borrowing">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-3">What is the loan period for books?</h3>
                        <p class="text-gray-600">Regular books can be borrowed for 14 days, while reserved materials are limited to 3 days. Reference materials are for library use only.</p>
                    </div>
                </div>

                <!-- Resources -->
                <div class="faq-item" data-category="resources">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-3">How do I access e-journals?</h3>
                        <p class="text-gray-600">You can access e-journals through our digital library portal. Log in with your university credentials and browse or search for your desired journals.</p>
                    </div>
                </div>

                <div class="faq-item" data-category="resources">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-3">Can I access library resources from home?</h3>
                        <p class="text-gray-600">Yes, most digital resources are available remotely. Use your university login credentials to access them through our website.</p>
                    </div>
                </div>

                <!-- Services -->
                <div class="faq-item" data-category="services">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-3">Do you offer research assistance?</h3>
                        <p class="text-gray-600">Yes, our librarians provide research consultation services. You can schedule an appointment online or visit the reference desk during library hours.</p>
                    </div>
                </div>

                <div class="faq-item" data-category="services">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-3">How do I request an interlibrary loan?</h3>
                        <p class="text-gray-600">Fill out the interlibrary loan request form on our website. Processing typically takes 5-7 business days.</p>
                    </div>
                </div>

                <!-- Facilities -->
                <div class="faq-item" data-category="facilities">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-3">Are there study rooms available?</h3>
                        <p class="text-gray-600">Yes, we have individual and group study rooms. You can reserve them online up to 2 weeks in advance.</p>
                    </div>
                </div>

                <div class="faq-item" data-category="facilities">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-3">Is printing available in the library?</h3>
                        <p class="text-gray-600">Yes, we have self-service printing stations. You can print using your student ID card or purchase a printing card from the circulation desk.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Home Button -->
    <a href="/" class="floating-home bg-blue-900 text-white p-4 rounded-full shadow-lg hover:bg-blue-800 transition-colors">
        <i class="fas fa-home"></i>
    </a>

    <!-- JavaScript for Search and Filter -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search');
            const categoryButtons = document.querySelectorAll('.category-btn');
            const faqItems = document.querySelectorAll('.faq-item');

            // Search functionality
            searchInput.addEventListener('input', filterFAQs);

            // Category filter
            categoryButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Update button styles
                    categoryButtons.forEach(btn => {
                        btn.classList.remove('bg-blue-900', 'text-white');
                        btn.classList.add('bg-white', 'text-blue-900', 'border', 'border-blue-900');
                    });
                    button.classList.remove('bg-white', 'text-blue-900', 'border', 'border-blue-900');
                    button.classList.add('bg-blue-900', 'text-white');

                    filterFAQs();
                });
            });

            function filterFAQs() {
                const searchTerm = searchInput.value.toLowerCase();
                const activeCategory = document.querySelector('.category-btn.bg-blue-900').dataset.category;

                faqItems.forEach(item => {
                    const question = item.querySelector('h3').textContent.toLowerCase();
                    const answer = item.querySelector('p').textContent.toLowerCase();
                    const category = item.dataset.category;

                    const matchesSearch = question.includes(searchTerm) || answer.includes(searchTerm);
                    const matchesCategory = activeCategory === 'all' || category === activeCategory;

                    if (matchesSearch && matchesCategory) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>
