<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all categories
        $result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $categories]);
        break;

    case 'POST':
        // Create new category
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        if (!$name) {
            echo json_encode(['success' => false, 'message' => 'Category name is required']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category created']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create category']);
        }
        break;

    case 'PUT':
        // Update category
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        if (!$id || !$name) {
            echo json_encode(['success' => false, 'message' => 'Category ID and name are required']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update category']);
        }
        break;

    case 'DELETE':
        // Delete category
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Category ID is required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
