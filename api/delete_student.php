<?php 
include '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $student_id = $_POST['student_id'] ?? '';
        
        if (empty($student_id)) {
            throw new Exception('Student ID is required');
        }

        // Check if student exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE student_id = ?");
        $check_stmt->bind_param("s", $student_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->fetch_assoc()['count'] === 0) {
            throw new Exception('Student not found');
        }

        // Delete the student
        $delete_stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        $delete_stmt->bind_param("s", $student_id);
        
        if ($delete_stmt->execute()) {
            // Get updated stats after deleting student
            $total_sql = "SELECT COUNT(*) as total FROM students";
            $male_sql = "SELECT COUNT(*) as count FROM students WHERE sex = 'Male'";
            $female_sql = "SELECT COUNT(*) as count FROM students WHERE sex = 'Female'";

            $total_result = $conn->query($total_sql);
            $male_result = $conn->query($male_sql);
            $female_result = $conn->query($female_sql);

            $stats = [
                'total_students' => $total_result->fetch_assoc()['total'],
                'male_count' => $male_result->fetch_assoc()['count'],
                'female_count' => $female_result->fetch_assoc()['count']
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Student deleted successfully',
                'student_id' => $student_id,
                'stats' => $stats
            ]);
        } else {
            throw new Exception('Error deleting student');
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    $conn->close();
}
?>
