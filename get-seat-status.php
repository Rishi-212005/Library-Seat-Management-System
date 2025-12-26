<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$date = $input['date'] ?? '';
$startTime = $input['start_time'] ?? 9;
$duration = $input['duration'] ?? 1;

if (empty($date)) {
    echo json_encode(['error' => 'Date is required']);
    exit;
}

try {
    // Calculate end time
    $endTime = $startTime + $duration;
    
    // Query to get all bookings for the selected date that overlap with the requested time
    $sql = "SELECT seat_id, status, start_time, duration, student_id, booking_code, attendance_code
            FROM seat_bookings 
            WHERE booking_date = :date 
            AND status IN ('booked', 'attended')
            AND (
                (start_time < :end_time AND (start_time + duration) > :start_time)
            )
            ORDER BY seat_id, start_time";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':date' => $date,
        ':start_time' => $startTime,
        ':end_time' => $endTime
    ]);
    
    $bookings = $stmt->fetchAll();
    
    // Initialize all seats as available
    $seatStatuses = [];
    
    // Regular seats (1-80)
    for ($i = 1; $i <= 80; $i++) {
        $seatStatuses[$i] = 'available';
    }
    
    // Computer seats (C1-C20)
    for ($i = 1; $i <= 20; $i++) {
        $seatStatuses["C$i"] = 'available';
    }
    
    // Update seat statuses based on bookings
    $conflictingSeats = [];
    $bookingDetails = [];
    
    foreach ($bookings as $booking) {
        $seatId = $booking['seat_id'];
        $status = $booking['status'];
        
        // Mark seat as occupied
        $seatStatuses[$seatId] = $status;
        $conflictingSeats[] = $seatId;
        
        $bookingDetails[] = [
            'seat_id' => $seatId,
            'status' => $status,
            'start_time' => $booking['start_time'],
            'duration' => $booking['duration'],
            'student_id' => $booking['student_id'],
            'booking_code' => $booking['booking_code'],
            'attendance_code' => $booking['attendance_code'],
            'time_slot' => sprintf('%02d:00 - %02d:00', $booking['start_time'], $booking['start_time'] + $booking['duration'])
        ];
    }
    
    // Get total bookings count for the date
    $countSql = "SELECT COUNT(*) as total FROM seat_bookings WHERE booking_date = :date";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([':date' => $date]);
    $totalBookings = $countStmt->fetch()['total'];
    
    $response = [
        'success' => true,
        'date' => $date,
        'start_time' => $startTime,
        'duration' => $duration,
        'seat_statuses' => $seatStatuses,
        'conflicting_seats' => $conflictingSeats,
        'booking_details' => $bookingDetails,
        'total_bookings' => $totalBookings,
        'debug' => [
            'query_params' => [
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime
            ],
            'bookings_found' => count($bookings),
            'sql_query' => $sql
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'success' => false
    ]);
}
?>
