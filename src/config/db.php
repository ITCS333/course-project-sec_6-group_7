<?php
$conn = new mysqli("localhost", "root", "", "course");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}