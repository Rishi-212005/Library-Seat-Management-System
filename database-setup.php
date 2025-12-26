<?php
require_once 'config/database.php';

echo "<h1>Database Setup</h1>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Create students table
    $sql = "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(255) NOT NULL,
        roll_number VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        course VARCHAR(100),
        year_of_study INT,
        phone VARCHAR(20),
        address TEXT,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        issue TEXT DEFAULT 'No issue',
        profile_image LONGTEXT,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p>✅ Students table created/verified</p>";
    
    // Create seat_bookings table
    $sql = "CREATE TABLE IF NOT EXISTS seat_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        seat_id VARCHAR(20) NOT NULL,
        is_computer BOOLEAN DEFAULT FALSE,
        booking_date DATE NOT NULL,
        start_time INT NOT NULL,
        duration INT NOT NULL,
        time_slot VARCHAR(20) NOT NULL,
        booking_code VARCHAR(20) UNIQUE NOT NULL,
        status ENUM('confirmed', 'active', 'cancelled', 'completed') DEFAULT 'confirmed',
        cancellation_reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        INDEX idx_booking_date (booking_date),
        INDEX idx_seat_time (seat_id, booking_date, time_slot),
        INDEX idx_student_bookings (student_id, booking_date),
        INDEX idx_booking_code (booking_code)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Seat bookings table created/verified</p>";
    
    // Create waiting_list table
    $sql = "CREATE TABLE IF NOT EXISTS waiting_list (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        seat_id VARCHAR(20) NOT NULL,
        is_computer BOOLEAN DEFAULT FALSE,
        booking_date DATE NOT NULL,
        start_time INT NOT NULL,
        duration INT NOT NULL,
        time_slots JSON NOT NULL,
        waiting_code VARCHAR(20) UNIQUE NOT NULL,
        status ENUM('waiting', 'confirmed', 'expired') DEFAULT 'waiting',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        INDEX idx_waiting_date (booking_date),
        INDEX idx_waiting_seat (seat_id, booking_date)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Waiting list table created/verified</p>";
    
    // Create email_logs table for tracking sent emails
    $sql = "CREATE TABLE IF NOT EXISTS email_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        email_to VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        booking_code VARCHAR(20),
        email_type ENUM('booking_confirmation', 'waitlist_notification', 'cancellation', 'reminder') NOT NULL,
        status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
        error_message TEXT NULL,
        sent_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        INDEX idx_email_student (student_id),
        INDEX idx_email_type (email_type),
        INDEX idx_email_status (status)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Email logs table created/verified</p>";
    
    // Insert test student if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE roll_number = 'TEST001'");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO students (student_name, roll_number, email, password, course, year_of_study, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Test Student',
            'TEST001',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT),
            'Computer Science',
            2,
            '1234567890'
        ]);
        echo "<p>✅ Test student created</p>";
    } else {
        echo "<p>✅ Test student already exists</p>";
    }
    
    echo "<h2>Test Credentials:</h2>";
    echo "<p><strong>Roll Number:</strong> TEST001</p>";
    echo "<p><strong>Password:</strong> password123</p>";
    echo "<p><strong>Email:</strong> test@example.com</p>";
    
    echo "<h2>Database Setup Complete!</h2>";
    echo "<p><a href='seat-booking.php'>Go to Seat Booking</a></p>";
    echo "<p><a href='student-login.php'>Go to Student Login</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
