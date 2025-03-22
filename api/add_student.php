<?php
include '../config.php';

header('Content-Type: application/json');

try {
    // Validate required fields
    $required_fields = ['student_id', 'first_name', 'last_name', 'email', 'phone', 
                       'year_level', 'permanent_address', 'birthday', 'sex', 
                       'citizenship', 'civil_status'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate student ID format (YY-####)
    if (!preg_match('/^\d{2}-\d{4}$/', $_POST['student_id'])) {
        throw new Exception("Student ID must be in format YY-#### (e.g., 22-4567)");
    }

    // Validate phone number format (0XXX-XXX-XXXX)
    if (!preg_match('/^0\d{3}-\d{3}-\d{4}$/', $_POST['phone'])) {
        throw new Exception("Phone number must be in format 0XXX-XXX-XXXX (e.g., 0912-345-6789)");
    }

    // Validate middle name if provided
    if (!empty($_POST['middle_name'])) {
        if (strlen($_POST['middle_name']) < 2 || str_contains($_POST['middle_name'], '.')) {
            throw new Exception("Middle name must be complete (e.g., 'Ang' not 'A.')");
        }
    }

    // Sanitize inputs
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
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

    // Add email domain validation
    $valid_domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'isu.edu.ph'];
    $email_domain = substr(strrchr($email, "@"), 1);
    if (!in_array($email_domain, $valid_domains)) {
        throw new Exception("Invalid email domain. Please use gmail.com, yahoo.com, outlook.com, or isu.edu.ph");
    }

    // Check for duplicate student ID
    $check_id_sql = "SELECT student_id FROM students WHERE student_id = ?";
    $check_id_stmt = $conn->prepare($check_id_sql);
    $check_id_stmt->bind_param("s", $student_id);
    $check_id_stmt->execute();
    if ($check_id_stmt->get_result()->num_rows > 0) {
        throw new Exception("Student ID already exists");
    }

    // Check for duplicate email
    $check_email_sql = "SELECT student_id FROM students WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    if ($check_email_stmt->get_result()->num_rows > 0) {
        throw new Exception("Email address already registered");
    }

    // Check for duplicate phone number
    $check_phone_sql = "SELECT student_id FROM students WHERE phone = ?";
    $check_phone_stmt = $conn->prepare($check_phone_sql);
    $check_phone_stmt->bind_param("s", $phone);
    $check_phone_stmt->execute();
    if ($check_phone_stmt->get_result()->num_rows > 0) {
        throw new Exception("Phone number already registered");
    }

    // Check for duplicate name combination
    $check_name_sql = "SELECT student_id FROM students WHERE first_name = ? AND middle_name = ? AND last_name = ? AND extension_name = ?";
    $check_name_stmt = $conn->prepare($check_name_sql);
    $check_name_stmt->bind_param("ssss", $first_name, $middle_name, $last_name, $extension_name);
    $check_name_stmt->execute();
    if ($check_name_stmt->get_result()->num_rows > 0) {
        throw new Exception("Student with this name combination already exists");
    }

    // Insert new student
    $sql = "INSERT INTO students (student_id, first_name, middle_name, last_name, 
            extension_name, email, phone, year_level, permanent_address, birthday, 
            sex, citizenship, civil_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssss", 
        $student_id, $first_name, $middle_name, $last_name, $extension_name,
        $email, $phone, $year_level, $permanent_address, $birthday,
        $sex, $citizenship, $civil_status
    );

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Student added successfully"]);
    } else {
        throw new Exception("Error executing query: " . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
}

$conn->close();
?>
