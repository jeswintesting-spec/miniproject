<?php
require_once '../db.php';
if(session_status()===PHP_SESSION_NONE) session_start();

$current_user_id = $_SESSION['id'];
$timeout_seconds = 120;

$stmt = $conn->prepare("
    SELECT id, name, image,
        CASE WHEN last_active >= (NOW() - INTERVAL ? SECOND) THEN 1 ELSE 0 END AS is_online
    FROM users
    WHERE id != ?
    ORDER BY name ASC
");
$stmt->bind_param("ii", $timeout_seconds, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $user_id = $row['id'];
    $user_name = htmlspecialchars($row['name']);
    $is_online = $row['is_online'];

    // Profile image
    $user_image = !empty($row['image']) ? ltrim($row['image'],'/') : 'uploads/default.png';
    $full_path = "../".$user_image;
    if(!file_exists($full_path)) $full_path = "../uploads/default.png";
    $user_image = $full_path.'?v='.time();

    // Unread messages
    $stmt2 = $conn->prepare("SELECT COUNT(*) FROM messages WHERE sender_id=? AND receiver_id=? AND is_read=0");
    $stmt2->bind_param("ii", $user_id, $current_user_id);
    $stmt2->execute();
    $stmt2->bind_result($unread);
    $stmt2->fetch();
    $stmt2->close();

    // Status dot
    $status_dot = $is_online 
        ? '<span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2"></span>' 
        : '<span class="inline-block w-3 h-3 rounded-full bg-gray-400 mr-2"></span>';

    // Unread dot
    $unread_dot = $unread > 0 
        ? '<span class="inline-block w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">'.$unread.'</span>' 
        : '';

    echo '<div class="user-item flex items-center p-2 rounded-lg mb-1 hover:bg-blue-50 cursor-pointer" data-id="'.$user_id.'" onclick="openChat('.$user_id.',\''.$user_name.'\')">
        <img src="'.$user_image.'" class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 shadow-sm mr-3" onclick="openModal(this.src)">
        <div class="flex-1 flex justify-between items-center">
            <span class="font-medium">'.$user_name.'</span>
            <div class="flex items-center">
                '.$status_dot.'
                '.$unread_dot.'
            </div>
        </div>
    </div>';
}
?>
