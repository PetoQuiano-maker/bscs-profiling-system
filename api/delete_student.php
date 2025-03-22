<?php
include '../config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception("Student ID is required");
    }

    $student_id = mysqli_real_escape_string($conn, $_POST['id']);
    
    $sql = "DELETE FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $student_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Student deleted successfully"]);
    } else {
        throw new Exception("Error deleting student");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
}

$conn->close();
?>
