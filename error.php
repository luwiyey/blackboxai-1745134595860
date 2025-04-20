<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - <?php echo SITE_NAME; ?></title>
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
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Oops! Something went wrong</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        We apologize for the inconvenience. An unexpected error has occurred.
                    </p>
                </div>
            </div>

            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <p class="text-center">
                    The system encountered an error while processing your request.<br>
                    Please try again later or contact support if the problem persists.
                </p>
            </div>

            <div class="flex flex-col space-y-4">
                <a href="javascript:history.back()" 
                   class="group relative flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-ppu-blue hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue transition duration-300">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-arrow-left"></i>
                    </span>
                    Go Back
                </a>

                <a href="index.php" 
                   class="group relative flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-ppu-blue bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue transition duration-300 border-ppu-blue">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-home"></i>
                    </span>
                    Return to Homepage
                </a>

                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                <div class="mt-4 p-4 bg-gray-100 rounded-md">
                    <h3 class="text-lg font-semibold mb-2">Error Details</h3>
                    <div class="text-sm text-gray-600">
                        <?php 
                        $error = error_get_last();
                        if ($error): 
                        ?>
                            <p><strong>Type:</strong> <?php echo $error['type']; ?></p>
                            <p><strong>Message:</strong> <?php echo $error['message']; ?></p>
                            <p><strong>File:</strong> <?php echo $error['file']; ?></p>
                            <p><strong>Line:</strong> <?php echo $error['line']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Need help? <a href="contact.php" class="font-medium text-ppu-blue hover:text-ppu-light-blue">Contact Support</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
    <script>
        // Log error to console for admin users
        console.error('Error Details:', <?php echo json_encode(error_get_last()); ?>);
    </script>
    <?php endif; ?>
</body>
</html>
