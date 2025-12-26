<?php
session_start();
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// DB connection
$conn = new mysqli("sql200.infinityfree.com", "if0_39418937", "library2025", "if0_39418937_library_management");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle OTP request
if (isset($_POST['send_otp'])) {
    $roll = trim($_POST['roll_number']);
    $stmt = $conn->prepare("SELECT email FROM students WHERE roll_number = ?");
    $stmt->bind_param("s", $roll);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($email);
        $stmt->fetch();

        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['roll_number'] = $roll;

        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'libraryseatmanagement@gmail.com'; // your email
            $mail->Password   = 'jgrx rble igqm alvm';    // your app password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('your_email@gmail.com', 'Library System');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Library System Password Reset OTP';
            $mail->Body    = "Your OTP for resetting password is <b>$otp</b>. It is valid for 5 minutes.";

            $mail->send();
            $success = "OTP has been sent to your registered email.";
        } catch (Exception $e) {
            $error = "Failed to send OTP. Please try again.";
        }
    } else {
        $error = "Roll number not found.";
    }
}

// Handle password reset
if (isset($_POST['verify_otp'])) {
    $enteredOtp = $_POST['otp'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $rollNumber = $_SESSION['roll_number'] ?? '';

    if ($enteredOtp != $_SESSION['otp']) {
        $error = "Invalid OTP.";
    } elseif (empty($newPassword) || $newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE students SET password = ? WHERE roll_number = ?");
        $stmt->bind_param("ss", $hashed, $rollNumber);
        if ($stmt->execute()) {
            unset($_SESSION['otp'], $_SESSION['roll_number']);
            header("Location: student-login.php");
            exit();
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Library System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container" style="max-width: 500px; margin: auto; padding: 2rem;">
    <h2>Forgot Password</h2>

    <?php if (!empty($error)): ?>
        <div style="color: red; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="color: green; margin-bottom: 1rem;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (empty($_SESSION['otp'])): ?>
        <form method="POST">
            <label for="roll_number">Enter your Roll Number:</label>
            <input type="text" name="roll_number" id="roll_number" required>
            <button type="submit" name="send_otp">Send OTP</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <label for="otp">Enter OTP sent to your email:</label>
            <input type="text" name="otp" id="otp" required>

            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" required>

            <button type="submit" name="verify_otp">Reset Password</button>
        </form>
    <?php endif; ?>

    <div style="margin-top: 1rem;">
        <a href="student-login.php">Back to Login</a>
    </div>
</div>
</body>
<style>
    /* Forgot Password Form Styles */
.container {
    background-color: #ffffff;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    margin-top: 3rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

h2 {
    text-align: center;
    color: #1f2937;
    margin-bottom: 1.5rem;
}

form {
    display: flex;
    flex-direction: column;
}

label {
    margin-top: 1rem;
    font-weight: 600;
    color: #374151;
}

input[type="text"],
input[type="password"] {
    padding: 10px;
    margin-top: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 5px;
    font-size: 1rem;
    background-color: #f9fafb;
    transition: border 0.3s ease;
}

input[type="text"]:focus,
input[type="password"]:focus {
    border-color: #2563eb;
    outline: none;
    background-color: #fff;
}

button[type="submit"] {
    margin-top: 1.5rem;
    padding: 10px;
    background-color: #2563eb;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
}

button[type="submit"]:hover {
    background-color: #1e40af;
}

a {
    display: inline-block;
    margin-top: 1rem;
    text-decoration: none;
    color: #2563eb;
    font-weight: 500;
}

a:hover {
    text-decoration: underline;
}

</style>
</html>
