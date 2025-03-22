<?php
include '../config.php';

header('Content-Type: application/json');

try {
    // Add original_student_id to required fields
    $required_fields = ['original_student_id', 'student_id', 'first_name', 'last_name', 'email', 'phone', 
                       'year_level', 'permanent_address', 'birthday', 'sex', 
                       'citizenship', 'civil_status'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate middle name if provided
    if (!empty($_POST['middle_name'])) {
        if (strlen($_POST['middle_name']) < 2 || str_contains($_POST['middle_name'], '.')) {
            throw new Exception("Middle name must be complete (e.g., 'Ang' not 'A.')");
        }
    }

    // Sanitize inputs
    $original_student_id = mysqli_real_escape_string($conn, $_POST['original_student_id']);
    $new_student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name'] ?? '');
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $extension_name = mysqli_real_escape_string($conn, $_POST['extension_name'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $year_level = mysqli_real_escape_string($conn, $_POST['year_level']);
    $permanent_address = mysqli_real_escape_string($conn, $_POST['permanent_address']);
    $birthday = mysqli_real_escape_string($conn, $_POST['birthday']);
    $sex = mysqli_real_escape_string($conn, $_POST['sex']);
    $citizenship = mysqli_real_escape_string($conn, $_POST['citizenship']);
    $civil_status = mysqli_real_escape_string($conn, $_POST['civil_status']);

    // Check if student exists before proceeding
    $check_exists = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $check_exists->bind_param("s", $original_student_id);
    $check_exists->execute();
    $current_data = $check_exists->get_result()->fetch_assoc();
    
    if (!$current_data) {
        throw new Exception("Student not found");
    }

    // Check if student ID format is valid
    if (!preg_match('/^\d{2}-\d{4}$/', $new_student_id)) {
        throw new Exception("Student ID must be in format YY-#### (e.g., 22-4567)");
    }

    // Check if new student ID already exists (only if it's different from original)
    if ($original_student_id !== $new_student_id) {
        $check_id = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $check_id->bind_param("s", $new_student_id);
        $check_id->execute();
        if ($check_id->get_result()->num_rows > 0) {
            throw new Exception("New Student ID already exists");
        }
    }

    // Email validation
    $valid_domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'isu.edu.ph'];
    $email_domain = substr(strrchr($email, "@"), 1);
    if (!in_array($email_domain, $valid_domains)) {
        throw new Exception("Invalid email domain. Please use gmail.com, yahoo.com, outlook.com, or isu.edu.ph");
    }

    // Check for duplicates only if values have changed
    if ($email !== $current_data['email']) {
        $check_email = $conn->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
        $check_email->bind_param("ss", $email, $original_student_id);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            throw new Exception("Email already exists for another student");
        }
    }

    if ($phone !== $current_data['phone']) {
        $check_phone = $conn->prepare("SELECT student_id FROM students WHERE phone = ? AND student_id != ?");
        $check_phone->bind_param("ss", $phone, $original_student_id);
        $check_phone->execute();
        if ($check_phone->get_result()->num_rows > 0) {
            throw new Exception("Phone number already exists for another student");
        }
    }

    // First update the student ID if it has changed
    if ($original_student_id !== $new_student_id) {
        $update_id_sql = "UPDATE students SET student_id = ? WHERE student_id = ?";
        $update_id_stmt = $conn->prepare($update_id_sql);
        $update_id_stmt->bind_param("ss", $new_student_id, $original_student_id);
        if (!$update_id_stmt->execute()) {
            throw new Exception("Error updating student ID");
        }
    }

    // Update query
    $update_sql = "UPDATE students SET 
                   first_name = ?, middle_name = ?, last_name = ?, extension_name = ?,
                   email = ?, phone = ?, year_level = ?, permanent_address = ?,
                   birthday = ?, sex = ?, citizenship = ?, civil_status = ?
                   WHERE student_id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssssssssssss", 
        $first_name, $middle_name, $last_name, $extension_name,
        $email, $phone, $year_level, $permanent_address,
        $birthday, $sex, $citizenship, $civil_status,
        $new_student_id  // Use the new student ID here
    );

    if ($update_stmt->execute()) {
        echo json_encode([
            "success" => true, 
            "message" => "Student updated successfully",
            "student_id" => $new_student_id
        ]);
    } else {
        throw new Exception($update_stmt->error);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "error" => $e->getMessage(),
        "details" => $conn->error ?? null
    ]);
}

$conn->close();
?>
