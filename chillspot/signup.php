<?php
header("Content-Type: application/json");

// Database connection
$servername = "localhost";
$username = "root";     // default XAMPP username
$password = "";         // default XAMPP password is blank
$dbname = "chillspot";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die(json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]));
}

// Read JSON data from fetch()
$data = json_decode(file_get_contents("php://input"), true);

$regno = $data["regno"] ?? "";
$name = $data["name"] ?? "";
$year = $data["year"] ?? "";
$dept = $data["dept"] ?? "";
$pass = $data["password"] ?? "";

// Validate
if (empty($regno) || empty($name) || empty($year) || empty($dept) || empty($pass)) {
  echo json_encode(["status" => "error", "message" => "Please fill all fields"]);
  exit;
}

// Hash password
$hashedPass = password_hash($pass, PASSWORD_DEFAULT);

// Check if regno already exists
$check = $conn->prepare("SELECT * FROM users WHERE regno = ?");
$check->bind_param("s", $regno);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
  echo json_encode(["status" => "error", "message" => "Registration number already exists"]);
  exit;
}

// Insert into database
$stmt = $conn->prepare("INSERT INTO users (regno, name, year, dept, password) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $regno, $name, $year, $dept, $hashedPass);

if ($stmt->execute()) {
  echo json_encode(["status" => "success", "message" => "Signup successful"]);
} else {
  echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
