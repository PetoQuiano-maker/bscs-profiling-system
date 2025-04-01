<?php
header('Content-Type: application/json');
include '../config.php';

try {
    // Validate required fields
    $required_fields = ['student_id', 'first_name', 'last_name', 'email', 'phone', 
                       'year_level', 'permanent_address', 'birthday', 'sex', 
                       'citizenship', 'civil_status'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("$field is required");
        }
    }

    $student_id = $_POST['student_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'] ?? null;
    $last_name = $_POST['last_name'];
    $extension_name = $_POST['extension_name'] ?? null;
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $year_level = $_POST['year_level'];
    $permanent_address = $_POST['permanent_address'];
    $birthday = $_POST['birthday'];
    $sex = $_POST['sex'];
    $citizenship = $_POST['citizenship'];
    $civil_status = $_POST['civil_status'];

    // Check for duplicate email excluding current student
    $email_check_sql = "SELECT COUNT(*) as count FROM students WHERE email = ? AND student_id != ?";
    $stmt = $conn->prepare($email_check_sql);
    $stmt->bind_param("ss", $email, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        throw new Exception("Email already exists");
    }

    // Update student record
    $sql = "UPDATE students SET 
            first_name = ?, 
            middle_name = ?, 
            last_name = ?, 
            extension_name = ?,
            email = ?, 
            phone = ?, 
            year_level = ?, 
            permanent_address = ?, 
            birthday = ?, 
            sex = ?, 
            citizenship = ?, 
            civil_status = ? 
            WHERE student_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssissssss",
        $first_name, $middle_name, $last_name, $extension_name,
        $email, $phone, $year_level, $permanent_address,
        $birthday, $sex, $citizenship, $civil_status, $student_id
    );

    if ($stmt->execute()) {
        // Get updated student data
        $select_sql = "SELECT * FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($select_sql);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $updated_student = $stmt->get_result()->fetch_assoc();

        // Get updated stats
        $stats_sql = "SELECT 
            COUNT(*) as total_students,
            SUM(CASE WHEN sex = 'Male' THEN 1 ELSE 0 END) as male_count,
            SUM(CASE WHEN sex = 'Female' THEN 1 ELSE 0 END) as female_count
            FROM students";
        $stats_result = $conn->query($stats_sql);
        $stats = $stats_result->fetch_assoc();

        echo json_encode([
            'success' => true,
            'message' => 'Student updated successfully',
            'student' => $updated_student,
            'stats' => $stats
        ]);
    } else {
        throw new Exception("Error updating student: " . $conn->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
