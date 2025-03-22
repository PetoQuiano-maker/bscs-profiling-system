<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include '../config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['student_id']) && !empty($data['first_name']) && !empty($data['middle_name']) && !empty($data['last_name']) && !empty($data['email']) && !empty($data['phone']) && !empty($data['year_level']) && !empty($data['permanent_address']) && !empty($data['birthday']) && !empty($data['sex']) && !empty($data['citizenship']) && !empty($data['civil_status'])) {
    
    $student_id = $conn->real_escape_string($data['student_id']);
    
    // Automatically format the student_id as 22-1234
    if (strpos($student_id, '-') === false && strlen($student_id) >= 6) {
        $student_id = substr($student_id, 0, 2) . '-' . substr($student_id, 2);
    }
    
    $first_name = $conn->real_escape_string($data['first_name']);
    $middle_name = $conn->real_escape_string($data['middle_name']);
    $last_name = $conn->real_escape_string($data['last_name']);
    $email = $conn->real_escape_string($data['email']);
    
    $phone = $conn->real_escape_string($data['phone']);
    // Automatically format the phone number as 0912-345-6789
    if (strpos($phone, '-') === false && strlen($phone) == 11) {
        $phone = substr($phone, 0, 4) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7);
    }
    
    $year_level = $conn->real_escape_string($data['year_level']);
    $extension_name = !empty($data['extension_name']) ? $conn->real_escape_string($data['extension_name']) : '';
    $permanent_address = $conn->real_escape_string($data['permanent_address']);
    $birthday = $conn->real_escape_string($data['birthday']);
    $sex = $conn->real_escape_string($data['sex']);
    $citizenship = $conn->real_escape_string($data['citizenship']);
    $civil_status = $conn->real_escape_string($data['civil_status']);

    $sql = "INSERT INTO students (student_id, first_name, middle_name, last_name, extension_name, email, phone, year_level, permanent_address, birthday, sex, citizenship, civil_status) 
            VALUES ('$student_id', '$first_name', '$middle_name', '$last_name', '$extension_name', '$email', '$phone', '$year_level', '$permanent_address', '$birthday', '$sex', '$citizenship', '$civil_status')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["message" => "Student added successfully"]);
    } else {
        echo json_encode(["error" => "Error: " . $conn->error]);
    }
} else {
    echo json_encode(["error" => "Invalid input"]);
}

$conn->close();
?>
