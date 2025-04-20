<?php
require_once 'config/config.php';
require_once 'db.php';

$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';
$message = '';
$status = 'error';

if (empty($token)) {
    $message = 'Invalid verification link.';
} else {
    // Check if token exists and is valid
    $stmt = $conn->prepare("SELECT id, email_verified FROM users WHERE verification_token = ? AND status = 'pending'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['email_verified']) {
            $message = 'Your email has already been verified.';
            $status = 'info';
        } else {
            // Update user status
            $update = $conn->prepare("UPDATE users SET email_verified = 1, status = 'active', verification_token = NULL WHERE id = ?");
            $update->bind_param("i", $user['id']);
            
            if ($update->execute()) {
                $message = 'Your email has been verified successfully. You can now login to your account.';
                $status = 'success';
                
                // Log the verification
                log_activity($user['id'], 'email_verified', 'User email verified successfully');
            } else {
                $message = 'Error verifying email. Please try again later.';
            }
        }
    } else {
        $message = 'Invalid or expired verification link.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo SITE_NAME; ?></title>
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
                
                <?php if ($status === 'success'): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <p class="text-center"><?php echo $message; ?></p>
                    </div>
                <?php elseif ($status === 'info'): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                        <p class="text-center"><?php echo $message; ?></p>
                    </div>
                <?php else: ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <p class="text-center"><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-ppu-blue hover:text-ppu-light-blue">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
