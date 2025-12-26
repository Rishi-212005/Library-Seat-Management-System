<?php
session_start();

// Handle CSV export first (before any HTML output)
if (isset($_POST['export_csv'])) {
    // Database configuration
    $host = 'sql200.infinityfree.com';
    $dbname = 'if0_39418937_library_management';
    $username = 'if0_39418937';
    $password = 'library2025';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch(PDOException $e) {
        die("Database connection failed for export.");
    }

    // Get filter parameters from POST
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $course_filter = $_POST['course'] ?? '';
    $search = $_POST['search'] ?? '';
    
    // Build WHERE clause for export
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
    
    try {
        $sql = "SELECT * FROM checkin_logs $whereClause ORDER BY recorded_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $export_logs = $stmt->fetchAll();
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="checkin_logs_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create file pointer
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
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
        foreach ($export_logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['student_id'],
                $log['student_name'],
                $log['roll_number'],
                $log['course'],
                $log['email'],
                $log['phone'] ?? '',
                $log['seat_id'],
                $log['is_computer'] ? 'Computer' : 'Regular',
                $log['booking_date'],
                $log['start_time'],
                $log['duration'],
                $log['time_slot'] ?? '',
                $log['booking_code'] ?? '',
                $log['attendance_code'] ?? '',
                $log['checkin_time'] ?? '',
                $log['recorded_at']
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (PDOException $e) {
        die("Export failed: " . $e->getMessage());
    }
}

// Database configuration for main page
$host = 'sql200.infinityfree.com';
$dbname = 'if0_39418937_library_management';
$username = 'if0_39418937';
$password = 'library2025';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// Check if librarian is logged in (you can modify this based on your auth system)
// Uncomment the lines below if you have librarian authentication
// if (!isset($_SESSION['librarian_id'])) {
//     header('Location: librarian-login.php');
//     exit;
// }

// Initialize filter variables
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$course_filter = $_GET['course'] ?? '';
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Get all courses for filter dropdown
function getAllCourses() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT DISTINCT course FROM checkin_logs ORDER BY course");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

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

// Get filtered data
function getCheckinLogs($date_from, $date_to, $course_filter, $search, $offset, $records_per_page) {
    global $pdo;
    
    list($whereClause, $params) = buildWhereClause($date_from, $date_to, $course_filter, $search);
    
    try {
        $sql = "SELECT * FROM checkin_logs $whereClause ORDER BY recorded_at DESC LIMIT $records_per_page OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Get total count for pagination
function getTotalCount($date_from, $date_to, $course_filter, $search) {
    global $pdo;
    
    list($whereClause, $params) = buildWhereClause($date_from, $date_to, $course_filter, $search);
    
    try {
        $sql = "SELECT COUNT(*) FROM checkin_logs $whereClause";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// Get statistics
function getStatistics($date_from, $date_to, $course_filter, $search) {
    global $pdo;
    
    list($whereClause, $params) = buildWhereClause($date_from, $date_to, $course_filter, $search);
    
    try {
        $sql = "SELECT 
                    COUNT(*) as total_bookings,
                    COUNT(CASE WHEN checkin_time IS NOT NULL THEN 1 END) as checked_in,
                    COUNT(CASE WHEN is_computer = 1 THEN 1 END) as computer_seats,
                    COUNT(CASE WHEN is_computer = 0 THEN 1 END) as regular_seats
                FROM checkin_logs $whereClause";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return [
            'total_bookings' => 0,
            'checked_in' => 0,
            'computer_seats' => 0,
            'regular_seats' => 0
        ];
    }
}

$courses = getAllCourses();
$logs = getCheckinLogs($date_from, $date_to, $course_filter, $search, $offset, $records_per_page);
$total_records = getTotalCount($date_from, $date_to, $course_filter, $search);
$total_pages = ceil($total_records / $records_per_page);
$statistics = getStatistics($date_from, $date_to, $course_filter, $search);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in History - Library Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 20px;
            color: white;
        }

        .stat-icon.total { background: linear-gradient(135deg, #007bff, #0056b3); }
        .stat-icon.checked { background: linear-gradient(135deg, #28a745, #1e7e34); }
        .stat-icon.computer { background: linear-gradient(135deg, #17a2b8, #138496); }
        .stat-icon.regular { background: linear-gradient(135deg, #ffc107, #e0a800); }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        /* Filters */
        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .filters-title {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .filter-input, .filter-select {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: #007bff;
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        /* Table */
        .table-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-title {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
        }

        .table-info {
            color: #666;
            font-size: 14px;
        }

        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-checked-in {
            background: #d4edda;
            color: #155724;
        }

        .status-booked {
            background: #fff3cd;
            color: #856404;
        }

        .seat-type {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .seat-computer {
            background: #cce5ff;
            color: #0066cc;
        }

        .seat-regular {
            background: #e6f3ff;
            color: #0080ff;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 10px 15px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            text-decoration: none;
            color: #007bff;
            font-weight: 500;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #007bff;
            color: white;
        }

        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination .disabled {
            color: #6c757d;
            cursor: not-allowed;
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #ccc;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                justify-content: stretch;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }

            .table-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .data-table {
                font-size: 12px;
            }

            .data-table th,
            .data-table td {
                padding: 8px 6px;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0;
            animation: fadeIn 0.6s ease-in-out forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        /* Export Form Hidden */
        #exportForm {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header loading">
            <h1><i class="fas fa-history"></i> Check-in History</h1>
            <p>View and manage all public check-in records</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid loading" style="animation-delay: 0.1s;">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-number"><?php echo number_format($statistics['total_bookings']); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon checked">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($statistics['checked_in']); ?></div>
                <div class="stat-label">Checked In</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon computer">
                    <i class="fas fa-desktop"></i>
                </div>
                <div class="stat-number"><?php echo number_format($statistics['computer_seats']); ?></div>
                <div class="stat-label">Computer Seats</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon regular">
                    <i class="fas fa-chair"></i>
                </div>
                <div class="stat-number"><?php echo number_format($statistics['regular_seats']); ?></div>
                <div class="stat-label">Regular Seats</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section loading" style="animation-delay: 0.2s;">
            <div class="filters-title">
                <i class="fas fa-filter"></i>
                Filter Records
            </div>
            <form method="GET" id="filterForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">From Date</label>
                        <input type="date" name="date_from" id="date_from" class="filter-input" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">To Date</label>
                        <input type="date" name="date_to" id="date_to" class="filter-input" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Course</label>
                        <select name="course" id="course" class="filter-select">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course); ?>" 
                                        <?php echo $course_filter === $course ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <input type="text" name="search" id="search" class="filter-input" 
                               placeholder="Name, Roll No, Email, Seat..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="librarian-history.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                    <button type="button" class="btn btn-success" onclick="exportData()">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </form>
        </div>

        <!-- Hidden Export Form -->
        <form method="POST" id="exportForm">
            <input type="hidden" name="export_csv" value="1">
            <input type="hidden" name="date_from" id="export_date_from">
            <input type="hidden" name="date_to" id="export_date_to">
            <input type="hidden" name="course" id="export_course">
            <input type="hidden" name="search" id="export_search">
        </form>

        <!-- Table -->
        <div class="table-section loading" style="animation-delay: 0.3s;">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-table"></i> Check-in Records
                </div>
                <div class="table-info">
                    Showing <?php echo number_format(min($records_per_page, $total_records - $offset)); ?> of <?php echo number_format($total_records); ?> records
                </div>
            </div>

            <?php if (empty($logs)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <h3>No Records Found</h3>
                    <p>No check-in records match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Public Info</th>
                                <th>Course</th>
                                <th>Seat</th>
                                <th>Booking Date</th>
                                <th>Time Slot</th>
                                <th>Duration</th>
                                <th>Booking Code</th>
                                <th>Status</th>
                                <th>Check-in Time</th>
                                <th>Recorded At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #2c3e50;">
                                            <?php echo htmlspecialchars($log['student_name']); ?>
                                        </div>
                                        <div style="font-size: 12px; color: #666;">
                                            <?php echo htmlspecialchars($log['roll_number']); ?>
                                        </div>
                                        <div style="font-size: 12px; color: #666;">
                                            <?php echo htmlspecialchars($log['email']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: #007bff;">
                                            <?php echo htmlspecialchars($log['course']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; margin-bottom: 4px;">
                                            <?php echo htmlspecialchars($log['seat_id']); ?>
                                        </div>
                                        <span class="seat-type <?php echo $log['is_computer'] ? 'seat-computer' : 'seat-regular'; ?>">
                                            <?php echo $log['is_computer'] ? 'Computer' : 'Regular'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($log['booking_date'])); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($log['time_slot'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php echo $log['duration']; ?> hours
                                    </td>
                                    <td>
                                        <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 4px;">
                                            <?php echo htmlspecialchars($log['booking_code'] ?? 'N/A'); ?>
                                        </code>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $log['checkin_time'] ? 'status-checked-in' : 'status-booked'; ?>">
                                            <?php echo $log['checkin_time'] ? 'Checked In' : 'Booked'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($log['checkin_time']): ?>
                                            <?php echo date('M d, Y H:i', strtotime($log['checkin_time'])); ?>
                                        <?php else: ?>
                                            <span style="color: #999;">Not checked in</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y H:i', strtotime($log['recorded_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add smooth loading animation
        document.addEventListener('DOMContentLoaded', function() {
            const loadingElements = document.querySelectorAll('.loading');
            loadingElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                }, index * 100);
            });
        });

        // Quick date filters
        function setDateFilter(days) {
            const today = new Date();
            const fromDate = new Date(today);
            fromDate.setDate(today.getDate() - days);
            
            document.querySelector('input[name="date_from"]').value = fromDate.toISOString().split('T')[0];
            document.querySelector('input[name="date_to"]').value = today.toISOString().split('T')[0];
        }

        // Export data function - FIXED VERSION
        function exportData() {
            // Get current filter values
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            const course = document.getElementById('course').value;
            const search = document.getElementById('search').value;
            
            // Set values in hidden export form
            document.getElementById('export_date_from').value = dateFrom;
            document.getElementById('export_date_to').value = dateTo;
            document.getElementById('export_course').value = course;
            document.getElementById('export_search').value = search;
            
            // Submit the export form
            document.getElementById('exportForm').submit();
        }

        // Auto-submit form on filter change
        document.querySelectorAll('.filter-input, .filter-select').forEach(element => {
            element.addEventListener('change', function() {
                // Optional: Auto-submit on change
                // document.getElementById('filterForm').submit();
            });
        });

        // Add quick filter buttons
        const quickFilters = document.createElement('div');
        quickFilters.innerHTML = `
            <div style="margin-top: 15px; text-align: center;">
                <span style="color: #666; font-size: 14px; margin-right: 15px;">Quick Filters:</span>
                <button type="button" class="btn btn-sm" onclick="setDateFilter(0)" style="padding: 6px 12px; font-size: 12px; margin: 2px;">Today</button>
                <button type="button" class="btn btn-sm" onclick="setDateFilter(7)" style="padding: 6px 12px; font-size: 12px; margin: 2px;">Last 7 Days</button>
                <button type="button" class="btn btn-sm" onclick="setDateFilter(30)" style="padding: 6px 12px; font-size: 12px; margin: 2px;">Last 30 Days</button>
            </div>
        `;
        document.querySelector('.filter-actions').parentNode.appendChild(quickFilters);
    </script>
</body>
</html>
