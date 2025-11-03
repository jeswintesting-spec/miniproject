<?php
require_once '../db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$current_user = $_SESSION['id'];
$receiver_id = intval($_GET['receiver_id']);

// Mark received messages as read
$conn->query("UPDATE messages SET is_read = 1 
              WHERE sender_id = $receiver_id 
              AND receiver_id = $current_user 
              AND is_read = 0");

// Fetch conversation
$sql = "
SELECT sender_id, receiver_id, message, timestamp 
FROM messages 
WHERE (sender_id = $current_user AND receiver_id = $receiver_id)
   OR (sender_id = $receiver_id AND receiver_id = $current_user)
ORDER BY timestamp ASC
";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $class = ($row['sender_id'] == $current_user) ? 'sent' : 'received';
    echo "<div class='message $class'>" . htmlspecialchars($row['message']) . "</div>";
}
?>
