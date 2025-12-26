<?php
require_once 'config/database.php';
require_once 'includes/email-functions.php';

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
    return isset($_SESSION['librarian_logged_in']) && $_SESSION['librarian_logged_in'] === true;
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

// Generate unique booking code
function generateUniqueBookingCode() {
    try {
        $pdo = getDBConnection();
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            // Generate a 8-character alphanumeric code
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
            
            // Check if code already exists in seat_bookings
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM seat_bookings WHERE booking_code = ?");
            $stmt->execute([$code]);
            $existsInBookings = $stmt->fetchColumn();
            
            // Check if code already exists in waiting_list
            $existsInWaitlist = 0;
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM waiting_list WHERE waiting_code = ?");
                $stmt->execute([$code]);
                $existsInWaitlist = $stmt->fetchColumn();
            } catch (PDOException $e) {
                // waiting_list table might not exist yet
                $existsInWaitlist = 0;
            }
            
            $attempts++;
            
        } while (($existsInBookings > 0 || $existsInWaitlist > 0) && $attempts < $maxAttempts);
        
        if ($attempts >= $maxAttempts) {
            // Fallback: use timestamp-based code
            $code = strtoupper(substr(md5(microtime()), 0, 8));
        }
        
        return $code;
        
    } catch (Exception $e) {
        error_log("Error generating booking code: " . $e->getMessage());
        // Fallback: use timestamp-based code
        return strtoupper(substr(md5(microtime()), 0, 8));
    }
}

// Generate unique attendance code
function generateUniqueAttendanceCode() {
    try {
        $pdo = getDBConnection();
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            // Generate a 6-character alphanumeric code with ATT- prefix
            $code = 'ATT-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
            
            // Check if code already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM seat_bookings WHERE attendance_code = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetchColumn();
            
            $attempts++;
            
        } while ($exists > 0 && $attempts < $maxAttempts);
        
        if ($attempts >= $maxAttempts) {
            // Fallback: use timestamp-based code
            $code = 'ATT-' . strtoupper(substr(md5(microtime()), 0, 6));
        }
        
        return $code;
        
    } catch (Exception $e) {
        error_log("Error generating attendance code: " . $e->getMessage());
        // Fallback: use timestamp-based code
        return 'ATT-' . strtoupper(substr(md5(microtime()), 0, 6));
    }
}

// Get student by roll number
function getStudentByRollNumber($rollNumber) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM students WHERE roll_number = ?");
        $stmt->execute([$rollNumber]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting student by roll number: " . $e->getMessage());
        return false;
    }
}

// Get student by ID
function getStudentById($id) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting student by ID: " . $e->getMessage());
        return false;
    }
}

// Check if student has active booking
function hasActiveBooking($studentId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM seat_bookings 
            WHERE student_id = ? AND status IN ('booked', 'attended') 
            AND booking_date >= CURDATE()
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("Error checking active booking: " . $e->getMessage());
        return false;
    }
}

// Check if roll number exists
function rollNumberExists($rollNumber, $excludeId = null) {
    try {
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
    } catch (Exception $e) {
        error_log("Error checking roll number existence: " . $e->getMessage());
        return false;
    }
}

// Check if email exists
function emailExists($email, $excludeId = null) {
    try {
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
    } catch (Exception $e) {
        error_log("Error checking email existence: " . $e->getMessage());
        return false;
    }
}

// Get all students
function getAllStudents() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT * FROM students ORDER BY registration_date DESC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting all students: " . $e->getMessage());
        return [];
    }
}

// Update student status with issue reason
function updateStudentStatus($studentId, $status, $issue = 'No issue') {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE students SET status = ?, issue = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$status, $issue, $studentId]);
    } catch (Exception $e) {
        error_log("Error updating student status: " . $e->getMessage());
        return false;
    }
}

// Check seat availability for specific time slots (no overlapping)
function checkSeatAvailability($seatId, $date, $startTime, $duration) {
    try {
        $pdo = getDBConnection();
        
        // Validate inputs
        if (empty($seatId) || empty($date) || empty($startTime) || empty($duration)) {
            return ['available' => false, 'error' => 'Invalid parameters'];
        }
        
        $endTime = $startTime + $duration;
        
        // Check for overlapping bookings
        $sql = "SELECT COUNT(*) FROM seat_bookings 
                WHERE seat_id = ? AND booking_date = ? AND status IN ('booked', 'attended')
                AND NOT (start_time >= ? OR (start_time + duration) <= ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$seatId, $date, $endTime, $startTime]);
        
        $overlappingCount = $stmt->fetchColumn();
        
        return ['available' => $overlappingCount == 0];
        
    } catch (Exception $e) {
        error_log("Availability check failed: " . $e->getMessage());
        return ['available' => false, 'error' => $e->getMessage()];
    }
}

