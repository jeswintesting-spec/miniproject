<?php
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "cucekevents";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Get action
$action = $_GET['action'] ?? '';

switch($action){
    case 'getEvents':
        $res = $conn->query("SELECT * FROM events ORDER BY date ASC, time ASC");
        $events = [];
        while($row = $res->fetch_assoc()) $events[] = $row;
        echo json_encode($events);
        break;

    case 'getNotifs':
        $res = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
        $notifs = [];
        while($row = $res->fetch_assoc()) $notifs[] = $row;
        echo json_encode($notifs);
        break;

    case 'addEvent':
        $data = json_decode(file_get_contents('php://input'), true);
        if(!$data) { echo json_encode(['error'=>'Invalid JSON']); exit; }
        $title = $conn->real_escape_string($data['title']);
        $date = $conn->real_escape_string($data['date']);
        $time = $conn->real_escape_string($data['time']);
        $desc = $conn->real_escape_string($data['desc']);
        $conn->query("INSERT INTO events (title, date, time, description) VALUES ('$title','$date','$time','$desc')");
        echo json_encode(['success'=>true]);
        break;

    case 'addNotif':
        $data = json_decode(file_get_contents('php://input'), true);
        if(!$data) { echo json_encode(['error'=>'Invalid JSON']); exit; }
        $text = $conn->real_escape_string($data['text']);
        $conn->query("INSERT INTO notifications (text) VALUES ('$text')");
        echo json_encode(['success'=>true]);
        break;

    case 'deleteEvent':
        $id = intval($_GET['id']);
        $conn->query("DELETE FROM events WHERE id=$id");
        echo json_encode(['success'=>true]);
        break;

    case 'deleteNotif':
        $id = intval($_GET['id']);
        $conn->query("DELETE FROM notifications WHERE id=$id");
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['error'=>'Invalid action']);
}

$conn->close();
