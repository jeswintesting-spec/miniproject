<?php
require_once '../db.php';

if (!isset($_SESSION['id'])) exit;
$senderId = $_SESSION['id'];
$receiverId = intval($_POST['receiver_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($receiverId <= 0 || empty($message)) exit;

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $senderId, $receiverId, $message);
$stmt->execute();

// Update last_active timestamp
$stmt2 = $conn->prepare("UPDATE users SET last_active=NOW() WHERE id=?");
$stmt2->bind_param("i", $senderId);
$stmt2->execute();

echo json_encode(['status'=>'success']);
?>
