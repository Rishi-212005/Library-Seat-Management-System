<?php
$mysqli = new mysqli("sql200.infinityfree.com", "if0_39418937", "library2025", "if0_39418937_library_management");

// Stop execution if connection fails
if ($mysqli->connect_error) {
    exit;
}

// 1. Delete all bookings from previous days// Set MySQL connection timezone to IST
$mysqli->query("SET time_zone = '+05:30'");

// 1. Delete all bookings from previous days (IST)
$mysqli->query("
    DELETE FROM seat_bookings
    WHERE booking_date < CONVERT_TZ(CURDATE(), @@session.time_zone, '+05:30')
");

// 2. Delete today's unclaimed bookings (IST)
$mysqli->query("
    DELETE FROM seat_bookings
    WHERE status = 'booked'
      AND check_in_time IS NULL
      AND booking_date = CONVERT_TZ(CURDATE(), @@session.time_zone, '+05:30')
      AND TIMESTAMPADD(MINUTE, 15, 
            STR_TO_DATE(
                CONCAT(booking_date, ' ', LPAD(start_time, 2, '0'), ':00'),
                '%Y-%m-%d %H:%i'
            )
        ) < CONVERT_TZ(NOW(), @@session.time_zone, '+05:30')
");

// Close the database connection
$mysqli->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Seat Booking - Library System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="main-navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <div class="nav-log"></div>
                <span class="nav-brand-text">District Library,Anantapur </span>
            </div>
            <div class="nav-menu">
                <a href="#" class="nav-link">Home</a>
                <a href="#" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <header class="header">
            <h1>Library Seat Booking</h1>
            <p>Select your preferred date and time to see real-time seat availability</p>
        </header>
        
        <!-- Status Messages -->
        <div id="statusMessage" class="status-message" style="display: none;"></div>
        
        <div class="booking-container">
            <div class="booking-controls">
                <h3>Booking Details</h3>
                
                <div class="form-group">
                    <label for="booking_date">Select Date</label>
                    <input type="date" id="booking_date" name="booking_date" min="">
                </div>
                
                <div class="form-group">
                    <label for="start_time">Start Time</label>
                    <select id="start_time" name="start_time">
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
                    <select id="duration" name="duration">
                        <option value="">Select duration</option>
                        <option value="1">1 Hour</option>
                        <option value="2">2 Hours</option>
                        <option value="3">3 Hours</option>
                        <option value="4">4 Hours</option>
                        <option value="5">5 Hours</option>
                        <option value="6">6 Hours</option>
                        <option value="7">7 Hours</option>
                        <option value="8">8 Hours</option>
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
                
                <button type="button" id="bookSeatBtn" class="btn btn-success" disabled>
                    Book Seat
                </button>
                
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
    
    <script src="script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateInput = document.getElementById('booking_date');

        // Set today's date as min
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);

        // Disable Sundays
        dateInput.addEventListener('input', function () {
            const selectedDate = new Date(this.value);
            const day = selectedDate.getDay(); // 0 = Sunday

            if (day === 0) {
                alert("Sundays are not allowed for booking.");
                this.value = ''; // Clear the input
            }
        });
    });
</script>

</body>
</html>
