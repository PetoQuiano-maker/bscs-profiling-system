<?php
include '../config.php';
require_once '../includes/validation.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception("Student ID is required");
    }

    $student_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    if (!validateStudentId($student_id)) {
        throw new Exception("Invalid student ID format");
    }
    
    $sql = "SELECT * FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Format data for display
        $student['formatted_birthday'] = date('F j, Y', strtotime($student['birthday']));
        $student['age'] = date_diff(date_create($student['birthday']), date_create('today'))->y;
        
        echo json_encode([
            "success" => true, 
            "student" => $student
        ]);
    } else {
        throw new Exception("Student not found");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}

$conn->close();
?>
