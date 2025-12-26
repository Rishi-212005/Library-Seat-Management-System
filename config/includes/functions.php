<?php
require_once 'config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}

// Check if librarian is logged in
function isLibrarianLoggedIn() {
    return isset($_SESSION['librarian_id']) && !empty($_SESSION['librarian_id']);
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Check if GD extension is available
function isGDAvailable() {
    return extension_loaded('gd') && function_exists('imagecreatefromstring');
}

// Optimized image processing function (with GD) - ONLY called if GD is available
function processAndCompressImageWithGD($imageData, $maxWidth = 800, $maxHeight = 600, $quality = 75) {
    // Double check GD is available
    if (!isGDAvailable()) {
        return false;
    }
    
    try {
        // Create image from string
        $image = imagecreatefromstring($imageData);
        if (!$image) {
            return false;
        }
        
        // Get original dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        // Calculate new dimensions while maintaining aspect ratio
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        // Create new image with calculated dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG images
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        
        // Resize image
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Output to string
        ob_start();
        imagejpeg($newImage, null, $quality);
        $compressedImageData = ob_get_contents();
        ob_end_clean();
        
        // Clean up memory
        imagedestroy($image);
        imagedestroy($newImage);
        
        return base64_encode($compressedImageData);
    } catch (Exception $e) {
        return false;
    }
}

// Simple image compression without GD (fallback)
function compressImageSimple($imageData, $compressionLevel = 6) {
    try {
        // Use gzcompress to reduce file size
        $compressed = gzcompress($imageData, $compressionLevel);
        return base64_encode($compressed);
    } catch (Exception $e) {
        // If compression fails, just encode as base64
        return base64_encode($imageData);
    }
}

// Main function to validate and process uploaded image
function validateAndProcessImage($file, $maxSize = 1048576) { // 1MB limit
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload failed'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size too large. Maximum 1MB allowed.'];
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF allowed.'];
    }
    
    // Read image data
    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) {
        return ['success' => false, 'error' => 'Failed to read image file'];
    }
    
    // Try to compress with GD first, fallback to simple compression
    if (isGDAvailable()) {
        $compressedImage = processAndCompressImageWithGD($imageData);
        if ($compressedImage !== false) {
            return ['success' => true, 'data' => $compressedImage, 'method' => 'GD'];
        }
    }
    
    // Fallback: simple compression
    $compressedImage = compressImageSimple($imageData);
    return ['success' => true, 'data' => $compressedImage, 'method' => 'Simple'];
}

// Get student by roll number
function getStudentByRollNumber($rollNumber) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM students WHERE roll_number = ?");
    $stmt->execute([$rollNumber]);
    return $stmt->fetch();
}

// Get student by ID
function getStudentById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Check if roll number exists
function rollNumberExists($rollNumber, $excludeId = null) {
    $pdo = getDBConnection();
    $sql = "SELECT COUNT(*) FROM students WHERE roll_number = ?";
    $params = [$rollNumber];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

// Check if email exists
function emailExists($email, $excludeId = null) {
    $pdo = getDBConnection();
    $sql = "SELECT COUNT(*) FROM students WHERE email = ?";
    $params = [$email];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

// Get all students
function getAllStudents() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM students ORDER BY registration_date DESC");
    return $stmt->fetchAll();
}

// Update student status with issue reason
function updateStudentStatus($studentId, $status, $issue = 'No issue') {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE students SET status = ?, issue = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    return $stmt->execute([$status, $issue, $studentId]);
}

// Get seat bookings
function getSeatBookings($date = null, $timeSlot = null) {
    $pdo = getDBConnection();
    $sql = "SELECT sb.*, s.student_name, s.roll_number 
            FROM seat_bookings sb 
            JOIN students s ON sb.student_id = s.id 
            WHERE sb.status = 'active'";
    $params = [];
    
    if ($date) {
        $sql .= " AND sb.booking_date = ?";
        $params[] = $date;
    }
    
    if ($timeSlot) {
        $sql .= " AND sb.time_slot = ?";
        $params[] = $timeSlot;
    }
    
    $sql .= " ORDER BY sb.booking_date DESC, sb.start_time ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Check seat availability
function isSeatAvailable($seatId, $date, $timeSlots) {
    $pdo = getDBConnection();
    $placeholders = str_repeat('?,', count($timeSlots) - 1) . '?';
    $sql = "SELECT COUNT(*) FROM seat_bookings 
            WHERE seat_id = ? AND booking_date = ? AND time_slot IN ($placeholders) AND status = 'active'";
    
    $params = array_merge([$seatId, $date], $timeSlots);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchColumn() == 0;
}

// Create seat booking
function createSeatBooking($studentId, $seatId, $isComputer, $date, $startTime, $duration, $timeSlots) {
    $pdo = getDBConnection();
    
    try {
        $pdo->beginTransaction();
        
        foreach ($timeSlots as $timeSlot) {
            $stmt = $pdo->prepare("
                INSERT INTO seat_bookings (student_id, seat_id, is_computer, booking_date, start_time, duration, time_slot) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$studentId, $seatId, $isComputer, $date, $startTime, $duration, $timeSlot]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// Format time for display
function formatTime($hour) {
    if ($hour <= 12) {
        return $hour == 12 ? "12:00 PM" : sprintf("%d:00 AM", $hour);
    } else {
        return sprintf("%d:00 PM", $hour - 12);
    }
}

// Generate time slots
function generateTimeSlots($startTime, $duration) {
    $slots = [];
    $start = (int)$startTime;
    
    for ($i = 0; $i < $duration; $i++) {
        $hour = $start + $i;
        if ($hour >= 9 && $hour <= 17) {
            $timeSlot = sprintf("%02d:00-%02d:00", $hour, $hour + 1);
            $slots[] = $timeSlot;
        }
    }
    
    return $slots;
}
?>
