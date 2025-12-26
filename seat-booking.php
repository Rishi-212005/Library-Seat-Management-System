<?php
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('student-login.php');
}

$student = getStudentById($_SESSION['student_id']);
if (!$student) {
    session_destroy();
    redirect('student-login.php');
}

$message = '';
$messageType = '';

// Handle seat booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_seat'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = "Invalid form submission. Please try again.";
        $messageType = "error";
    } else {
        $seatId = sanitizeInput($_POST['seat_id'] ?? '');
        $bookingDate = sanitizeInput($_POST['booking_date'] ?? '');
        $startTime = (int)sanitizeInput($_POST['start_time'] ?? '');
        $duration = (int)($_POST['duration'] ?? 0);
        $isComputer = isset($_POST['is_computer']) && $_POST['is_computer'] === '1' ? 1 : 0;
        
        // Detailed validation
        if (empty($seatId)) {
            $message = "Please select a seat.";
            $messageType = "error";
        } elseif (empty($bookingDate)) {
            $message = "Please select a booking date.";
            $messageType = "error";
        } elseif (empty($startTime)) {
            $message = "Please select a start time.";
            $messageType = "error";
        } elseif ($duration <= 0 || $duration > 8) {
            $message = "Please select a valid duration (1-8 hours).";
            $messageType = "error";
        } elseif (strtotime($bookingDate) < strtotime(date('Y-m-d'))) {
            $message = "Cannot book seats for past dates.";
            $messageType = "error";
        } else {
            // Create booking
            $bookingResult = createSeatBooking($_SESSION['student_id'], $seatId, $isComputer, $bookingDate, $startTime, $duration);
            
            if ($bookingResult['success']) {
                // Send confirmation email
                $emailSent = sendBookingConfirmationEmail($student, $seatId, $bookingDate, $startTime, $duration, $bookingResult['booking_code'], 'booked');
                
                $emailStatus = $emailSent ? " A confirmation email has been sent to your registered email address." : " Note: Email notification could not be sent, but your booking is confirmed.";
                
                $message = "🎉 Seat booked successfully!<br><br>
                          <strong>Booking Code:</strong> <span style='background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; font-family: monospace;'>" . $bookingResult['booking_code'] . "</span><br>
                          <strong>Attendance Code:</strong> <span style='background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-family: monospace;'>" . $bookingResult['attendance_code'] . "</span><br><br>
                          <small>Please provide the attendance code to the librarian when you arrive.</small>" . $emailStatus;
                $messageType = "success";
            } else {
                $message = "Booking failed: " . $bookingResult['error'];
                $messageType = "error";
            }
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.html');
}

// Get current bookings for the student
$currentBookings = getCurrentStudentBookings($_SESSION['student_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Booking - Library System</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .floor-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .floor-tab {
            padding: 12px 24px;
            background: #f3f4f6;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        
        .floor-tab.active {
            background: #3b82f6;
            color: white;
        }
        
        .floor-content {
            display: none;
        }
        
        .floor-content.active {
            display: block;
        }
        
        .floor-section {
            margin-bottom: 40px;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #ddd;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 20px 0 15px 0;
        }
        
        /* Table Layout Styles */
        .tables-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin: 20px 0;
        }
        
        .table-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .table-seats-top {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .table-center {
            background: #8b5cf6;
            color: white;
            padding: 20px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 8px 0;
        }
        
        .table-seats-bottom {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        
        .table-7 {
            grid-column: 1 / -1;
            justify-self: center;
        }
        
        /* Base seat styles */
        .seat, .computer-seat {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            border: 2px solid;
        }
        
        /* CRITICAL: Seat status colors - EXACTLY matching database status */
        .seat.available, .computer-seat.available {
            background: #f0fdf4 !important;
            border-color: #22c55e !important;
            color: #15803d !important;
            cursor: pointer !important;
        }

        .seat.booked, .computer-seat.booked {
            background: #fef3c7 !important;
            border-color: #f59e0b !important;
            color: #d97706 !important;
            cursor: not-allowed !important;
        }

        .seat.attended, .computer-seat.attended {
            background: #fee2e2 !important;
            border-color: #dc2626 !important;
            color: #dc2626 !important;
            cursor: not-allowed !important;
        }

        .seat.attended::after, .computer-seat.attended::after {
            content: '✓';
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 12px;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .seat.selected, .computer-seat.selected {
            background: #eff6ff !important;
            border-color: #3b82f6 !important;
            color: #3b82f6 !important;
            border-width: 3px !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
            transform: scale(1.05) !important;
            cursor: pointer !important;
        }

        .seat.available:hover, .computer-seat.available:hover {
            background: #dcfce7 !important;
            transform: scale(1.05);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .seat.booked:hover, .computer-seat.booked:hover,
        .seat.attended:hover, .computer-seat.attended:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        
        /* Computer Station Styles */
        .computers-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 20px 0;
            justify-items: center;
        }
        
        .computer-station {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .computer-desk {
            width: 80px;
            height: 60px;
            background: #374151;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .computer-desk::before {
            content: '💻';
            font-size: 24px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-success {
            background: #22c55e;
            color: white;
        }
        
        .btn-success:hover:not(:disabled) {
            background: #16a34a;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Message Styles */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
        
        .warning-message {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid #ffeaa7;
        }
        
        /* Booking Info Styles */
        .current-bookings {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .booking-item {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #3b82f6;
        }
        
        .booking-item.booked {
            border-left-color: #f59e0b;
        }
        
        .booking-item.attended {
            border-left-color: #22c55e;
            background: #f0fdf4;
        }
        
        .booking-item.cancelled {
            border-left-color: #ef4444;
            opacity: 0.7;
        }
        
        .seat-legend {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .legend-seat {
            width: 25px;
            height: 25px;
            font-size: 12px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            border: 2px solid;
        }
        
        .legend-seat.available {
            background: #f0fdf4;
            border-color: #22c55e;
            color: #15803d;
        }
        
        .legend-seat.booked {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #d97706;
        }
        
        .legend-seat.attended {
            background: #fee2e2;
            border-color: #dc2626;
            color: #dc2626;
            position: relative;
        }
        
        .legend-seat.attended::after {
            content: '✓';
            position: absolute;
            top: -3px;
            right: -3px;
            font-size: 8px;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            width: 12px;
            height: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .legend-seat.selected {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #3b82f6;
        }
        
        .legend-seat.computer {
            background: #f0f9ff;
            border-color: #0ea5e9;
            color: #0ea5e9;
        }

        /* Debug info */
        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 11px;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .booking-container {
                grid-template-columns: 1fr;
            }
            
            .tables-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .computers-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="main-navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <img src="assets/college-logo.avif" alt="SRS Government Degree College Logo" class="nav-logo">
                <span class="nav-brand-text">SRS Government Degree College</span>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="index.html" class="nav-link">Home</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </div>
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <div class="container">
        <nav class="nav-bar">
            <ul>
                <li><span>Welcome, <?php echo htmlspecialchars($student['student_name']); ?></span></li>
                <li>
                    <a href="index.html">Home</a>
                    <a href="?logout=1">Logout</a>
                </li>
            </ul>
        </nav>
        
        <header class="header">
            <h1>Library Seat Booking</h1>
            <p>Select your preferred date, time, and seat</p>
        </header>
        
        <?php if (!empty($message)): ?>
            <div class="<?php echo $messageType; ?>-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="booking-container">
            <div class="booking-controls">
                <h3>Booking Details</h3>
                
                <form method="POST" id="bookingForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" id="seat_id" name="seat_id">
                    <input type="hidden" id="is_computer" name="is_computer">
                    
                    <div class="form-group">
                        <label for="booking_date">Select Date</label>
                        <input type="date" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <select id="start_time" name="start_time" required>
                            <option value="">Choose start time</option>
                            <option value="9">09:00 AM</option>
                            <option value="10">10:00 AM</option>
                            <option value="11">11:00 AM</option>
                            <option value="12">12:00 PM</option>
                            <option value="13">01:00 PM</option>
                            <option value="14">02:00 PM</option>
                            <option value="15">03:00 PM</option>
                            <option value="16">04:00 PM</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration (Hours)</label>
                        <select id="duration" name="duration" required>
                            <option value="">Select duration</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Selected Seat</label>
                        <div id="selectedSeatInfo" style="padding: 12px; background: #f8f9fa; border-radius: 8px; color: #666;">
                            No seat selected
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Booking Summary</label>
                        <div id="bookingSummary" style="padding: 12px; background: #f8f9fa; border-radius: 8px; color: #666;">
                            Please select date, time, duration, and seat
                        </div>
                    </div>
                    
                    <!-- Debug Info -->
                    <div id="debugInfo" class="debug-info" style="display: block;">
                        <strong>Database Status:</strong><br>
                        <div id="debugContent">Select a date to see seat statuses from database</div>
                    </div>
                    
                    <button type="submit" name="book_seat" id="bookSeatBtn" class="btn btn-success" disabled>Book Seat</button>
                </form>
                
                <div class="seat-legend">
                    <h4>Seat Status Legend</h4>
                    <div class="legend-item">
                        <span class="legend-seat available">A</span>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-seat booked">B</span>
                        <span>Booked (Waiting for Check-in)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-seat attended">A</span>
                        <span>Attended (Checked-in)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-seat selected">S</span>
                        <span>Selected</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-seat computer">💻</span>
                        <span>Computer Station</span>
                    </div>
                </div>
                
                <?php if (!empty($currentBookings)): ?>
                <div class="current-bookings">
                    <h4>Your Current Bookings</h4>
                    <?php foreach ($currentBookings as $booking): ?>
                        <div class="booking-item <?php echo $booking['status']; ?>">
                            <strong>Seat:</strong> <?php echo htmlspecialchars($booking['seat_id']); ?><br>
                            <strong>Date:</strong> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?><br>
                            <strong>Time:</strong> <?php echo formatTime($booking['start_time']) . ' - ' . formatTime($booking['start_time'] + $booking['duration']); ?><br>
                            <strong>Booking Code:</strong> <span style="background: #007bff; color: white; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($booking['booking_code']); ?></span><br>
                            <strong>Attendance Code:</strong> <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($booking['attendance_code']); ?></span><br>
                            <strong>Status:</strong> <?php echo ucfirst($booking['status']); ?>
                            <?php if ($booking['status'] === 'attended' && !empty($booking['check_in_time'])): ?>
                                <br><strong>Check-in Time:</strong> <?php echo date('M d, Y H:i', strtotime($booking['check_in_time'])); ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="seat-layout">
                <div class="floor-tabs">
                    <button class="floor-tab active" data-floor="first">First Floor</button>
                    <button class="floor-tab" data-floor="second">Second Floor</button>
                </div>
                
                <div id="firstFloor" class="floor-content active">
                    <div class="floor-section">
                        <h3 class="section-title">First Floor - Study Tables</h3>
                        
                        <div class="tables-container">
                            <!-- Table 1 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="1" data-is-computer="false">1</div>
                                    <div class="seat available" data-seat-id="2" data-is-computer="false">2</div>
                                    <div class="seat available" data-seat-id="3" data-is-computer="false">3</div>
                                </div>
                                <div class="table-center">Table 1</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="5" data-is-computer="false">5</div>
                                    <div class="seat available" data-seat-id="6" data-is-computer="false">6</div>
                                    <div class="seat available" data-seat-id="4" data-is-computer="false">4</div>
                                </div>
                            </div>
                            
                            <!-- Table 2 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="7" data-is-computer="false">7</div>
                                    <div class="seat available" data-seat-id="8" data-is-computer="false">8</div>
                                    <div class="seat available" data-seat-id="9" data-is-computer="false">9</div>
                                </div>
                                <div class="table-center">Table 2</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="11" data-is-computer="false">11</div>
                                    <div class="seat available" data-seat-id="12" data-is-computer="false">12</div>
                                    <div class="seat available" data-seat-id="10" data-is-computer="false">10</div>
                                </div>
                            </div>
                            
                            <!-- Table 3 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="13" data-is-computer="false">13</div>
                                    <div class="seat available" data-seat-id="14" data-is-computer="false">14</div>
                                    <div class="seat available" data-seat-id="15" data-is-computer="false">15</div>
                                </div>
                                <div class="table-center">Table 3</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="17" data-is-computer="false">17</div>
                                    <div class="seat available" data-seat-id="18" data-is-computer="false">18</div>
                                    <div class="seat available" data-seat-id="16" data-is-computer="false">16</div>
                                </div>
                            </div>
                            
                            <!-- Table 4 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="19" data-is-computer="false">19</div>
                                    <div class="seat available" data-seat-id="20" data-is-computer="false">20</div>
                                    <div class="seat available" data-seat-id="21" data-is-computer="false">21</div>
                                </div>
                                <div class="table-center">Table 4</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="23" data-is-computer="false">23</div>
                                    <div class="seat available" data-seat-id="24" data-is-computer="false">24</div>
                                    <div class="seat available" data-seat-id="22" data-is-computer="false">22</div>
                                </div>
                            </div>
                            
                            <!-- Table 5 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="25" data-is-computer="false">25</div>
                                    <div class="seat available" data-seat-id="26" data-is-computer="false">26</div>
                                    <div class="seat available" data-seat-id="27" data-is-computer="false">27</div>
                                </div>
                                <div class="table-center">Table 5</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="29" data-is-computer="false">29</div>
                                    <div class="seat available" data-seat-id="30" data-is-computer="false">30</div>
                                    <div class="seat available" data-seat-id="28" data-is-computer="false">28</div>
                                </div>
                            </div>
                            
                            <!-- Table 6 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="31" data-is-computer="false">31</div>
                                    <div class="seat available" data-seat-id="32" data-is-computer="false">32</div>
                                    <div class="seat available" data-seat-id="33" data-is-computer="false">33</div>
                                </div>
                                <div class="table-center">Table 6</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="35" data-is-computer="false">35</div>
                                    <div class="seat available" data-seat-id="36" data-is-computer="false">36</div>
                                    <div class="seat available" data-seat-id="34" data-is-computer="false">34</div>
                                </div>
                            </div>
                            
                            <!-- Table 7 -->
                            <div class="table-group table-7">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="37" data-is-computer="false">37</div>
                                    <div class="seat available" data-seat-id="38" data-is-computer="false">38</div>
                                    <div class="seat available" data-seat-id="39" data-is-computer="false">39</div>
                                </div>
                                <div class="table-center">Table 7</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="40" data-is-computer="false">40</div>
                                </div>
                            </div>
                        </div>
                        
                        <h3 class="section-title">First Floor - Computer Stations</h3>
                        <div class="computers-container">
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C1" data-is-computer="true">1</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C2" data-is-computer="true">2</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C3" data-is-computer="true">3</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C4" data-is-computer="true">4</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C5" data-is-computer="true">5</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C6" data-is-computer="true">6</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C7" data-is-computer="true">7</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C8" data-is-computer="true">8</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C9" data-is-computer="true">9</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C10" data-is-computer="true">10</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="secondFloor" class="floor-content">
                    <div class="floor-section">
                        <h3 class="section-title">Second Floor - Study Tables</h3>
                        
                        <div class="tables-container">
                            <!-- Table 8 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="41" data-is-computer="false">41</div>
                                    <div class="seat available" data-seat-id="42" data-is-computer="false">42</div>
                                    <div class="seat available" data-seat-id="43" data-is-computer="false">43</div>
                                </div>
                                <div class="table-center">Table 8</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="45" data-is-computer="false">45</div>
                                    <div class="seat available" data-seat-id="46" data-is-computer="false">46</div>
                                    <div class="seat available" data-seat-id="44" data-is-computer="false">44</div>
                                </div>
                            </div>
                            
                            <!-- Table 9 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="47" data-is-computer="false">47</div>
                                    <div class="seat available" data-seat-id="48" data-is-computer="false">48</div>
                                    <div class="seat available" data-seat-id="49" data-is-computer="false">49</div>
                                </div>
                                <div class="table-center">Table 9</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="51" data-is-computer="false">51</div>
                                    <div class="seat available" data-seat-id="52" data-is-computer="false">52</div>
                                    <div class="seat available" data-seat-id="50" data-is-computer="false">50</div>
                                </div>
                            </div>
                            
                            <!-- Table 10 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="53" data-is-computer="false">53</div>
                                    <div class="seat available" data-seat-id="54" data-is-computer="false">54</div>
                                    <div class="seat available" data-seat-id="55" data-is-computer="false">55</div>
                                </div>
                                <div class="table-center">Table 10</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="57" data-is-computer="false">57</div>
                                    <div class="seat available" data-seat-id="58" data-is-computer="false">58</div>
                                    <div class="seat available" data-seat-id="56" data-is-computer="false">56</div>
                                </div>
                            </div>
                            
                            <!-- Table 11 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="59" data-is-computer="false">59</div>
                                    <div class="seat available" data-seat-id="60" data-is-computer="false">60</div>
                                    <div class="seat available" data-seat-id="61" data-is-computer="false">61</div>
                                </div>
                                <div class="table-center">Table 11</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="63" data-is-computer="false">63</div>
                                    <div class="seat available" data-seat-id="64" data-is-computer="false">64</div>
                                    <div class="seat available" data-seat-id="62" data-is-computer="false">62</div>
                                </div>
                            </div>
                            
                            <!-- Table 12 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="65" data-is-computer="false">65</div>
                                    <div class="seat available" data-seat-id="66" data-is-computer="false">66</div>
                                    <div class="seat available" data-seat-id="67" data-is-computer="false">67</div>
                                </div>
                                <div class="table-center">Table 12</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="69" data-is-computer="false">69</div>
                                    <div class="seat available" data-seat-id="70" data-is-computer="false">70</div>
                                    <div class="seat available" data-seat-id="68" data-is-computer="false">68</div>
                                </div>
                            </div>
                            
                            <!-- Table 13 -->
                            <div class="table-group">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="71" data-is-computer="false">71</div>
                                    <div class="seat available" data-seat-id="72" data-is-computer="false">72</div>
                                    <div class="seat available" data-seat-id="73" data-is-computer="false">73</div>
                                </div>
                                <div class="table-center">Table 13</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="75" data-is-computer="false">75</div>
                                    <div class="seat available" data-seat-id="76" data-is-computer="false">76</div>
                                    <div class="seat available" data-seat-id="74" data-is-computer="false">74</div>
                                </div>
                            </div>
                            
                            <!-- Table 14 -->
                            <div class="table-group table-7">
                                <div class="table-seats-top">
                                    <div class="seat available" data-seat-id="77" data-is-computer="false">77</div>
                                    <div class="seat available" data-seat-id="78" data-is-computer="false">78</div>
                                    <div class="seat available" data-seat-id="79" data-is-computer="false">79</div>
                                </div>
                                <div class="table-center">Table 14</div>
                                <div class="table-seats-bottom">
                                    <div class="seat available" data-seat-id="80" data-is-computer="false">80</div>
                                </div>
                            </div>
                        </div>
                        
                        <h3 class="section-title">Second Floor - Computer Stations</h3>
                        <div class="computers-container">
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C11" data-is-computer="true">11</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C12" data-is-computer="true">12</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C13" data-is-computer="true">13</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C14" data-is-computer="true">14</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C15" data-is-computer="true">15</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C16" data-is-computer="true">16</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C17" data-is-computer="true">17</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C18" data-is-computer="true">18</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C19" data-is-computer="true">19</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk"></div>
                                <div class="computer-seat available" data-seat-id="C20" data-is-computer="true">20</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="navigation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedSeat = null;
            
            // Set default date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('booking_date').value = today;
            
            // Event listeners
            document.getElementById('start_time').addEventListener('change', updateDurationOptions);
            document.getElementById('booking_date').addEventListener('change', updateSeatAvailability);
            document.getElementById('start_time').addEventListener('change', updateSeatAvailability);
            document.getElementById('duration').addEventListener('change', updateSeatAvailability);
            
            // Floor tab switching
            document.querySelectorAll('.floor-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    switchFloor(this.dataset.floor);
                });
            });
            
            // Seat click handlers
            document.querySelectorAll('.seat, .computer-seat').forEach(seat => {
                seat.addEventListener('click', function() {
                    if (!this.classList.contains('booked') && !this.classList.contains('attended')) {
                        selectSeat(this);
                    }
                });
            });
            
            function selectSeat(seatElement) {
                // Remove previous selection
                document.querySelectorAll('.seat.selected, .computer-seat.selected').forEach(seat => {
                    seat.classList.remove('selected');
                });
                
                // Select new seat
                seatElement.classList.add('selected');
                selectedSeat = {
                    id: seatElement.dataset.seatId,
                    isComputer: seatElement.dataset.isComputer === 'true'
                };
                
                // Update form
                document.getElementById('seat_id').value = selectedSeat.id;
                document.getElementById('is_computer').value = selectedSeat.isComputer ? '1' : '';
                
                // Update display
                const seatType = selectedSeat.isComputer ? 'Computer Station' : 'Study Seat';
                document.getElementById('selectedSeatInfo').innerHTML = `
                    <strong>Selected:</strong> ${selectedSeat.id} (${seatType})
                `;
                
                updateBookingSummary();
                updateBookButton();
            }
            
            function updateDurationOptions() {
                const startTime = parseInt(document.getElementById('start_time').value);
                const durationSelect = document.getElementById('duration');
                
                durationSelect.innerHTML = '<option value="">Select duration</option>';
                
                if (startTime) {
                    // Library closes at 17:00 (5 PM)
                    const maxDuration = 17 - startTime;
                    
                    for (let i = 1; i <= Math.min(maxDuration, 8); i++) {
                        const option = document.createElement('option');
                        option.value = i;
                        option.textContent = i + ' Hour' + (i > 1 ? 's' : '');
                        durationSelect.appendChild(option);
                    }
                }
                
                updateBookingSummary();
                updateBookButton();
            }
            
            function updateSeatAvailability() {
                const date = document.getElementById('booking_date').value;
                const startTime = document.getElementById('start_time').value;
                const duration = document.getElementById('duration').value;
                
                console.log('=== UPDATING SEAT AVAILABILITY ===');
                console.log('Date:', date, 'Start Time:', startTime, 'Duration:', duration);
                
                if (!date) {
                    console.log('No date selected');
                    resetAllSeatsToAvailable();
                    updateDebugInfo('No date selected');
                    return;
                }
                
                // Always fetch seat data for the selected date
                fetchSeatData(date, startTime, duration);
            }
            
            function resetAllSeatsToAvailable() {
                document.querySelectorAll('.seat, .computer-seat').forEach(seat => {
                    seat.classList.remove('booked', 'attended', 'selected');
                    seat.classList.add('available');
                });
            }
            
            function fetchSeatData(date, startTime, duration) {
                console.log('Fetching seat data for:', { date, startTime, duration });
                
                const requestData = {
                    date: date,
                    start_time: startTime ? parseInt(startTime) : 9,
                    duration: duration ? parseInt(duration) : 1
                };
                
                fetch('get-booked-seats.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('=== SERVER RESPONSE ===');
                    console.log('Full response:', data);
                    
                    if (data.success) {
                        updateSeatDisplay(data.bookedSeats || [], data.seatStatuses || {});
                        updateDebugInfo(data.debug);
                    } else {
                        console.error('Server error:', data.error);
                        resetAllSeatsToAvailable();
                        updateDebugInfo('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    resetAllSeatsToAvailable();
                    updateDebugInfo('Network error: ' + error.message);
                });
            }
            
            function updateSeatDisplay(conflictingSeats, seatStatuses) {
                console.log('=== UPDATING SEAT DISPLAY ===');
                console.log('Conflicting seats:', conflictingSeats);
                console.log('All seat statuses:', seatStatuses);
                
                let updatedCount = 0;
                const seatUpdates = [];
                
                document.querySelectorAll('.seat, .computer-seat').forEach(seat => {
                    const seatId = seat.dataset.seatId;
                    const oldClasses = seat.className;
                    
                    // Reset all status classes
                    seat.classList.remove('booked', 'attended', 'selected', 'available');
                    
                    // Apply status based on database data
                    if (seatStatuses[seatId]) {
                        // Use exact status from database
                        seat.classList.add(seatStatuses[seatId]);
                        seatUpdates.push(`Seat ${seatId}: ${seatStatuses[seatId]} (from database)`);
                        updatedCount++;
                    } else if (conflictingSeats.includes(seatId)) {
                        // Fallback for conflicting seats
                        seat.classList.add('booked');
                        seatUpdates.push(`Seat ${seatId}: booked (conflicting)`);
                        updatedCount++;
                    } else {
                        // Available by default
                        seat.classList.add('available');
                    }
                    
                    const newClasses = seat.className;
                    if (oldClasses !== newClasses) {
                        console.log(`Seat ${seatId}: ${oldClasses} → ${newClasses}`);
                    }
                });
                
                console.log(`Updated ${updatedCount} seats with database status`);
                console.log('Seat updates:', seatUpdates);
                
                // Clear selection if selected seat is now unavailable
                if (selectedSeat && (conflictingSeats.includes(selectedSeat.id) || 
                    (seatStatuses[selectedSeat.id] && seatStatuses[selectedSeat.id] !== 'available'))) {
                    console.log(`Clearing selection for seat ${selectedSeat.id} - now unavailable`);
                    clearSeatSelection();
                }
            }
            
            function clearSeatSelection() {
                selectedSeat = null;
                document.querySelectorAll('.seat.selected, .computer-seat.selected').forEach(seat => {
                    seat.classList.remove('selected');
                });
                document.getElementById('selectedSeatInfo').innerHTML = '<span style="color: #666;">No seat selected</span>';
                document.getElementById('seat_id').value = '';
                document.getElementById('is_computer').value = '';
                updateBookingSummary();
                updateBookButton();
            }
            
            function updateDebugInfo(debugData) {
                const debugContent = document.getElementById('debugContent');
                if (debugData && typeof debugData === 'object') {
                    let debugText = '';
                    if (debugData.bookingDetails && debugData.bookingDetails.length > 0) {
                        debugText += `Found ${debugData.totalBookingsFound} bookings for ${debugData.date}:\n`;
                        debugData.bookingDetails.forEach(booking => {
                            debugText += `- Seat ${booking.seat_id}: ${booking.status} (${booking.time_slot}) - Student ${booking.student_id}\n`;
                        });
                    } else {
                        debugText += `No bookings found for ${debugData.date}\n`;
                    }
                    debugText += `\nSeat Statuses: ${JSON.stringify(debugData.allSeatStatuses, null, 2)}`;
                    debugContent.textContent = debugText;
                } else {
                    debugContent.textContent = debugData || 'No debug data';
                }
            }
            
            function updateBookingSummary() {
                const date = document.getElementById('booking_date').value;
                const startTime = document.getElementById('start_time').value;
                const duration = document.getElementById('duration').value;
                const summaryDiv = document.getElementById('bookingSummary');
                
                if (!date || !startTime || !duration || !selectedSeat) {
                    summaryDiv.innerHTML = '<span style="color: #666;">Please select date, time, duration, and seat</span>';
                    return;
                }
                
                const startHour = parseInt(startTime);
                const endHour = startHour + parseInt(duration);
                const seatType = selectedSeat.isComputer ? 'Computer Station' : 'Study Seat';
                
                summaryDiv.innerHTML = `
                    <strong>Booking Summary:</strong><br>
                    <strong>Seat:</strong> ${selectedSeat.id} (${seatType})<br>
                    <strong>Date:</strong> ${new Date(date).toLocaleDateString()}<br>
                    <strong>Time:</strong> ${formatTime(startHour)} - ${formatTime(endHour)}<br>
                    <strong>Duration:</strong> ${duration} hour(s)
                `;
            }
            
            function updateBookButton() {
                const date = document.getElementById('booking_date').value;
                const startTime = document.getElementById('start_time').value;
                const duration = document.getElementById('duration').value;
                const bookBtn = document.getElementById('bookSeatBtn');
                
                bookBtn.disabled = !(date && startTime && duration && selectedSeat);
            }
            
            function switchFloor(floor) {
                // Update tabs
                document.querySelectorAll('.floor-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelector(`[data-floor="${floor}"]`).classList.add('active');
                
                // Update content
                document.querySelectorAll('.floor-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(`${floor}Floor`).classList.add('active');
                
                // Clear selection when switching floors
                if (selectedSeat) {
                    clearSeatSelection();
                }
            }
            
            function formatTime(hour) {
                if (hour <= 12) {
                    return hour === 12 ? '12:00 PM' : `${hour}:00 AM`;
                } else {
                    return `${hour - 12}:00 PM`;
                }
            }
            
            // Initialize on page load
            console.log('=== PAGE LOADED ===');
            console.log('Initial date:', today);
            updateDurationOptions();
            updateSeatAvailability(); // This will load seat data for today's date
        });
    </script>
</body>
</html>
