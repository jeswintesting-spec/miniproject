<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Connect to DB
$conn = new mysqli("localhost", "root", "", "chillspot");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $admin_number = trim($_POST['admin_number']);
    $password = trim($_POST['password']);

    // Debug output (will appear on screen)
    echo "<pre>DEBUG: Form submitted\n";
    echo "Admin number: $admin_number\n";
    echo "Password: $password\n</pre>";

    // Check if admin exists
    $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_number = ?");
    $stmt->bind_param("s", $admin_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<script>alert('Admin number not found!'); window.history.back();</script>";
        exit();
    }

    $row = $result->fetch_assoc();

    // Debug password check
    echo "<pre>DEBUG: Found in DB. DB password = {$row['password']}</pre>";

    if ($row['password'] === $password) {
        $_SESSION['admin_number'] = $admin_number;
        echo "<pre>DEBUG: Password matched. Redirecting...</pre>";

        // Redirect
        if ($admin_number === 'admincanteen') {
            header("Location: ../cucekcanteen/admincanteen.html");
        } elseif ($admin_number === 'adminlibrary') {
            header("Location: ../cuceklibrary/libraryadmin.html");
        } elseif ($admin_number === 'sportshubadmin') {
            header("Location: ../cuceksportshub/sportshubadmin.html");
        } elseif ($admin_number === 'eventsadmin') {
            header("Location: ../cucekevents/eventsadmin.html");
        } elseif ($admin_number === 'labadmin') {
            header("Location: ../cuceklabs/labadmin.html");
        } elseif ($admin_number === 'mainadmin') {
            header("Location: adminmain.html");        
        } else {
            echo "<script>alert('Unknown admin number!'); window.history.back();</script>";
        }
        exit();
    } else {
        echo "<script>alert('Incorrect password!'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<pre>DEBUG: Form not submitted as POST.</pre>";
}

$conn->close();
?>
