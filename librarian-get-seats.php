<?php
session_start();
require_once 'includes/functions.php';

// Check if librarian is logged in
if (!isLibrarianLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

try {
    $date = sanitizeInput($_POST['date'] ?? date('Y-m-d'));
    
    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        throw new Exception('Invalid date format');
    }
    
    $pdo = getDBConnection();
    
    // Get all bookings for the selected date
    $stmt = $pdo->prepare("
        SELECT 
            sb.seat_id,
            sb.start_time,
            sb.duration,
            sb.status,
            sb.booking_code,
            sb.attendance_code,
            sb.check_in_time,
            sb.created_at,
            s.student_name,
            s.roll_number,
            s.email
        FROM seat_bookings sb
        LEFT JOIN students s ON sb.student_id = s.id
        WHERE sb.booking_date = ?
        ORDER BY sb.seat_id, sb.start_time
    ");
    
    $stmt->execute([$date]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize bookings by seat_id
    $seatData = [];
    $stats = [
        'total' => 0,
        'booked' => 0,
        'attended' => 0,
        'cancelled' => 0
    ];
    
    foreach ($bookings as $booking) {
        $seatId = $booking['seat_id'];
        
        // For multiple bookings on same seat, prioritize by status
        if (!isset($seatData[$seatId]) || 
            ($booking['status'] === 'attended' && $seatData[$seatId]['status'] !== 'attended') ||
            ($booking['status'] === 'booked' && $seatData[$seatId]['status'] === 'cancelled')) {
            
            $seatData[$seatId] = [
                'seat_id' => $seatId,
                'student_name' => $booking['student_name'] ?? 'Unknown',
                'roll_number' => $booking['roll_number'] ?? 'N/A',
                'email' => $booking['email'] ?? 'N/A',
                'start_time' => (int)$booking['start_time'],
                'duration' => (int)$booking['duration'],
                'status' => $booking['status'],
                'booking_code' => $booking['booking_code'],
                'attendance_code' => $booking['attendance_code'],
                'check_in_time' => $booking['check_in_time'] ? date('H:i', strtotime($booking['check_in_time'])) : null,
                'created_at' => $booking['created_at']
            ];
        }
        
        // Count statistics
        $stats['total']++;
        $stats[$booking['status']]++;
    }
    
    echo json_encode([
        'success' => true,
        'seats' => $seatData,
        'stats' => $stats,
        'date' => $date,
        'message' => 'Seat data loaded successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error in librarian-get-seats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load seat data: ' . $e->getMessage(),
        'seats' => [],
        'stats' => ['total' => 0, 'booked' => 0, 'attended' => 0, 'cancelled' => 0]
    ]);
}
?>
