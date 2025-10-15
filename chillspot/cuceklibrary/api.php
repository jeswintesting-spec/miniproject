<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // For local testing
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
$conn = new mysqli("localhost", "root", "", "librarysystem");
if ($conn->connect_error) {
    echo json_encode(["error" => "DB Connection failed: " . $conn->connect_error]);
    exit;
}

// Read action
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    $action = $data['action'] ?? '';
}

// --------------------- ACTIONS ---------------------

if ($action === 'getAllData') {
    // Get all settings
    $settings = [];
    $result = $conn->query("SELECT * FROM settings");
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Notifications
    $notifications = [];
    $res = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
    while ($row = $res->fetch_assoc()) {
        $notifications[] = $row;
    }

    // Books
    $books = [];
    $res = $conn->query("SELECT * FROM books ORDER BY id DESC");
    while ($row = $res->fetch_assoc()) {
        $books[] = $row;
    }

    echo json_encode([
        "settings" => $settings,
        "notifications" => $notifications,
        "books" => $books
    ]);
    exit;
}

// Save library status
if ($action === 'saveStatus') {
    $status = $conn->real_escape_string($data['status'] ?? 'auto');
    $customText = $conn->real_escape_string($data['customText'] ?? '');

    $conn->query("UPDATE settings SET setting_value='$status' WHERE setting_key='libraryStatus'");
    $conn->query("UPDATE settings SET setting_value='$customText' WHERE setting_key='customLibraryStatus'");

    echo json_encode(["success" => true, "message" => "Status updated"]);
    exit;
}

// Save crowd level
if ($action === 'saveCrowd') {
    $level = $conn->real_escape_string($data['level'] ?? 'Medium');
    $conn->query("UPDATE settings SET setting_value='$level' WHERE setting_key='crowdLevel'");
    echo json_encode(["success" => true, "message" => "Crowd updated"]);
    exit;
}

// Add notification
if ($action === 'addNotification') {
    $message = $conn->real_escape_string($data['message'] ?? '');
    if ($message === '') {
        echo json_encode(["error" => "Message cannot be empty"]);
        exit;
    }
    $conn->query("INSERT INTO notifications (message) VALUES ('$message')");
    echo json_encode(["success" => true]);
    exit;
}

// Remove notification
if ($action === 'removeNotification') {
    $id = intval($data['id'] ?? 0);
    $conn->query("DELETE FROM notifications WHERE id=$id");
    echo json_encode(["success" => true]);
    exit;
}

// Add book
if ($action === 'addBook') {
    $title = $conn->real_escape_string($data['title'] ?? '');
    $author = $conn->real_escape_string($data['author'] ?? '');
    $year = $conn->real_escape_string($data['year'] ?? '');
    if ($title === '' || $author === '' || $year === '') {
        echo json_encode(["error" => "All book fields required"]);
        exit;
    }
    $conn->query("INSERT INTO books (title, author, publication_year) VALUES ('$title','$author','$year')");
    echo json_encode(["success" => true]);
    exit;
}

// Remove book
if ($action === 'removeBook') {
    $id = intval($data['id'] ?? 0);
    $conn->query("DELETE FROM books WHERE id=$id");
    echo json_encode(["success" => true]);
    exit;
}

echo json_encode(["error" => "Invalid action"]);
?>
