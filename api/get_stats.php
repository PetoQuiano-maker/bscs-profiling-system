<?php
include '../config.php';

header('Content-Type: application/json');

try {
    // Get total students count
    $total_sql = "SELECT COUNT(*) as total FROM students";
    $total_result = $conn->query($total_sql);
    $total_students = $total_result->fetch_assoc()['total'];

    // Get gender counts
    $male_sql = "SELECT COUNT(*) as count FROM students WHERE sex = 'Male'";
    $female_sql = "SELECT COUNT(*) as count FROM students WHERE sex = 'Female'";

    $male_result = $conn->query($male_sql);
    $female_result = $conn->query($female_sql);

    $male_count = $male_result->fetch_assoc()['count'];
    $female_count = $female_result->fetch_assoc()['count'];

    echo json_encode([
        'success' => true,
        'total_students' => $total_students,
        'male_count' => $male_count,
        'female_count' => $female_count
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
