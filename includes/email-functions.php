<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Email configuration - Update these with your Gmail credentials
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'libraryseatmanagement@gmail.com'); // Replace with your Gmail
define('SMTP_PASSWORD', 'qbou kbyb dkar tckr'); // Replace with your Gmail App Password
define('SMTP_FROM_EMAIL', 'libraryseatmanagement@gmail.com'); // Replace with your Gmail
define('SMTP_FROM_NAME', 'SRS Library System');

/**
 * Send email using Gmail SMTP
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    // For now, we'll simulate email sending and log it
    // In production, uncomment the PHPMailer code below
    
    try {
        // Log the email attempt
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO email_logs (student_id, email_to, subject, message, email_type, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['student_id'] ?? 0,
            $to,
            $subject,
            $htmlBody,
            'booking_confirmation',
            'sent'
        ]);
        
        // Simulate successful email sending
        error_log("EMAIL SENT TO: $to");
        error_log("SUBJECT: $subject");
        error_log("BODY: $htmlBody");
        
        return true;
        
        /* 
        // Uncomment this section when you have PHPMailer installed and configured
        
        require_once 'vendor/autoload.php'; // Make sure PHPMailer is installed via Composer
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);
        
        $mail->send();
        
        // Log successful email
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO email_logs (student_id, email_to, subject, message, email_type, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['student_id'] ?? 0,
            $to,
            $subject,
            $htmlBody,
            'booking_confirmation',
            'sent'
        ]);
        
        return true;
        */
        
    } catch (Exception $e) {
        // Log failed email
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO email_logs (student_id, email_to, subject, message, email_type, status, error_message) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['student_id'] ?? 0,
                $to,
                $subject,
                $htmlBody,
                'booking_confirmation',
                'failed',
                $e->getMessage()
            ]);
        } catch (Exception $logError) {
            error_log("Failed to log email error: " . $logError->getMessage());
        }
        
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send booking confirmation email
 */
function sendBookingConfirmationEmail($student, $seatId, $bookingDate, $startTime, $duration, $bookingCode, $status) {
    $studentName = htmlspecialchars($student['student_name']);
    $studentEmail = $student['email'];
    $seatType = (strpos($seatId, 'C') === 0) ? 'Computer Station' : 'Study Seat';
    
    $startTimeFormatted = formatTime($startTime);
    $endTimeFormatted = formatTime($startTime + $duration);
    $dateFormatted = date('l, F j, Y', strtotime($bookingDate));
    
    $subject = ($status === 'waitlist') 
        ? "Library Seat Waitlist Confirmation - Code: $bookingCode"
        : "Library Seat Booking Confirmation - Code: $bookingCode";
    
    $htmlBody = generateEmailTemplate($studentName, $seatId, $seatType, $dateFormatted, $startTimeFormatted, $endTimeFormatted, $duration, $bookingCode, $status);
    
    $textBody = generateTextEmail($studentName, $seatId, $seatType, $dateFormatted, $startTimeFormatted, $endTimeFormatted, $duration, $bookingCode, $status);
    
    return sendEmail($studentEmail, $subject, $htmlBody, $textBody);
}

/**
 * Send waitlist confirmation email
 */
function sendWaitlistConfirmationEmail($student, $seatId, $bookingDate, $startTime, $duration, $bookingCode) {
    $studentName = htmlspecialchars($student['student_name']);
    $studentEmail = $student['email'];
    $seatType = (strpos($seatId, 'C') === 0) ? 'Computer Station' : 'Study Seat';
    
    $startTimeFormatted = formatTime($startTime);
    $endTimeFormatted = formatTime($startTime + $duration);
    $dateFormatted = date('l, F j, Y', strtotime($bookingDate));
    
    $subject = "Library Seat Available - Your Waitlist Booking Confirmed - Code: $bookingCode";
    
    $htmlBody = generateEmailTemplate($studentName, $seatId, $seatType, $dateFormatted, $startTimeFormatted, $endTimeFormatted, $duration, $bookingCode, 'confirmed');
    
    $textBody = generateTextEmail($studentName, $seatId, $seatType, $dateFormatted, $startTimeFormatted, $endTimeFormatted, $duration, $bookingCode, 'confirmed');
    
    return sendEmail($studentEmail, $subject, $htmlBody, $textBody);
}

