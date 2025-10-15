<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow cross-origin (for testing)
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$conn = new mysqli("localhost", "root", "", "sports_hub");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Parse the action
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'notifications') {
        $res = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
        $data = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
    } elseif ($action === 'events') {
        $res = $conn->query("SELECT * FROM events ORDER BY date ASC, time ASC");
        $data = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
    } else {
        echo json_encode(["error" => "Invalid action"]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    if ($action === 'addNotification') {
        $msg = $conn->real_escape_string($input['message']);
        $conn->query("INSERT INTO notifications (message) VALUES ('$msg')");
        echo json_encode(["success" => true]);
    } elseif ($action === 'addEvent') {
        $title = $conn->real_escape_string($input['title']);
        $location = $conn->real_escape_string($input['location']);
        $date = $conn->real_escape_string($input['date']);
        $time = $conn->real_escape_string($input['time']);
        $conn->query("INSERT INTO events (title, location, date, time) VALUES ('$title', '$location', '$date', '$time')");
        echo json_encode(["success" => true]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $input);

    if ($action === 'deleteNotification') {
        $id = intval($input['id']);
        $conn->query("DELETE FROM notifications WHERE id=$id");
        echo json_encode(["success" => true]);
    } elseif ($action === 'deleteEvent') {
        $id = intval($input['id']);
        $conn->query("DELETE FROM events WHERE id=$id");
        echo json_encode(["success" => true]);
    }
}

$conn->close();
?>
