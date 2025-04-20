<?php
require_once 'config/config.php';
require_once 'db.php';
require_once 'templates/emails/password_reset.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $error = null;
    $success = null;

    if (!is_valid_email($email)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if user exists and is verified
        $stmt = $conn->prepare("SELECT id, name, email_verified, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if (!$user['email_verified']) {
                $error = "Please verify your email address first.";
            } elseif ($user['status'] !== 'active') {
                $error = "Your account is not active. Please contact support.";
            } else {
                // Generate reset token
                $token = generate_token();
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save reset token
                $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                $update->bind_param("ssi", $token, $expiry, $user['id']);

                if ($update->execute()) {
                    // Send reset email
                    $email_body = get_password_reset_email($user['name'], $token);
                    if (send_email($email, "Reset your password", $email_body)) {
                        $success = "Password reset instructions have been sent to your email.";
                        
                        // Log the password reset request
                        log_activity($user['id'], 'password_reset_requested', 'Password reset requested');
                    } else {
                        $error = "Failed to send reset email. Please try again later.";
                    }
                } else {
                    $error = "An error occurred. Please try again later.";
                }
            }
        } else {
            // Show success message even if email doesn't exist (security best practice)
            $success = "If an account exists with this email, password reset instructions have been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
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
                <p class="mt-2 text-center text-sm text-gray-600">
                    Enter your email address and we'll send you instructions to reset your password.
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div>
                    <label for="email" class="sr-only">Email address</label>
                    <input id="email" name="email" type="email" required 
                           class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm" 
                           placeholder="Email address">
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-ppu-blue hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue transition duration-300">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-key"></i>
                        </span>
                        Send Reset Link
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            <a href="login.php" class="font-medium text-ppu-blue hover:text-ppu-light-blue">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Login
                            </a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
