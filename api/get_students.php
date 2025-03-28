<?php
include '../config.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT * FROM students ORDER BY student_id ASC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $students = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure consistent data format
        $students[] = array_map(function($value) {
            return $value === null ? '' : $value;
        }, $row);
    }

    echo json_encode($students);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>
