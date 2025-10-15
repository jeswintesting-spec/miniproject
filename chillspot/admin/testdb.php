<?php
$conn = new mysqli("localhost", "root", "", "chillspot");

if ($conn->connect_error) {
  die("DB Connection failed: " . $conn->connect_error);
}
echo "Database Connected Successfully!";
?>
