<?php
session_start();
require_once 'includes/Autoloader.php';
spl_autoload_register([Autoloader::class, 'loadClass']);

use includes\User;
use includes\Validator;
use includes\Notification;

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $studentId = trim($_POST['student-id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Basic validation
    if (!$fullname) {
        $errors[] = 'Full Name is required.';
    }
    if (!$studentId) {
        $errors[] = 'Student ID is required.';
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid Email is required.';
    }
    if (!$password) {
        $errors[] = 'Password is required.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$role || !in_array($role, ['student', 'faculty', 'librarian', 'admin'])) {
        $errors[] = 'Please select a valid role.';
    }

    if (empty($errors)) {
        try {
            $user = User::getInstance();

            // Prepare user data with status pending
            $userData = [
                'name' => $fullname,
                'student_id' => $studentId,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'status' => 'pending' // Set status to pending for admin approval
            ];

            $userId = $user->create($userData);

            // Send verification email (assuming Notification class handles this)
            Notification::sendVerificationEmail($email);

            $success = 'Registration successful! Please check your email to verify your account. Your account will be activated after admin approval.';
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - Pan Pacific University Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="assets/style.css" />
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
        /* Gradient Background */
        body {
            background: linear-gradient(135deg, #417028, #263e79, white); /* Green to Blue to White */
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg">
            <div>
                <h1 class="text-2xl font-bold text-center text-ppu-blue mb-2">Pan Pacific University</h1>
                <p class="text-sm text-center text-ppu-green">University Library</p>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Create your account</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Already have an account?
                    <a href="login.html" class="font-medium text-ppu-blue hover:text-ppu-light-blue">
                        Sign in
                    </a>
                </p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <form class="mt-8 space-y-6" action="register.php" method="POST">
                <div class="rounded-md shadow-sm space-y-3">
                    <div>
                        <label for="fullname" class="sr-only">Full Name</label>
                        <input id="fullname" name="fullname" type="text" required
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm"
                               placeholder="Full Name" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" />
                    </div>
                    <div>
                        <label for="student-id" class="sr-only">Student ID</label>
                        <input id="student-id" name="student-id" type="text" required
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm"
                               placeholder="Student ID" value="<?php echo htmlspecialchars($_POST['student-id'] ?? ''); ?>" />
                    </div>
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" required
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm"
                               placeholder="Email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm"
                               placeholder="Password" />
                    </div>
                    <div>
                        <label for="confirm-password" class="sr-only">Confirm Password</label>
                        <input id="confirm-password" name="confirm-password" type="password" required
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm"
                               placeholder="Confirm Password" />
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Select Your Role</label>
                        <select id="role" name="role" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue sm:text-sm rounded-md">
                            <option value="" disabled <?php echo empty($_POST['role']) ? 'selected' : ''; ?>>Select your role</option>
                            <option value="student" <?php echo (($_POST['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="faculty" <?php echo (($_POST['role'] ?? '') === 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                            <option value="librarian" <?php echo (($_POST['role'] ?? '') === 'librarian') ? 'selected' : ''; ?>>Librarian</option>
                            <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                <div>
                    <button type="submit"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-ppu-blue hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                        Register
                    </button>
                </div>
            </form>
            <div class="text-center">
                <div class="flex items-center">
                    <hr class="flex-grow border-t border-gray-300" />
                    <p class="mx-4 text-sm text-gray-600">
                        <a href="index.html" class="font-medium text-ppu-blue hover:text-ppu-light-blue">
                            Back to Home
                        </a>
                    </p>
                    <hr class="flex-grow border-t border-gray-300" />
                </div>
            </div>
        </div>
    </div>
</body>
</html>
