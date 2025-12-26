<?php
require_once 'includes/functions.php';

$student = null;
$error = '';
// Handle Re-Register Request
if (isset($_POST['re_register']) && isset($_POST['student_id'])) {
        $conn = getDBConnection();
    $studentId = (int) $_POST['student_id'];

$stmt = $conn->prepare("DELETE FROM students WHERE id = :id");
if ($stmt->execute(['id' => $studentId])) {
    header("Location: student-register.php");
    exit();
}else {
        $error = "Failed to delete existing record. Please try again.";
    }
}


// Handle status check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            }
            // If credentials are correct, show status regardless of approval status
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Status - Library System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="main-navbar">
        <div class="nav-container">
            <div class="nav-brand">

                <span class="nav-brand-text">District Library,Anantapur </span>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="index.html" class="nav-link">Home</a>
                <a href="student-login.php" class="nav-link">Public Login</a>
                <a href="student-register.php" class="nav-link">Register</a>
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
                    <h1>Check Registration Status</h1>
                    <p class="college-subtitle">Secure status verification</p>
                </div>
            </div>
        </header>
        
        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; text-align: center; border: 1px solid #f5c6cb;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($student): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); padding: 2rem; border-radius: var(--border-radius-lg); margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-primary); text-align: center;">Registration Status</h3>
                    
                    <div style="display: grid; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light);">
                            <strong>Name:</strong>
                            <span><?php echo htmlspecialchars($student['student_name']); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light);">
                            <strong>Roll Number/AADHAR number:</strong>
                            <span><?php echo htmlspecialchars($student['roll_number']); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light);">
                            <strong>Course:</strong>
                            <span><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light);">
                            <strong>Email:</strong>
                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light);">
                            <strong>Phone:</strong>
                            <span><?php echo htmlspecialchars($student['phone']); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light);">
                            <strong>Registration Date:</strong>
                            <span><?php echo date('d M Y, h:i A', strtotime($student['registration_date'])); ?></span>
                        </div>
                        
                        <?php if (!empty($student['updated_at']) && $student['updated_at'] !== $student['registration_date']): ?>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light);">
                            <strong>Last Updated:</strong>
                            <span><?php echo date('d M Y, h:i A', strtotime($student['updated_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light);">
                            <strong>Status:</strong>
                            <span style="
                                padding: 0.25rem 0.75rem; 
                                border-radius: 20px; 
                                font-weight: 600; 
                                font-size: 0.85rem;
                                <?php 
                                switch($student['status']) {
                                    case 'approved': echo 'background: #d4edda; color: #155724;'; break;
                                    case 'rejected': echo 'background: #f8d7da; color: #721c24;'; break;
                                    case 'pending': echo 'background: #fff3cd; color: #856404;'; break;
                                }
                                ?>
                            ">
                                <?php echo strtoupper($student['status']); ?>
                            </span>
                        </div>
                        
                        <?php if ($student['status'] === 'rejected' && !empty($student['issue']) && $student['issue'] !== 'No issue'): ?>
                            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; border: 1px solid #f5c6cb; margin-top: 1rem;">
                                <strong>🚫 Rejection Reason:</strong><br>
                                <?php echo htmlspecialchars($student['issue']); ?>
                                <br><br>
                                <small><strong>Next Steps:</strong> Please contact the librarian to resolve the issues mentioned above, or register again with correct documents.</small>
                                <br><br>
<form method="POST" onsubmit="return confirm('Are you sure you want to re-register? Your current data will be deleted.')">
    <input type="hidden" name="re_register" value="1">
    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
    <button type="submit" class="btn btn-danger" style="padding: 8px 16px; background-color: #dc2626; color: white; border: none; border-radius: 5px;">
        Re-Register
    </button>
</form>

                            </div>
                        <?php endif; ?>
                        
                        <?php if ($student['status'] === 'pending'): ?>
                            <div style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 5px; border: 1px solid #ffeaa7; margin-top: 1rem;">
                                <strong>⏳ Status:</strong> Your registration is under review by the librarian. Please wait for approval.
                                <br><br>
                                <small><strong>Estimated Time:</strong> Registration reviews are typically completed within 1-2 business days.</small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($student['status'] === 'approved'): ?>
                            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; border: 1px solid #c3e6cb; margin-top: 1rem;">
                                <strong>✅ Congratulations!</strong> Your registration has been approved. You can now login and book library seats.
                                <br><br>
                                <a href="student-login.php" class="btn btn-success" style="margin-top: 10px; display: inline-block; padding: 8px 16px; background: #059669; color: white; text-decoration: none; border-radius: 5px;">Login Now</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="statusForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="rollNumber">Roll Number/AADHAR Number</label>
                    <input type="text" id="rollNumber" name="rollNumber" value="<?php echo htmlspecialchars($_POST['rollNumber'] ?? ''); ?>" placeholder="Enter your roll number/AADHAR Number" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Check Status</button>
                    <a href="student-login.php" class="btn btn-secondary">Back to Login</a>
                    <a href="student-register.php" class="btn btn-secondary">Register New Account</a>
                </div>
            </form>
            
            <!-- Security Notice -->
            <div style="margin-top: 2rem; padding: 1rem; background: var(--bg-secondary); border-radius: var(--border-radius); border: 1px solid var(--border-light);">
                <h4 style="margin-bottom: 1rem; color: var(--text-primary);">🔒 Secure Status Check</h4>
                <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                    For your security, both roll number/AADHAR and password are required to view your registration status and personal information.
                </p>
                <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary); line-height: 1.6;">
                    <li><strong style="color: #d97706;">Pending:</strong> Your registration is under review by the librarian</li>
                    <li><strong style="color: #059669;">Approved:</strong> You can login and book seats</li>
                    <li><strong style="color: #dc2626;">Rejected:</strong> View the specific rejection reason and next steps</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="navigation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('rollNumber').focus();
        });
        
        // Form validation
        document.getElementById('statusForm').addEventListener('submit', function(e) {
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
