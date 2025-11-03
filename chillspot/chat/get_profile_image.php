<?php
require_once '../db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$user_id = $_SESSION['id'] ?? 0;

$image_path = '../uploads/default.png';
if ($user_id) {
    $stmt = $conn->prepare("SELECT image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($img);
    if ($stmt->fetch() && !empty($img) && file_exists("../uploads/$img")) {
        $image_path = "../uploads/$img";
    }
    $stmt->close();
}

echo json_encode(['image' => $image_path]);
