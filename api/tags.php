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
        // Get all tags
        $result = $conn->query("SELECT * FROM tags ORDER BY name ASC");
        $tags = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $tags]);
        break;

    case 'POST':
        // Create new tag
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        if (!$name) {
            echo json_encode(['success' => false, 'message' => 'Tag name is required']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tag created']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create tag']);
        }
        break;

    case 'PUT':
        // Update tag
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        if (!$id || !$name) {
            echo json_encode(['success' => false, 'message' => 'Tag ID and name are required']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE tags SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tag updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update tag']);
        }
        break;

    case 'DELETE':
        // Delete tag
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Tag ID is required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tag deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete tag']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
