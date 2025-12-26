<?php
session_start();

// Database connection
require_once 'config.php';

// Get student_id from POST or session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $_SESSION['student_id'] = $student_id;
} else {
    $student_id = $_SESSION['student_id'] ?? null;
}

if (!$student_id) {
    header('Location: student-login.php');
    exit;
}

// Get student details
function getStudentById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

// Get booking history from checkin_logs
function getBookingHistory($studentId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM checkin_logs 
            WHERE student_id = ? 
            ORDER BY booking_date DESC, start_time DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Format time function
function formatTime($hour) {
    if ($hour <= 12) {
        return $hour == 12 ? '12:00 PM' : $hour . ':00 AM';
    } else {
        return ($hour - 12) . ':00 PM';
    }
}

$student = getStudentById($student_id);
$bookingHistory = getBookingHistory($student_id);

if (!$student) {
    echo "Student not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - <?php echo htmlspecialchars($student['student_name']); ?></title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Section */
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

        /* Navigation Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb a:hover {
            color: #0056b3;
        }

        .breadcrumb span {
            color: #6c757d;
            margin: 0 10px;
        }

        /* Stats Summary */
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 20px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* History Cards */
        .history-container {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .history-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .history-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .history-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .history-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .history-title h3 {
            color: #2c3e50;
            font-size: 20px;
        }

        .history-date {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .history-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-icon {
            width: 35px;
            height: 35px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007bff;
            font-size: 14px;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .detail-value {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 500;
        }

        .history-codes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .code-item {
            text-align: center;
        }

        .code-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .code-value {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 6px;
            color: white;
        }

        .booking-code {
            background: #007bff;
        }

        .attendance-code {
            background: #28a745;
        }

        .checkin-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background: #d4edda;
            color: #155724;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #c3e6cb;
        }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 15px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #6c757d;
            font-size: 32px;
        }

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        /* Back Button */
        .back-section {
            text-align: center;
            margin-top: 40px;
        }

        .back-btn {
            background: white;
            color: #007bff;
            padding: 15px 30px;
            border: 2px solid #007bff;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }

            .history-details {
                grid-template-columns: 1fr;
            }

            .history-codes {
                grid-template-columns: 1fr;
            }

            .history-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .stats-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-summary {
                grid-template-columns: 1fr;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Breadcrumb Navigation -->
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <span>></span>
            <a href="student-profile.php"><i class="fas fa-user"></i> Profile</a>
            <span>></span>
            <span>Booking History</span>
        </div>

        <!-- Header Section -->
        <div class="header loading">
            <h1>Booking History</h1>
            <p>Complete record of your library seat bookings and check-ins</p>
        </div>

        <!-- Stats Summary -->
        <?php if (!empty($bookingHistory)): ?>
            <?php
            $totalBookings = count($bookingHistory);
            $totalHours = array_sum(array_column($bookingHistory, 'duration'));
            $thisMonth = date('Y-m');
            $thisMonthBookings = array_filter($bookingHistory, function($booking) use ($thisMonth) {
                return strpos($booking['booking_date'], $thisMonth) === 0;
            });
            $thisMonthCount = count($thisMonthBookings);
            ?>
            <div class="stats-summary">
                <div class="stat-card loading" style="animation-delay: 0.1s;">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card loading" style="animation-delay: 0.2s;">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalHours; ?></div>
                    <div class="stat-label">Total Hours</div>
                </div>
                <div class="stat-card loading" style="animation-delay: 0.3s;">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo $thisMonthCount; ?></div>
                    <div class="stat-label">This Month</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- History Container -->
        <?php if (!empty($bookingHistory)): ?>
            <div class="history-container">
                <?php foreach ($bookingHistory as $index => $history): ?>
                    <div class="history-card loading" style="animation-delay: <?php echo ($index + 4) * 0.1; ?>s;">
                        <div class="history-header">
                            <div class="history-title">
                                <div class="history-icon">
                                    <i class="fas <?php echo $history['is_computer'] ? 'fa-desktop' : 'fa-chair'; ?>"></i>
                                </div>
                                <h3>Seat <?php echo htmlspecialchars($history['seat_id']); ?></h3>
                            </div>
                            <div class="history-date">
                                <?php echo date('M d, Y', strtotime($history['booking_date'])); ?>
                            </div>
                        </div>

                        <div class="history-details">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Time Slot</div>
                                    <div class="detail-value">
                                        <?php echo formatTime($history['start_time']) . ' - ' . formatTime($history['start_time'] + $history['duration']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Duration</div>
                                    <div class="detail-value"><?php echo $history['duration']; ?> Hour<?php echo $history['duration'] > 1 ? 's' : ''; ?></div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Check-in Time</div>
                                    <div class="detail-value">
                                        <?php if (!empty($history['checkin_time'])): ?>
                                            <?php echo date('M d, Y H:i', strtotime($history['checkin_time'])); ?>
                                        <?php else: ?>
                                            <span style="color: #dc3545;">Not checked in</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Recorded At</div>
                                    <div class="detail-value"><?php echo date('M d, Y H:i', strtotime($history['recorded_at'])); ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($history['booking_code']) || !empty($history['attendance_code'])): ?>
                        <div class="history-codes">
                            <?php if (!empty($history['booking_code'])): ?>
                            <div class="code-item">
                                <div class="code-label">Booking Code</div>
                                <div class="code-value booking-code"><?php echo htmlspecialchars($history['booking_code']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($history['attendance_code'])): ?>
                            <div class="code-item">
                                <div class="code-label">Attendance Code</div>
                                <div class="code-value attendance-code"><?php echo htmlspecialchars($history['attendance_code']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($history['checkin_time'])): ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <div class="checkin-badge">
                                <i class="fas fa-check-circle"></i>
                                Successfully Checked In
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state loading">
                <div class="empty-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3>No Booking History</h3>
                <p>You haven't made any bookings yet.<br>Start booking seats to see your history here.</p>
                <a href="dashboard.php" class="btn-secondary">
                    <i class="fas fa-plus"></i>
                    Book Your First Seat
                </a>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="back-section">
            <a href="student-profile.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Profile
            </a>
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
    </script>
</body>
</html>
