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

// Get current bookings
function getCurrentBookings($studentId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM seat_bookings 
            WHERE student_id = ? 
            AND booking_date >= CURDATE() 
            AND status IN ('booked', 'attended')
            ORDER BY booking_date ASC, start_time ASC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Cancel booking function
function cancelBooking($bookingId, $studentId) {
    global $pdo;
    try {
        // Verify the booking belongs to the student
        $stmt = $pdo->prepare("
            SELECT id FROM seat_bookings 
            WHERE id = ? AND student_id = ? AND status IN ('booked', 'attended')
        ");
        $stmt->execute([$bookingId, $studentId]);
        
        if ($stmt->fetch()) {
            // Delete the booking
            $deleteStmt = $pdo->prepare("DELETE FROM seat_bookings WHERE id = ?");
            $deleteStmt->execute([$bookingId]);
            return ['success' => true, 'message' => 'Booking cancelled successfully!'];
        } else {
            return ['success' => false, 'message' => 'Booking not found or cannot be cancelled.'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error cancelling booking: ' . $e->getMessage()];
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
$message = '';
$messageType = '';

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $bookingId = $_POST['booking_id'] ?? null;
    if ($bookingId) {
        $result = cancelBooking($bookingId, $student_id);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

$currentBookings = getCurrentBookings($student_id);

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
    <title>Current Bookings - <?php echo htmlspecialchars($student['student_name']); ?></title>
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
            max-width: 1000px;
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

        /* Status Messages */
        .status-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Booking Cards */
        .bookings-container {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .booking-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .booking-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .booking-title h3 {
            color: #2c3e50;
            font-size: 20px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.booked {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-badge.attended {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .booking-details {
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

        .booking-codes {
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

        .booking-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .btn {
            padding: 10px 20px;
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

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
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

            .booking-details {
                grid-template-columns: 1fr;
            }

            .booking-codes {
                grid-template-columns: 1fr;
            }

            .booking-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .booking-actions {
                justify-content: center;
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
            <span>Current Bookings</span>
        </div>

        <!-- Header Section -->
        <div class="header loading">
            <h1>Your Current Bookings</h1>
            <p>Manage your active seat reservations and check-in status</p>
        </div>

        <!-- Status Messages -->
        <?php if (!empty($message)): ?>
            <div class="status-message <?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Bookings Container -->
        <?php if (!empty($currentBookings)): ?>
            <div class="bookings-container">
                <?php foreach ($currentBookings as $index => $booking): ?>
                    <div class="booking-card loading" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="booking-header">
                            <div class="booking-title">
                                <div class="booking-icon">
                                    <i class="fas <?php echo $booking['is_computer'] ? 'fa-desktop' : 'fa-chair'; ?>"></i>
                                </div>
                                <h3>Seat <?php echo htmlspecialchars($booking['seat_id']); ?></h3>
                            </div>
                            <div class="status-badge <?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </div>
                        </div>

                        <div class="booking-details">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Time</div>
                                    <div class="detail-value">
                                        <?php echo formatTime($booking['start_time']) . ' - ' . formatTime($booking['start_time'] + $booking['duration']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Duration</div>
                                    <div class="detail-value"><?php echo $booking['duration']; ?> Hour<?php echo $booking['duration'] > 1 ? 's' : ''; ?></div>
                                </div>
                            </div>

                            <?php if ($booking['status'] === 'attended' && !empty($booking['check_in_time'])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Check-in Time</div>
                                        <div class="detail-value"><?php echo date('M d, Y H:i', strtotime($booking['check_in_time'])); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="booking-codes">
                            <div class="code-item">
                                <div class="code-label">Booking Code</div>
                                <div class="code-value booking-code"><?php echo htmlspecialchars($booking['booking_code']); ?></div>
                            </div>
                            <div class="code-item">
                                <div class="code-label">Attendance Code</div>
                                <div class="code-value attendance-code"><?php echo htmlspecialchars($booking['attendance_code']); ?></div>
                            </div>
                        </div>

                        <?php if ($booking['status'] !== 'attended'): ?>
                        <div class="booking-actions">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                <button type="submit" name="cancel_booking" class="btn btn-cancel">
                                    <i class="fas fa-times"></i>
                                    Cancel Booking
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state loading">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3>No Current Bookings</h3>
                <p>You don't have any active bookings at the moment.<br>Book a seat to start studying in the library.</p>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-plus"></i>
                    Book a Seat
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

        // Add click feedback for buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.classList.contains('btn-cancel')) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                }
            });
        });
    </script>
</body>
</html>
