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

// Handle the request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getResourceById($db, $_GET['id']);
        } else {
            getAllResources($db);
        }
        break;
    case 'POST':
        createResource($db, json_decode(file_get_contents('php://input'), true));
        break;
    case 'PUT':
        updateResource($db, json_decode(file_get_contents('php://input'), true));
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteResource($db, $_GET['id']);
        }
        break;
    default:
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

function getResourceById($db, $id) {
    $query = "SELECT id, title, description, link FROM resources WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resource) {
        echo json_encode(['success' => true, 'data' => $resource]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Resource not found']);
    }
}

function createResource($db, $data) {
    $query = "INSERT INTO resources (title, description, link) VALUES (:title, :description, :link)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':link', $data['link']);
    
    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create resource']);
    }
}

function updateResource($db, $data) {
    $query = "UPDATE resources SET title = :title, description = :description, link = :link WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id']);
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':link', $data['link']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resource updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update resource']);
    }
}

function deleteResource($db, $id) {
    $query = "DELETE FROM resources WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resource deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete resource']);
    }
}
?>
