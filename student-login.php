<?php
require_once 'includes/functions.php';

$error = '';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('seatb/get-booked-seats.php');
}
if (isset($_POST['re_register']) && isset($_POST['student_id'])) {
    $conn = getDBConnection(); // Ensure this function returns a PDO connection
    $studentId = (int) $_POST['student_id'];

    $stmt = $conn->prepare("DELETE FROM students WHERE id = :id");
    if ($stmt->execute(['id' => $studentId])) {
        header("Location: student-register.php");
        exit();
    } else {
        $error = "Failed to delete existing record. Please try again.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $rollNumber = sanitizeInput($_POST['rollNumber'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($rollNumber) || empty($password)) {
            $error = "Please enter both roll number and password.";
        } else {
            $student = getStudentByRollNumber($rollNumber);
            
            if (!$student) {
                $error = "Invalid roll number or password.";
            } elseif (!verifyPassword($password, $student['password'])) {
                $error = "Invalid roll number or password.";
            } elseif ($student['status'] === 'pending') {
                $error = "Your registration is pending approval from the librarian. Please wait for approval.";
            } elseif ($student['status'] === 'rejected') {
                $rejectionReason = (!empty($student['issue']) && $student['issue'] !== 'No issue') 
                    ? $student['issue'] 
                    : "Please contact the librarian for more information.";
                $error = "Your registration has been rejected. Reason: " . $rejectionReason;
                    $showReRegisterForm = true;
    $rejectedStudentId = $student['id'];
            } else {
                // Login successful
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['student_name'];
                $_SESSION['roll_number'] = $student['roll_number'];
                $_SESSION['student_email'] = $student['email'];
                $_SESSION['student_course'] = $student['course'];
                
                redirect('seatb/get-booked-seats.php');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Library System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="main-navbar">
        <div class="nav-container">
            <div class="nav-brand">

                <span class="nav-brand-text">District Library,Anantapur</span>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="index.html" class="nav-link">Home</a>
                <a href="about-us.html" class="nav-link">About Us</a>

                <a href="gallery.html" class="nav-link">Gallery</a>
                <a href="contact-us.html" class="nav-link">Contact Us</a>
                <a href="library-system.html" class="nav-link nav-highlight">Library System</a>
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
            <div class="college-branding">

                <div class="college-info">
                    <h1>Public Login</h1>
                    <p class="college-subtitle">Access your library account</p>
                </div>
            </div>
        </header>
        
        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; text-align: center; border: 1px solid #f5c6cb;">
                    <strong>Login Failed:</strong><br>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        <?php if (!empty($showReRegisterForm) && isset($rejectedStudentId)): ?>
    <form method="POST" onsubmit="return confirm('Are you sure you want to re-register? Your current data will be deleted.')">
        <input type="hidden" name="re_register" value="1">
        <input type="hidden" name="student_id" value="<?php echo $rejectedStudentId; ?>">
        <button type="submit" class="btn btn-danger" style="margin-top: 1rem; padding: 8px 16px; background-color: #dc2626; color: white; border: none; border-radius: 5px;">
            Re-Register
        </button>
    </form>
<?php endif; ?>

            
            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="rollNumber">Roll Number/AADHAR Number</label>
                    <input type="text" id="rollNumber" name="rollNumber" value="<?php echo htmlspecialchars($_POST['rollNumber'] ?? ''); ?>" placeholder="Enter your roll number" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <div style="text-align: right; margin-bottom: 1rem;">
    <a href="forgot-password.php" style="color: #2563eb; text-decoration: none;">Forgot Password?</a>
</div>

                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                    <a href="check-status.php" class="btn btn-secondary">🔍 Check Status</a>
                    <a href="student-register.php" class="btn btn-secondary">Register New Account</a>
                    <a href="index.html" class="btn btn-secondary">Back to Home</a>
                </div>
            </form>
            
            <!-- Status Information -->
            <div style="margin-top: 2rem; padding: 1rem; background: var(--bg-secondary); border-radius: var(--border-radius); border: 1px solid var(--border-light);">
                <h4 style="margin-bottom: 1rem; color: var(--text-primary);">Account Status Information:</h4>
                <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary); line-height: 1.6;">
                    <li><strong style="color: #d97706;">Pending:</strong> Your registration is under review by the librarian</li>
                    <li><strong style="color: #059669;">Approved:</strong> You can login and book seats</li>
                    <li><strong style="color: #dc2626;">Rejected:</strong> Check the rejection reason and contact the librarian</li>
                </ul>
                <div style="margin-top: 1rem; padding: 0.75rem; background: #e8f4fd; border-radius: 5px; border: 1px solid #bee5eb;">
                    <strong>🔍 Status Check:</strong> Use the "Check Status" button to view your detailed registration status, including any rejection reasons, using your roll number and password.
                </div>
            </div>
        </div>
    </div>
    
    <script src="navigation.js"></script>
    <script>
        // Clear form on page load if there was an error
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on roll number field
            document.getElementById('rollNumber').focus();
        });
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const rollNumber = document.getElementById('rollNumber').value.trim();
            const password = document.getElementById('password').value;
            
            if (!rollNumber) {
                e.preventDefault();
                alert('Please enter your roll number');
                document.getElementById('rollNumber').focus();
                return false;
            }
            
            if (!password) {
                e.preventDefault();
                alert('Please enter your password');
                document.getElementById('password').focus();
                return false;
            }
        });
    </script>
</body>
</html>
