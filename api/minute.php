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

// Tắt hiển thị lỗi
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
        
        // Validate required fields
        if (!isset($data['session_id']) || !isset($data['start_time']) || 
            !isset($data['end_time']) || !isset($data['face_count'])) {
            throw new Exception('All fields are required: session_id, start_time, end_time, face_count');
        }
        
        $database = new Database();
        $db = $database->getConnection();
        
        // Tính thời lượng (để debug)
        $start = new DateTime($data['start_time']);
        $end = new DateTime($data['end_time']);
        $duration = $start->diff($end)->format('%I:%S');
        
        $query = "INSERT INTO minutes SET session_id=:session_id, start_time=:start_time, end_time=:end_time, face_count=:face_count";
        $stmt = $db->prepare($query);
        
        $session_id = $data['session_id'];
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        $face_count = $data['face_count'];
        
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":start_time", $start_time);
        $stmt->bindParam(":end_time", $end_time);
        $stmt->bindParam(":face_count", $face_count);
        
        if ($stmt->execute()) {
            error_log("Minute data saved: Session $session_id, $face_count faces, Duration: $duration");
            
            echo json_encode([
                'success' => true,
                'message' => 'Minute data saved successfully',
                'minute_id' => $db->lastInsertId(),
                'duration' => $duration,
                'faces' => $face_count
            ]);
        } else {
            throw new Exception('Failed to save minute data in database');
        }
        
    } catch (Exception $e) {
        error_log("Minute data save error: " . $e->getMessage());
        
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