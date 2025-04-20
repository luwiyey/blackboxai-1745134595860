<?php
require_once 'config/config.php';
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? 1 : 0;
    $error = null;

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Check if account is locked
        if ($user['account_locked']) {
            $lockout_time = new DateTime($user['lockout_time']);
            $now = new DateTime();
            $diff = $now->diff($lockout_time);
            
            if ($diff->i < LOCKOUT_TIME) { // Still within lockout period
                $minutes_left = LOCKOUT_TIME - $diff->i;
                $error = "Account is locked. Please try again in {$minutes_left} minutes.";
            } else {
                // Reset lockout if lockout period has passed
                $reset = $conn->prepare("UPDATE users SET account_locked = 0, failed_login_attempts = 0 WHERE id = ?");
                $reset->bind_param("i", $user['id']);
                $reset->execute();
            }
        }

        // Proceed with login if account is not locked
        if (!$error) {
            if (!$user['email_verified']) {
                $error = "Please verify your email address before logging in.";
            } elseif ($user['status'] !== 'active') {
                $error = "Your account is not active. Please contact support.";
            } elseif (password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'student_id' => $user['student_id']
                ];

                // Reset failed login attempts
                $reset = $conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked = 0 WHERE id = ?");
                $reset->bind_param("i", $user['id']);
                $reset->execute();

                // Set remember me cookie if checked
                if ($remember_me) {
                    setcookie("user_email", $email, time() + (86400 * 30), "/"); // 30 days
                }

                // Log the successful login
                log_activity($user['id'], 'user_login', 'User logged in successfully');

                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        break;
                    case 'librarian':
                        header("Location: librarian/dashboard.php");
                        break;
                    case 'faculty':
                        header("Location: faculty/dashboard.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                exit();
            } else {
                // Failed login attempt
                $failed_attempts = $user['failed_login_attempts'] + 1;
                $update = $conn->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
                $update->bind_param("ii", $failed_attempts, $user['id']);
                $update->execute();

                if ($failed_attempts >= MAX_LOGIN_ATTEMPTS) {
                    $now = date('Y-m-d H:i:s');
                    $lock = $conn->prepare("UPDATE users SET account_locked = 1, lockout_time = ? WHERE id = ?");
                    $lock->bind_param("si", $now, $user['id']);
                    $lock->execute();
                    $error = "Too many failed attempts. Account locked for " . LOCKOUT_TIME . " minutes.";
                    
                    // Log the account lock
                    log_activity($user['id'], 'account_locked', 'Account locked due to too many failed login attempts');
                } else {
                    $remaining_attempts = MAX_LOGIN_ATTEMPTS - $failed_attempts;
                    $error = "Invalid password. {$remaining_attempts} attempts remaining.";
                }
            }
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
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
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Sign in to your account</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <a href="register.php" class="font-medium text-ppu-blue hover:text-ppu-light-blue">
                        create a new account
                    </a>
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm" 
                               placeholder="Email address"
                               value="<?php echo isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-ppu-blue focus:border-ppu-blue focus:z-10 sm:text-sm" 
                               placeholder="Password">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox" 
                               class="h-4 w-4 text-ppu-blue focus:ring-ppu-light-blue border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="forgot-password.php" class="font-medium text-ppu-blue hover:text-ppu-light-blue">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-ppu-blue hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue transition duration-300">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt"></i>
                        </span>
                        Sign in
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
                            Back to <a href="index.php" class="font-medium text-ppu-blue hover:text-ppu-light-blue">Home</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
