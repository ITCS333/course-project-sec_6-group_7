<?php

session_start();

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$raw = file_get_contents('php://input');

$data = json_decode($raw, true);

if (!$data || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false]);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false]);
    exit;
}

require_once __DIR__ . '/../../common/db.php';

try {

    $db = getDBConnection();

    $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = ?";

    $stmt = $db->prepare($sql);

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['logged_in'] = true;

        $response = [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'is_admin' => $user['is_admin']
            ]
        ];

        echo json_encode($response);

        exit;

    } else {

        $response = [
            'success' => false
        ];

        echo json_encode($response);
        exit;
    }

} catch (PDOException $e) {

    error_log($e->getMessage());

    echo json_encode([
        'success' => false
    ]);

    exit;
}

?>