<?php
require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_user = $_SESSION['id'];

// ✅ Fetch all users except current one, with dept, status, unread count
$sql = "
SELECT 
    u.id, 
    u.name, 
    u.dept,
    (CASE 
        WHEN (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(u.last_active)) < 60 THEN 'Online'
        ELSE 'Offline'
    END) AS status,
    (
        SELECT COUNT(*) 
        FROM messages 
        WHERE receiver_id = $current_user 
          AND sender_id = u.id 
          AND is_read = 0
    ) AS unread_count
FROM users u
WHERE u.id != $current_user
ORDER BY u.name ASC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $isOnline = $row['status'] === 'Online';
        $statusColor = $isOnline ? 'bg-green-500 animate-pulse' : 'bg-gray-400';
        $unreadBadge = $row['unread_count'] > 0 
            ? '<span class="ml-auto bg-red-500 h-2 w-2 rounded-full"></span>' 
            : '';

        $name = htmlspecialchars($row['name']);
        $dept = htmlspecialchars($row['dept']);
        $status = htmlspecialchars($row['status']);

        echo "
        <div class='user-item flex items-center justify-between p-3 rounded-lg hover:bg-gray-100 cursor-pointer transition'
             data-id='{$row['id']}'
             data-name='{$name}'
             data-status='{$status}'
             onclick='openChat({$row['id']}, \"{$name}\")'>
             
            <div class='flex items-center space-x-2'>
                <div class='h-2.5 w-2.5 rounded-full {$statusColor}'></div>
                <div class='flex flex-col leading-tight'>
                    <span class='font-medium text-gray-800'>{$name}</span>
                    <span class='text-xs text-gray-500'>{$dept}</span>
                </div>
            </div>
            {$unreadBadge}
        </div>
        ";
    }
} else {
    echo "<div class='p-3 text-gray-500 text-sm'>No other users found.</div>";
}
?>
