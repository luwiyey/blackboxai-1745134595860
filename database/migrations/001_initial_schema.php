<?php
class InitialSchema {
    public function up($conn) {
        $queries = [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                student_id VARCHAR(50) UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('student', 'faculty', 'librarian', 'admin', 'department_head') DEFAULT 'student',
                department VARCHAR(100),
                profile_picture VARCHAR(255),
                google_id VARCHAR(255) UNIQUE,
                gesture_hash VARCHAR(255),
                theme_preference VARCHAR(50) DEFAULT 'light',
                language_preference VARCHAR(10) DEFAULT 'en',
                status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
                email_verified_at TIMESTAMP NULL,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",

            // Books table
            "CREATE TABLE IF NOT EXISTS books (
                id INT AUTO_INCREMENT PRIMARY KEY,
                isbn VARCHAR(13) UNIQUE NOT NULL,
                title VARCHAR(255) NOT NULL,
                author VARCHAR(255) NOT NULL,
                publisher VARCHAR(255),
                publication_year YEAR,
                category VARCHAR(100) NOT NULL,
                tags TEXT,
                description TEXT,
                cover_image VARCHAR(255),
                pdf_preview VARCHAR(255),
                qr_code VARCHAR(255),
                total_copies INT NOT NULL DEFAULT 1,
                available_copies INT NOT NULL DEFAULT 1,
                price DECIMAL(10,2) NOT NULL,
                popularity_score FLOAT DEFAULT 0,
                average_rating FLOAT DEFAULT 0,
                total_ratings INT DEFAULT 0,
                status ENUM('available', 'unavailable') DEFAULT 'available',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FULLTEXT(title, author, tags, description)
            )",

            // Loans table
            "CREATE TABLE IF NOT EXISTS loans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                book_id INT NOT NULL,
                borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                due_date DATE NOT NULL,
                return_date TIMESTAMP NULL,
                status ENUM('borrowed', 'returned', 'overdue', 'lost') DEFAULT 'borrowed',
                fine_amount DECIMAL(10,2) DEFAULT 0.00,
                fine_paid DECIMAL(10,2) DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (book_id) REFERENCES books(id)
            )",

