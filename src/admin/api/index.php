<?php
/**
 * User Management API
 *
 * A RESTful API that handles all CRUD operations for user management
 * and password changes for the Admin Portal.
 * Uses PDO to interact with a MySQL database.
 *
 * Database Table (ground truth: see schema.sql):
 * Table: users
 * Columns:
 *   - id         (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
 *   - name       (VARCHAR(100), NOT NULL)
 *   - email      (VARCHAR(100), NOT NULL, UNIQUE)
 *   - password   (VARCHAR(255), NOT NULL) - bcrypt hash
 *   - is_admin   (TINYINT(1), NOT NULL, DEFAULT 0)
 *   - created_at (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)
 *
 * HTTP Methods Supported:
 *   - GET    : Retrieve all users (with optional search/sort query params)
 *   - GET    : Retrieve a single user by id (?id=1)
 *   - POST   : Create a new user
 *   - POST   : Change a user's password (?action=change_password)
 *   - PUT    : Update an existing user's name, email, or is_admin
 *   - DELETE : Delete a user by id (?id=1)
 *
 * Response Format: JSON
 * All responses have the shape:
 *   { "success": true,  "data": ... }
 *   { "success": false, "message": "..." }
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
    // TODO: Build a SELECT query for id, name, email, is_admin, created_at.
    $sql = "SELECT id, name, email, is_admin, created_at FROM users";

    $params = [];
    // TODO: If the 'search' query parameter is present, append a WHERE clause:
    if ($search) {
        $sql .= " WHERE name LIKE :search OR email LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    // TODO: If the 'sort' query parameter is present and is one of the allowed
     $allowedSort = ['name', 'email', 'is_admin'];

    if ($sort && in_array($sort, $allowedSort)) {
        $direction = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY $sort $direction";
    }
    // TODO: Prepare the statement, bind any parameters, and execute.
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    // TODO: Fetch all rows as an associative array.
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // TODO: Call sendResponse() with the array and HTTP status 200.
    sendResponse($users, 200);
}

function getUserById($db, $id) {
    // TODO: Prepare SELECT query: SELECT id, name, email, is_admin, created_at
     $stmt = $db->prepare("
        SELECT id, name, email, is_admin, created_at
        FROM users
        WHERE id = :id
    ");
    // TODO: Bind :id and execute.
    $stmt->execute([':id' => $id]);
    // TODO: Fetch one row.
     $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // TODO: If no row is found, call sendResponse() with an error message and HTTP 404.
    if (!$user) {
        sendResponse('User not found', 404);
    }
    //       If found, call sendResponse() with the row and HTTP 200.
    sendResponse($user, 200);
}

function createUser($db, $data) {
    // TODO: Check that name, email, and password are all present and non-empty.
     if (
        !isset($data['name']) ||
        !isset($data['email']) ||
        !isset($data['password']) ||
        trim($data['name']) === '' ||
        trim($data['email']) === '' ||
        trim($data['password']) === ''
    ) {
        sendResponse('Missing required fields', 400);
    }
    // TODO: Trim whitespace from name, email, and password.
    // TODO: Validate that password is at least 8 characters.
    $name = trim($data['name']);
    $email = trim($data['email']);
    $password = trim($data['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse('Invalid email format', 400);
    }

    if (strlen($password) < 8) {
        sendResponse('Password must be at least 8 characters', 400);
    }
    // TODO: Check whether the email already exists in the users table.
    $check = $db->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);

    if ($check->fetch()) {
        sendResponse('Email already exists', 409);
    }
    // TODO: Hash the password using password_hash($password, PASSWORD_DEFAULT).
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    // TODO: Read is_admin from $data; default to 0 if not provided.
      $is_admin = isset($data['is_admin']) && $data['is_admin'] == 1 ? 1 : 0;
    // TODO: Prepare and execute an INSERT INTO users (name, email, password, is_admin)
    // TODO: If the insert succeeds, call sendResponse() with the new user's id and HTTP 201.
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, is_admin)
        VALUES (:name, :email, :password, :is_admin)
    ");

    $success = $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashed,
        ':is_admin' => $is_admin
    ]);

    if ($success) {
        $newId = $db->lastInsertId();
        sendResponse(['id' => $newId], 201);
    } else {
        sendResponse('Failed to create user', 500);
    }
}
function updateUser($db, $data) {
    // TODO: Check that id is present in $data.
    if (!isset($data['id'])) {
        sendResponse('Missing user id', 400);
    }

    $id = $data['id'];
    // TODO: Look up the user by id. If not found, call sendResponse() with HTTP 404.
    $check = $db->prepare("SELECT id FROM users WHERE id = :id");
    $check->execute([':id' => $id]);

    if (!$check->fetch()) {
        sendResponse('User not found', 404);
    }
    // TODO: Dynamically build the SET clause for only the fields provided
      $fields = [];
    $params = [':id' => $id];

    if (isset($data['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = trim($data['name']);
    }

    if (isset($data['email'])) {
        $email = trim($data['email']);
    // TODO: If email is being updated, check it is not already used by another user
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse('Invalid email format', 400);
        }

        $dup = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $dup->execute([
            ':email' => $email,
            ':id' => $id
        ]);

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
        sendResponse('No data to update', 200);
    }
    // TODO: Prepare the UPDATE statement, bind parameters, and execute.
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);

    $success = $stmt->execute($params);
    // TODO: If successful, call sendResponse() with a success message and HTTP 200.
     if ($success) {
        sendResponse('User updated successfully', 200);
    } else {
        sendResponse('Failed to update user', 500);
    }
}
function deleteUser($db, $id) {
    // TODO: Check that $id is present and non-zero.
    if (!$id) {
        sendResponse('Missing user id', 400);
    }
    // TODO: Check that a user with this id exists.
     $check = $db->prepare("SELECT id FROM users WHERE id = :id");
    $check->execute([':id' => $id]);

    if (!$check->fetch()) {
        sendResponse('User not found', 404);
    }
    // TODO: Prepare and execute: DELETE FROM users WHERE id = :id
     $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $success = $stmt->execute([':id' => $id]);
    // TODO: If successful, call sendResponse() with a success message and HTTP 200.
    //       If the query fails, call sendResponse() with HTTP 500.
     if ($success) {
        sendResponse('User deleted successfully', 200);
    } else {
        sendResponse('Failed to delete user', 500);
    }
}

function changePassword($db, $data) {
    // TODO: Check that id, current_password, and new_password are all present.
    //       If any are missing, call sendResponse() with HTTP 400.
    if (
        !isset($data['id']) ||
        !isset($data['current_password']) ||
        !isset($data['new_password'])
    ) {
        sendResponse('Missing required fields', 400);
    }

    $id = $data['id'];
    $current = $data['current_password'];
    $new = $data['new_password'];
    // TODO: Validate that new_password is at least 8 characters.
    //       If not, call sendResponse() with HTTP 400.
    if (strlen($new) < 8) {
        sendResponse('Password must be at least 8 characters', 400);
    }
    // TODO: SELECT password FROM users WHERE id = :id to retrieve the current hash.
    //       If no user is found, call sendResponse() with HTTP 404.
    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse('User not found', 404);
    }
    // TODO: Call password_verify($current_password, $hash).
    //       If verification fails, call sendResponse() with HTTP 401 (Unauthorized).
    if (!password_verify($current, $user['password'])) {
        sendResponse('Current password is incorrect', 401);
    }
    // TODO: Hash the new password: password_hash($new_password, PASSWORD_DEFAULT).
     $hashed = password_hash($new, PASSWORD_DEFAULT);
    // TODO: Prepare and execute: UPDATE users SET password = :password WHERE id = :id
    $update = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
    $success = $update->execute([
        ':password' => $hashed,
        ':id' => $id
    ]);
    // TODO: If successful, call sendResponse() with a success message and HTTP 200.
    //       If the query fails, call sendResponse() with HTTP 500.
    if ($success) {
        sendResponse('Password updated successfully', 200);
    } else {
        sendResponse('Failed to update password', 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {

    if ($method === 'GET') {
        // TODO: If the 'id' query parameter is present and non-empty, call getUserById($db, $id).
        // TODO: Otherwise, call getUsers($db) (supports optional search/sort parameters).
         if ($id) {
            getUserById($db, $id);
        } else {
            getUsers($db);
        }
    } elseif ($method === 'POST') {
        // TODO: If the 'action' query parameter equals 'change_password', call changePassword($db, $data).
        // TODO: Otherwise, call createUser($db, $data).
         if ($action === 'change_password') {
            changePassword($db, $data);
        } else {
            createUser($db, $data);
        }
    } elseif ($method === 'PUT') {
        // TODO: Call updateUser($db, $data).
        //       The user id to update comes from the JSON body, not the query string.
        updateUser($db, $data);

    } elseif ($method === 'DELETE') {
        // TODO: Read the 'id' query parameter.
        // TODO: Call deleteUser($db, $id).
        deleteUser($db, $id);

    } else {
        // TODO: Return HTTP 405 (Method Not Allowed) with a JSON error message.
        sendResponse('Method not allowed', 405);
    }

} catch (PDOException $e) {
    // TODO: Log the error (e.g. error_log($e->getMessage())).
     error_log($e->getMessage());
    // TODO: Call sendResponse() with a generic "Database error" message and HTTP 500.
    //       Do NOT expose the raw exception message to the client.
    sendResponse('Database error', 500);

} catch (Exception $e) {
    // TODO: Call sendResponse() with the exception message and HTTP 500.
    sendResponse($e->getMessage(), 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Sends a JSON response and terminates execution.
 *
 * @param mixed $data       Data to include in the response.
 *                          On success, pass the payload directly.
 *                          On error, pass a string message.
 * @param int   $statusCode HTTP status code (default 200).
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Call http_response_code($statusCode).
    http_response_code($statusCode);
    // TODO: If $statusCode indicates success (< 400), echo:
    //         json_encode(['success' => true, 'data' => $data])
    //       Otherwise echo:
    //         json_encode(['success' => false, 'message' => $data])
    if ($statusCode < 400) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $data
        ]);
    }
    // TODO: Call exit to stop further execution.
    exit;
}


/**
 * Validates an email address.
 *
 * @param  string $email
 * @return bool   True if the email passes FILTER_VALIDATE_EMAIL, false otherwise.
 */
function validateEmail($email) {
    // TODO: return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}


/**
 * Sanitizes a string input value.
 * Use this before inserting user-supplied strings into the database.
 *
 * @param  string $data
 * @return string Trimmed, tag-stripped, and HTML-escaped string.
 */
function sanitizeInput($data) {
    // TODO: trim($data)
    $data = trim($data);
    // TODO: strip_tags(...)
    $data = strip_tags($data);
    // TODO: htmlspecialchars(..., ENT_QUOTES, 'UTF-8')
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // TODO: Return the sanitized value.
    return $data;
}

?>
