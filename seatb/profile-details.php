<?php
session_start();

// Database connection
require_once 'config.php';

// Get student_id from POST or session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $_SESSION['student_id'] = $student_id;
} else {
    $student_id = $_SESSION['student_id'] ?? null;
}

if (!$student_id) {
    header('Location: student-login.php');
    exit;
}

// Get student details function
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

// Fetch student data FIRST - this was the missing piece
$student = getStudentById($student_id);

if (!$student) {
    echo "Student not found.";
    exit;
}

// Validate input data function
function validateInput($data) {
    $errors = [];
    
    // Only validate email if it's provided and different from current
    if (!empty(trim($data['email']))) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
    }
    
    // Only validate phone if it's provided and different from current
    if (!empty(trim($data['phone']))) {
        if (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
            $errors[] = "Phone number must be 10-15 digits.";
        }
    }
    
    if (!empty($data['password']) && strlen($data['password']) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    
    if (!empty($data['password']) && $data['password'] !== $data['confirm_password']) {
        $errors[] = "Passwords do not match.";
    }
    
    return $errors;
}

// Update student details function
function updateStudentDetails($id, $data, $currentStudent, $fieldsToUpdate) {
    global $pdo;
    try {
        $updateFields = [];
        $params = [];
        
        // Only update email if it's in fieldsToUpdate and provided
        if (in_array('email', $fieldsToUpdate) && !empty(trim($data['email']))) {
            $updateFields[] = "email = ?";
            $params[] = $data['email'];
        }
        
        // Only update phone if it's in fieldsToUpdate and provided
        if (in_array('phone', $fieldsToUpdate) && !empty(trim($data['phone']))) {
            $updateFields[] = "phone = ?";
            $params[] = $data['phone'];
        }
        
        // Add password update if provided
        if (!empty($data['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // If no fields to update
        if (empty($updateFields)) {
            return ['success' => true, 'message' => 'No changes were made.'];
        }
        
        $sql = "UPDATE students SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return ['success' => true, 'message' => 'Profile updated successfully!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error updating profile: ' . $e->getMessage()];
    }
}

// Initialize message variables
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $formData = [
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    // Get which fields were edited (sent as hidden inputs)
    $fieldsToUpdate = [];
    if (isset($_POST['email_edited']) && $_POST['email_edited'] === '1') {
        $fieldsToUpdate[] = 'email';
    }
    if (isset($_POST['phone_edited']) && $_POST['phone_edited'] === '1') {
        $fieldsToUpdate[] = 'phone';
    }
    
    $errors = validateInput($formData);
    
    if (empty($errors)) {
        $result = updateStudentDetails($student_id, $formData, $student, $fieldsToUpdate);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            // Refresh student data after successful update
            $student = getStudentById($student_id);
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - <?php echo htmlspecialchars($student['student_name']); ?></title>
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
            max-width: 800px;
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
            border: 1px solid #e9ecef;
        }
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .header p {
            color: #666;
            font-size: 16px;
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
        /* Status Messages */
        .status-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /* Profile Form */
        .profile-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 30px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .section-title {
            color: #2c3e50;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-icon {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .form-group {
            position: relative;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
        }
        .form-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .form-input:disabled {
            background: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }
        .edit-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            transition: color 0.3s;
        }
        .edit-toggle:hover {
            color: #0056b3;
        }
        .password-toggle {
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
        }
        .password-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .password-note {
            background: #e3f2fd;
            color: #1565c0;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        /* Buttons */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
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
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
                align-items: center;
            }
            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
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
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <span>></span>
            <a href="student-profile.php"><i class="fas fa-user"></i> Profile</a>
            <span>></span>
            <span>Profile Settings</span>
        </div>

        <!-- Header Section -->
        <div class="header loading">
            <h1>Profile Settings</h1>
            <p>Update your personal information and account details</p>
        </div>

        <!-- Status Messages -->
        <?php if (!empty($message)): ?>
            <div class="status-message <?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Form -->
        <div class="profile-form loading" style="animation-delay: 0.1s;">
            <form method="POST" id="profileForm">
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                <input type="hidden" name="email_edited" id="email_edited" value="0">
                <input type="hidden" name="phone_edited" id="phone_edited" value="0">
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        Personal Information
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="student_name">Full Name</label>
                            <input type="text"
                                   id="student_name"
                                   name="student_name"
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($student['student_name']); ?>"
                                   disabled readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="roll_number">Roll Number</label>
                            <input type="text"
                                   id="roll_number"
                                   name="roll_number"
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($student['roll_number']); ?>"
                                   disabled readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="course">Course</label>
                            <input type="text"
                                   id="course"
                                   name="course"
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($student['course']); ?>"
                                   disabled readonly>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="form-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-address-book"></i>
                        </div>
                        Contact Information
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <div style="position: relative;">
                                <input type="email"
                                       id="email"
                                       name="email"
                                       class="form-input"
                                       value="<?php echo htmlspecialchars($student['email']); ?>"
                                       disabled>
                                <button type="button" class="edit-toggle" onclick="toggleEdit('email')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <div style="position: relative;">
                                <input type="tel"
                                       id="phone"
                                       name="phone"
                                       class="form-input"
                                       value="<?php echo htmlspecialchars($student['phone']); ?>"
                                       disabled>
                                <button type="button" class="edit-toggle" onclick="toggleEdit('phone')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Section -->
                <div class="form-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        Security Settings
                    </div>
                    
                    <div class="password-section">
                        <div class="password-note">
                            <i class="fas fa-info-circle"></i>
                            Leave password fields empty if you don't want to change your password.
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="password">New Password</label>
                                <div style="position: relative;">
                                    <input type="password"
                                           id="password"
                                           name="password"
                                           class="form-input"
                                           placeholder="Enter new password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm New Password</label>
                                <div style="position: relative;">
                                    <input type="password"
                                           id="confirm_password"
                                           name="confirm_password"
                                           class="form-input"
                                           placeholder="Confirm new password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Profile
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i>
                        Reset Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Back Button -->
        <div class="back-section">
            <a href="student-profile.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Profile
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

        // Toggle edit mode for input fields
        function toggleEdit(fieldId) {
            const input = document.getElementById(fieldId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            const editedFlag = document.getElementById(fieldId + '_edited');
            
            if (input.disabled) {
                input.disabled = false;
                input.focus();
                // Keep the edit icon instead of changing to check
                icon.className = 'fas fa-edit';
                button.style.color = '#28a745'; // Green color to indicate it's editable
                if (editedFlag) editedFlag.value = '1';
            } else {
                input.disabled = true;
                icon.className = 'fas fa-edit';
                button.style.color = '#007bff'; // Blue color for normal state
                if (editedFlag) editedFlag.value = '0';
            }
        }

        // Toggle password visibility
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Reset form to original values
        function resetForm() {
            if (confirm('Are you sure you want to reset all changes?')) {
                location.reload();
            }
        }

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password && password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>
