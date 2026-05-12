<?php
// Include the database connection
require_once './config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handle the request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Check for comments action
        if (isset($_GET['action']) && $_GET['action'] === 'comments' && isset($_GET['resource_id'])) {
            getComments($db, $_GET['resource_id']);
        }
        // Check for single resource by ID
        elseif (isset($_GET['id'])) {
            getResourceById($db, $_GET['id']);
        }
        // Get all resources
        else {
            getAllResources($db);
        }
        break;
        
    case 'POST':
        // Check if it's a comment action
        if (isset($_GET['action']) && $_GET['action'] === 'comment') {
            addComment($db, json_decode(file_get_contents('php://input'), true));
        }
        // Otherwise create a resource
        else {
            createResource($db, json_decode(file_get_contents('php://input'), true));
        }
        break;
        
    case 'PUT':
        updateResource($db, json_decode(file_get_contents('php://input'), true));
        break;
        
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteResource($db, $_GET['id']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing resource ID']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}

// Functions for CRUD operations

function getAllResources($db) {
    try {
        $query = "SELECT id, title, description, link FROM resources ORDER BY id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $resources]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getResourceById($db, $id) {
    try {
        $query = "SELECT id, title, description, link FROM resources WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $resource = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resource) {
            echo json_encode(['success' => true, 'data' => $resource]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Resource not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function createResource($db, $data) {
    try {
        // Validate required fields
        if (!isset($data['title']) || !isset($data['description']) || !isset($data['link'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
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
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Resource created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create resource']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateResource($db, $data) {
    try {
        // Validate required fields
        if (!isset($data['id']) || !isset($data['title']) || !isset($data['description']) || !isset($data['link'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        $query = "UPDATE resources SET title = :title, description = :description, link = :link WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':link', $data['link']);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Resource updated']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Resource not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update resource']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteResource($db, $id) {
    try {
        $query = "DELETE FROM resources WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Resource deleted']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Resource not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete resource']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getComments($db, $resourceId) {
    try {
        $query = "SELECT id, resource_id, author, text, created_at FROM comments WHERE resource_id = :resource_id ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $comments]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function addComment($db, $data) {
    try {
        // Validate required fields
        if (!isset($data['resource_id']) || !isset($data['author']) || !isset($data['text'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        $query = "INSERT INTO comments (resource_id, author, text) VALUES (:resource_id, :author, :text)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':resource_id', $data['resource_id'], PDO::PARAM_INT);
        $stmt->bindParam(':author', $data['author']);
        $stmt->bindParam(':text', $data['text']);
        
        if ($stmt->execute()) {
            $newId = $db->lastInsertId();
            
            // Fetch the newly created comment to return it
            $fetchQuery = "SELECT id, resource_id, author, text, created_at FROM comments WHERE id = :id";
            $fetchStmt = $db->prepare($fetchQuery);
            $fetchStmt->bindParam(':id', $newId, PDO::PARAM_INT);
            $fetchStmt->execute();
            $comment = $fetchStmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(201);
            echo json_encode(['success' => true, 'comment' => $comment, 'message' => 'Comment added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
