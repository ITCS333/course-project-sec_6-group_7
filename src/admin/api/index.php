<?php
// --- HEADERS ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/db.php';
$db = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? null;
$order = $_GET['order'] ?? 'asc';

// ---------------- GET USERS ----------------
function getUsers($db, $search, $sort, $order) {
    $sql = "SELECT id, name, email, is_admin, created_at FROM users";
    $params = [];

    if ($search) {
        $sql .= " WHERE name LIKE :search OR email LIKE :search";
        $params[':search'] = "%$search%";
    }

    $allowed = ['name', 'email', 'is_admin'];
    if ($sort && in_array($sort, $allowed)) {
        $dir = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY $sort $dir";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC), 200);
}

// ---------------- GET BY ID ----------------
function getUserById($db, $id) {
    $stmt = $db->prepare("SELECT id, name, email, is_admin, created_at FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) sendResponse('User not found', 404);

    sendResponse($user, 200);
}

// ---------------- CREATE ----------------
function createUser($db, $data) {
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        sendResponse('Missing fields', 400);
    }

    $name = trim($data['name']);
    $email = trim($data['email']);
    $password = $data['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse('Invalid email format', 400);
    }

    if (strlen($password) < 8) {
        sendResponse('Password must be at least 8 characters', 400);
    }

    $check = $db->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);
    if ($check->fetch()) sendResponse('Email already exists', 409);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $is_admin = isset($data['is_admin']) ? (int)$data['is_admin'] : 0;

    $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (:n, :e, :p, :a)");
    $stmt->execute([
        ':n' => $name,
        ':e' => $email,
        ':p' => $hash,
        ':a' => $is_admin
    ]);

    sendResponse(['id' => $db->lastInsertId()], 201);
}

// ---------------- UPDATE ----------------
function updateUser($db, $data) {
    if (empty($data['id'])) sendResponse('Missing user id', 400);

    $id = $data['id'];

    $check = $db->prepare("SELECT id FROM users WHERE id = :id");
    $check->execute([':id' => $id]);
    if (!$check->fetch()) sendResponse('User not found', 404);

    $fields = [];
    $params = [':id' => $id];

    if (isset($data['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = trim($data['name']);
    }

    if (isset($data['email'])) {
        $email = trim($data['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse('Invalid email format', 400);
        }

        $dup = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $dup->execute([':email' => $email, ':id' => $id]);
        if ($dup->fetch()) sendResponse('Email already exists', 409);

        $fields[] = "email = :email";
        $params[':email'] = $email;
    }

    if (isset($data['is_admin'])) {
        $fields[] = "is_admin = :is_admin";
        $params[':is_admin'] = (int)$data['is_admin'];
    }

    if (!$fields) sendResponse(['message' => 'No changes'], 200);

    $sql = "UPDATE users SET " . implode(',', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    sendResponse(['message' => 'Updated'], 200);
}

// ---------------- DELETE ----------------
function deleteUser($db, $id) {
    if (!$id) sendResponse('Missing user id', 400);

    $check = $db->prepare("SELECT id FROM users WHERE id = :id");
    $check->execute([':id' => $id]);
    if (!$check->fetch()) sendResponse('User not found', 404);

    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);

    sendResponse(['message' => 'Deleted'], 200);
}

// ---------------- CHANGE PASSWORD ----------------
function changePassword($db, $data) {
    if (!isset($data['id'], $data['current_password'], $data['new_password'])) {
        sendResponse('Missing fields', 400);
    }

    if (strlen($data['new_password']) < 8) {
        sendResponse('Password must be at least 8 characters', 400);
    }

    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) sendResponse('User not found', 404);

    if (!password_verify($data['current_password'], $user['password'])) {
        sendResponse('Current password is incorrect', 401);
    }

    $hash = password_hash($data['new_password'], PASSWORD_DEFAULT);

    $update = $db->prepare("UPDATE users SET password = :p WHERE id = :id");
    $update->execute([':p' => $hash, ':id' => $data['id']]);

    sendResponse(['message' => 'Password updated'], 200);
}

// ---------------- ROUTER ----------------
try {

    if ($method === 'GET') {
        if ($id) getUserById($db, $id);
        else getUsers($db, $search, $sort, $order);

    } elseif ($method === 'POST') {
        if ($action === 'change_password') changePassword($db, $data);
        else createUser($db, $data);

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

// ---------------- RESPONSE ----------------
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);

    if ($statusCode < 400) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => $data]);
    }
    exit;
}
?>