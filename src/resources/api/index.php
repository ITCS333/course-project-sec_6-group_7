<?php
// Include the database connection
require_once './config/Database.php';

// Set response headers FIRST, before any output
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Handle the request method
$method = $_SERVER['REQUEST_METHOD'];

// Parse the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        // Handle different GET actions
        if ($action === 'comments' && isset($_GET['resource_id'])) {
            getComments($db, $_GET['resource_id']);
        }
        elseif ($action === 'search' && isset($_GET['q'])) {
            searchResources($db, $_GET['q']);
        }
        elseif (isset($_GET['id'])) {
            getResourceById($db, $_GET['id']);
        }
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
        $input = json_decode(file_get_contents('php://input'), true);
        updateResource($db, $input);
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
        else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing resource ID']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        break;
}

/**
 * Get all resources
 */
function getAllResources($db) {
    try {
        $query = "SELECT id, title, description, link FROM resources ORDER BY id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $resources]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get a single resource by ID
 */
function getResourceById($db, $id) {
    try {
        $query = "SELECT id, title, description, link FROM resources WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $resource = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resource) {
            http_response_code(200);
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

/**
 * Search resources by title or description
 */
function searchResources($db, $query) {
    try {
        $searchQuery = "%" . $query . "%";
        $sql = "SELECT id, title, description, link FROM resources 
                WHERE title LIKE :query OR description LIKE :query2 
                ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':query', $searchQuery);
        $stmt->bindParam(':query2', $searchQuery);
        $stmt->execute();
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $resources]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Create a new resource
 */
function createResource($db, $data) {
    try {
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
        
        // Validate URL format
        if (!filter_var($data['link'], FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
            return;
        }
        
        $title = trim($data['title']);
        $description = isset($data['description']) ? trim($data['description']) : '';
        $link = trim($data['link']);
        
        $query = "INSERT INTO resources (title, description, link) VALUES (:title, :description, :link)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':link', $link);
        
        if ($stmt->execute()) {
            $newId = $db->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'id' => $newId, 
                'message' => 'Resource created successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create resource']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update an existing resource
 */
function updateResource($db, $data) {
    try {
        // Validate required fields
        if (!isset($data['id']) || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Resource ID is required']);
            return;
        }
        
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
        
        // Validate URL format
        if (!filter_var($data['link'], FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
            return;
        }
        
        $id = $data['id'];
        $title = trim($data['title']);
        $description = isset($data['description']) ? trim($data['description']) : '';
        $link = trim($data['link']);
        
        // Check if resource exists
        $checkQuery = "SELECT id FROM resources WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Resource not found']);
            return;
        }
        
        $query = "UPDATE resources SET title = :title, description = :description, link = :link WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':link', $link);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Resource updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update resource']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete a resource
 */
function deleteResource($db, $id) {
    try {
        // Check if resource exists
        $checkQuery = "SELECT id FROM resources WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Resource not found']);
            return;
        }
        
        $query = "DELETE FROM resources WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Resource deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete resource']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get comments for a resource
 */
function getComments($db, $resourceId) {
    try {
        // Check if resource exists
        $checkQuery = "SELECT id FROM resources WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $resourceId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Resource not found']);
            return;
        }
        
        $query = "SELECT id, resource_id, author, text, created_at 
                  FROM comments 
                  WHERE resource_id = :resource_id 
                  ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $comments]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Add a comment to a resource
 */
function addComment($db, $data) {
    try {
        // Validate required fields
        if (!isset($data['resource_id']) || empty($data['resource_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Resource ID is required']);
            return;
        }
        
        if (!isset($data['text']) || empty(trim($data['text']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Comment text is required']);
            return;
        }
        
        if (!isset($data['author']) || empty(trim($data['author']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Author is required']);
            return;
        }
        
        $resourceId = $data['resource_id'];
        $author = trim($data['author']);
        $text = trim($data['text']);
        
        // Check if resource exists
        $checkQuery = "SELECT id FROM resources WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $resourceId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Resource not found']);
            return;
        }
        
        $query = "INSERT INTO comments (resource_id, author, text) VALUES (:resource_id, :author, :text)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
        $stmt->bindParam(':author', $author);
        $stmt->bindParam(':text', $text);
        
        if ($stmt->execute()) {
            $newId = $db->lastInsertId();
            
            // Fetch the newly created comment
            $fetchQuery = "SELECT id, resource_id, author, text, created_at FROM comments WHERE id = :id";
            $fetchStmt = $db->prepare($fetchQuery);
            $fetchStmt->bindParam(':id', $newId, PDO::PARAM_INT);
            $fetchStmt->execute();
            $comment = $fetchStmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'comment' => $comment,
                'message' => 'Comment added successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete a comment
 */
function deleteComment($db, $id) {
    try {
        // Check if comment exists
        $checkQuery = "SELECT id FROM comments WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Comment not found']);
            return;
        }
        
        $query = "DELETE FROM comments WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
