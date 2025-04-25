<?php
class Statistics {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Get total number of books
     */
    public function getTotalBooks() {
        try {
            $sql = "SELECT COUNT(*) as total FROM books";
            $result = $this->conn->query($sql);
            return (int)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting total books: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total number of users
     */
    public function getTotalUsers() {
        try {
            $sql = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
            $result = $this->conn->query($sql);
            return (int)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting total users: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get number of active loans
     */
    public function getActiveLoanCount() {
        try {
            $sql = "SELECT COUNT(*) as total FROM loans WHERE status = 'borrowed'";
            $result = $this->conn->query($sql);
            return (int)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting active loan count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get number of overdue loans
     */
    public function getOverdueLoanCount() {
        try {
            $sql = "SELECT COUNT(*) as total FROM loans 
                    WHERE status = 'borrowed' 
                    AND due_date < CURRENT_DATE";
            $result = $this->conn->query($sql);
            return (int)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting overdue loan count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total fines
     */
    public function getTotalFines() {
        try {
            $sql = "SELECT COALESCE(SUM(fine_amount), 0) as total FROM loans";
            $result = $this->conn->query($sql);
            return (float)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting total fines: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get collected fines
     */
    public function getCollectedFines() {
        try {
            $sql = "SELECT COALESCE(SUM(fine_paid), 0) as total FROM loans";
            $result = $this->conn->query($sql);
            return (float)$result->fetch_assoc()['total'];
        } catch (Exception $e) {
            error_log("Error getting collected fines: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get monthly loan count for the past 12 months
     */
    public function getMonthlyLoanCount() {
        try {
            $sql = "SELECT DATE_FORMAT(borrow_date, '%Y-%m') as month,
                           COUNT(*) as count
                    FROM loans
                    WHERE borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY month
                    ORDER BY month ASC";
            
            $result = $this->conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['month']] = (int)$row['count'];
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getting monthly loan count: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get monthly registration count for the past 12 months
     */
    public function getMonthlyRegistrationCount() {
        try {
            $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                           COUNT(*) as count
                    FROM users
                    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY month
                    ORDER BY month ASC";
            
            $result = $this->conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['month']] = (int)$row['count'];
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getting monthly registration count: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get most popular books
     */
    public function getPopularBooks($limit = 10) {
        try {
            $sql = "SELECT b.*, COUNT(l.id) as borrow_count
                    FROM books b
                    LEFT JOIN loans l ON b.id = l.book_id
                    GROUP BY b.id
                    ORDER BY borrow_count DESC
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting popular books: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity($limit = 10) {
        try {
            $sql = "SELECT al.*, u.name as user_name
                    FROM activity_logs al
                    JOIN users u ON al.user_id = u.id
                    ORDER BY al.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                $activities[] = [
                    'description' => $this->formatActivityDescription($row),
                    'time_ago' => $this->getTimeAgo($row['created_at']),
                    'icon' => $this->getActivityIcon($row['action']),
                    'color' => $this->getActivityColor($row['action'])
                ];
            }
            return $activities;
        } catch (Exception $e) {
            error_log("Error getting recent activity: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Format activity description
     */
    private function formatActivityDescription($activity) {
        $user = $activity['user_name'];
        
        switch ($activity['action']) {
            case 'book_borrowed':
                return "{$user} borrowed a book";
            case 'book_returned':
                return "{$user} returned a book";
            case 'payment_recorded':
                return "{$user} made a payment";
            case 'user_registered':
                return "New user registration: {$user}";
            case 'book_added':
                return "New book added to the library";
            case 'fine_paid':
                return "{$user} paid their fines";
            default:
                return $activity['details'];
        }
    }

    /**
     * Get time ago in human readable format
     */
    private function getTimeAgo($timestamp) {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return "Just now";
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . " minute" . ($mins > 1 ? "s" : "") . " ago";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . " day" . ($days > 1 ? "s" : "") . " ago";
        } else {
            return date('M j, Y', $time);
        }
    }

    /**
     * Get icon for activity type
     */
    private function getActivityIcon($action) {
        switch ($action) {
            case 'book_borrowed':
                return 'hand-holding';
            case 'book_returned':
                return 'undo';
            case 'payment_recorded':
                return 'money-bill-wave';
            case 'user_registered':
                return 'user-plus';
            case 'book_added':
                return 'book';
            case 'fine_paid':
                return 'check-circle';
            default:
                return 'info-circle';
        }
    }

    /**
     * Get color for activity type
     */
    private function getActivityColor($action) {
        switch ($action) {
            case 'book_borrowed':
                return 'blue';
            case 'book_returned':
                return 'green';
            case 'payment_recorded':
                return 'purple';
            case 'user_registered':
                return 'indigo';
            case 'book_added':
                return 'yellow';
            case 'fine_paid':
                return 'green';
            default:
                return 'gray';
        }
    }

    /**
     * Get category distribution
     */
    public function getCategoryDistribution() {
        try {
            $sql = "SELECT category, COUNT(*) as count
                    FROM books
                    GROUP BY category
                    ORDER BY count DESC";
            
            $result = $this->conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['category']] = (int)$row['count'];
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getting category distribution: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user role distribution
     */
    public function getUserRoleDistribution() {
        try {
            $sql = "SELECT role, COUNT(*) as count
                    FROM users
                    WHERE status = 'active'
                    GROUP BY role";
            
            $result = $this->conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['role']] = (int)$row['count'];
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getting user role distribution: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get daily fine collection for the past month
     */
    public function getDailyFineCollection() {
        try {
            $sql = "SELECT DATE(created_at) as date,
                           SUM(amount) as total
                    FROM payments
                    WHERE status = 'verified'
                    AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)
                    GROUP BY date
                    ORDER BY date ASC";
            
            $result = $this->conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['date']] = (float)$row['total'];
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getting daily fine collection: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get average loan duration
     */
    public function getAverageLoanDuration() {
        try {
            $sql = "SELECT AVG(DATEDIFF(COALESCE(return_date, CURRENT_DATE), borrow_date)) as avg_days
                    FROM loans
                    WHERE status IN ('returned', 'borrowed')";
            
            $result = $this->conn->query($sql);
            return round((float)$result->fetch_assoc()['avg_days'], 1);
        } catch (Exception $e) {
            error_log("Error getting average loan duration: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get borrowing behavior by user role for the past 12 months
     */
    public function getBorrowingBehaviorByUserRole() {
        try {
            $sql = "SELECT u.role, DATE_FORMAT(l.borrow_date, '%Y-%m') as month, COUNT(*) as count
                    FROM loans l
                    JOIN users u ON l.user_id = u.id
                    WHERE l.borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY u.role, month
                    ORDER BY month ASC";
            
            $result = $this->conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['role']][$row['month']] = (int)$row['count'];
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getting borrowing behavior by user role: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get reading engagement by course/class for the past 12 months
     */
    public function getReadingEngagementByCourse() {
        try {
            $sql = "SELECT u.course, DATE_FORMAT(l.borrow_date, '%Y-%m') as month, COUNT(*) as count
                    FROM loans l
                    JOIN users u ON l.user_id = u.id
                    WHERE l.borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY u.course, month
                    ORDER BY month ASC";
            
            $result = $this->conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['course']][$row['month']] = (int)$row['count'];
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getting reading engagement by course: " . $e->getMessage());
            return [];
        }
    /**
     * Get borrowing trends by department for the past 12 months
     */
    public function getBorrowingTrendsByDepartment() {
        try {
            $sql = "SELECT u.department, DATE_FORMAT(l.borrow_date, '%Y-%m') as month, COUNT(*) as count
                    FROM loans l
                    JOIN users u ON l.user_id = u.id
                    WHERE l.borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY u.department, month
                    ORDER BY month ASC";
            
            $result = $this->conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['department']][$row['month']] = (int)$row['count'];
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getting borrowing trends by department: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get reading engagement by department for the past 12 months
     */
    public function getReadingEngagementByDepartment() {
        try {
            $sql = "SELECT u.department, DATE_FORMAT(l.borrow_date, '%Y-%m') as month, COUNT(*) as count
                    FROM loans l
                    JOIN users u ON l.user_id = u.id
                    WHERE l.borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY u.department, month
                    ORDER BY month ASC";
            
            $result = $this->conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['department']][$row['month']] = (int)$row['count'];
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getting reading engagement by department: " . $e->getMessage());
            return [];
        }
    }
}
}
