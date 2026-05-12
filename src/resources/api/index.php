<?php
// Set response headers FIRST - before any output
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include the database connection
require_once './config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Handle the request method
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        // Handle comments request
        if ($action === 'comments' && isset($_GET['resource_id'])) {
            getComments($db, $_GET['resource_id']);
        }
        // Handle search
        elseif ($action === 'search' && isset($_GET['q'])) {
            searchResources($db, $_GET['q']);
        }
        // Handle single resource
        elseif (isset($_GET['id'])) {
            getResourceById($db, $_GET['id']);
        }
        // Get all resources
        else {
            getAllResources($db);
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        // Handle comment creation
        if ($action === 'comment') {
            addComment($db, $input);
        }
        // Handle resource creation
        else {
            createResource($db, $input);
        }
        break;
        
    case 'PUT':
        updateResource($db, json_decode(file_get_contents('php://input'), true));
        break;
        
    case 'DELETE':
        // Handle comment deletion
        if ($action === 'comment' && isset($_GET['id'])) {
            deleteComment($db, $_GET['id']);
        }
        // Handle resource deletion
        elseif (isset($_GET['id'])) {
            deleteResource($db, $_GET['id']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}

// Functions for CRUD operations

function getAllResources($db) {
    $query = "SELECT id, title, description, link FROM resources";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $resources]);
}

function searchResources($db, $q) {
    $query = "SELECT id, title, description, link FROM resources WHERE title LIKE :q OR description LIKE :q2";
    $searchTerm = "%$q%";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':q', $searchTerm);
    $stmt->bindParam(':q2', $searchTerm);
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $resources]);
}

function getResourceById($db, $id) {
    $query = "SELECT id, title, description, link FROM resources WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resource) {
        echo json_encode(['success' => true, 'data' => $resource]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Resource not found']);
    }
}

function createResource($db, $data) {
    // Validate required fields
    if (!isset($data['title']) || empty(trim($data['title']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }
    if (!isset($data['link']) || empty(trim($data['link']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Link is required']);
        return;
    }
    if (!filter_var($data['link'], FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
        return;
    }
    
    $query = "INSERT INTO resources (title, description, link) VALUES (:title, :description, :link)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':link', $data['link']);
    
    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
        http_response_code(201);
        echo json_encode(['success' => true, 'id' => $newId]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create resource']);
    }
}

function updateResource($db, $data) {
    // Validate required fields
    if (!isset($data['id']) || !isset($data['title']) || !isset($data['link'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    if (!filter_var($data['link'], FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
        return;
    }
    
    // Check if resource exists
    $checkQuery = "SELECT id FROM resources WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $data['id']);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Resource not found']);
        return;
    }
    
    $query = "UPDATE resources SET title = :title, description = :description, link = :link WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id']);
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':link', $data['link']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resource updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update resource']);
    }
}

function deleteResource($db, $id) {
    // Check if resource exists
    $checkQuery = "SELECT id FROM resources WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Resource not found']);
        return;
    }
    
    $query = "DELETE FROM resources WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resource deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete resource']);
    }
}

function getComments($db, $resourceId) {
    $query = "SELECT id, resource_id, author, text, created_at FROM comments WHERE resource_id = :resource_id ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':resource_id', $resourceId);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $comments]);
}

function addComment($db, $data) {
    // Validate required fields
    if (!isset($data['resource_id']) || !isset($data['author']) || !isset($data['text'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    if (empty(trim($data['text']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Comment text is required']);
        return;
    }
    
    // Check if resource exists
    $checkQuery = "SELECT id FROM resources WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $data['resource_id']);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Resource not found']);
        return;
    }
    
    $query = "INSERT INTO comments (resource_id, author, text) VALUES (:resource_id, :author, :text)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':resource_id', $data['resource_id']);
    $stmt->bindParam(':author', $data['author']);
    $stmt->bindParam(':text', $data['text']);
    
    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
        $fetchQuery = "SELECT id, resource_id, author, text, created_at FROM comments WHERE id = :id";
        $fetchStmt = $db->prepare($fetchQuery);
        $fetchStmt->bindParam(':id', $newId);
        $fetchStmt->execute();
        $comment = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        echo json_encode(['success' => true, 'comment' => $comment]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }
}

function deleteComment($db, $id) {
    // Check if comment exists
    $checkQuery = "SELECT id FROM comments WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        return;
    }
    
    $query = "DELETE FROM comments WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comment deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
    }
}
?>
