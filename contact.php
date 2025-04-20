<?php
require_once 'includes/Database.php';
require_once 'includes/Validator.php';
require_once 'includes/Notification.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $department = $_POST['department'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($department) || empty($message)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Here you would typically save to database and/or send email
        // For now, just show success message
        $success_message = "Thank you for your message. We will get back to you soon.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Pan Pacific University Library</title>
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
        <div class="max-w-2xl mx-auto">
            <h2 class="text-3xl font-bold mb-8">Contact Us</h2>
            
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="grid md:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">Visit Us</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt text-blue-900 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium">Address</p>
                                <p class="text-gray-600">123 University Avenue<br>Quezon City, Philippines</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-clock text-blue-900 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium">Library Hours</p>
                                <p class="text-gray-600">Monday-Friday: 8:00 AM - 8:00 PM<br>Saturday: 8:00 AM - 5:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">Contact Information</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <i class="fas fa-phone text-blue-900 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium">Phone</p>
                                <p class="text-gray-600">+63 (2) 8123-4567</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-envelope text-blue-900 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium">Email</p>
                                <p class="text-gray-600">library@panpacificu.edu.ph</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-comments text-blue-900 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium">Live Chat</p>
                                <p class="text-gray-600">Available during library hours</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-semibold mb-6">Send Us a Message</h3>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select id="department" name="department" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a department</option>
                            <option value="general">General Inquiry</option>
                            <option value="circulation">Circulation</option>
                            <option value="reference">Reference</option>
                            <option value="technical">Technical Services</option>
                            <option value="admin">Administration</option>
                        </select>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea id="message" name="message" rows="5" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full bg-blue-900 text-white px-6 py-3 rounded-md hover:bg-blue-800 transition-colors">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Floating Home Button -->
    <a href="/" class="floating-home bg-blue-900 text-white p-4 rounded-full shadow-lg hover:bg-blue-800 transition-colors">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>
