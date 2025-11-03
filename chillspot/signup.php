<?php
header("Content-Type: application/json");

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "chillspot";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}

// Get form data
$regno = $_POST["regno"] ?? "";
$name = $_POST["name"] ?? "";
$year = $_POST["year"] ?? "";
$dept = $_POST["dept"] ?? "";
$pass = $_POST["password"] ?? "";

// Validate
if (empty($regno) || empty($name) || empty($year) || empty($dept) || empty($pass)) {
  echo json_encode(["status" => "error", "message" => "Please fill all fields"]);
  exit;
}

// Check image
if (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
  echo json_encode(["status" => "error", "message" => "Please upload a valid image"]);
  exit;
}

$image = $_FILES['image'];
$allowed = ['jpg', 'jpeg', 'png', 'gif'];
$ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
  echo json_encode(["status" => "error", "message" => "Only JPG, JPEG, PNG, or GIF files are allowed"]);
  exit;
}

// Folder setup (absolute path)
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0777, true);
}

$imageName = uniqid("IMG_", true) . "." . $ext;
$imageFullPath = $uploadDir . $imageName;
$dbImagePath = "uploads/" . $imageName;

// Try to upload
if (!move_uploaded_file($image['tmp_name'], $imageFullPath)) {
  echo json_encode([
    "status" => "error",
    "message" => "Failed to upload image. Check folder permissions.",
    "details" => [
      "tmp_name" => $image['tmp_name'],
      "uploadDir" => realpath($uploadDir),
      "error_code" => $image['error']
    ]
  ]);
  exit;
}

// Hash password
$hashedPass = password_hash($pass, PASSWORD_DEFAULT);

// Check if regno exists
$check = $conn->prepare("SELECT id FROM users WHERE regno = ?");
$check->bind_param("s", $regno);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
  echo json_encode(["status" => "error", "message" => "Registration number already exists"]);
  exit;
}

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (regno, name, year, dept, password, image) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $regno, $name, $year, $dept, $hashedPass, $dbImagePath);

if ($stmt->execute()) {
  echo json_encode(["status" => "success", "message" => "Signup successful"]);
} else {
  echo json_encode(["status" => "error", "message" => "Database insert failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
