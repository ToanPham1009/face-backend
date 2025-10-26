<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

error_reporting(0);
ini_set('display_errors', 0);

include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Kiểm tra session_id
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            throw new Exception('Session ID is required');
        }

        // Kiểm tra file video
        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Video file is required. Error code: ' . $_FILES['video']['error']);
        }

        $session_id = intval($_POST['session_id']);
        $start_time = $_POST['start_time'] ?? date('Y-m-d H:i:s');
        $end_time = $_POST['end_time'] ?? date('Y-m-d H:i:s');

        // Tạo thư mục lưu video
        $video_dir = __DIR__ . '/../videos/';
        if (!file_exists($video_dir)) {
            if (!mkdir($video_dir, 0755, true)) {
                throw new Exception('Cannot create videos directory');
            }
        }

        // Kiểm tra dung lượng file
        $max_file_size = 100 * 1024 * 1024; // 100MB
        if ($_FILES['video']['size'] > $max_file_size) {
            throw new Exception('Video file too large. Maximum size: 100MB');
        }

        // Tạo tên file an toàn
        $timestamp = date('Y-m-d_H-i-s');
        $random_string = bin2hex(random_bytes(4));
        $file_name = "session_{$session_id}_{$timestamp}_{$random_string}.webm";
        $file_path = $video_dir . $file_name;

        // Di chuyển file upload
        if (!move_uploaded_file($_FILES['video']['tmp_name'], $file_path)) {
            throw new Exception('Failed to save video file to server');
        }

        // Kiểm tra file đã được lưu
        if (!file_exists($file_path)) {
            throw new Exception('Video file was not saved properly');
        }

        $file_size = filesize($file_path);

        // Lưu thông tin video vào database
        $database = new Database();
        $db = $database->getConnection();

        $query = "INSERT INTO session_videos 
                 SET session_id=:session_id, file_path=:file_path, 
                     start_time=:start_time, end_time=:end_time, file_size=:file_size";
        $stmt = $db->prepare($query);

        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":file_path", $file_name);
        $stmt->bindParam(":start_time", $start_time);
        $stmt->bindParam(":end_time", $end_time);
        $stmt->bindParam(":file_size", $file_size);

        if ($stmt->execute()) {
            $video_id = $db->lastInsertId();
            
            error_log("Video saved: ID {$video_id}, Session {$session_id}, Size: {$file_size} bytes");
            
            echo json_encode([
                'success' => true,
                'message' => 'Video saved successfully',
                'file_path' => $file_name,
                'file_size' => $file_size,
                'video_id' => $video_id,
                'file_url' => '/videos/' . $file_name
            ]);
        } else {
            // Xóa file nếu không lưu được vào database
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            throw new Exception('Failed to save video information to database');
        }

    } catch (Exception $e) {
        error_log("Video upload error: " . $e->getMessage());
        
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