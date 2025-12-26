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

$reportType = sanitizeInput($_GET['type'] ?? 'daily');
$startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days')));
$endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));

try {
    $pdo = getDBConnection();
    
    // Get report data based on type
    $reportData = [];
    $stats = [];
    
    switch ($reportType) {
        case 'daily':
            $stmt = $pdo->prepare("
                SELECT 
                    sb.booking_date,
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN sb.status = 'booked' THEN 1 ELSE 0 END) as pending_checkins,
                    SUM(CASE WHEN sb.status = 'attended' THEN 1 ELSE 0 END) as attended,
                    SUM(CASE WHEN sb.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM seat_bookings sb
                WHERE sb.booking_date BETWEEN ? AND ?
                GROUP BY sb.booking_date
                ORDER BY sb.booking_date DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            $reportData = $stmt->fetchAll();
            break;
            
        case 'student':
            $stmt = $pdo->prepare("
                SELECT 
                    s.student_name,
                    s.roll_number,
                    s.email,
                    COUNT(sb.id) as total_bookings,
                    SUM(CASE WHEN sb.status = 'attended' THEN 1 ELSE 0 END) as attended_sessions,
                    SUM(CASE WHEN sb.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_sessions,
                    MAX(sb.booking_date) as last_booking_date
                FROM students s
                LEFT JOIN seat_bookings sb ON s.id = sb.student_id 
                    AND sb.booking_date BETWEEN ? AND ?
                WHERE s.status = 'approved'
                GROUP BY s.id
                HAVING total_bookings > 0
                ORDER BY total_bookings DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            $reportData = $stmt->fetchAll();
            break;
            
        case 'seat':
            $stmt = $pdo->prepare("
                SELECT 
                    sb.seat_id,
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN sb.status = 'attended' THEN 1 ELSE 0 END) as attended_sessions,
                    SUM(sb.duration) as total_hours,
                    AVG(sb.duration) as avg_duration
                FROM seat_bookings sb
                WHERE sb.booking_date BETWEEN ? AND ?
                GROUP BY sb.seat_id
                ORDER BY total_bookings DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            $reportData = $stmt->fetchAll();
            break;
    }
    
    // Calculate overall statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as pending_checkins,
            SUM(CASE WHEN status = 'attended' THEN 1 ELSE 0 END) as attended,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(duration) as total_hours
        FROM seat_bookings 
        WHERE booking_date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Error generating reports: " . $e->getMessage());
    $reportData = [];
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Library System</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .reports-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .controls-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
        }
        
        .form-group select,
        .form-group input {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-success {
            background: #22c55e;
            color: white;
        }
        
        .btn-success:hover {
            background: #16a34a;
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
        
        .report-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            position: sticky;
            top: 0;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .controls-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .export-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="main-navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <img src="assets/college-logo.avif" alt="SRS Government Degree College Logo" class="nav-logo">
                <span class="nav-brand-text">SRS Government Degree College - Reports</span>
            </div>
            <div class="nav-menu">
                <a href="librarian-dashboard.html" class="nav-link">Dashboard</a>
                <a href="librarian-attendance.php" class="nav-link">Attendance</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="reports-container">
        <div class="header-section">
            <h1>📊 Reports & Analytics</h1>
            <p>Comprehensive library usage statistics and reports</p>
        </div>
        
        <div class="controls-section">
            <form method="GET" class="controls-grid">
                <div class="form-group">
                    <label for="type">Report Type:</label>
                    <select name="type" id="type">
                        <option value="daily" <?php echo $reportType === 'daily' ? 'selected' : ''; ?>>Daily Summary</option>
                        <option value="student" <?php echo $reportType === 'student' ? 'selected' : ''; ?>>Student Usage</option>
                        <option value="seat" <?php echo $reportType === 'seat' ? 'selected' : ''; ?>>Seat Utilization</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo $startDate; ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo $endDate; ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
        
        <?php if (!empty($stats)): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: #3b82f6;"><?php echo $stats['total_bookings'] ?? 0; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #22c55e;"><?php echo $stats['attended'] ?? 0; ?></div>
                <div class="stat-label">Attended Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #f59e0b;"><?php echo $stats['pending_checkins'] ?? 0; ?></div>
                <div class="stat-label">Pending Check-ins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ef4444;"><?php echo $stats['cancelled'] ?? 0; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #8b5cf6;"><?php echo $stats['total_hours'] ?? 0; ?></div>
                <div class="stat-label">Total Hours</div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="report-table">
            <div class="table-header">
                <h3><?php echo ucfirst($reportType); ?> Report 
                    (<?php echo date('M j, Y', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?>)
                </h3>
                <div class="export-buttons">
                    <button onclick="exportToCSV()" class="btn btn-success">📄 Export CSV</button>
                    <button onclick="printReport()" class="btn btn-primary">🖨️ Print</button>
                </div>
            </div>
            
            <div class="table-content">
                <?php if (empty($reportData)): ?>
                    <div class="no-data">
                        <h3>No Data Available</h3>
                        <p>No records found for the selected date range and report type.</p>
                    </div>
                <?php else: ?>
                    <table id="reportTable">
                        <thead>
                            <tr>
                                <?php if ($reportType === 'daily'): ?>
                                    <th>Date</th>
                                    <th>Total Bookings</th>
                                    <th>Pending Check-ins</th>
                                    <th>Attended</th>
                                    <th>Cancelled</th>
                                    <th>Attendance Rate</th>
                                <?php elseif ($reportType === 'student'): ?>
                                    <th>Student Name</th>
                                    <th>Roll Number</th>
                                    <th>Email</th>
                                    <th>Total Bookings</th>
                                    <th>Attended</th>
                                    <th>Cancelled</th>
                                    <th>Attendance Rate</th>
                                    <th>Last Booking</th>
                                <?php elseif ($reportType === 'seat'): ?>
                                    <th>Seat ID</th>
                                    <th>Total Bookings</th>
                                    <th>Attended Sessions</th>
                                    <th>Total Hours</th>
                                    <th>Avg Duration</th>
                                    <th>Utilization Rate</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <?php if ($reportType === 'daily'): ?>
                                        <td><?php echo date('M j, Y', strtotime($row['booking_date'])); ?></td>
                                        <td><?php echo $row['total_bookings']; ?></td>
                                        <td><?php echo $row['pending_checkins']; ?></td>
                                        <td><?php echo $row['attended']; ?></td>
                                        <td><?php echo $row['cancelled']; ?></td>
                                        <td>
                                            <?php 
                                            $rate = $row['total_bookings'] > 0 ? 
                                                round(($row['attended'] / $row['total_bookings']) * 100, 1) : 0;
                                            echo $rate . '%';
                                            ?>
                                        </td>
                                    <?php elseif ($reportType === 'student'): ?>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo $row['total_bookings']; ?></td>
                                        <td><?php echo $row['attended_sessions']; ?></td>
                                        <td><?php echo $row['cancelled_sessions']; ?></td>
                                        <td>
                                            <?php 
                                            $rate = $row['total_bookings'] > 0 ? 
                                                round(($row['attended_sessions'] / $row['total_bookings']) * 100, 1) : 0;
                                            echo $rate . '%';
                                            ?>
                                        </td>
                                        <td><?php echo $row['last_booking_date'] ? date('M j, Y', strtotime($row['last_booking_date'])) : 'N/A'; ?></td>
                                    <?php elseif ($reportType === 'seat'): ?>
                                        <td><?php echo htmlspecialchars($row['seat_id']); ?></td>
                                        <td><?php echo $row['total_bookings']; ?></td>
                                        <td><?php echo $row['attended_sessions']; ?></td>
                                        <td><?php echo $row['total_hours']; ?></td>
                                        <td><?php echo round($row['avg_duration'], 1); ?> hrs</td>
                                        <td>
                                            <?php 
                                            $rate = $row['total_bookings'] > 0 ? 
                                                round(($row['attended_sessions'] / $row['total_bookings']) * 100, 1) : 0;
                                            echo $rate . '%';
                                            ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function exportToCSV() {
            const table = document.getElementById('reportTable');
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }
            
            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = `library_report_${new Date().toISOString().split('T')[0]}.csv`;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        function printReport() {
            window.print();
        }
        
        // Set default date range to last 7 days if not specified
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            
            if (!startDate.value) {
                const date = new Date();
                date.setDate(date.getDate() - 7);
                startDate.value = date.toISOString().split('T')[0];
            }
            
            if (!endDate.value) {
                endDate.value = new Date().toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>
