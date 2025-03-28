<?php
// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Function to handle fatal errors
function fatal_handler() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Fatal Error: ' . $error['message'],
            'error' => true
        ]);
        exit(1);
    }
}
register_shutdown_function('fatal_handler');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bscs_profiling";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
