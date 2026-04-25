<?php
/**
 * Authentication Handler for Login Form
 */

// --- Session Management ---
// TODO: Start a PHP session using session_start()
session_start();

// --- Set Response Headers ---
// TODO: Set the Content-Type header to 'application/json'
header('Content-Type: application/json');

// --- Check Request Method ---
// TODO: Verify that the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

// --- Get POST Data ---
// TODO: Retrieve the raw POST data
$rawData = file_get_contents('php://input');

// TODO: Decode the JSON data into a PHP associative array
$data = json_decode($rawData, true);

// ✅ handle invalid / empty JSON
if (!$data || !is_array($data)) {
    echo json_encode(['success' => false]);
    exit;
}

// TODO: Extract the email and password
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false]);
    exit;
}

// TODO: Store the email and password in variables
$email = trim($data['email']);
$password = $data['password'];

// --- Server-Side Validation ---
// TODO: Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false]);
    exit;
}

// TODO: Validate password length
if (strlen($password) < 8) {
    echo json_encode(['success' => false]);
    exit;
}

// --- Database Connection ---
// TODO: Get the database connection
require_once __DIR__ . '/../../config/db.php';

try {
    $db = getDBConnection();

    // --- Prepare SQL Query ---
    $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = ?";

    // --- Prepare the Statement ---
    $stmt = $db->prepare($sql);

    // --- Execute the Query ---
    $stmt->execute([$email]);

    // --- Fetch User Data ---
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---
    if ($user && password_verify($password, $user['password'])) {

        // --- Handle Successful Authentication ---

        // TODO: Store user information in session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['logged_in'] = true;

        // TODO: Prepare a success response array
        $response = [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'is_admin' => $user['is_admin']
            ]
        ];

        // TODO: Encode the response array as JSON and echo it
        echo json_encode($response);

        // TODO: Exit the script
        exit;

    } else {

        // --- Handle Failed Authentication ---
        $response = [
            'success' => false
        ];

        echo json_encode($response);
        exit;
    }

} catch (PDOException $e) {

    // TODO: Log error
    error_log($e->getMessage());

    // TODO: Return generic error
    echo json_encode([
        'success' => false
    ]);

    exit;
}
?>