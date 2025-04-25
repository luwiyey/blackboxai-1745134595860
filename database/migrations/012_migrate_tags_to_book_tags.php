<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    // Fetch all books with tags stored as JSON
    $result = $conn->query("SELECT id, tags FROM books WHERE tags IS NOT NULL AND tags != '[]'");

    $insertStmt = $conn->prepare("INSERT INTO book_tags (book_id, tag_id) VALUES (?, ?)");

    while ($row = $result->fetch_assoc()) {
        $bookId = $row['id'];
        $tagsJson = $row['tags'];
        $tags = json_decode($tagsJson, true);

        if (is_array($tags)) {
            foreach ($tags as $tagId) {
                $insertStmt->bind_param("ii", $bookId, $tagId);
                $insertStmt->execute();
            }
        }
    }

    // Optionally, remove the tags column from books table after migration
    // $conn->query("ALTER TABLE books DROP COLUMN tags");

    echo "Tags migration to book_tags table completed successfully.\n";
} catch (Exception $e) {
    echo "Error during tags migration: " . $e->getMessage() . "\n";
}
?>
