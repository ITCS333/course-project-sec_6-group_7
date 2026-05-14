<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../common/db.php';

$db = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$resourceId = $_GET['resource_id'] ?? null;
$commentId = $_GET['comment_id'] ?? null;

function getAllResources($db) {

    $query = "SELECT id, title, description, link, created_at FROM resources";

    $search = $_GET['search'] ?? '';

    if (!empty($search)) {
        $query .= " WHERE title LIKE :search OR description LIKE :search";
    }

    $allowedSort = ['title', 'created_at'];

    $sort = $_GET['sort'] ?? 'created_at';

    if (!in_array($sort, $allowedSort)) {
        $sort = 'created_at';
    }

    $order = strtolower($_GET['order'] ?? 'desc');

    if ($order !== 'asc' && $order !== 'desc') {
        $order = 'desc';
    }

    $query .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($query);

    if (!empty($search)) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }

    $stmt->execute();

    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data' => $resources
    ]);
}

function getResourceById($db, $resourceId) {

    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource ID.'
        ], 400);
    }

    $query = "SELECT id, title, description, link, created_at FROM resources WHERE id = ?";

    $stmt = $db->prepare($query);

    $stmt->execute([$resourceId]);

    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resource) {

        sendResponse([
            'success' => true,
            'data' => $resource
        ]);

    } else {

        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }
}

function createResource($db, $data) {

    $validation = validateRequiredFields($data, ['title', 'link']);

    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.'
        ], 400);
    }

    $title = sanitizeInput($data['title']);
    $link = sanitizeInput($data['link']);

    $description = '';

    if (isset($data['description'])) {
        $description = sanitizeInput($data['description']);
    }

    if (!validateUrl($link)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid URL.'
        ], 400);
    }

    $query = "INSERT INTO resources (title, description, link) VALUES (?, ?, ?)";

    $stmt = $db->prepare($query);

    $stmt->execute([
        $title,
        $description,
        $link
    ]);

    if ($stmt->rowCount() > 0) {

        sendResponse([
            'success' => true,
            'message' => 'Resource created successfully.',
            'id' => $db->lastInsertId()
        ], 201);

    } else {

        sendResponse([
            'success' => false,
            'message' => 'Failed to create resource.'
        ], 500);
    }
}

function updateResource($db, $data) {

    if (!isset($data['id']) || !is_numeric($data['id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource ID.'
        ], 400);
    }

    $checkQuery = "SELECT id FROM resources WHERE id = ?";

    $checkStmt = $db->prepare($checkQuery);

    $checkStmt->execute([$data['id']]);

    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    $fields = [];
    $values = [];

    if (isset($data['title'])) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }

    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data['description']);
    }

    if (isset($data['link'])) {

        if (!validateUrl($data['link'])) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid URL.'
            ], 400);
        }

        $fields[] = "link = ?";
        $values[] = sanitizeInput($data['link']);
    }

    if (empty($fields)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields to update.'
        ], 400);
    }

    $query = "UPDATE resources SET " . implode(', ', $fields) . " WHERE id = ?";

    $values[] = $data['id'];

    $stmt = $db->prepare($query);

    $success = $stmt->execute($values);

    if ($success) {

        sendResponse([
            'success' => true,
            'message' => 'Resource updated successfully.'
        ], 200);

    } else {

        sendResponse([
            'success' => false,
            'message' => 'Failed to update resource.'
        ], 500);
    }
}

function deleteResource($db, $resourceId) {

    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource ID.'
        ], 400);
    }

    $checkQuery = "SELECT id FROM resources WHERE id = ?";

    $checkStmt = $db->prepare($checkQuery);

    $checkStmt->execute([$resourceId]);

    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    $query = "DELETE FROM resources WHERE id = ?";

    $stmt = $db->prepare($query);

    $stmt->execute([$resourceId]);

    if ($stmt->rowCount() > 0) {

        sendResponse([
            'success' => true,
            'message' => 'Resource deleted successfully.'
        ], 200);

    } else {

        sendResponse([
            'success' => false,
            'message' => 'Failed to delete resource.'
        ], 500);
    }
}

