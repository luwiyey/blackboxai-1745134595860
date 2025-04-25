<?php
class Recommendation {
    private $conn;
    private $tableLoans = 'loans';
    private $tableBooks = 'books';

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Get book recommendations for a user based on collaborative filtering
     * Suggest books based on:
     * - What other users read (borrowed)
     * - What the user has previously borrowed
     * - Most borrowed books in categories user likes
     */
    public function getRecommendationsForUser($userId, $limit = 10) {
        try {
            // Step 1: Get categories of books the user has borrowed
            $sql = "SELECT DISTINCT b.category
                    FROM {$this->tableLoans} l
                    JOIN {$this->tableBooks} b ON l.book_id = b.id
                    WHERE l.user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $userCategories = [];
            while ($row = $result->fetch_assoc()) {
                $userCategories[] = $row['category'];
            }

            if (empty($userCategories)) {
                // If no borrowing history, recommend most popular books overall
                return $this->getMostPopularBooks($limit);
            }

            // Step 2: Get books borrowed by other users who borrowed same categories
            $placeholders = implode(',', array_fill(0, count($userCategories), '?'));
            $types = str_repeat('s', count($userCategories));
            $params = $userCategories;

            $sql = "SELECT b.*, COUNT(l.id) as borrow_count
                    FROM {$this->tableLoans} l
                    JOIN {$this->tableBooks} b ON l.book_id = b.id
                    WHERE b.category IN ($placeholders)
                    AND l.user_id != ?
                    GROUP BY b.id
                    ORDER BY borrow_count DESC
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $bindParams = array_merge($params, [$userId, $limit]);
            $types .= "ii";
            $stmt->bind_param($types, ...$bindParams);
            $stmt->execute();
            $result = $stmt->get_result();
            $recommendations = $result->fetch_all(MYSQLI_ASSOC);

            if (count($recommendations) < $limit) {
                // Fill remaining with most popular books overall
                $remaining = $limit - count($recommendations);
                $popular = $this->getMostPopularBooks($remaining);
                $recommendations = array_merge($recommendations, $popular);
            }

            return $recommendations;
        } catch (Exception $e) {
            error_log("Error getting recommendations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get most popular books overall
     */
    private function getMostPopularBooks($limit) {
        try {
            $sql = "SELECT b.*, COUNT(l.id) as borrow_count
                    FROM {$this->tableBooks} b
                    LEFT JOIN {$this->tableLoans} l ON b.id = l.book_id
                    GROUP BY b.id
                    ORDER BY borrow_count DESC
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting most popular books: " . $e->getMessage());
            return [];
        }
    }
}
?>
