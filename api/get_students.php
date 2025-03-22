<?php
include 'config.php';

$sql = "SELECT student_id, first_name, middle_name, last_name, extension_name, email, phone, year_level, permanent_address, birthday, sex, citizenship, civil_status FROM students";
$result = $conn->query($sql);

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);
$conn->close();
?>
