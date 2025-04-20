<?php
require_once 'config/config.php';
require_once 'db.php';

$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';
$error = null;
$success = null;
$valid_token = false;
$user_id = null;

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $valid_token = true;
        $user_id = $result->fetch_assoc()['id'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];
    
    // Validate password
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user_id);
        
        if ($update->execute()) {
            $success = "Your password has been reset successfully. You can now login with your new password.";
            
            // Log the password reset
            log_activity($user_id, 'password_reset_completed', 'Password reset completed successfully');
            
            // Clear any existing sessions
            session_destroy();
        } else {
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg">
            <div>
                <div class="header flex items-center justify-center mb-6">
                    <img src="photos/PUlogo.png" alt="University Logo" class="w-20 h-20 mr-4">
                    <div>
                        <h1 class="text-2xl font-bold text-ppu-blue">PANPACIFIC</h1>
                        <h1 class="text-2xl font-bold text-ppu-green">UNIVERSITY</h1>
                    </div>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Reset your password</h2>
            </div>

            <?php if (!$valid_token && !$success): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">Invalid or expired reset link. Please request a new password reset.</span>
                </div>
                <div class="mt-6 text-center">
                    <a href="forgot-password.php" class="text-ppu-blue hover:text-ppu-light-blue">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Password Reset
                    </a>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-ppu-blue hover:text-ppu-light-blue">
                        <i class="fas fa-sign-in-alt mr-2"></i>Proceed to Login
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($valid_token && !$success): ?>
                <form class="mt-8 space-y-6" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?token=" . $token); ?>">
                    <div class="rounded-md shadow-sm -space-y-px">
                        <div>
                            <label for="password" class="sr-only">New Password</label>
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm" 
                                   placeholder="New Password">
                        </div>
                        <div>
                            <label for="confirm-password" class="sr-only">Confirm New Password</label>
                            <input id="confirm-password" name="confirm-password" type="password" required 
                                   class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm" 
                                   placeholder="Confirm New Password">
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-ppu-blue hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue transition duration-300">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-key"></i>
                            </span>
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add password strength indicator
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm-password');

        if (password && confirmPassword) {
            password.addEventListener('input', function() {
                // Add password strength validation logic here
                const value = this.value;
                const hasUpperCase = /[A-Z]/.test(value);
                const hasLowerCase = /[a-z]/.test(value);
                const hasNumbers = /\d/.test(value);
                const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(value);
                const isLongEnough = value.length >= 8;

                // You can add visual indicators based on password strength
            });

            confirmPassword.addEventListener('input', function() {
                if (this.value !== password.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    </script>
</body>
</html>
