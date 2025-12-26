<?php
session_start();
require_once 'includes/functions.php';

// Check if librarian is logged in
if (!isLibrarianLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    $today = date('Y-m-d');
    
    // Get total students
    $stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'approved'");
    $totalStudents = $stmt->fetchColumn();
    
    // Get today's bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seat_bookings WHERE booking_date = ?");
    $stmt->execute([$today]);
    $todayBookings = $stmt->fetchColumn();
    
    // Get active bookings (booked status)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seat_bookings WHERE booking_date = ? AND status = 'booked'");
    $stmt->execute([$today]);
    $activeBookings = $stmt->fetchColumn();
    
    // Calculate attendance rate
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'attended' THEN 1 ELSE 0 END) as attended
        FROM seat_bookings 
        WHERE booking_date = ?
    ");
    $stmt->execute([$today]);
    $attendanceData = $stmt->fetch();
    
    $attendanceRate = 0;
    if ($attendanceData['total'] > 0) {
        $attendanceRate = round(($attendanceData['attended'] / $attendanceData['total']) * 100);
    }
    
    echo json_encode([
        'totalStudents' => (int)$totalStudents,
        'todayBookings' => (int)$todayBookings,
        'activeBookings' => (int)$activeBookings,
        'attendanceRate' => (int)$attendanceRate
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
    echo json_encode([
        'totalStudents' => 0,
        'todayBookings' => 0,
        'activeBookings' => 0,
        'attendanceRate' => 0
    ]);
}
?>
