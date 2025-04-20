<?php
require_once __DIR__ . '/../includes/Autoloader.php';

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

    // Select database
    $pdo->exec("USE " . DB_NAME);

    // Load schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);
    echo "Schema loaded successfully\n";

    // Insert default data
    $defaultData = [
        // Admin user
        "INSERT INTO users (name, email, password, role, status, email_verified) VALUES (
            'System Admin',
            'admin@panpacific.edu.ph',
            '" . password_hash('admin123', PASSWORD_BCRYPT) . "',
            'admin',
            'active',
            1
        ) ON DUPLICATE KEY UPDATE email = email",

        // Sample book categories
        "INSERT INTO books (isbn, title, author, publisher, publication_year, category, description, total_copies, available_copies) VALUES
        ('9780132350884', 'Clean Code: A Handbook of Agile Software Craftsmanship', 'Robert C. Martin', 'Prentice Hall', 2008, 'Computer Science', 'A book about writing cleaner code', 3, 3),
        ('9780134685991', 'Effective Java', 'Joshua Bloch', 'Addison-Wesley Professional', 2017, 'Computer Science', 'Best practices for Java programming', 2, 2),
        ('9780262033848', 'Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', 2009, 'Computer Science', 'Comprehensive coverage of algorithms', 4, 4),
        ('9780133594140', 'Computer Networks', 'Andrew S. Tanenbaum', 'Pearson', 2010, 'Computer Science', 'Principles of computer networking', 3, 3),
        ('9780321125217', 'Database System Concepts', 'Abraham Silberschatz', 'McGraw-Hill', 2010, 'Computer Science', 'Introduction to database systems', 3, 3),
        ('9780134093413', 'Computer Organization and Design', 'David A. Patterson', 'Morgan Kaufmann', 2013, 'Computer Science', 'Computer architecture fundamentals', 2, 2),
        ('9780321573513', 'Patterns of Enterprise Application Architecture', 'Martin Fowler', 'Addison-Wesley', 2002, 'Computer Science', 'Enterprise software patterns', 2, 2),
        ('9780201633610', 'Design Patterns', 'Erich Gamma', 'Addison-Wesley', 1994, 'Computer Science', 'Elements of Reusable Object-Oriented Software', 3, 3),
        ('9780321146533', 'Test Driven Development', 'Kent Beck', 'Addison-Wesley', 2002, 'Computer Science', 'Building better software through testing', 2, 2),
        ('9780321934116', 'Continuous Delivery', 'Jez Humble', 'Addison-Wesley', 2010, 'Computer Science', 'Software delivery automation', 2, 2)"
    ];

    foreach ($defaultData as $sql) {
        $pdo->exec($sql);
    }
    echo "Default data inserted successfully\n";

    // Create required directories
    $directories = [
        '../uploads',
        '../uploads/book_covers',
        '../uploads/pdf_previews',
        '../uploads/payment_proofs',
        '../uploads/profile_photos',
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
    $protectedDirs = ['../logs', '../cache'];

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

    echo "\nInitialization completed successfully!\n";
    echo "\nDefault admin credentials:\n";
    echo "Email: admin@panpacific.edu.ph\n";
    echo "Password: admin123\n";
    echo "\nPlease change these credentials immediately after first login.\n";

} catch (PDOException $e) {
    die("Database initialization error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

// Function to generate a secure random string
function generateSecureString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
?>