            // Payments table
            "CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reference_number VARCHAR(50) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                loan_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                method ENUM('cash', 'gcash', 'credit_card') NOT NULL,
                gcash_reference VARCHAR(100),
                gcash_screenshot VARCHAR(255),
                status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
                rejection_reason TEXT,
                verified_by INT,
                verified_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (loan_id) REFERENCES loans(id),
                FOREIGN KEY (verified_by) REFERENCES users(id)
            )",


            // Activity Logs table
            "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Book Reviews table
            "CREATE TABLE IF NOT EXISTS book_reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                book_id INT NOT NULL,
                user_id INT NOT NULL,
                rating INT CHECK (rating BETWEEN 1 AND 5),
                review TEXT,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (book_id) REFERENCES books(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Course Reading Lists table
            "CREATE TABLE IF NOT EXISTS course_reading_lists (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                faculty_id INT NOT NULL,
                course_code VARCHAR(50),
                semester VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (faculty_id) REFERENCES users(id)
            )",

            // Reading List Items table
            "CREATE TABLE IF NOT EXISTS reading_list_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reading_list_id INT NOT NULL,
                book_id INT NOT NULL,
                notes TEXT,
                order_number INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reading_list_id) REFERENCES course_reading_lists(id),
                FOREIGN KEY (book_id) REFERENCES books(id)
            )",

            // Book Recommendations table
            "CREATE TABLE IF NOT EXISTS book_recommendations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                faculty_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                author VARCHAR(255),
                isbn VARCHAR(13),
                reason TEXT,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                reviewed_by INT,
                review_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (faculty_id) REFERENCES users(id),
                FOREIGN KEY (reviewed_by) REFERENCES users(id)
            )",

            // System Settings table
            "CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                description TEXT,
                updated_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (updated_by) REFERENCES users(id)
            )",

            // Notifications table
            "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT,
                read_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Search Logs table
            "CREATE TABLE IF NOT EXISTS search_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                query TEXT,
                filters TEXT,
                results_count INT,
                voice_search BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Password Resets table
            "CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            // Email Verifications table
            "CREATE TABLE IF NOT EXISTS email_verifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Captcha Attempts table
            "CREATE TABLE IF NOT EXISTS captcha_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                attempt_count INT DEFAULT 1,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",

            // Voice Commands table
            "CREATE TABLE IF NOT EXISTS voice_commands (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                command TEXT NOT NULL,
                recognized_text TEXT,
                action_taken VARCHAR(100),
                success BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Academic Content table
            "CREATE TABLE IF NOT EXISTS academic_content (
                id INT AUTO_INCREMENT PRIMARY KEY,
                faculty_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                file_path VARCHAR(255),
                content_type ENUM('pdf', 'link', 'other') NOT NULL,
                course_code VARCHAR(50),
                semester VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (faculty_id) REFERENCES users(id)
            )",

            // Student Engagement table
            "CREATE TABLE IF NOT EXISTS student_engagement (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                book_id INT NOT NULL,
                reading_list_id INT,
                engagement_type ENUM('view', 'download', 'preview', 'borrow') NOT NULL,
                duration_seconds INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES users(id),
                FOREIGN KEY (book_id) REFERENCES books(id),
                FOREIGN KEY (reading_list_id) REFERENCES course_reading_lists(id)
            )",

            // Department Analytics table
            "CREATE TABLE IF NOT EXISTS department_analytics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                department VARCHAR(100) NOT NULL,
                total_books INT DEFAULT 0,
                total_loans INT DEFAULT 0,
                active_loans INT DEFAULT 0,
                overdue_loans INT DEFAULT 0,
                total_students INT DEFAULT 0,
                total_faculty INT DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",

            // AI Recommendations table
            "CREATE TABLE IF NOT EXISTS ai_recommendations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                book_id INT NOT NULL,
                score FLOAT NOT NULL,
                reason TEXT,
                model_version VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (book_id) REFERENCES books(id)
            )"
        ];

        // Execute all CREATE TABLE queries first
        foreach ($queries as $query) {
            if (!$conn->query($query)) {
                throw new Exception("Error executing query: " . $conn->error);
            }
        }

        // Insert initial system settings
        $settingsQuery = "INSERT INTO system_settings (setting_key, setting_value, description) VALUES
            ('library_name', 'Pan Pacific University Library', 'Name of the library'),
            ('library_email', 'library@panpacificu.edu.ph', 'Library contact email'),
            ('fine_per_day', '5.00', 'Fine amount per day for overdue books (in PHP)'),
            ('loan_duration_student', '14', 'Default loan duration for students (in days)'),
            ('loan_duration_faculty', '30', 'Default loan duration for faculty (in days)'),
            ('max_loans_student', '3', 'Maximum number of active loans for students'),
            ('max_loans_faculty', '5', 'Maximum number of active loans for faculty'),
            ('enable_gcash', 'true', 'Enable GCash payments'),
            ('enable_voice_commands', 'true', 'Enable voice command features'),
            ('enable_ai_recommendations', 'true', 'Enable AI-based book recommendations'),
            ('enable_gesture_login', 'true', 'Enable gesture-based login'),
            ('enable_google_login', 'true', 'Enable Google OAuth login'),
            ('reminder_days_before', '3', 'Days before due date to send reminder'),
            ('library_hours', '8:00 AM - 8:00 PM', 'Library operating hours'),
            ('maintenance_mode', 'false', 'System maintenance mode'),
            ('theme_primary_color', '#800000', 'Primary theme color (Maroon)'),
            ('theme_secondary_color', '#FFD700', 'Secondary theme color (Gold)'),
            ('enable_dark_mode', 'true', 'Enable dark mode option'),
            ('enable_filipino_lang', 'true', 'Enable Filipino language option'),
            ('max_login_attempts', '5', 'Maximum failed login attempts before captcha'),
            ('session_timeout', '120', 'Session timeout in minutes'),
            ('pdf_preview_pages', '5', 'Number of pages allowed in PDF preview'),
            ('enable_sms_notifications', 'false', 'Enable SMS notifications via email-to-text'),
            ('backup_retention_days', '30', 'Number of days to retain backups')";

        if (!$conn->query($settingsQuery)) {
            throw new Exception("Error inserting system settings: " . $conn->error);
        }
    }

    public function down($conn) {
        $tables = [
            'email_verifications',
            'password_resets',
            'ai_recommendations',
            'student_engagement',
            'academic_content',
            'voice_commands',
            'captcha_attempts',
            'search_logs',
            'notifications',
            'activity_logs',
            'reading_list_items',
            'course_reading_lists',
            'book_reviews',
            'book_recommendations',
            'payments',
            'loans',
            'books',
            'department_analytics',
            'system_settings',
            'users'
        ];

        foreach ($tables as $table) {
            $query = "DROP TABLE IF EXISTS {$table}";
            if (!$conn->query($query)) {
                throw new Exception("Error dropping table {$table}: " . $conn->error);
            }
        }
    }
}
