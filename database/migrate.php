<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Migration.php';

try {
    // Create database if it doesn't exist
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully\n";

    // Initialize database connection for migrations
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize migration manager
    $migration = new Migration($conn);

    // Get command line argument if provided
    $command = $argv[1] ?? 'migrate';

    switch ($command) {
        case 'migrate':
            echo "Running migrations...\n";
            $migration->migrate();
            echo "Migrations completed successfully.\n";
            break;

        case 'rollback':
            echo "Rolling back last batch of migrations...\n";
            $migration->rollback();
            echo "Rollback completed successfully.\n";
            break;

        case 'reset':
            echo "Resetting database...\n";
            $migration->reset();
            echo "Database reset completed successfully.\n";
            break;

        case 'refresh':
            echo "Refreshing database...\n";
            $migration->reset();
            $migration->migrate();
            echo "Database refresh completed successfully.\n";
            break;

        case 'init':
            echo "Initializing system...\n";
            
            // Create required directories
            $directories = [
                '../uploads',
                '../uploads/book_covers',
                '../uploads/pdf_previews',
                '../uploads/payment_proofs',
                '../uploads/profile_photos',
                '../uploads/academic_content',
                '../uploads/gcash_screenshots',
                '../logs',
                '../cache'
            ];

            foreach ($directories as $dir) {
                $path = __DIR__ . '/' . $dir;
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                    echo "Created directory: $dir\n";
                }
            }

            // Create .htaccess files to protect sensitive directories
            $htaccessContent = "Order deny,allow\nDeny from all";
            $protectedDirs = ['../logs', '../cache', '../uploads'];

            foreach ($protectedDirs as $dir) {
                $htaccessPath = __DIR__ . '/' . $dir . '/.htaccess';
                if (!file_exists($htaccessPath)) {
                    file_put_contents($htaccessPath, $htaccessContent);
                    echo "Created .htaccess in: $dir\n";
                }
            }

            // Create empty index.html files to prevent directory listing
            $indexContent = "<html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1><p>You don't have permission to access this resource.</p></body></html>";
            $allDirs = array_merge($directories, ['../includes', '../templates', '../config', '../database']);

            foreach ($allDirs as $dir) {
                $indexPath = __DIR__ . '/' . $dir . '/index.html';
                if (!file_exists($indexPath)) {
                    file_put_contents($indexPath, $indexContent);
                    echo "Created index.html in: $dir\n";
                }
            }

            // Create robots.txt
            $robotsContent = "User-agent: *\nDisallow: /includes/\nDisallow: /templates/\nDisallow: /config/\nDisallow: /database/\nDisallow: /logs/\nDisallow: /cache/\nDisallow: /uploads/\n";
            $robotsPath = __DIR__ . '/../robots.txt';
            if (!file_exists($robotsPath)) {
                file_put_contents($robotsPath, $robotsContent);
                echo "Created robots.txt\n";
            }

            // Initialize composer if not already done
            if (!file_exists(__DIR__ . '/../vendor')) {
                echo "Installing Composer dependencies...\n";
                exec('cd ' . __DIR__ . '/.. && composer install');
            }

            // Run migrations
            $migration->migrate();

            echo "\nSystem initialization completed successfully!\n";
            echo "\nDefault admin credentials:\n";
            echo "Email: admin@panpacificu.edu.ph\n";
            echo "Password: admin123\n";
            echo "\nPlease change these credentials immediately after first login.\n";
            break;

        default:
            echo "Unknown command: {$command}\n";
            echo "Available commands:\n";
            echo "  init      - Initialize the system (create directories, run migrations)\n";
            echo "  migrate   - Run pending migrations\n";
            echo "  rollback  - Rollback last batch of migrations\n";
            echo "  reset     - Reset database by rolling back all migrations\n";
            echo "  refresh   - Reset and re-run all migrations\n";
            break;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
