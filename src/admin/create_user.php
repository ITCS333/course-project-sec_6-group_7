<?php
session_start();
header("Content-Type: application/json");

require_once "../config/db.php";

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] != 1) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$name = $_POST["name"] ?? "";
$email = $_POST["email"] ?? "";
$password = $_POST["password"] ?? "";
$is_admin = $_POST["is_admin"] ?? 0;

if (!$name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $name, $email, $hashed, $is_admin);
$stmt->execute();

echo json_encode(["success" => true]);