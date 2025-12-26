<?php
require_once 'config/database.php';

echo "<h2>Database Migration Script</h2>";
echo "<p>Checking and updating database schema...</p>";

try {
    $pdo = getDBConnection();
    
    // Check if seat_bookings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'seat_bookings'");
    if (!$stmt->fetch()) {
        echo "<p>Creating seat_bookings table...</p>";
        $pdo->exec("
            CREATE TABLE seat_bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                seat_id VARCHAR(10) NOT NULL,
                is_computer TINYINT(1) DEFAULT 0,
                booking_date DATE NOT NULL,
                start_time INT NOT NULL,
                duration INT NOT NULL,
                time_slot VARCHAR(20),
                booking_code VARCHAR(10),
                status VARCHAR(20) DEFAULT 'confirmed',
                cancellation_reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_seat_date_time (seat_id, booking_date, time_slot),
                INDEX idx_student_date (student_id, booking_date),
                INDEX idx_booking_code (booking_code),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            )
        ");
        echo "<p style='color: green;'>✓ seat_bookings table created successfully</p>";
    } else {
        echo "<p>✓ seat_bookings table already exists</p>";
        
        // Check and add missing columns
        $stmt = $pdo->query("SHOW COLUMNS FROM seat_bookings");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'booking_code' => "ADD COLUMN booking_code VARCHAR(10)",
            'status' => "ADD COLUMN status VARCHAR(20) DEFAULT 'confirmed'",
            'time_slot' => "ADD COLUMN time_slot VARCHAR(20)",
            'is_computer' => "ADD COLUMN is_computer TINYINT(1) DEFAULT 0",
            'cancellation_reason' => "ADD COLUMN cancellation_reason TEXT",
            'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($requiredColumns as $column => $sql) {
            if (!in_array($column, $columns)) {
                $pdo->exec("ALTER TABLE seat_bookings $sql");
                echo "<p style='color: blue;'>✓ Added column: $column</p>";
            }
        }
    }
    
    // Check if waiting_list table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'waiting_list'");
    if (!$stmt->fetch()) {
        echo "<p>Creating waiting_list table...</p>";
        $pdo->exec("
            CREATE TABLE waiting_list (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                seat_id VARCHAR(10) NOT NULL,
                is_computer TINYINT(1) DEFAULT 0,
                booking_date DATE NOT NULL,
                start_time INT NOT NULL,
                duration INT NOT NULL,
                time_slots JSON,
                waiting_code VARCHAR(10),
                status VARCHAR(20) DEFAULT 'waiting',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_seat_date (seat_id, booking_date),
                INDEX idx_student_date (student_id, booking_date),
                INDEX idx_waiting_code (waiting_code),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            )
        ");
        echo "<p style='color: green;'>✓ waiting_list table created successfully</p>";
    } else {
        echo "<p>✓ waiting_list table already exists</p>";
    }
    
    // Generate booking codes for existing records without them
    $stmt = $pdo->query("SELECT COUNT(*) FROM seat_bookings WHERE booking_code IS NULL OR booking_code = ''");
    $missingCodes = $stmt->fetchColumn();
    
    if ($missingCodes > 0) {
        echo "<p>Generating booking codes for $missingCodes existing records...</p>";
        $stmt = $pdo->query("SELECT id FROM seat_bookings WHERE booking_code IS NULL OR booking_code = ''");
        $records = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($records as $id) {
            do {
                $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM seat_bookings WHERE booking_code = ?");
                $checkStmt->execute([$code]);
            } while ($checkStmt->fetchColumn() > 0);
            
            $updateStmt = $pdo->prepare("UPDATE seat_bookings SET booking_code = ? WHERE id = ?");
            $updateStmt->execute([$code, $id]);
        }
        echo "<p style='color: green;'>✓ Generated booking codes for all existing records</p>";
    }
    
    // Add indexes for better performance
    try {
        $pdo->exec("CREATE INDEX idx_seat_date_time ON seat_bookings (seat_id, booking_date, time_slot)");
        echo "<p style='color: blue;'>✓ Added performance index</p>";
    } catch (Exception $e) {
        // Index might already exist
    }
    
    echo "<h3 style='color: green;'>✅ Database migration completed successfully!</h3>";
    echo "<p><a href='seat-booking.php'>Go to Seat Booking</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
