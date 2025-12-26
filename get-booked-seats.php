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
    <title>Library Seat Booking - District Library,Anantapur </title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="main-navbar">
        <div class="nav-container">
            <div class="nav-brand">

                <span class="nav-brand-text">District Library,Anantapur </span>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="index.html" class="nav-link">Home</a>
                <a href="about-us.html" class="nav-link">About Us</a>
                <a href="academics.html" class="nav-link">Academics</a>
                <a href="gallery.html" class="nav-link">Gallery</a>
                <a href="contact-us.html" class="nav-link">Contact Us</a>
                <a href="library-system.html" class="nav-link">Library System</a>
                <span class="nav-user">Welcome, <?php echo htmlspecialchars($student['student_name']); ?></span>
                <a href="?logout=1" class="nav-link nav-logout">Logout</a>
            </div>
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <div class="container">
        <header class="header">
            <h1>Library Seat Booking</h1>
            <p>Select your preferred date, time, and seat to book your study space</p>
            <div class="user-info">
                <strong>Student:</strong> <?php echo htmlspecialchars($student['student_name']); ?> 
                (<?php echo htmlspecialchars($student['roll_number']); ?>) - 
                <?php echo htmlspecialchars($student['course']); ?>
            </div>
        </header>
        
        <!-- Status Messages -->
        <?php if (!empty($message)): ?>
            <div id="statusMessage" class="status-message <?php echo $messageType; ?>" style="display: block;">
                <?php echo $message; ?>
            </div>
        <?php else: ?>
            <div id="statusMessage" class="status-message" style="display: none;"></div>
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
                        <div id="selectedSeatInfo" class="seat-info">
                            No seat selected
                        </div>
                    </div>
                    
                    <button type="button" id="refreshBtn" class="btn btn-secondary">
                        🔄 Refresh Seat Status
                    </button>
                    
                    <button type="submit" name="book_seat" id="bookSeatBtn" class="btn btn-success" disabled>
                        Book Seat
                    </button>
                </form>
                
                <!-- Debug Information -->
                <div class="debug-section">
                    <h4>Database Status</h4>
                    <div id="debugInfo" class="debug-info">
                        Select date and time to see database status
                    </div>
                </div>
                
                <!-- Seat Legend -->
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

                <!-- Current Bookings -->
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
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C1" data-is-computer="true">1</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C2" data-is-computer="true">2</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C3" data-is-computer="true">3</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C4" data-is-computer="true">4</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C5" data-is-computer="true">5</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C6" data-is-computer="true">6</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C7" data-is-computer="true">7</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C8" data-is-computer="true">8</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C9" data-is-computer="true">9</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
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
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C11" data-is-computer="true">11</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C12" data-is-computer="true">12</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C13" data-is-computer="true">13</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C14" data-is-computer="true">14</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C15" data-is-computer="true">15</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C16" data-is-computer="true">16</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C17" data-is-computer="true">17</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C18" data-is-computer="true">18</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
                                <div class="computer-seat available" data-seat-id="C19" data-is-computer="true">19</div>
                            </div>
                            <div class="computer-station">
                                <div class="computer-desk">💻</div>
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
            document.getElementById('refreshBtn').addEventListener('click', updateSeatAvailability);
            
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
                
                fetch(`get-seat-status.php?t=${Date.now()}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
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
                        updateSeatDisplay(data.seat_statuses || {});
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
            
            function updateSeatDisplay(seatStatuses) {
                console.log('=== UPDATING SEAT DISPLAY ===');
                console.log('Seat statuses:', seatStatuses);
                
                let updatedCount = 0;
                
                document.querySelectorAll('.seat, .computer-seat').forEach(seat => {
                    const seatId = seat.dataset.seatId;
                    const oldClasses = seat.className;
                    
                    // Reset all status classes
                    seat.classList.remove('booked', 'attended', 'selected', 'available');
                    
                    // Apply status based on database data
                    if (seatStatuses[seatId]) {
                        // Use exact status from database
                        seat.classList.add(seatStatuses[seatId]);
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
                
                // Clear selection if selected seat is now unavailable
                if (selectedSeat && seatStatuses[selectedSeat.id] && seatStatuses[selectedSeat.id] !== 'available') {
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
                updateBookButton();
            }
            
            function updateDebugInfo(debugData) {
                const debugContent = document.getElementById('debugInfo');
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
            
            // Initialize on page load
            console.log('=== PAGE LOADED ===');
            console.log('Initial date:', today);
            updateDurationOptions();
            updateSeatAvailability(); // This will load seat data for today's date
        });
    </script>
</body>
</html>
