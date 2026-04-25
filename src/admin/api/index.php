<?php
/**
 * User Management API
 *
 * A RESTful API that handles all CRUD operations for user management
 * and password changes for the Admin Portal.
 * Uses PDO to interact with a MySQL database.
 */


// TODO: Set headers for JSON response and CORS.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// TODO: Include the database connection file.
require_once __DIR__ . '/../../config/db.php';

// TODO: Get the PDO database connection by calling getDBConnection().
$db = getDBConnection();

// TODO: Read the HTTP request method from $_SERVER['REQUEST_METHOD'].
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Read the raw request body for POST and PUT requests.
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// TODO: Read query string parameters.
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? null;
$order = $_GET['order'] ?? 'asc';


function getUsers($db) {
    global $search, $sort, $order;

    // TODO: Build a SELECT query
    $sql = "SELECT id, name, email, is_admin, created_at FROM users";
    $params = [];

    // TODO: search
    if ($search) {
        $sql .= " WHERE name LIKE :search OR email LIKE :search";
        $params[':search'] = "%$search%";
    }

    // TODO: sort
    $allowed = ['name', 'email', 'is_admin'];
    if ($sort && in_array($sort, $allowed)) {
        $dir = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY $sort $dir";
    }

    // TODO: execute
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // TODO: fetch
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: response
    sendResponse($users, 200);
}


function getUserById($db, $id) {
    // TODO: select
    $stmt = $db->prepare("
        SELECT id, name, email, is_admin, created_at
        FROM users WHERE id = :id
    ");

    // TODO: bind + execute
    $stmt->execute([':id' => $id]);

    // TODO: fetch
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: check
    if (!$user) {
        sendResponse('User not found', 404);
    }

    sendResponse($user, 200);
}


function createUser($db, $data) {
    // TODO: required fields
    if (
        empty($data['name']) ||
        empty($data['email']) ||
        empty($data['password'])
    ) {
        sendResponse('Missing fields', 400);
    }

    // TODO: sanitize
    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = trim($data['password']);

    // TODO: validate email
    if (!validateEmail($email)) {
        sendResponse('Invalid email format', 400);
    }

    // TODO: password length
    if (strlen($password) < 8) {
        sendResponse('Password must be at least 8 characters', 400);
    }

    // TODO: check duplicate
    $check = $db->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);

    if ($check->fetch()) {
        sendResponse('Email already exists', 409);
    }

    // TODO: hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // TODO: is_admin
    $is_admin = isset($data['is_admin']) && $data['is_admin'] == 1 ? 1 : 0;

    // TODO: insert
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, is_admin)
        VALUES (:name, :email, :password, :is_admin)
    ");

    $ok = $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hash,
        ':is_admin' => $is_admin
    ]);

    // TODO: response
    if ($ok) {
        sendResponse(['id' => $db->lastInsertId()], 201);
    }

    sendResponse('Failed to create user', 500);
}


function updateUser($db, $data) {
    // TODO: id check
    if (!isset($data['id'])) {
        sendResponse('Missing user id', 400);
    }

    $id = $data['id'];

    // TODO: check exists
    $check = $db->prepare("SELECT id FROM users WHERE id = :id");
    $check->execute([':id' => $id]);

    if (!$check->fetch()) {
        sendResponse('User not found', 404);
    }

    // TODO: build fields
    $fields = [];
    $params = [':id' => $id];

    if (isset($data['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = sanitizeInput($data['name']);
    }

    if (isset($data['email'])) {
        $email = sanitizeInput($data['email']);

        if (!validateEmail($email)) {
            sendResponse('Invalid email format', 400);
        }

        $dup = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $dup->execute([':email' => $email, ':id' => $id]);

        if ($dup->fetch()) {
            sendResponse('Email already exists', 409);
        }

        $fields[] = "email = :email";
        $params[':email'] = $email;
    }

    if (isset($data['is_admin'])) {
        $fields[] = "is_admin = :is_admin";
        $params[':is_admin'] = $data['is_admin'] == 1 ? 1 : 0;
    }

    if (empty($fields)) {
        sendResponse(['message' => 'No changes'], 200);
    }

    // TODO: update
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);

    if ($stmt->execute($params)) {
        sendResponse(['message' => 'Updated'], 200);
    }

    sendResponse('Failed to update user', 500);
}


function deleteUser($db, $id) {
    // TODO: id check
    if (!$id) {
        sendResponse('Missing user id', 400);
    }

    // TODO: exists
    $check = $db->prepare("SELECT id FROM users WHERE id = :id");
    $check->execute([':id' => $id]);

    if (!$check->fetch()) {
        sendResponse('User not found', 404);
    }

    // TODO: delete
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");

    if ($stmt->execute([':id' => $id])) {
        sendResponse(['message' => 'Deleted'], 200);
    }

    sendResponse('Failed to delete user', 500);
}


function changePassword($db, $data) {
    // TODO: fields
    if (
        !isset($data['id']) ||
        !isset($data['current_password']) ||
        !isset($data['new_password'])
    ) {
        sendResponse('Missing fields', 400);
    }

    // TODO: length
    if (strlen($data['new_password']) < 8) {
        sendResponse('Password must be at least 8 characters', 400);
    }

    // TODO: get current
    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse('User not found', 404);
    }

    // TODO: verify
    if (!password_verify($data['current_password'], $user['password'])) {
        sendResponse('Current password is incorrect', 401);
    }

    // TODO: update
    $hash = password_hash($data['new_password'], PASSWORD_DEFAULT);

    $update = $db->prepare("UPDATE users SET password = :password WHERE id = :id");

    if ($update->execute([
        ':password' => $hash,
        ':id' => $data['id']
    ])) {
        sendResponse(['message' => 'Password updated'], 200);
    }

    sendResponse('Failed to update password', 500);
}


// ================= ROUTER =================
try {

    if ($method === 'GET') {
        if (!empty($id)) {
            getUserById($db, $id);
        } else {
            getUsers($db);
        }

    } elseif ($method === 'POST') {
        if ($action === 'change_password') {
            changePassword($db, $data);
        } else {
            createUser($db, $data);
        }

    } elseif ($method === 'PUT') {
        updateUser($db, $data);

    } elseif ($method === 'DELETE') {
        deleteUser($db, $id);

    } else {
        sendResponse('Method not allowed', 405);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    sendResponse('Database error', 500);

} catch (Exception $e) {
    sendResponse($e->getMessage(), 500);
}


// ================= HELPERS =================
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);

    if ($statusCode < 400) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => $data]);
    }

    exit;
}

function validateEmail($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>