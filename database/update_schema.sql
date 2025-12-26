

CREATE TABLE IF NOT EXISTS seat_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    seat_id VARCHAR(10) NOT NULL,
    is_computer BOOLEAN DEFAULT FALSE,
    booking_date DATE NOT NULL,
    start_time INT NOT NULL,
    duration INT NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    booking_code VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('confirmed', 'waitlist', 'active', 'cancelled', 'completed') DEFAULT 'confirmed',
    cancellation_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_booking_date_time (booking_date, time_slot),
    INDEX idx_student_bookings (student_id, status),
    INDEX idx_seat_availability (seat_id, booking_date, time_slot, status),
    INDEX idx_booking_code (booking_code)
);

-- Create waiting list table for better management
CREATE TABLE IF NOT EXISTS waiting_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    seat_id VARCHAR(10) NOT NULL,
    is_computer BOOLEAN DEFAULT FALSE,
    booking_date DATE NOT NULL,
    start_time INT NOT NULL,
    duration INT NOT NULL,
    time_slots JSON NOT NULL,
    waiting_code VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('waiting', 'confirmed', 'expired', 'cancelled') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_waiting_date (booking_date, status),
    INDEX idx_student_waiting (student_id, status)
);
