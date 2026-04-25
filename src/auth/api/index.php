<?php

session_start();
header('Content-Type: application/json');

//only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

//get JSON input
$input = json_decode(file_get_contents('php://input'), true);

//check fields
if (!isset($input['email']) || !isset($input['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

//basic validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Invalid password']);
    exit;
}

//connect to database
require_once __DIR__ . '/../../config/db.php';

try {
    $db = getDBConnection();

    $stmt = $db->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    //check user + password
    if ($user && password_verify($password, $user['password'])) {

        // store session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['logged_in'] = true;

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'is_admin' => $user['is_admin']
            ]
        ]);

    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}

exit;