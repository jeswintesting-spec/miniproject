<?php
header("Content-Type: application/json");
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "chillspot";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}

// Get data
$data = json_decode(file_get_contents("php://input"), true);
$regno = $data["regno"] ?? "";
$pass = $data["password"] ?? "";

// Validate inputs
if (empty($regno) || empty($pass)) {
  echo json_encode(["status" => "error", "message" => "Please fill all fields."]);
  exit;
}

// Check user
$stmt = $conn->prepare("SELECT * FROM users WHERE regno = ?");
$stmt->bind_param("s", $regno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo json_encode(["status" => "error", "message" => "Invalid registration number or password."]);
  exit;
}

$user = $result->fetch_assoc();

// Verify password
if (password_verify($pass, $user['password'])) {
  // ✅ Store all needed info in the session
  $_SESSION['id'] = $user['id'];
  $_SESSION['regno'] = $user['regno'];
  $_SESSION['name'] = $user['name'];
  $_SESSION['dept'] = $user['dept'];
  $_SESSION['year'] = $user['year'];

  echo json_encode(["status" => "success", "message" => "Login successful."]);
} else {
  echo json_encode(["status" => "error", "message" => "Invalid registration number or password."]);
}

$stmt->close();
$conn->close();
?>
