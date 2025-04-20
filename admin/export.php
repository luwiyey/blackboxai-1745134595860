<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';

// Check if user is logged in and is an admin
Auth::requireRole('admin');

if (isset($_POST['export'])) {
    $dbHost = DB_HOST;
    $dbUser = DB_USER;
    $dbPass = DB_PASS;
    $dbName = DB_NAME;

    $filename = 'library_db_backup_' . date('Ymd_His') . '.sql';

    // Command to export database using mysqldump
    $command = "mysqldump --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} > /tmp/{$filename}";

    exec($command, $output, $returnVar);

    if ($returnVar === 0 && file_exists("/tmp/{$filename}")) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=' . basename($filename));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize("/tmp/{$filename}"));
        readfile("/tmp/{$filename}");
        unlink("/tmp/{$filename}");
        exit;
    } else {
        $error = "Failed to export database.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Database Export - PPU Library Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
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
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-ppu-blue">PPU Library Admin</h1>
                </div>
                <div class="flex items-center">
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto mt-10 bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold mb-6">Database Export</h2>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="export.php">
            <button type="submit" name="export"
                    class="bg-ppu-blue text-white px-6 py-2 rounded-md hover:bg-ppu-light-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppu-blue">
                Export Database
            </button>
        </form>
    </div>
</body>
</html>
