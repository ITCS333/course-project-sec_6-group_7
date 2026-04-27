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


// TODO: (Optional) Set CORS headers if your frontend and backend are on different domains
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');


// --- Check Request Method ---
// TODO: Verify that the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}


// --- Get POST Data ---
// TODO: Retrieve the raw POST data
$raw = file_get_contents('php://input');

// TODO: Decode the JSON data into a PHP associative array
$data = json_decode($raw, true);

// TODO: Extract the email and password
if (!$data || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false]);
    exit;
}

// TODO: Store the email and password in variables
$email = trim($data['email']);
$password = $data['password'];


// --- Server-Side Validation (Optional but Recommended) ---
// TODO: Validate the email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false]);
    exit;
}

// TODO: Validate the password length
if (strlen($password) < 8) {
    echo json_encode(['success' => false]);
    exit;
}


// --- Database Connection ---
// TODO: Get the database connection using the provided function
require_once __DIR__ . '/../../common/db.php';

try {

    $db = getDBConnection();

    // --- Prepare SQL Query ---
    // TODO: Write SELECT query
    $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = ?";

    // --- Prepare the Statement ---
    // TODO: Prepare statement
    $stmt = $db->prepare($sql);

    // --- Execute the Query ---
    // TODO: Execute with email
    $stmt->execute([$email]);

    // --- Fetch User Data ---
    // TODO: Fetch user
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---
    // TODO: Check + verify password
    if ($user && password_verify($password, $user['password'])) {

        // --- Handle Successful Authentication ---
        
        // TODO: Store session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['logged_in'] = true;

        // TODO: Prepare success response
        $response = [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'is_admin' => $user['is_admin']
            ]
        ];

        // TODO: Echo JSON
        echo json_encode($response);

        // TODO: Exit
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

// --- End of Script ---
?>