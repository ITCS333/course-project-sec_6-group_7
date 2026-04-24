<?php
session_start();
header("Content-Type: application/json");

require_once "../config/db.php";

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] != 1) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$id = $_POST["id"] ?? "";

if (!$id) {
    echo json_encode(["success" => false, "message" => "Missing ID"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(["success" => true]);