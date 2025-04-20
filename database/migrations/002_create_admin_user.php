<?php
class CreateAdminUser {
    public function up($conn) {
        // Generate a secure password hash for 'admin123' (this should be changed after first login)
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (
            name, 
            email, 
            password, 
            role, 
            status, 
            email_verified_at,
            department
        ) VALUES (
            'System Administrator',
            'admin@panpacificu.edu.ph',
            '{$password}',
            'admin',
            'active',
            CURRENT_TIMESTAMP,
            'Library'
        )";

        if (!$conn->query($query)) {
            throw new Exception("Error creating admin user: " . $conn->error);
        }

        // Log the admin user creation
        $adminId = $conn->insert_id;
        $logQuery = "INSERT INTO activity_logs (
            user_id, 
            action, 
            details,
            ip_address
        ) VALUES (
            {$adminId},
            'user_created',
            'Initial admin user created during system setup',
            'system'
        )";

        if (!$conn->query($logQuery)) {
            throw new Exception("Error logging admin creation: " . $conn->error);
        }
    }

    public function down($conn) {
        // Remove the admin user
        $query = "DELETE FROM users WHERE email = 'admin@panpacificu.edu.ph'";
        if (!$conn->query($query)) {
            throw new Exception("Error removing admin user: " . $conn->error);
        }
    }
}