function getCommentsByResourceId($db, $resourceId) {

    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource ID.'
        ], 400);
    }

    $query = "SELECT id, resource_id, author, text, created_at
              FROM comments_resource
              WHERE resource_id = ?
              ORDER BY created_at ASC";

    $stmt = $db->prepare($query);

    $stmt->execute([$resourceId]);

    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data' => $comments
    ]);
}

function createComment($db, $data) {

    $validation = validateRequiredFields(
        $data,
        ['resource_id', 'author', 'text']
    );

    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.'
        ], 400);
    }

    if (!is_numeric($data['resource_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource ID.'
        ], 400);
    }

    $checkQuery = "SELECT id FROM resources WHERE id = ?";

    $checkStmt = $db->prepare($checkQuery);

    $checkStmt->execute([$data['resource_id']]);

    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    $query = "INSERT INTO comments_resource
              (resource_id, author, text)
              VALUES (?, ?, ?)";

    $stmt = $db->prepare($query);

    $stmt->execute([
        $data['resource_id'],
        $author,
        $text
    ]);

    if ($stmt->rowCount() > 0) {

    $newComment = [
        'id' => $db->lastInsertId(),
        'resource_id' => $data['resource_id'],
        'author' => $author,
        'text' => $text
    ];

    sendResponse([
        'success' => true,
        'message' => 'Comment created successfully.',
        'id' => $db->lastInsertId(),
        'data' => $newComment
    ], 201);

} else {

    sendResponse([
        'success' => false,
        'message' => 'Failed to create comment.'
    ], 500);
}
}

function deleteComment($db, $commentId) {

    if (!$commentId || !is_numeric($commentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid comment ID.'
        ], 400);
    }

    $checkQuery = "SELECT id FROM comments_resource WHERE id = ?";

    $checkStmt = $db->prepare($checkQuery);

    $checkStmt->execute([$commentId]);

    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Comment not found.'
        ], 404);
    }

    $query = "DELETE FROM comments_resource WHERE id = ?";

    $stmt = $db->prepare($query);

    $stmt->execute([$commentId]);

    if ($stmt->rowCount() > 0) {

        sendResponse([
            'success' => true,
            'message' => 'Comment deleted successfully.'
        ], 200);

    } else {

        sendResponse([
            'success' => false,
            'message' => 'Failed to delete comment.'
        ], 500);
    }
}

try {

    if ($method === 'GET') {

        if ($action === 'comments') {

            getCommentsByResourceId($db, $resourceId);

        } elseif ($id) {

            getResourceById($db, $id);

        } else {

            getAllResources($db);
        }

    } elseif ($method === 'POST') {

        if ($action === 'comment') {

            createComment($db, $data);

        } else {

            createResource($db, $data);
        }

    } elseif ($method === 'PUT') {

        updateResource($db, $data);

    } elseif ($method === 'DELETE') {

        if ($action === 'delete_comment') {

            deleteComment($db, $commentId);

        } else {

            deleteResource($db, $id);
        }

    } else {

        sendResponse([
            'success' => false,
            'message' => 'Method not allowed.'
        ], 405);
    }

} catch (PDOException $e) {

    error_log($e->getMessage());

    sendResponse([
        'success' => false,
        'message' => 'Server error.'
    ], 500);

} catch (Exception $e) {

    error_log($e->getMessage());

    sendResponse([
        'success' => false,
        'message' => 'Server error.'
    ], 500);
}

function sendResponse($data, $statusCode = 200) {

    http_response_code($statusCode);

    if (!is_array($data)) {
        $data = ['message' => $data];
    }

    echo json_encode($data);

    exit;
}

function validateUrl($url) {

    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput($data) {

    return htmlspecialchars(
        strip_tags(
            trim($data)
        ),
        ENT_QUOTES,
        'UTF-8'
    );
}

function validateRequiredFields($data, $requiredFields) {

    $missing = [];

    foreach ($requiredFields as $field) {

        if (
            !isset($data[$field]) ||
            trim($data[$field]) === ''
        ) {
            $missing[] = $field;
        }
    }

    return [
        'valid' => count($missing) === 0,
        'missing' => $missing
    ];
}
?>