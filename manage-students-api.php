<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'get_students':
            $status = $_GET['status'] ?? 'all';
            
            $sql = "SELECT id, student_name, roll_number, course, email, phone, status, issue, 
                           registration_date, updated_at, passport_photo, id_card_photo 
                    FROM students";
            
            $params = [];
            if ($status !== 'all') {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY 
                      CASE status 
                        WHEN 'pending' THEN 1 
                        WHEN 'approved' THEN 2 
                        WHEN 'rejected' THEN 3 
                      END, 
                      registration_date DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll();
            
            // Process images for display
            foreach ($students as &$student) {
                $student['passport_photo'] = processImageForDisplay($student['passport_photo']);
                $student['id_card_photo'] = processImageForDisplay($student['id_card_photo']);
            }
            
            echo json_encode(['success' => true, 'students' => $students]);
            break;
            
        case 'get_statistics':
            $stats = [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0
            ];
            
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM students GROUP BY status");
            while ($row = $stmt->fetch()) {
                $stats[$row['status']] = (int)$row['count'];
                $stats['total'] += (int)$row['count'];
            }
            
            echo json_encode(['success' => true, 'statistics' => $stats]);
            break;
            
        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $studentId = (int)($input['student_id'] ?? 0);
            $status = $input['status'] ?? '';
            $issue = $input['issue'] ?? 'No issue';
            
            if (!$studentId || !in_array($status, ['pending', 'approved', 'rejected'])) {
                throw new Exception('Invalid parameters');
            }
            
            $stmt = $pdo->prepare("UPDATE students SET status = ?, issue = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([$status, $issue, $studentId]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Student status updated successfully']);
            } else {
                throw new Exception('Failed to update student status');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function processImageForDisplay($imageData) {
    if (empty($imageData)) {
        return null;
    }
    
    // Check if it's already a data URL
    if (strpos($imageData, 'data:image') === 0) {
        return $imageData;
    }
    
    // Try to decode base64 data
    $decoded = base64_decode($imageData, true);
    if ($decoded === false) {
        return null;
    }
    
    // Check if it's compressed data (starts with gzip header)
    if (substr($decoded, 0, 2) === "\x1f\x8b") {
        // It's gzipped, decompress it
        $decompressed = @gzuncompress($decoded);
        if ($decompressed !== false) {
            $decoded = $decompressed;
        }
    }
    
    // Detect image type and create data URL
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($decoded);
    
    if (strpos($mimeType, 'image/') === 0) {
        return 'data:' . $mimeType . ';base64,' . base64_encode($decoded);
    }
    
    return null;
}
?>
