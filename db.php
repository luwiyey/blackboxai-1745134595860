<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'library_db';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create users table with enhanced fields
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    student_id VARCHAR(50),
    role ENUM('student', 'faculty', 'librarian', 'admin') DEFAULT 'student',
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_token_expiry DATETIME,
    failed_login_attempts INT(1) DEFAULT 0,
    account_locked TINYINT(1) DEFAULT 0,
    lockout_time DATETIME,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Create books table
$sql_books = "CREATE TABLE IF NOT EXISTS books (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(13) UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    publisher VARCHAR(255),
    publication_year INT(4),
    edition VARCHAR(50),
    category VARCHAR(100),
    subject VARCHAR(100),
    description TEXT,
    cover_image VARCHAR(255),
    pdf_preview VARCHAR(255),
    total_copies INT(11) DEFAULT 1,
    available_copies INT(11) DEFAULT 1,
    location VARCHAR(100),
    added_by INT(11),
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id)
)";

// Create loans table
$sql_loans = "CREATE TABLE IF NOT EXISTS loans (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    book_id INT(11),
    user_id INT(11),
    borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    due_date DATETIME,
    return_date DATETIME,
    status ENUM('borrowed', 'returned', 'overdue', 'lost') DEFAULT 'borrowed',
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    fine_paid BOOLEAN DEFAULT FALSE,
    fine_payment_ref VARCHAR(50),
    fine_payment_screenshot VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

// Create book_reviews table
$sql_reviews = "CREATE TABLE IF NOT EXISTS book_reviews (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    book_id INT(11),
    user_id INT(11),
    rating INT(1),
    review TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

// Create reading_lists table
$sql_reading_lists = "CREATE TABLE IF NOT EXISTS reading_lists (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    faculty_id INT(11),
    course_code VARCHAR(50),
    semester VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(id)
)";

// Create reading_list_books table (junction table)
$sql_reading_list_books = "CREATE TABLE IF NOT EXISTS reading_list_books (
    reading_list_id INT(11),
    book_id INT(11),
    order_number INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reading_list_id, book_id),
    FOREIGN KEY (reading_list_id) REFERENCES reading_lists(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
)";

// Create payments table for GCash integration
$sql_payments = "CREATE TABLE IF NOT EXISTS payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11),
    loan_id INT(11),
    amount DECIMAL(10,2) NOT NULL,
    payment_type ENUM('fine', 'lost_book', 'other') DEFAULT 'fine',
    reference_number VARCHAR(50) UNIQUE,
    gcash_screenshot VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT(11),
    verification_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (loan_id) REFERENCES loans(id),
    FOREIGN KEY (verified_by) REFERENCES users(id)
)";

// Execute all table creation queries
$tables = [
    'users' => $sql_users,
    'books' => $sql_books,
    'loans' => $sql_loans,
    'book_reviews' => $sql_reviews,
    'reading_lists' => $sql_reading_lists,
    'reading_list_books' => $sql_reading_list_books,
    'payments' => $sql_payments
];

foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql) === FALSE) {
        die("Error creating table $table_name: " . $conn->error);
    }
}

// Create default admin user if not exists
$admin_email = 'admin@library.com';
$admin_password = password_hash('admin123', PASSWORD_BCRYPT);

$check_admin = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_admin->bind_param("s", $admin_email);
$check_admin->execute();
$result = $check_admin->get_result();

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, email_verified) VALUES (?, ?, ?, 'admin', 'active', TRUE)");
    $admin_name = 'System Admin';
    $stmt->bind_param("sss", $admin_name, $admin_email, $admin_password);
    $stmt->execute();
}

echo "Database setup completed successfully!";
?>