// Create seat booking with attendance code
function createSeatBooking($studentId, $seatId, $isComputer, $date, $startTime, $duration) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Check if student already has an active booking
        if (hasActiveBooking($studentId)) {
            throw new Exception("You already have an active booking. Only one booking per student is allowed.");
        }
        
        // Validate inputs
        if (empty($studentId) || empty($seatId) || empty($date) || empty($startTime) || empty($duration)) {
            throw new Exception("Missing required booking parameters");
        }
        
        // Validate student exists
        $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        if (!$stmt->fetch()) {
            throw new Exception("Student not found with ID: $studentId");
        }
        
        // Validate date format
        if (!DateTime::createFromFormat('Y-m-d', $date)) {
            throw new Exception("Invalid date format: $date");
        }
        
        // Validate start time and duration
        if (!is_numeric($startTime) || $startTime < 9 || $startTime > 16) {
            throw new Exception("Invalid start time: $startTime");
        }
        
        if (!is_numeric($duration) || $duration < 1 || $duration > 8) {
            throw new Exception("Invalid duration: $duration");
        }
        
        // Check seat availability
        $availability = checkSeatAvailability($seatId, $date, $startTime, $duration);
        if (!$availability['available']) {
            throw new Exception("Seat is not available for the selected time slot");
        }
        
        // Generate codes
        $bookingCode = generateUniqueBookingCode();
        $attendanceCode = generateUniqueAttendanceCode();
        
        // Insert booking record
        $stmt = $pdo->prepare("
            INSERT INTO seat_bookings (
                student_id, seat_id, is_computer, booking_date, start_time, duration, 
                booking_code, attendance_code, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'booked', NOW())
        ");
        
        $result = $stmt->execute([
            $studentId, 
            $seatId, 
            $isComputer ? 1 : 0, 
            $date, 
            $startTime, 
            $duration, 
            $bookingCode,
            $attendanceCode
        ]);
        
        if (!$result) {
            throw new Exception("Failed to create booking");
        }
        
        $pdo->commit();
        return [
            'success' => true,
            'booking_code' => $bookingCode,
            'attendance_code' => $attendanceCode
        ];
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Booking creation failed: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Get current student bookings
function getCurrentStudentBookings($studentId) {
    try {
        $pdo = getDBConnection();
        
        $sql = "SELECT seat_id, booking_date, start_time, duration, booking_code, attendance_code, 
                       status, check_in_time, created_at
                FROM seat_bookings 
                WHERE student_id = ? AND booking_date >= CURDATE()
                ORDER BY booking_date ASC, start_time ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error getting current student bookings: " . $e->getMessage());
        return [];
    }
}

// Get booked seats for a specific date and time range
function getBookedSeats($date, $startTime = null, $duration = null) {
    try {
        $pdo = getDBConnection();
        
        $sql = "SELECT seat_id, start_time, duration, status FROM seat_bookings 
                WHERE booking_date = ? AND status IN ('booked', 'attended')";
        $params = [$date];
        
        if ($startTime !== null && $duration !== null) {
            $endTime = $startTime + $duration;
            $sql .= " AND NOT (start_time >= ? OR (start_time + duration) <= ?)";
            $params[] = $endTime;
            $params[] = $startTime;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error getting booked seats: " . $e->getMessage());
        return [];
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

// Cancel booking function
function cancelBooking($bookingCode, $reason = 'Cancelled by student') {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Update booking status
        $stmt = $pdo->prepare("
            UPDATE seat_bookings 
            SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP 
            WHERE booking_code = ? AND status IN ('booked')
        ");
        $result = $stmt->execute([$bookingCode]);
        
        $pdo->commit();
        return $result;
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error cancelling booking: " . $e->getMessage());
        return false;
    }
}

// Mark attendance (for librarian portal) - ENHANCED VERSION
function markAttendance($attendanceCode) {
    try {
        $pdo = getDBConnection();
        $pdo->exec("SET time_zone = '+05:30'"); // Set MySQL to India time
        $pdo->beginTransaction();
        
        // First, check if the attendance code exists and get booking details
        $stmt = $pdo->prepare("
            SELECT sb.*, s.student_name, s.roll_number 
            FROM seat_bookings sb
            LEFT JOIN students s ON sb.student_id = s.id
            WHERE sb.attendance_code = ?
        ");
        $stmt->execute([$attendanceCode]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Invalid attendance code. Please check the code and try again.'];
        }
        
        if ($booking['status'] === 'attended') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Student has already been checked in at ' . date('H:i', strtotime($booking['check_in_time']))];
        }
        
        if ($booking['status'] === 'cancelled') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'This booking has been cancelled and cannot be checked in.'];
        }
        
        if ($booking['status'] !== 'booked') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Invalid booking status. Only confirmed bookings can be checked in.'];
        }
        
        // Check if the booking is for today
        $bookingDate = $booking['booking_date'];
        $today = date('Y-m-d');
        
        if ($bookingDate !== $today) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'This booking is for ' . date('M d, Y', strtotime($bookingDate)) . '. Only today\'s bookings can be checked in.'];
        }
        
        // Time window validation with India timezone
        $indiaTime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $now = $indiaTime;
        
        $bookingDateTimeStr = $booking['booking_date'] . ' ' . str_pad($booking['start_time'], 2, '0', STR_PAD_LEFT) . ':00';
        $bookingTime = DateTime::createFromFormat('Y-m-d H:i', $bookingDateTimeStr, new DateTimeZone('Asia/Kolkata'));
        
        $earlyWindow = clone $bookingTime;
        $lateWindow = clone $bookingTime;
        $earlyWindow->modify('-90 minutes');
        $lateWindow->modify('+15 minutes');
        
        if ($now < $earlyWindow) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Check-in not allowed yet. You can check in only within 90 minutes before start time.'];
        } elseif ($now > $lateWindow) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Check-in window closed. You are more than 15 minutes late.'];
        }
        
        // Update booking status to attended with timezone-aware timestamp
        $stmt = $pdo->prepare("
            UPDATE seat_bookings 
            SET status = 'attended', 
                check_in_time = CONVERT_TZ(NOW(), 'UTC', '+05:30'), 
                updated_at = CONVERT_TZ(NOW(), 'UTC', '+05:30')
            WHERE attendance_code = ? AND status = 'booked'
        ");
        $result = $stmt->execute([$attendanceCode]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            
            $studentName = $booking['student_name'] ?? 'Unknown Student';
            $seatId = $booking['seat_id'];
            $startTime = formatTime($booking['start_time']);
            $duration = $booking['duration'];
            
            return [
                'success' => true, 
                'message' => "✅ Check-in successful!\n\nStudent: {$studentName}\nSeat: {$seatId}\nTime: {$startTime} ({$duration} hour" . ($duration > 1 ? 's' : '') . ")",
                'booking_details' => [
                    'student_name' => $studentName,
                    'seat_id' => $seatId,
                    'start_time' => $startTime,
                    'duration' => $duration,
                    'check_in_time' => $indiaTime->format('H:i')
                ]
            ];
        } else {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to update attendance. Please try again.'];
        }
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error marking attendance: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred. Please contact system administrator.'];
    }
}

