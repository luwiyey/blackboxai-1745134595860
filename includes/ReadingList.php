<?php
class ReadingList {
    private $db;
    private $logger;
    private static $instance = null;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function create($userId, $name, $description = '', $isPublic = false) {
        try {
            $data = [
                'user_id' => $userId,
                'name' => $name,
                'description' => $description,
                'is_public' => $isPublic ? 1 : 0
            ];

            $listId = $this->db->insert('reading_lists', $data);

            $this->logger->logActivity(
                $userId,
                'reading_list_created',
                "Created reading list: $name"
            );

            return $listId;
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'create_reading_list_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function addBook($listId, $userId, $bookId) {
        try {
            // Check if user owns the list
            if (!$this->isOwner($listId, $userId)) {
                throw new Exception("Unauthorized access to reading list");
            }

            // Check if book already exists in list
            if ($this->hasBook($listId, $bookId)) {
                throw new Exception("Book already exists in reading list");
            }

            $data = [
                'list_id' => $listId,
                'book_id' => $bookId,
                'added_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('reading_list_books', $data);

            $this->logger->logActivity(
                $userId,
                'book_added_to_list',
                "Added book to reading list: $listId"
            );

            return true;
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'add_book_to_list_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function removeBook($listId, $userId, $bookId) {
        try {
            if (!$this->isOwner($listId, $userId)) {
                throw new Exception("Unauthorized access to reading list");
            }

            return $this->db->delete(
                'reading_list_books',
                'list_id = ? AND book_id = ?',
                [$listId, $bookId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'remove_book_from_list_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function getList($listId, $userId = null) {
        try {
            $list = $this->db->fetchOne(
                "SELECT rl.*, u.name as creator_name 
                FROM reading_lists rl 
                JOIN users u ON rl.user_id = u.id 
                WHERE rl.id = ?",
                [$listId]
            );

            if (!$list) {
                return null;
            }

            // Check if user has access
            if (!$list['is_public'] && $list['user_id'] !== $userId) {
                throw new Exception("Unauthorized access to reading list");
            }

            // Get books in list
            $list['books'] = $this->getBooks($listId);

            return $list;
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'get_reading_list_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function getUserLists($userId) {
        try {
            return $this->db->fetchAll(
                "SELECT rl.*, 
                    (SELECT COUNT(*) FROM reading_list_books WHERE list_id = rl.id) as book_count 
                FROM reading_lists rl 
                WHERE rl.user_id = ? 
                ORDER BY rl.created_at DESC",
                [$userId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'get_user_lists_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function getPublicLists($limit = 10, $offset = 0) {
        try {
            return $this->db->fetchAll(
                "SELECT rl.*, u.name as creator_name,
                    (SELECT COUNT(*) FROM reading_list_books WHERE list_id = rl.id) as book_count 
                FROM reading_lists rl 
                JOIN users u ON rl.user_id = u.id 
                WHERE rl.is_public = 1 
                ORDER BY rl.created_at DESC 
                LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                null,
                'get_public_lists_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function update($listId, $userId, $data) {
        try {
            if (!$this->isOwner($listId, $userId)) {
                throw new Exception("Unauthorized access to reading list");
            }

            $allowedFields = ['name', 'description', 'is_public'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                throw new Exception("No valid fields to update");
            }

            return $this->db->update(
                'reading_lists',
                $updateData,
                'id = ?',
                [$listId]
            );
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'update_reading_list_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function delete($listId, $userId) {
        try {
            if (!$this->isOwner($listId, $userId)) {
                throw new Exception("Unauthorized access to reading list");
            }

            $this->db->beginTransaction();

            // Delete books from list
            $this->db->delete('reading_list_books', 'list_id = ?', [$listId]);

            // Delete list
            $this->db->delete('reading_lists', 'id = ?', [$listId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->logError(
                $userId,
                'delete_reading_list_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    public function share($listId, $userId, $targetUserId) {
        try {
            if (!$this->isOwner($listId, $userId)) {
                throw new Exception("Unauthorized access to reading list");
            }

            // Get list details
            $list = $this->getList($listId, $userId);
            
            // Create notification
            $notification = Notification::getInstance();
            $notification->send(
                $targetUserId,
                'reading_list_shared',
                "User shared reading list: {$list['name']}",
                "/reading-list.php?id=$listId"
            );

            return true;
        } catch (Exception $e) {
            $this->logger->logError(
                $userId,
                'share_reading_list_error',
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    private function isOwner($listId, $userId) {
        return $this->db->exists(
            'reading_lists',
            'id = ? AND user_id = ?',
            [$listId, $userId]
        );
    }

    private function hasBook($listId, $bookId) {
        return $this->db->exists(
            'reading_list_books',
            'list_id = ? AND book_id = ?',
            [$listId, $bookId]
        );
    }

    private function getBooks($listId) {
        return $this->db->fetchAll(
            "SELECT b.*, rlb.added_at 
            FROM reading_list_books rlb 
            JOIN books b ON rlb.book_id = b.id 
            WHERE rlb.list_id = ? 
            ORDER BY rlb.added_at DESC",
            [$listId]
        );
    }

    private function __clone() {}
    private function __wakeup() {}
}
?>
