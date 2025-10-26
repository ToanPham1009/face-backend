<?php
// Thêm các header và xử lý CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Tắt hiển thị lỗi trên production
error_reporting(0);
ini_set('display_errors', 0);

include 'database.php';

// Đảm bảo chỉ xử lý POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Nhận và decode JSON data
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data');
        }
        
        $database = new Database();
        $db = $database->getConnection();
        
        if (!isset($data['action'])) {
            throw new Exception('Action is required');
        }
        
        if ($data['action'] === 'create') {
            // Tạo session mới
            if (!isset($data['name']) || !isset($data['start_time'])) {
                throw new Exception('Name and start_time are required');
            }
            
            $query = "INSERT INTO sessions SET name=:name, start_time=:start_time";
            $stmt = $db->prepare($query);
            
            $name = $data['name'];
            $start_time = $data['start_time'];
            
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":start_time", $start_time);
            
            if ($stmt->execute()) {
                $session_id = $db->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'session_id' => $session_id,
                    'message' => 'Session created successfully'
                ]);
            } else {
                throw new Exception('Failed to create session in database');
            }
            
        } elseif ($data['action'] === 'update') {
            // Cập nhật session
            if (!isset($data['session_id']) || !isset($data['end_time']) || !isset($data['total_faces'])) {
                throw new Exception('session_id, end_time and total_faces are required');
            }
            
            $query = "UPDATE sessions SET end_time=:end_time, total_faces=:total_faces WHERE id=:session_id";
            $stmt = $db->prepare($query);
            
            $session_id = $data['session_id'];
            $end_time = $data['end_time'];
            $total_faces = $data['total_faces'];
            
            $stmt->bindParam(":end_time", $end_time);
            $stmt->bindParam(":total_faces", $total_faces);
            $stmt->bindParam(":session_id", $session_id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Session updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update session in database');
            }
        } else {
            throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Only POST requests are allowed'
    ]);
}
?>