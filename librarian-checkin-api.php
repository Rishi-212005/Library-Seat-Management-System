<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

// Database connection
$conn = new mysqli("sql200.infinityfree.com", "if0_39418937", "library2025", "if0_39418937_library_management");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Set MySQL timezone
if (!$conn->query("SET time_zone = '+05:30'")) {
    echo json_encode(['success' => false, 'message' => 'Failed to set MySQL timezone: ' . $conn->error]);
    exit();
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['attendance_code']) || empty($data['attendance_code'])) {
    echo json_encode(['success' => false, 'message' => 'Attendance code is required.']);
    exit();
}

$attendance_code = $conn->real_escape_string($data['attendance_code']);

// Query the booking record
$sql = "SELECT * FROM seat_bookings WHERE attendance_code = '$attendance_code' AND status = 'booked' LIMIT 1";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $conn->error]);
    exit();
}

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // Time window check
    $bookingDateTimeStr = $row['booking_date'] . ' ' . str_pad($row['start_time'], 2, '0', STR_PAD_LEFT) . ':00';
    $bookingTime = DateTime::createFromFormat('Y-m-d H:i', $bookingDateTimeStr);
    $now = new DateTime();

    $earlyWindow = clone $bookingTime;
    $lateWindow = clone $bookingTime;
    $earlyWindow->modify('-30 minutes');
    $lateWindow->modify('+15 minutes');

    if ($now < $earlyWindow) {
        echo json_encode(['success' => false, 'message' => 'Check-in not allowed yet. You can check in only within 30 minutes before start time.']);
        exit();
    } elseif ($now > $lateWindow) {
        echo json_encode(['success' => false, 'message' => 'Check-in window closed. You are more than 15 minutes late.']);
        exit();
    }

    // Fetch student details
    $studentId = $row['student_id'];
    $studentSql = "SELECT * FROM students WHERE id = $studentId LIMIT 1";
    $studentResult = $conn->query($studentSql);

    if (!$studentResult) {
        echo json_encode(['success' => false, 'message' => 'Student query failed: ' . $conn->error]);
        exit();
    }
    
    if ($studentResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit();
    }
    
    $student = $studentResult->fetch_assoc();

    // Update booking status
    $updateSql = "UPDATE seat_bookings 
                 SET status = 'attended', 
                     check_in_time = NULL
                 WHERE id = {$row['id']}";

    if ($conn->query($updateSql)) {
        // Log the check-in
        $insertLogSql = "INSERT INTO checkin_logs (
            student_id, student_name, roll_number, course, email, phone,
            seat_id, is_computer, booking_date, start_time, duration,
            time_slot, booking_code, attendance_code, checkin_time
        ) VALUES (
            {$row['student_id']},
            '{$conn->real_escape_string($student['student_name'])}',
            '{$conn->real_escape_string($student['roll_number'])}',
            '{$conn->real_escape_string($student['course'])}',
            '{$conn->real_escape_string($student['email'])}',
            '{$conn->real_escape_string($student['phone'])}',
            '{$row['seat_id']}',
            {$row['is_computer']},
            '{$row['booking_date']}',
            {$row['start_time']},
            {$row['duration']},
            '{$row['time_slot']}',
            '{$row['booking_code']}',
            '{$row['attendance_code']}',
            NOW()
        )";
        
        if ($conn->query($insertLogSql)) {
            echo json_encode(['success' => true, 'message' => 'Student checked in successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to log check-in: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid or already used attendance code.']);
}

$conn->close();
?>