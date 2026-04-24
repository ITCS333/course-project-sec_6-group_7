<?php
session_start();
header("Content-Type: application/json");

require_once "../config/db.php";

//allow only admins
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] != 1) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

//get users
$result = $conn->query("SELECT id, name, email, is_admin FROM users");

$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode([
    "success" => true,
    "users" => $users
]);