// Auto-cancel late bookings (to be run via cron job)
function autoCancelLateBookings() {
    try {
        $pdo = getDBConnection();
        
        $currentTime = new DateTime();
        $currentDate = $currentTime->format('Y-m-d');
        $currentHour = (int)$currentTime->format('H');
        
        // Find bookings that are more than 15 minutes late
        $stmt = $pdo->prepare("
            UPDATE seat_bookings 
            SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP
            WHERE booking_date = ? AND start_time <= ? AND status = 'booked'
            AND TIMESTAMPDIFF(MINUTE, CONCAT(booking_date, ' ', LPAD(start_time, 2, '0'), ':00:00'), NOW()) > 15
        ");
        $stmt->execute([$currentDate, $currentHour]);
        
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Error in auto-cancel late bookings: " . $e->getMessage());
        return 0;
    }
}

// Get all seat statuses for librarian view (using hardcoded seat list)
function getAllSeatsWithStatus() {
    global $conn;

    // Step 1: Define all seat IDs manually
    $seats = [];
    $allSeatIds = ['1','2','3','4','5','c1','c2','c3']; // Add or change as needed
    foreach ($allSeatIds as $seatId) {
        $seats[$seatId] = 'available';
    }

    // Step 2: Update status based on today's bookings
    $bookingQuery = "SELECT seat_id, status FROM seat_bookings WHERE booking_date = CURDATE()";
    $bookingResult = mysqli_query($conn, $bookingQuery);
    while ($row = mysqli_fetch_assoc($bookingResult)) {
        $dbStatus = strtolower($row['status']);
        if ($dbStatus === 'confirmed') {
            $seats[$row['seat_id']] = 'booked';
        } elseif ($dbStatus === 'active') {
            $seats[$row['seat_id']] = 'attended';
        }
    }

    // Step 3: Return as array of [seat_id => status]
    $seatStatusList = [];
    foreach ($seats as $seat_id => $status) {
        $seatStatusList[] = [
            'seat_id' => $seat_id,
            'status' => $status
        ];
    }

    return $seatStatusList;
}

?>
