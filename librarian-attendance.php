<?php
session_start();
require_once 'includes/functions.php';

// Check if librarian is logged in
if (!isLibrarianLoggedIn()) {
    header('Location: librarian-login-final.html');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    header('Location: logout.php');
    exit();
}

$message = '';
$messageType = '';

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $attendanceCode = sanitizeInput($_POST['attendance_code'] ?? '');
    
    if (empty($attendanceCode)) {
        $message = "Please enter an attendance code.";
        $messageType = "error";
    } else {
        $result = markAttendance($attendanceCode);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

// Get today's bookings
$today = date('Y-m-d');
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT sb.*, s.student_name, s.roll_number 
        FROM seat_bookings sb 
        JOIN students s ON sb.student_id = s.id 
        WHERE sb.booking_date = ? 
        ORDER BY sb.start_time ASC, sb.seat_id ASC
    ");
    $stmt->execute([$today]);
    $todaysBookings = $stmt->fetchAll();
    
    // Get statistics
    $stats = [
        'total' => count($todaysBookings),
        'booked' => count(array_filter($todaysBookings, fn($b) => $b['status'] === 'booked')),
        'attended' => count(array_filter($todaysBookings, fn($b) => $b['status'] === 'attended')),
        'cancelled' => count(array_filter($todaysBookings, fn($b) => $b['status'] === 'cancelled'))
    ];
    
} catch (Exception $e) {
    $todaysBookings = [];
    $stats = ['total' => 0, 'booked' => 0, 'attended' => 0, 'cancelled' => 0];
    error_log("Error fetching today's bookings: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Attendance Portal</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .attendance-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .stat-card.total .stat-number { color: #3b82f6; }
        .stat-card.booked .stat-number { color: #f59e0b; }
        .stat-card.attended .stat-number { color: #22c55e; }
        .stat-card.cancelled .stat-number { color: #ef4444; }
        
        .attendance-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .btn {
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #2563eb;
        }
        
        .bookings-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table-content {
            max-height: 600px;
            overflow-y: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-booked {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-attended {
            background: #dcfce7;
            color: #15803d;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .attendance-code {
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .attendance-container {
                padding: 10px;
            }
            
            .table-content {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <nav class="main-navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <img src="assets/college-logo.avif" alt="SRS Government Degree College Logo" class="nav-logo">
                <span class="nav-brand-text">SRS Government Degree College - Librarian Portal</span>
            </div>
            <div class="nav-menu">
                <a href="librarian-dashboard.html" class="nav-link">Dashboard</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="attendance-container">
        <header style="text-align: center; margin-bottom: 30px;">
            <h1>Attendance Management Portal</h1>
            <p>Mark student attendance using attendance codes</p>
            <p><strong>Today's Date:</strong> <?php echo date('F j, Y'); ?></p>
        </header>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card booked">
                <div class="stat-number"><?php echo $stats['booked']; ?></div>
                <div class="stat-label">Waiting Check-in</div>
            </div>
            <div class="stat-card attended">
                <div class="stat-number"><?php echo $stats['attended']; ?></div>
                <div class="stat-label">Attended</div>
            </div>
            <div class="stat-card cancelled">
                <div class="stat-number"><?php echo $stats['cancelled']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
        
        <!-- Attendance Form -->
        <div class="attendance-form">
            <h3>Mark Attendance</h3>
            <form method="POST" id="attendanceForm">
                <div class="form-group">
                    <label for="attendance_code">Enter Attendance Code:</label>
                    <input type="text" id="attendance_code" name="attendance_code" 
                           placeholder="ATT-XXXXXX" maxlength="10" 
                           style="text-transform: uppercase;" required>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Format: ATT-XXXXXX (e.g., ATT-ABC123)
                    </small>
                </div>
                <button type="submit" name="mark_attendance" class="btn">Mark Attendance</button>
            </form>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <button onclick="refreshPage()" class="btn btn-secondary">Refresh Data</button>
            <button onclick="exportToday()" class="btn btn-secondary">Export Today's Report</button>
        </div>
        
        <!-- Today's Bookings -->
        <div class="bookings-table">
            <div class="table-header">
                <h3>Today's Bookings (<?php echo date('F j, Y'); ?>)</h3>
            </div>
            <div class="table-content">
                <?php if (empty($todaysBookings)): ?>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <p>No bookings found for today.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Seat</th>
                                <th>Student</th>
                                <th>Roll Number</th>
                                <th>Duration</th>
                                <th>Attendance Code</th>
                                <th>Status</th>
                                <th>Check-in Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todaysBookings as $booking): ?>
                                <tr>
                                    <td><?php echo formatTime($booking['start_time']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['seat_id']); ?>
                                        <?php if ($booking['is_computer']): ?>
                                            <small style="color: #0ea5e9;">(Computer)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['roll_number']); ?></td>
                                    <td><?php echo $booking['duration']; ?> hour(s)</td>
                                    <td>
                                        <span class="attendance-code">
                                            <?php echo htmlspecialchars($booking['attendance_code']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['check_in_time']): ?>
                                            <?php echo date('H:i', strtotime($booking['check_in_time'])); ?>
                                        <?php else: ?>
                                            <span style="color: #666;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-format attendance code input
        document.getElementById('attendance_code').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            
            if (value.length > 3 && !value.startsWith('ATT-')) {
                if (value.startsWith('ATT')) {
                    value = 'ATT-' + value.substring(3);
                } else {
                    value = 'ATT-' + value;
                }
            }
            
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            e.target.value = value;
        });
        
        // Auto-submit form when complete code is entered
        document.getElementById('attendance_code').addEventListener('keyup', function(e) {
            if (e.target.value.length === 10 && e.target.value.startsWith('ATT-')) {
                // Auto-submit after a short delay
                setTimeout(() => {
                    if (confirm('Submit attendance code: ' + e.target.value + '?')) {
                        document.getElementById('attendanceForm').submit();
                    }
                }, 500);
            }
        });
        
        function refreshPage() {
            window.location.reload();
        }
        
        function exportToday() {
            // Simple CSV export
            const today = new Date().toISOString().split('T')[0];
            window.open(`export-bookings.php?date=${today}`, '_blank');
        }
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            // Only refresh if no form is being filled
            if (!document.getElementById('attendance_code').value) {
                window.location.reload();
            }
        }, 30000);
        
        // Focus on attendance code input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('attendance_code').focus();
        });
    </script>
</body>
</html>
