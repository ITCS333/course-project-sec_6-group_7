<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["logged_in" => false]);
    exit;
}

echo json_encode([
    "logged_in" => true,
    "user_id" => $_SESSION['user_id'],
    "is_admin" => $_SESSION['is_admin']
]);