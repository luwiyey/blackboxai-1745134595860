<?php
class SampleData {
    public function up($conn) {
        // Sample departments
        $departments = [
            'College of Engineering',
            'College of Business',
            'College of Education',
            'College of Arts and Sciences',
            'College of Information Technology'
        ];

        // Sample users (faculty and students)
        $users = [
            // Faculty members
            [
                'name' => 'Dr. Juan Dela Cruz',
                'email' => 'jdelacruz@panpacificu.edu.ph',
                'password' => password_hash('faculty123', PASSWORD_DEFAULT),
                'role' => 'faculty',
                'department' => 'College of Engineering',
                'student_id' => null
            ],
            [
                'name' => 'Prof. Maria Santos',
                'email' => 'msantos@panpacificu.edu.ph',
                'password' => password_hash('faculty123', PASSWORD_DEFAULT),
                'role' => 'faculty',
                'department' => 'College of Business',
                'student_id' => null
            ],
            // Students
            [
                'name' => 'Pedro Penduko',
                'email' => 'ppenduko@student.panpacificu.edu.ph',
                'password' => password_hash('student123', PASSWORD_DEFAULT),
                'role' => 'student',
                'department' => 'College of Engineering',
                'student_id' => '2023-0001'
            ],
            [
                'name' => 'Juan Luna',
                'email' => 'jluna@student.panpacificu.edu.ph',
                'password' => password_hash('student123', PASSWORD_DEFAULT),
                'role' => 'student',
                'department' => 'College of Arts and Sciences',
                'student_id' => '2023-0002'
            ]
        ];

        // Sample books
        $books = [
            [
                'isbn' => '9780134685991',
                'title' => 'Engineering Mathematics',
                'author' => 'John Bird',
                'publisher' => 'Routledge',
                'publication_year' => 2021,
                'category' => 'Engineering',
                'description' => 'Comprehensive guide to engineering mathematics',
                'price' => 1500.00,
                'total_copies' => 5
            ],
            [
                'isbn' => '9780133594140',
                'title' => 'Database Systems',
                'author' => 'Thomas Connolly',
                'publisher' => 'Pearson',
                'publication_year' => 2020,
                'category' => 'Information Technology',
                'description' => 'Complete guide to database management systems',
                'price' => 1200.00,
                'total_copies' => 3
            ],
            [
                'isbn' => '9780134601540',
                'title' => 'Financial Management',
                'author' => 'Eugene Brigham',
                'publisher' => 'Cengage',
                'publication_year' => 2019,
                'category' => 'Business',
                'description' => 'Principles of financial management',
                'price' => 1300.00,
                'total_copies' => 4
            ]
        ];

        // Insert users
        foreach ($users as $user) {
            $query = "INSERT INTO users (
                name, 
                email, 
                password, 
                role, 
                department,
                student_id,
                status,
                email_verified_at
            ) VALUES (
                '{$user['name']}',
                '{$user['email']}',
                '{$user['password']}',
                '{$user['role']}',
                '{$user['department']}',
                " . ($user['student_id'] ? "'{$user['student_id']}'" : "NULL") . ",
                'active',
                CURRENT_TIMESTAMP
            )";

            if (!$conn->query($query)) {
                throw new Exception("Error inserting user {$user['name']}: " . $conn->error);
            }
        }

        // Insert books
        foreach ($books as $book) {
            $query = "INSERT INTO books (
                isbn,
                title,
                author,
                publisher,
                publication_year,
                category,
                description,
                price,
                total_copies,
                available_copies,
                status
            ) VALUES (
                '{$book['isbn']}',
                '{$book['title']}',
                '{$book['author']}',
                '{$book['publisher']}',
                {$book['publication_year']},
                '{$book['category']}',
                '{$book['description']}',
                {$book['price']},
                {$book['total_copies']},
                {$book['total_copies']},
                'available'
            )";

            if (!$conn->query($query)) {
                throw new Exception("Error inserting book {$book['title']}: " . $conn->error);
            }
        }

        // Create sample reading lists
        $query = "INSERT INTO course_reading_lists (
            title,
            description,
            faculty_id,
            course_code,
            semester
        ) VALUES (
            'Engineering Mathematics References',
            'Essential references for Engineering Mathematics course',
            (SELECT id FROM users WHERE email = 'jdelacruz@panpacificu.edu.ph'),
            'MATH101',
            '2023-2024-1'
        )";

        if (!$conn->query($query)) {
            throw new Exception("Error creating reading list: " . $conn->error);
        }

        // Add books to reading list
        $readingListId = $conn->insert_id;
        $query = "INSERT INTO reading_list_items (
            reading_list_id,
            book_id,
            order_number
        ) VALUES (
            {$readingListId},
            (SELECT id FROM books WHERE isbn = '9780134685991'),
            1
        )";

        if (!$conn->query($query)) {
            throw new Exception("Error adding book to reading list: " . $conn->error);
        }

        // Create sample book review
        $query = "INSERT INTO book_reviews (
            book_id,
            user_id,
            rating,
            review,
            status
        ) VALUES (
            (SELECT id FROM books WHERE isbn = '9780134685991'),
            (SELECT id FROM users WHERE email = 'ppenduko@student.panpacificu.edu.ph'),
            5,
            'Excellent book for engineering students. Very comprehensive and easy to understand.',
            'approved'
        )";

        if (!$conn->query($query)) {
            throw new Exception("Error creating book review: " . $conn->error);
        }
    }

    public function down($conn) {
        // Remove sample data in reverse order of dependencies
        $tables = [
            'book_reviews',
            'reading_list_items',
            'course_reading_lists',
            'books',
            'users'
        ];

        foreach ($tables as $table) {
            // Skip admin user when deleting users
            $where = $table === 'users' ? "WHERE role != 'admin'" : "";
            $query = "DELETE FROM {$table} {$where}";
            
            if (!$conn->query($query)) {
                throw new Exception("Error removing data from {$table}: " . $conn->error);
            }
        }
    }
}
