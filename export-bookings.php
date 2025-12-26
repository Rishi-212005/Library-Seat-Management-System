<?php
session_start();
require_once 'includes/functions.php';

// Check if librarian is logged in
if (!isset($_SESSION['librarian_logged_in']) || $_SESSION['librarian_logged_in'] !== true) {
    header('Location: librarian-login-final.html');
    exit();
}

$date = sanitizeInput($_GET['date'] ?? date('Y-m-d'));

try {
    $pdo = getDBConnection();
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
            s.email,
            s.phone
        FROM seat_bookings sb
        LEFT JOIN students s ON sb.student_id = s.id
        WHERE sb.booking_date = ?
        ORDER BY sb.seat_id, sb.start_time
    ");
    
    $stmt->execute([$date]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="library_bookings_' . $date . '.csv"');
    
    // Create CSV output
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Date',
        'Seat ID',
        'Student Name',
        'Roll Number',
        'Email',
        'Phone',
        'Start Time',
        'Duration (Hours)',
        'Status',
        'Booking Code',
        'Attendance Code',
        'Check-in Time',
        'Booking Created'
    ]);
    
    // CSV data
    foreach ($bookings as $booking) {
        fputcsv($output, [
            $date,
            $booking['seat_id'],
            $booking['student_name'] ?? 'N/A',
            $booking['roll_number'] ?? 'N/A',
            $booking['email'] ?? 'N/A',
            $booking['phone'] ?? 'N/A',
            sprintf('%02d:00', $booking['start_time']),
            $booking['duration'],
            ucfirst($booking['status']),
            $booking['booking_code'],
            $booking['attendance_code'],
            $booking['check_in_time'] ? date('H:i', strtotime($booking['check_in_time'])) : 'N/A',
            date('Y-m-d H:i:s', strtotime($booking['created_at']))
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("Error exporting bookings: " . $e->getMessage());
    header('Content-Type: text/html');
    echo "Error exporting data: " . $e->getMessage();
}
?>
