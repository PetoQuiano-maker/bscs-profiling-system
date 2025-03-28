<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
    include '../config.php';
    require_once '../includes/validation.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Sanitize all inputs
    array_walk($_POST, function(&$value) {
        $value = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    });

    // Validate required fields
    $required_fields = [
        'student_id', 'first_name', 'last_name', 'email', 'phone',
        'year_level', 'permanent_address', 'birthday', 'sex',
        'citizenship', 'civil_status'
    ];
    
    $missing_fields = array_filter($required_fields, function($field) {
        return empty($_POST[$field]);
    });

    if (!empty($missing_fields)) {
        throw new Exception("Required fields missing: " . implode(', ', $missing_fields));
    }

    // Validate formats
    if (!validateStudentId($_POST['student_id'])) {
        throw new Exception("Invalid student ID format");
    }

    if (!validatePhoneNumber($_POST['phone'])) {
        throw new Exception("Invalid phone number format");
    }

    if (!validateEmail($_POST['email'])) {
        throw new Exception("Invalid email format");
    }

    if (!validateBirthDate($_POST['birthday'])) {
        throw new Exception("Invalid birth date. Student must be at least 16 years old and born before 2010");
    }

    // Capitalize names before checking duplicates and saving
    $_POST['first_name'] = properNameCase($_POST['first_name']);
    $_POST['middle_name'] = properNameCase($_POST['middle_name']);
    $_POST['last_name'] = properNameCase($_POST['last_name']);
    $_POST['extension_name'] = properNameCase($_POST['extension_name']);
    $_POST['permanent_address'] = properNameCase($_POST['permanent_address']);

    // Standardize citizenship
    $_POST['citizenship'] = standardizeCitizenship($_POST['citizenship']);

    // Check for duplicate student ID
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $_POST['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()['count'] > 0) {
        throw new Exception("Student ID already exists");
    }

    // Check for duplicate name
    $name_check_sql = "SELECT COUNT(*) as count FROM students WHERE 
                      first_name = ? AND 
                      COALESCE(middle_name, '') = COALESCE(?, '') AND 
                      last_name = ? AND 
                      COALESCE(extension_name, '') = COALESCE(?, '')";
    
    $stmt = $conn->prepare($name_check_sql);
    $stmt->bind_param("ssss", $_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['extension_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'duplicate_name',
            'message' => 'A student with this name already exists'
        ]);
        exit;
    }
    
    // Check for duplicate email
    $email_check_sql = "SELECT COUNT(*) as count FROM students WHERE LOWER(email) = LOWER(?)";
    $stmt = $conn->prepare($email_check_sql);
    $stmt->bind_param("s", $_POST['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'duplicate_email',
            'message' => 'This email address is already registered'
        ]);
        exit;
    }

    // Add check for duplicate phone number
    $phone_check_sql = "SELECT COUNT(*) as count FROM students WHERE phone = ?";
    $stmt = $conn->prepare($phone_check_sql);
    $stmt->bind_param("s", $_POST['phone']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'duplicate_phone',
            'message' => 'This phone number is already registered'
        ]);
        exit;
    }

    // Prepare insert statement
    $sql = "INSERT INTO students (
        student_id, first_name, middle_name, last_name, extension_name, 
        email, phone, year_level, permanent_address, birthday, 
        sex, citizenship, civil_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    // Capitalize names and address before saving
    $stmt->bind_param("sssssssssssss",
        $_POST['student_id'],
        properNameCase($_POST['first_name']),
        properNameCase($_POST['middle_name']),
        properNameCase($_POST['last_name']),
        properNameCase($_POST['extension_name']),
        strtolower($_POST['email']),
        $_POST['phone'],
        $_POST['year_level'],
        properNameCase(str_replace('Array', '', $_POST['permanent_address'])), // Remove any "Array" text
        $_POST['birthday'],
        $_POST['sex'],
        standardizeCitizenship($_POST['citizenship']),
        $_POST['civil_status']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error saving student: " . $stmt->error);
    }

    // Get updated stats and return response
    $stats = $conn->query("SELECT 
        (SELECT COUNT(*) FROM students) as total_students,
        (SELECT COUNT(*) FROM students WHERE sex = 'Male') as male_count,
        (SELECT COUNT(*) FROM students WHERE sex = 'Female') as female_count
    ")->fetch_assoc();

    echo json_encode([
        'success' => true,
        'message' => 'Student added successfully',
        'student' => array_merge($_POST, ['id' => $conn->insert_id]),
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log('Error in add_student.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
