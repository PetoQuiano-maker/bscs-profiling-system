<?php
function logAuditEvent($conn, $action, $student_id, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Begin transaction to ensure atomicity
    $conn->begin_transaction();
    
    try {
        $sql = "INSERT INTO audit_logs (action, student_id, details, ip_address) 
                VALUES (?, ?, ?, ?)";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $action, $student_id, $details, $ip_address);
        $result = $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        return $result;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error logging audit event: " . $e->getMessage());
        return false;
    }
}
?>
