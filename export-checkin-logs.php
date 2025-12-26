<?php
session_start();

// Database connection
require_once 'config.php';

// Check if export is requested
if (!isset($_GET['export']) || $_GET['export'] !== 'csv') {
    header('Location: librarian-history.php');
    exit;
}

// Get filter parameters
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$course_filter = $_GET['course'] ?? '';
$search = $_GET['search'] ?? '';

// Build the WHERE clause based on filters
function buildWhereClause($date_from, $date_to, $course_filter, $search) {
    $conditions = [];
    $params = [];
    
    if (!empty($date_from)) {
        $conditions[] = "booking_date >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $conditions[] = "booking_date <= ?";
        $params[] = $date_to;
    }
    
    if (!empty($course_filter)) {
        $conditions[] = "course = ?";
        $params[] = $course_filter;
    }
    
    if (!empty($search)) {
        $conditions[] = "(student_name LIKE ? OR roll_number LIKE ? OR email LIKE ? OR seat_id LIKE ? OR booking_code LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    return [$whereClause, $params];
}

// Get all filtered data for export
function getAllCheckinLogs($date_from, $date_to, $course_filter, $search) {
    global $pdo;
    
    list($whereClause, $params) = buildWhereClause($date_from, $date_to, $course_filter, $search);
    
    try {
        $sql = "SELECT * FROM checkin_logs $whereClause ORDER BY recorded_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$logs = getAllCheckinLogs($date_from, $date_to, $course_filter, $search);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="checkin_logs_' . date('Y-m-d_H-i-s') . '.csv"');

// Create file pointer
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'ID',
    'Student ID',
    'Student Name',
    'Roll Number',
    'Course',
    'Email',
    'Phone',
    'Seat ID',
    'Seat Type',
    'Booking Date',
    'Start Time',
    'Duration (Hours)',
    'Time Slot',
    'Booking Code',
    'Attendance Code',
    'Check-in Time',
    'Recorded At'
]);

// Add data rows
foreach ($logs as $log) {
    fputcsv($output, [
        $log['id'],
        $log['student_id'],
        $log['student_name'],
        $log['roll_number'],
        $log['course'],
        $log['email'],
        $log['phone'],
        $log['seat_id'],
        $log['is_computer'] ? 'Computer' : 'Regular',
        $log['booking_date'],
        $log['start_time'],
        $log['duration'],
        $log['time_slot'],
        $log['booking_code'],
        $log['attendance_code'],
        $log['checkin_time'],
        $log['recorded_at']
    ]);
}

fclose($output);
exit;
?>
