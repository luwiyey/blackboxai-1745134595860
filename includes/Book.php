<?php
class Book {
    private $conn;
    private $table = 'books';

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Add tags for a book (replace existing tags)
     */
    public function setTags($bookId, array $tags) {
        try {
            // Delete existing tags
            $sqlDelete = "DELETE FROM book_tags WHERE book_id = ?";
            $stmtDelete = $this->conn->prepare($sqlDelete);
            $stmtDelete->bind_param("i", $bookId);
            $stmtDelete->execute();

            // Insert new tags
            $sqlInsert = "INSERT INTO book_tags (book_id, tag_id) VALUES (?, ?)";
            $stmtInsert = $this->conn->prepare($sqlInsert);

            foreach ($tags as $tagId) {
                $stmtInsert->bind_param("ii", $bookId, $tagId);
                $stmtInsert->execute();
            }
            return true;
        } catch (Exception $e) {
            error_log("Error setting book tags: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Update cover image path for a book
     */
    public function updateCover($bookId, $coverPath) {
        try {
            $sql = "UPDATE {$this->table} SET cover = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $coverPath, $bookId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating book cover: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cover image path for a book
     */
    public function getCover($bookId) {
        try {
            $sql = "SELECT cover FROM {$this->table} WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row ? $row['cover'] : null;
        } catch (Exception $e) {
            error_log("Error getting book cover: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update PDF snippet for a book
     */
    public function updatePdfSnippet($bookId, $snippet) {
        try {
            $sql = "UPDATE {$this->table} SET pdf_snippet = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $snippet, $bookId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating PDF snippet: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get PDF snippet for a book
     */
    public function getPdfSnippet($bookId) {
        try {
            $sql = "SELECT pdf_snippet FROM {$this->table} WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row ? $row['pdf_snippet'] : null;
        } catch (Exception $e) {
            error_log("Error getting PDF snippet: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Add a book review
     */
    public function addReview($bookId, $userId, $rating, $review) {
        try {
            $sql = "INSERT INTO book_reviews (book_id, user_id, rating, review) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiis", $bookId, $userId, $rating, $review);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error adding book review: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reviews for a book
     */
    public function getReviews($bookId) {
        try {
            $sql = "SELECT br.*, u.name as user_name FROM book_reviews br JOIN users u ON br.user_id = u.id WHERE br.book_id = ? ORDER BY br.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting book reviews: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update reading status for a user and book
     */
    public function updateReadingStatus($userId, $bookId, $status) {
        try {
            $sql = "INSERT INTO user_books (user_id, book_id, reading_status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reading_status = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiss", $userId, $bookId, $status, $status);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating reading status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reading status for a user and book
     */
    public function getReadingStatus($userId, $bookId) {
        try {
            $sql = "SELECT reading_status FROM user_books WHERE user_id = ? AND book_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $bookId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row ? $row['reading_status'] : null;
        } catch (Exception $e) {
            error_log("Error getting reading status: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Add a new book
     */
    public function add($data) {
        try {
            $sql = "INSERT INTO {$this->table} (isbn, title, author, publisher, publication_year, 
                    category, description, total_copies, available_copies, price, shelf_code, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssids",
                $data['isbn'],
                $data['title'],
                $data['author'],
                $data['publisher'],
                $data['publication_year'],
                $data['category'],
                $data['description'],
                $data['total_copies'],
                $data['total_copies'], // Initially, available copies equals total copies
                $data['price'],
                $data['shelf_code'] ?? null
            );

            $result = $stmt->execute();
            if ($result && isset($data['tags'])) {
                $bookId = $this->conn->insert_id;
                $this->setTags($bookId, $data['tags']);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Error adding book: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing book
     */
    public function update($id, $data) {
        try {
            // Get current book data
            $currentBook = $this->getById($id);
            if (!$currentBook) {
                return false;
            }

            // Calculate new available copies
            $copiesDiff = $data['total_copies'] - $currentBook['total_copies'];
            $newAvailableCopies = $currentBook['available_copies'] + $copiesDiff;

            $sql = "UPDATE {$this->table} 
                    SET isbn = ?, title = ?, author = ?, publisher = ?, 
                        publication_year = ?, category = ?, description = ?, 
                        total_copies = ?, available_copies = ?, price = ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sssssssidi",
                $data['isbn'],
                $data['title'],
                $data['author'],
                $data['publisher'],
                $data['publication_year'],
                $data['category'],
                $data['description'],
                $data['total_copies'],
                $newAvailableCopies,
                $data['price'],
                $id
            );

            $result = $stmt->execute();
            if ($result && isset($data['tags'])) {
                $this->setTags($id, $data['tags']);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Error updating book: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a book
     */
    public function delete($id) {
        try {
            // Check if book has any active loans
            $sql = "SELECT COUNT(*) as active_loans FROM loans 
                    WHERE book_id = ? AND status = 'borrowed'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $activeLoans = $result->fetch_assoc()['active_loans'];

            if ($activeLoans > 0) {
                return false; // Can't delete book with active loans
            }

            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error deleting book: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all books with filtering and pagination
     */
    public function getAll($search = '', $category = '', $sort = 'title', $order = 'asc', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($search) {
                $where[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                $types .= "sss";
            }

            if ($category) {
                $where[] = "category = ?";
                $params[] = $category;
                $types .= "s";
            }

            $whereClause = implode(" AND ", $where);
            $orderBy = in_array($sort, ['title', 'author', 'publication_year', 'available_copies']) ? $sort : 'title';
            $orderDir = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

            $sql = "SELECT * FROM {$this->table} 
                    WHERE {$whereClause} 
                    ORDER BY {$orderBy} {$orderDir} 
                    LIMIT ? OFFSET ?";

            $stmt = $this->conn->prepare($sql);
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";

            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting books: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total number of books (for pagination)
     */
    public function getTotal($search = '', $category = '') {
        try {
            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($search) {
                $where[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                $types .= "sss";
            }

            if ($category) {
                $where[] = "category = ?";
                $params[] = $category;
                $types .= "s";
            }

            $whereClause = implode(" AND ", $where);
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
            
            $stmt = $this->conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return (int)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting total books: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get a single book by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting book by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all unique categories
     */
    public function getAllCategories() {
        try {
            $sql = "SELECT DISTINCT category FROM {$this->table} ORDER BY category";
            $result = $this->conn->query($sql);
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row['category'];
            }
            return $categories;
        } catch (Exception $e) {
            error_log("Error getting categories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a book is available for loan
     */
    public function isAvailable($id) {
        try {
            $sql = "SELECT available_copies FROM {$this->table} WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $book = $result->fetch_assoc();
            return $book && $book['available_copies'] > 0;
        } catch (Exception $e) {
            error_log("Error checking book availability: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update book availability when borrowed
     */
    public function updateAvailability($id, $increment = false) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET available_copies = available_copies " . ($increment ? "+ 1" : "- 1") . ",
                        status = CASE 
                            WHEN available_copies " . ($increment ? "+ 1" : "- 1") . " > 0 THEN 'available'
                            ELSE 'unavailable'
                        END
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating book availability: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search books by various criteria
     */
    public function search($query, $filters = []) {
        try {
            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($query) {
                $where[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ? OR description LIKE ?)";
                $searchTerm = "%{$query}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                $types .= "ssss";
            }

            if (isset($filters['category']) && $filters['category']) {
                $where[] = "category = ?";
                $params[] = $filters['category'];
                $types .= "s";
            }

            if (isset($filters['publication_year']) && $filters['publication_year']) {
                $where[] = "publication_year = ?";
                $params[] = $filters['publication_year'];
                $types .= "s";
            }

            if (isset($filters['available']) && $filters['available']) {
                $where[] = "available_copies > 0";
            }

            $whereClause = implode(" AND ", $where);
            $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} ORDER BY title ASC";
            
            $stmt = $this->conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error searching books: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get book loan history
     */
    public function getLoanHistory($id) {
        try {
            $sql = "SELECT l.*, u.name as user_name, u.student_id
                    FROM loans l
                    JOIN users u ON l.user_id = u.id
                    WHERE l.book_id = ?
                    ORDER BY l.borrow_date DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting book loan history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get similar books based on category
     */
    public function getSimilarBooks($id, $limit = 5) {
        try {
            $sql = "SELECT b2.* 
                    FROM {$this->table} b1 
                    JOIN {$this->table} b2 ON b1.category = b2.category 
                    WHERE b1.id = ? AND b2.id != ? 
                    ORDER BY RAND() 
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $id, $id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting similar books: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get report data for books
     */
    public function getReportData() {
        try {
            $sql = "SELECT b.*,
                    (SELECT COUNT(*) FROM loans l WHERE l.book_id = b.id) as borrow_count,
                    (SELECT COUNT(*) FROM loans l WHERE l.book_id = b.id AND l.status = 'borrowed') as active_loans,
                    (SELECT COUNT(*) FROM loans l WHERE l.book_id = b.id AND l.status = 'overdue') as overdue_loans
                    FROM {$this->table} b
                    ORDER BY b.title ASC";
            
            $result = $this->conn->query($sql);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting book report data: " . $e->getMessage());
            return [];
        }
    }
}
