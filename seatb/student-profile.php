<?php
session_start();

// Database connection (assuming you have this)
require_once 'config.php';

// Get student_id from POST or session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $_SESSION['student_id'] = $student_id;
} else {
    $student_id = $_SESSION['student_id'] ?? null;
}

if (!$student_id) {
    header('Location: student-login.php');
    exit;
}

// Fetch student details
function getStudentById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

$student = getStudentById($student_id);

if (!$student) {
    echo "Student not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - <?php echo htmlspecialchars($student['student_name']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Header Section */
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            position: relative;
            border: 1px solid #e9ecef;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }

        .student-info h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .student-details {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .detail-item i {
            color: #007bff;
            width: 16px;
        }

        /* Navigation Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb a:hover {
            color: #0056b3;
        }

        .breadcrumb span {
            color: #6c757d;
            margin: 0 10px;
        }

        /* Stats Cards */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 20px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* Action Cards */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 24px;
        }

        .action-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .action-card p {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Back Button */
        .back-section {
            text-align: center;
            margin-top: 40px;
        }

        .back-btn {
            background: white;
            color: #007bff;
            padding: 15px 30px;
            border: 2px solid #007bff;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }

            .header {
                padding: 20px;
            }

            .student-details {
                flex-direction: column;
                gap: 15px;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }

            .stats-section {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0;
            animation: fadeIn 0.6s ease-in-out forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Breadcrumb Navigation -->
        <div class="breadcrumb">
            <a href="get-booked-seats.php"><i class="fas fa-home"></i> Dashboard</a>
            <span>></span>
            <span>Student Profile</span>
        </div>

        <!-- Header Section -->
        <div class="header loading">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
            </div>
            <div class="student-info">
                <h1><?php echo htmlspecialchars($student['student_name']); ?></h1>
                <div class="student-details">
                    <div class="detail-item">
                        <i class="fas fa-id-card"></i>
                        <span><?php echo htmlspecialchars($student['roll_number']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span><?php echo htmlspecialchars($student['course']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($student['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="actions-grid">
            <div class="action-card loading" style="animation-delay: 0.1s;">
                <div class="action-icon">
                    <i class="fas fa-chair"></i>
                </div>
                <h3>Current Booking</h3>
                <p>View and manage your active seat reservations, check-in status, and booking details.</p>
                <form action="current-booking.php" method="POST" style="display: inline;">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                    <button class="btn" type="submit">
                        <i class="fas fa-eye"></i>
                        View Current Booking
                    </button>
                </form>
            </div>

            <div class="action-card loading" style="animation-delay: 0.2s;">
                <div class="action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3>Booking History</h3>
                <p>Access your complete booking history, past reservations, and attendance records.</p>
                <form action="booking-history.php" method="POST" style="display: inline;">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                    <button class="btn" type="submit">
                        <i class="fas fa-list"></i>
                        View History
                    </button>
                </form>
            </div>

            <div class="action-card loading" style="animation-delay: 0.3s;">
                <div class="action-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h3>Profile Settings</h3>
                <p>Update your personal information, contact details, and account preferences.</p>
                <form action="profile-details.php" method="POST" style="display: inline;">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                    <button class="btn" type="submit">
                        <i class="fas fa-edit"></i>
                        Edit Profile
                    </button>
                </form>
            </div>
        </div>

        <!-- Back Button -->
        <div class="back-section">
            <a href="get-booked-seats.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // Add smooth loading animation
        document.addEventListener('DOMContentLoaded', function() {
            const loadingElements = document.querySelectorAll('.loading');
            loadingElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                }, index * 100);
            });
        });

        // Add click feedback for buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>
</body>
</html>
