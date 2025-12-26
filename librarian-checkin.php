<?php
// Set timezone to match your check-in system
date_default_timezone_set('Asia/Kolkata');

$mysqli = new mysqli("sql200.infinityfree.com", "if0_39418937", "library2025", "if0_39418937_library_management");

// Stop execution if connection fails
if ($mysqli->connect_error) {
    exit;
}

// Set MySQL connection timezone to IST
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
    <title>Public Check-In - Library System</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .checkin-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .checkin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .checkin-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #5a67d8;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="nav-bar">
            <ul>
                <li><span>Public Check-In System</span></li>
                <li>
                    <a href="librarian-dashboard.html">Dashboard</a>
                    <a href="index.html">Home</a>
                    <a href="#" id="logoutBtn">Logout</a>
                </li>
            </ul>
        </nav>
        
        <div class="checkin-container">
            <div class="checkin-header">
                <h1>Public Check-In</h1>
                <p>Enter attendance code to check in public</p>
            </div>

            <div class="checkin-form">
                <form id="checkinForm">
                    <div class="form-group">
                        <label for="attendanceCode">Attendance Code:</label>
                        <input type="text" id="attendanceCode" name="attendanceCode" 
                               placeholder="Enter attendance code" required>
                    </div>
                    <button type="submit" class="btn">Check In Public</button>
                    <a href="librarian-dashboard.html" class="btn btn-secondary" style="margin-left: 10px; text-decoration: none;">Back to Dashboard</a>
                </form>
            </div>

            <div id="messageContainer"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('checkinForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const attendanceCode = document.getElementById('attendanceCode').value.trim();
                if (attendanceCode) {
                    checkInStudent(attendanceCode);
                }
            });

            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = 'logout.php';
                }
            });
        });

        function checkInStudent(attendanceCode) {
            fetch('librarian-checkin-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ attendance_code: attendanceCode })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    document.getElementById('attendanceCode').value = '';
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error checking in student', 'error');
            });
        }

        function showMessage(message, type) {
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.innerHTML = `
                <div class="message ${type}">
                    ${message}
                </div>
            `;
            
            setTimeout(() => {
                messageContainer.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
