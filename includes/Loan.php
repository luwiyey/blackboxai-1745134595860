<?php
class Loan {
    private $conn;
    private $table = 'loans';
    private $finePerDay = 5.00; // Default fine amount per day

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Issue a new loan with borrowing limit check
     */
    public function issueLoan($data) {
        try {
            // Check if user has reached their loan limit
            $userLoanCount = $this->getUserActiveLoanCount($data['user_id']);
            $userRole = $this->getUserRole($data['user_id']);
            $maxLoans = $userRole === 'faculty' ? 10 : 5; // Updated limits: Faculty 10, others 5

            if ($userLoanCount >= $maxLoans) {
                throw new Exception("User has reached their maximum loan limit");
            }

            // Check if book is available
            $bookAvailable = $this->checkBookAvailability($data['book_id']);
            if (!$bookAvailable) {
                throw new Exception("Book is not available for loan");
            }

            // Start transaction
            $this->conn->begin_transaction();

            // Insert loan record
            $sql = "INSERT INTO {$this->table} (user_id, book_id, borrow_date, due_date, status) 
                    VALUES (?, ?, CURRENT_TIMESTAMP, ?, 'borrowed')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iis", $data['user_id'], $data['book_id'], $data['due_date']);
            $stmt->execute();

            // Update book availability
            $sql = "UPDATE books 
                    SET available_copies = available_copies - 1,
                        status = CASE WHEN available_copies - 1 = 0 THEN 'unavailable' ELSE status END
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $data['book_id']);
            $stmt->execute();

            // Log the activity
            $this->logActivity($data['user_id'], 'book_borrowed', "Book ID: {$data['book_id']} borrowed");

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error issuing loan: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Return a book
     */
    public function returnBook($loanId) {
        try {
            // Get loan details
            $loan = $this->getById($loanId);
            if (!$loan) {
                throw new Exception("Loan not found");
            }

            // Calculate fine if overdue
            $fine = 0;
            if (strtotime($loan['due_date']) < time()) {
                $daysOverdue = floor((time() - strtotime($loan['due_date'])) / (60 * 60 * 24));
                $fine = $daysOverdue * $this->finePerDay;
            }

            // Start transaction
            $this->conn->begin_transaction();

            // Update loan record
            $sql = "UPDATE {$this->table} 
                    SET status = 'returned', 
                        return_date = CURRENT_TIMESTAMP,
                        fine_amount = ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("di", $fine, $loanId);
            $stmt->execute();

            // Update book availability
            $sql = "UPDATE books 
                    SET available_copies = available_copies + 1,
                        status = 'available'
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $loan['book_id']);
            $stmt->execute();

            // Log the activity
            $this->logActivity($loan['user_id'], 'book_returned', "Book ID: {$loan['book_id']} returned");

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error returning book: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extend a loan
     */
    public function extendLoan($loanId, $newDueDate) {
        try {
            // Get loan details
            $loan = $this->getById($loanId);
            if (!$loan) {
                throw new Exception("Loan not found");
            }

            // Check if loan is already overdue
            if (strtotime($loan['due_date']) < time()) {
                throw new Exception("Cannot extend overdue loan");
            }

            // Update loan due date
            $sql = "UPDATE {$this->table} SET due_date = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $newDueDate, $loanId);
            $stmt->execute();

            // Log the activity
            $this->logActivity($loan['user_id'], 'loan_extended', "Loan ID: {$loanId} extended");

            return true;
        } catch (Exception $e) {
            error_log("Error extending loan: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a book as lost
     */
    public function markAsLost($loanId) {
        try {
            // Get loan details
            $loan = $this->getById($loanId);
            if (!$loan) {
                throw new Exception("Loan not found");
            }

            // Get book price
            $sql = "SELECT price FROM books WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $loan['book_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $book = $result->fetch_assoc();

            // Start transaction
            $this->conn->begin_transaction();

            // Update loan status and fine
            $sql = "UPDATE {$this->table} 
                    SET status = 'lost',
                        fine_amount = ?,
                        return_date = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("di", $book['price'], $loanId);
            $stmt->execute();

            // Update book total copies
            $sql = "UPDATE books 
                    SET total_copies = total_copies - 1
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $loan['book_id']);
            $stmt->execute();

            // Log the activity
            $this->logActivity($loan['user_id'], 'book_lost', "Book ID: {$loan['book_id']} marked as lost");

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error marking book as lost: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all loans with filtering and pagination
     */
    public function getAll($search = '', $status = '', $sort = 'borrow_date', $order = 'desc', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($search) {
                $where[] = "(b.title LIKE ? OR u.name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm]);
                $types .= "ss";
            }

            if ($status) {
                $where[] = "l.status = ?";
                $params[] = $status;
                $types .= "s";
            }

            $whereClause = implode(" AND ", $where);
            $orderBy = in_array($sort, ['borrow_date', 'due_date', 'return_date']) ? $sort : 'borrow_date';
            $orderDir = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

            $sql = "SELECT l.*, b.title as book_title, b.isbn, u.name as user_name, u.student_id
                    FROM {$this->table} l
                    JOIN books b ON l.book_id = b.id
                    JOIN users u ON l.user_id = u.id
                    WHERE {$whereClause}
                    ORDER BY l.{$orderBy} {$orderDir}
                    LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";

            $stmt = $this->conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting loans: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total number of loans (for pagination)
     */
    public function getTotal($search = '', $status = '') {
        try {
            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($search) {
                $where[] = "(b.title LIKE ? OR u.name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm]);
                $types .= "ss";
            }

            if ($status) {
                $where[] = "l.status = ?";
                $params[] = $status;
                $types .= "s";
            }

            $whereClause = implode(" AND ", $where);
            $sql = "SELECT COUNT(*) as total 
                    FROM {$this->table} l
                    JOIN books b ON l.book_id = b.id
                    JOIN users u ON l.user_id = u.id
                    WHERE {$whereClause}";
            
            $stmt = $this->conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return (int)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting total loans: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get a single loan by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT l.*, b.title as book_title, b.isbn, u.name as user_name, u.student_id
                    FROM {$this->table} l
                    JOIN books b ON l.book_id = b.id
                    JOIN users u ON l.user_id = u.id
                    WHERE l.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting loan by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user's active loan count
     */
    private function getUserActiveLoanCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM {$this->table} 
                    WHERE user_id = ? AND status = 'borrowed'";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            return (int)$result->fetch_assoc()['count'];
        } catch (Exception $e) {
            error_log("Error getting user loan count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user's role
     */
    private function getUserRole($userId) {
        try {
            $sql = "SELECT role FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc()['role'];
        } catch (Exception $e) {
            error_log("Error getting user role: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check book availability
     */
    private function checkBookAvailability($bookId) {
        try {
            $sql = "SELECT available_copies FROM books WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $bookId);
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
     * Log activity
     */
    private function logActivity($userId, $action, $details) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iss", $userId, $action, $details);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }

    /**
     * Get overdue loans
     */
    public function getOverdueLoans() {
        try {
            $sql = "SELECT l.*, b.title as book_title, b.isbn, u.name as user_name, u.email, u.student_id
                    FROM {$this->table} l
                    JOIN books b ON l.book_id = b.id
                    JOIN users u ON l.user_id = u.id
                    WHERE l.status = 'borrowed' 
                    AND l.due_date < CURRENT_DATE
                    ORDER BY l.due_date ASC";
            
            $result = $this->conn->query($sql);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting overdue loans: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate fines for overdue loans
     */
    public function calculateFines() {
        try {
            $sql = "UPDATE {$this->table}
                    SET fine_amount = DATEDIFF(CURRENT_DATE, due_date) * ?
                    WHERE status = 'borrowed'
                    AND due_date < CURRENT_DATE
                    AND fine_amount = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("d", $this->finePerDay);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error calculating fines: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's loan history
     */
    public function getUserLoanHistory($userId) {
        try {
            $sql = "SELECT l.*, b.title as book_title, b.isbn
                    FROM {$this->table} l
                    JOIN books b ON l.book_id = b.id
                    WHERE l.user_id = ?
                    ORDER BY l.borrow_date DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user loan history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get book's loan history
     */
    public function getBookLoanHistory($bookId) {
        try {
            $sql = "SELECT l.*, u.name as user_name, u.student_id
                    FROM {$this->table} l
                    JOIN users u ON l.user_id = u.id
                    WHERE l.book_id = ?
                    ORDER BY l.borrow_date DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting book loan history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get report data for loans
     */
    public function getReportData($startDate = null, $endDate = null) {
        try {
            $where = ["1=1"];
            $params = [];
            $types = "";

            if ($startDate) {
                $where[] = "l.borrow_date >= ?";
                $params[] = $startDate;
                $types .= "s";
            }

            if ($endDate) {
                $where[] = "l.borrow_date <= ?";
                $params[] = $endDate;
                $types .= "s";
            }

            $whereClause = implode(" AND ", $where);

            $sql = "SELECT l.*,
                    b.title as book_title,
                    b.isbn,
                    b.author,
                    u.name as user_name,
                    u.student_id,
                    DATEDIFF(COALESCE(l.return_date, CURRENT_DATE), l.due_date) as days_overdue,
                    CASE 
                        WHEN l.status = 'borrowed' AND l.due_date < CURRENT_DATE THEN 'overdue'
                        ELSE l.status 
                    END as current_status
                    FROM {$this->table} l
                    JOIN books b ON l.book_id = b.id
                    JOIN users u ON l.user_id = u.id
                    WHERE {$whereClause}
                    ORDER BY l.borrow_date DESC";

            $stmt = $this->conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting loan report data: " . $e->getMessage());
            return [];
        }
    }
}
