<?php
// Site Configuration
define('SITE_NAME', 'PanPacific University Library');
define('SITE_URL', 'http://localhost:8000');
define('ADMIN_EMAIL', 'library@panpacific.edu.ph');
define('SUPPORT_EMAIL', 'support@panpacific.edu.ph');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_db');
define('DB_USER', 'library_user');
define('DB_PASS', 'your_secure_password');
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 30); // minutes
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_LIFETIME', 7200); // 2 hours
define('REMEMBER_ME_DURATION', 2592000); // 30 days
define('CSRF_LIFETIME', 7200); // 2 hours

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);

// Library Configuration
define('MAX_BOOKS_PER_USER', 5);
define('LOAN_DURATION', 14); // days
define('FINE_PER_DAY', 5.00); // ₱5 per day
define('RENEWAL_LIMIT', 2);
define('RESERVATION_LIMIT', 3);
define('RESERVATION_DURATION', 2); // days

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'library@panpacific.edu.ph');
define('SMTP_PASSWORD', 'your_smtp_password');
define('SMTP_ENCRYPTION', 'tls');

// Pagination Configuration
define('ITEMS_PER_PAGE', 20);
define('PAGE_RANGE', 5);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour
define('CACHE_PATH', __DIR__ . '/../cache/');

// Logging Configuration
define('LOG_PATH', __DIR__ . '/../logs/');
define('ERROR_LOG_FILE', LOG_PATH . 'error.log');
define('ACCESS_LOG_FILE', LOG_PATH . 'access.log');
define('ACTIVITY_LOG_FILE', LOG_PATH . 'activity.log');

// API Configuration
define('API_VERSION', '1.0');
define('API_KEY_LIFETIME', 2592000); // 30 days
define('API_RATE_LIMIT', 100); // requests per minute

// Social Media Links
define('FACEBOOK_URL', 'https://facebook.com/panpacificuniversity');
define('TWITTER_URL', 'https://twitter.com/panpacificuni');
define('INSTAGRAM_URL', 'https://instagram.com/panpacificuni');
define('LINKEDIN_URL', 'https://linkedin.com/school/panpacificuniversity');

// Contact Information
define('LIBRARY_PHONE', '+63 (2) 8123-4567');
define('LIBRARY_ADDRESS', '123 University Avenue, Quezon City, Philippines');
define('LIBRARY_HOURS', '8:00 AM - 8:00 PM');

// Create required directories if they don't exist
$directories = [
    UPLOAD_PATH,
    UPLOAD_PATH . 'book_covers/',
    UPLOAD_PATH . 'pdf_previews/',
    UPLOAD_PATH . 'payment_proofs/',
    UPLOAD_PATH . 'profile_photos/',
    CACHE_PATH,
    LOG_PATH
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Initialize error logging
ini_set('error_log', ERROR_LOG_FILE);

// Set PHP configuration
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
ini_set('post_max_size', '10M');
ini_set('upload_max_filesize', '5M');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_limiter', 'nocache');

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Helper Functions
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function send_email($to, $subject, $body) {
    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom(SMTP_USERNAME, SITE_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}

function handle_file_upload($file, $allowed_extensions, $upload_path) {
    try {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file upload parameters');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File size exceeds limit');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('File was only partially uploaded');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file was uploaded');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception('Missing temporary folder');
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception('Failed to write file to disk');
            case UPLOAD_ERR_EXTENSION:
                throw new Exception('File upload stopped by extension');
            default:
                throw new Exception('Unknown upload error');
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds limit');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions)) {
            throw new Exception('Invalid file type');
        }

        $filename = generate_token(16) . '.' . $extension;
        $filepath = $upload_path . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file');
        }

        return [
            'success' => true,
            'file_name' => $filename,
            'file_path' => $filepath,
            'mime_type' => $mime_type
        ];
    } catch (Exception $e) {
        error_log("File upload error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function log_activity($user_id, $action, $details = '') {
    Logger::getInstance()->logActivity($user_id, $action, $details);
}
?>
