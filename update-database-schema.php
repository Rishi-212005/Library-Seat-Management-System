<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Database Schema Update</h2>";
    echo "<p>Starting database schema update...</p>";
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM seat_bookings LIKE 'attendance_code'");
    if ($stmt->rowCount() == 0) {
        // Add attendance_code column
        $pdo->exec("ALTER TABLE seat_bookings ADD COLUMN attendance_code VARCHAR(20) UNIQUE NULL AFTER booking_code");
        echo "<p>✅ Added attendance_code column</p>";
    } else {
        echo "<p>ℹ️ attendance_code column already exists</p>";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM seat_bookings LIKE 'check_in_time'");
    if ($stmt->rowCount() == 0) {
        // Add check_in_time column
        $pdo->exec("ALTER TABLE seat_bookings ADD COLUMN check_in_time TIMESTAMP NULL AFTER attendance_code");
        echo "<p>✅ Added check_in_time column</p>";
    } else {
        echo "<p>ℹ️ check_in_time column already exists</p>";
    }
    
    // Update status enum
    $pdo->exec("ALTER TABLE seat_bookings MODIFY COLUMN status ENUM('booked', 'attended', 'cancelled') DEFAULT 'booked'");
    echo "<p>✅ Updated status enum values</p>";
    
    // Add index for attendance_code if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE seat_bookings ADD INDEX idx_attendance_code (attendance_code)");
        echo "<p>✅ Added index for attendance_code</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p>ℹ️ Index for attendance_code already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Update existing records to have attendance codes
    $stmt = $pdo->prepare("
        UPDATE seat_bookings 
        SET attendance_code = CONCAT('ATT-', UPPER(SUBSTRING(MD5(CONCAT(id, booking_code)), 1, 6)))
        WHERE attendance_code IS NULL AND status IN ('booked', 'attended')
    ");
    $stmt->execute();
    $updatedRows = $stmt->rowCount();
    echo "<p>✅ Updated $updatedRows existing records with attendance codes</p>";
    
    echo "<h3>✅ Database schema update completed successfully!</h3>";
    echo "<p><a href='seat-booking.php'>Go to Seat Booking</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error updating database schema:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
