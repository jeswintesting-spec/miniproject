<?php
// api.php - The single backend endpoint for the Canteen Application.
// Handles fetching and updating data in the MySQL database.

// --- Database Configuration ---
$servername = "localhost"; // Default for XAMPP
$username = "root";      // Default for XAMPP
$password = "";          // Default for XAMPP
$dbname = "chillspot_canteen";

// --- Headers ---
// Allow requests from any origin (for development, security risk for production)
header("Access-Control-Allow-Origin: *");
// Specify that the response content type is JSON
header("Content-Type: application/json; charset=UTF-8");
// Allow POST and GET methods
header("Access-Control-Allow-Methods: POST, GET");
// Allow the Content-Type header in requests
header("Access-Control-Allow-Headers: Content-Type");

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If connection fails, send a server error response and exit
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Determine the request method (e.g., GET or POST)
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // --- Handle GET request: Fetch all data for display ---
    try {
        $data = [
            'status' => [],
            'notification' => '',
            'menu' => []
        ];

        // Fetch settings (status and notification)
        $settings_sql = "SELECT setting_key, setting_value FROM settings";
        $settings_result = $conn->query($settings_sql);
        while ($row = $settings_result->fetch_assoc()) {
            if ($row['setting_key'] === 'notification') {
                $data['notification'] = $row['setting_value'];
            } else {
                $data['status'][$row['setting_key']] = $row['setting_value'];
            }
        }

        // Fetch menu items
        $menu_sql = "SELECT id, name, price FROM menu_items ORDER BY id";
        $menu_result = $conn->query($menu_sql);
        while ($row = $menu_result->fetch_assoc()) {
            $data['menu'][] = $row;
        }

        // Send the combined data as a JSON response
        echo json_encode($data);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch data: " . $e->getMessage()]);
    }

} elseif ($method === 'POST') {
    // --- Handle POST request: Update all data from admin panel ---
    $input = json_decode(file_get_contents('php://input'), true);

    // Start a transaction to ensure all updates succeed or fail together
    $conn->begin_transaction();

    try {
        // Update status and notification using a prepared statement
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        
        // Update canteenStatus
        $key = 'canteenStatus';
        $value = $input['status']['canteenStatus'];
        $stmt->execute();
        
        // Update customStatus
        $key = 'customStatus';
        $value = $input['status']['customStatus'];
        $stmt->execute();

        // Update notification
        $key = 'notification';
        $value = $input['notification'];
        $stmt->execute();
        
        $stmt->close();

        // Update menu: Simple approach is to delete all and re-insert
        $conn->query("DELETE FROM menu_items");
        
        // Insert the new list of menu items using a prepared statement
        $menu_stmt = $conn->prepare("INSERT INTO menu_items (name, price) VALUES (?, ?)");
        $menu_stmt->bind_param("sd", $name, $price);
        
        if (!empty($input['menu'])) {
            foreach ($input['menu'] as $item) {
                $name = $item['name'];
                $price = $item['price'];
                $menu_stmt->execute();
            }
        }
        $menu_stmt->close();

        // If all database queries were successful, commit the transaction
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Data updated successfully."]);

    } catch (Exception $e) {
        // If any query fails, roll back the transaction to prevent partial updates
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to update data: " . $e->getMessage()]);
    }
}

// Close the database connection
$conn->close();
?>

