<?php
// --- Database Connection ---
$servername = "localhost"; // Default for XAMPP
$username = "root";      // Default for XAMPP
$password = "";          // Default for XAMPP
$dbname = "cucek_labs";    // The database we created

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection and exit if it fails
if ($conn->connect_error) {
  http_response_code(500);
  die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// Set the header to indicate the response is JSON
header('Content-Type: application/json');

// --- API Logic ---
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // --- Handle GET Request (Fetch all labs) ---
    $sql = "SELECT id, name, status, crowd, event FROM labs";
    $result = $conn->query($sql);

    $labs = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Use the lab ID as the key for easy lookup in JavaScript
            $labs[$row['id']] = $row;
        }
    }
    echo json_encode($labs);

} elseif ($method === 'POST') {
    // --- Handle POST Request (Update labs) ---
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("UPDATE labs SET status = ?, crowd = ?, event = ? WHERE id = ?");
    $stmt->bind_param("ssss", $status, $crowd, $event, $id);

    foreach ($input as $labId => $details) {
        $status = $details['status'];
        $crowd = $details['crowd'];
        $event = $details['event'];
        $id = $labId;
        $stmt->execute();
    }

    $stmt->close();
    echo json_encode(['message' => 'Labs updated successfully!']);

} else {
    // Handle unsupported methods
    http_response_code(405); // 405 Method Not Allowed
    echo json_encode(['error' => 'Method not supported.']);
}

$conn->close();
?>

