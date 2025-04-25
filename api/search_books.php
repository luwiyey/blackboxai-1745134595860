<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$tags = isset($_GET['tags']) ? explode(',', $_GET['tags']) : [];

$params = [];
$types = '';
$where = [];
$joinTags = false;

if ($search) {
    $where[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= 'ssss';
}

if ($category) {
    $where[] = "b.category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($tags)) {
    $joinTags = true;
    $placeholders = implode(',', array_fill(0, count($tags), '?'));
    $where[] = "bt.tag_id IN ($placeholders)";
    $params = array_merge($params, $tags);
    $types .= str_repeat('i', count($tags));
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT DISTINCT b.* FROM books b ";

if ($joinTags) {
    $sql .= "JOIN book_tags bt ON b.id = bt.book_id ";
}

$sql .= "$whereClause ORDER BY b.title ASC LIMIT 50";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$books = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $books]);
?>