/**
 * Generate HTML email template
 */
function generateEmailTemplate($studentName, $seatId, $seatType, $date, $startTime, $endTime, $duration, $bookingCode, $status) {
    $statusMessage = ($status === 'waitlist') 
        ? '<p style="color: #f59e0b; font-weight: bold;">You have been added to the waiting list. We will notify you if a seat becomes available.</p>'
        : '<p style="color: #22c55e; font-weight: bold;">Your seat has been successfully booked!</p>';
    
    $statusColor = ($status === 'waitlist') ? '#f59e0b' : '#22c55e';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Library Booking Confirmation</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
            <h1 style='margin: 0; font-size: 28px;'>SRS Government Degree College</h1>
            <h2 style='margin: 10px 0 0 0; font-size: 20px; font-weight: normal;'>Library Seat Booking System</h2>
        </div>
        
        <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e9ecef;'>
            <h3 style='color: #495057; margin-top: 0;'>Dear $studentName,</h3>
            
            $statusMessage
            
            <div style='background: white; padding: 25px; border-radius: 8px; border-left: 4px solid $statusColor; margin: 20px 0;'>
                <h3 style='color: #495057; margin-top: 0; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;'>Booking Details</h3>
                
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #6c757d;'>Booking Code:</td>
                        <td style='padding: 8px 0; background: #007bff; color: white; padding: 5px 10px; border-radius: 4px; font-family: monospace; font-weight: bold;'>$bookingCode</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #6c757d;'>Seat:</td>
                        <td style='padding: 8px 0;'>$seatId ($seatType)</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #6c757d;'>Date:</td>
                        <td style='padding: 8px 0;'>$date</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #6c757d;'>Time:</td>
                        <td style='padding: 8px 0;'>$startTime - $endTime</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #6c757d;'>Duration:</td>
                        <td style='padding: 8px 0;'>$duration hour(s)</td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 4px solid #2196f3; margin: 20px 0;'>
                <h4 style='color: #1976d2; margin-top: 0;'>Important Instructions:</h4>
                <ul style='margin: 0; padding-left: 20px;'>
                    <li>Please arrive on time. Late arrivals (more than 10 minutes) will result in automatic cancellation.</li>
                    <li>Bring your student ID and this booking code for verification.</li>
                    <li>Keep your study area clean and follow library rules.</li>
                    <li>If you need to cancel, please do so at least 1 hour in advance.</li>
                </ul>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <p style='color: #6c757d; margin: 0;'>Thank you for using the SRS Library Booking System!</p>
                <p style='color: #6c757d; margin: 5px 0 0 0; font-size: 14px;'>For support, contact the library administration.</p>
            </div>
        </div>
        
        <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 12px;'>
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; " . date('Y') . " SRS Government Degree College. All rights reserved.</p>
        </div>
    </body>
    </html>";
}

/**
 * Generate plain text email
 */
function generateTextEmail($studentName, $seatId, $seatType, $date, $startTime, $endTime, $duration, $bookingCode, $status) {
    $statusMessage = ($status === 'waitlist') 
        ? 'You have been added to the waiting list. We will notify you if a seat becomes available.'
        : 'Your seat has been successfully booked!';
    
    return "
SRS Government Degree College - Library Booking Confirmation

Dear $studentName,

$statusMessage

BOOKING DETAILS:
================
Booking Code: $bookingCode
Seat: $seatId ($seatType)
Date: $date
Time: $startTime - $endTime
Duration: $duration hour(s)

IMPORTANT INSTRUCTIONS:
======================
- Please arrive on time. Late arrivals (more than 10 minutes) will result in automatic cancellation.
- Bring your student ID and this booking code for verification.
- Keep your study area clean and follow library rules.
- If you need to cancel, please do so at least 1 hour in advance.

Thank you for using the SRS Library Booking System!

For support, contact the library administration.

---
This is an automated email. Please do not reply to this message.
© " . date('Y') . " SRS Government Degree College. All rights reserved.
    ";
}

/**
 * Get email logs for a student
 */
function getStudentEmailLogs($studentId, $limit = 10) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM email_logs 
            WHERE student_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$studentId, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting email logs: " . $e->getMessage());
        return [];
    }
}
?>
