<?php
include 'config.php';

header('Content-Type: application/json');

function backup() {
    global $conn, $dbname;
    
    try {
        $tables = array('students', 'audit_logs');
        $backup = array(
            'metadata' => array(
                'timestamp' => date('Y-m-d H:i:s'),
                'database' => $dbname,
                'version' => '1.0'
            ),
            'tables' => array()
        );
        
        foreach ($tables as $table) {
            // Get table structure
            $structure = array();
            $structureResult = $conn->query("DESCRIBE $table");
            while ($row = $structureResult->fetch_assoc()) {
                $structure[] = $row;
            }
            
            // Get table data
            $result = $conn->query("SELECT * FROM $table");
            $rows = array();
            while ($row = $result->fetch_assoc()) {
                // Convert all values to appropriate types
                foreach ($row as $key => $value) {
                    if (is_numeric($value)) {
                        $row[$key] = $value + 0; // Convert to number if numeric
                    }
                }
                $rows[] = $row;
            }
            
            $backup['tables'][$table] = array(
                'structure' => $structure,
                'data' => $rows
            );
        }
        
        $backupDir = __DIR__ . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        
        $filename = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($filename, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return ['success' => true, 'message' => 'Backup created successfully', 'filename' => basename($filename)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()];
    }
}

function restore($file) {
    global $conn;
    
    try {
        if (!isset($_FILES[$file])) {
            throw new Exception('No file uploaded');
        }
        
        $uploadedFile = $_FILES[$file]['tmp_name'];
        $content = file_get_contents($uploadedFile);
        $backup = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid backup file format');
        }
        
        // Validate backup structure
        if (!isset($backup['metadata']) || !isset($backup['tables'])) {
            throw new Exception('Invalid backup file structure');
        }
        
        $conn->begin_transaction();
        
        // Restore tables
        foreach ($backup['tables'] as $tableName => $tableData) {
            if (!isset($tableData['data'])) {
                continue;
            }
            
            // Clear existing data
            $conn->query("TRUNCATE TABLE `$tableName`");
            
            // Insert new data
            foreach ($tableData['data'] as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                
                // Prepare the SQL statement
                $sql = "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES (" . 
                       implode(', ', array_fill(0, count($values), '?')) . ")";
                
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    throw new Exception("Error preparing statement: " . $conn->error);
                }
                
                // Bind parameters with appropriate types
                $types = '';
                foreach ($values as $value) {
                    if (is_int($value)) $types .= 'i';
                    elseif (is_float($value)) $types .= 'd';
                    else $types .= 's';
                }
                
                $stmt->bind_param($types, ...$values);
                if (!$stmt->execute()) {
                    throw new Exception("Error executing statement: " . $stmt->error);
                }
                $stmt->close();
            }
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Database restored successfully'];
    } catch (Exception $e) {
        if ($conn->connect_error === null) {
            $conn->rollback();
        }
        return ['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'backup':
            echo json_encode(backup());
            break;
        case 'restore':
            echo json_encode(restore('backupFile'));
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>
