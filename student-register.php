<?php
require_once 'includes/functions.php';

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid form submission. Please try again.";
    } else {
        // Sanitize and validate input
        $studentName = sanitizeInput($_POST['studentName'] ?? '');
        $rollNumber = sanitizeInput($_POST['rollNumber'] ?? '');
$course = null;

        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        
        // Validation
        if (strlen($studentName) < 2) {
            $errors[] = "Name must be at least 2 characters long.";
        }
        
        if (strlen($rollNumber) < 3) {
            $errors[] = "Roll number must be at least 3 characters long.";
        }
        
        if (rollNumberExists($rollNumber)) {
            $errors[] = "Roll number already exists.";
        }
        

        
        if (!validateEmail($email)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        if (emailExists($email)) {
            $errors[] = "Email address already exists.";
        }
        
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            $errors[] = "Please enter a valid 10-digit phone number.";
        }
        
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }
        
        // Handle file uploads with compression
        $passportPhoto = null;
        $idCardPhoto = null;
        
        if (isset($_FILES['passportPhoto'])) {
            $result = validateAndProcessImage($_FILES['passportPhoto']);
            if ($result['success']) {
                $passportPhoto = $result['data'];
            } else {
                $errors[] = "Passport photo: " . $result['error'];
            }
        } else {
            $errors[] = "Please upload a passport size photo.";
        }
        
        if (isset($_FILES['idCard'])) {
            $result = validateAndProcessImage($_FILES['idCard']);
            if ($result['success']) {
                $idCardPhoto = $result['data'];
            } else {
                $errors[] = "ID card photo: " . $result['error'];
            }
        } else {
            $errors[] = "Please upload an identity card photo.";
        }
        
        // If no errors, save to database
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO students (student_name, roll_number, course, email, phone, password, passport_photo, id_card_photo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $hashedPassword = hashPassword($password);
                $result = $stmt->execute([
                    $studentName, $rollNumber, $course, $email, $phone, 
                    $hashedPassword, $passportPhoto, $idCardPhoto
                ]);
                
                if ($result) {
                    $success = true;
                    // Clear form data
                    $_POST = [];
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            } catch (Exception $e) {
                $errors[] = "Database error: " . $e->getMessage();
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
    <title>Public Registration -District Library,Anantapur </title>
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
                    <h1>Public Registration</h1>
                    <p class="college-subtitle">District Library,Anantapur </p>
                </div>
            </div>
            <p class="system-description">Register for Library Access - Select Your Course</p>
        </header>
        
        <div class="form-container">
            <?php if ($success): ?>
                <div class="success-message" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; text-align: center;">
                    <strong>Registration Successful!</strong><br>
                    Your documents have been submitted for librarian approval. Please wait for approval before logging in.
                    <br><br>
                    <a href="student-login.php" class="btn btn-primary" style="margin-top: 10px;">Go to Login</a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <strong>Please fix the following errors:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="registrationForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="studentName">Name *</label>
                    <input type="text" id="studentName" name="studentName" value="<?php echo htmlspecialchars($_POST['studentName'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="rollNumber">Roll Number/AADHAR Nmuber *</label>
                    <input type="text" id="rollNumber" name="rollNumber" value="<?php echo htmlspecialchars($_POST['rollNumber'] ?? ''); ?>" required>
                </div>
                
 
                
                <div class="form-group">
                    <label for="passportPhoto">Passport Size Photo *</label>
                    <input type="file" id="passportPhoto" name="passportPhoto" accept="image/*" required>
                    <small style="color: var(--text-muted); font-size: 0.85rem;">
                        Max file size: 1MB. 
                        <?php if (function_exists('imagecreatefromstring')): ?>
                            Images will be automatically compressed and resized.
                        <?php else: ?>
                            <strong>Note:</strong> GD extension not available - please use smaller images.
                        <?php endif; ?>
                        Supported formats: JPG, PNG, GIF
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="idCard">Identity Card Photo/AADHAR Photo *</label>
                    <input type="file" id="idCard" name="idCard" accept="image/*" required>
                    <small style="color: var(--text-muted); font-size: 0.85rem;">
                        Max file size: 1MB. 
                        <?php if (function_exists('imagecreatefromstring')): ?>
                            Images will be automatically compressed and resized.
                        <?php else: ?>
                            <strong>Note:</strong> GD extension not available - please use smaller images.
                        <?php endif; ?>
                        Supported formats: JPG, PNG, GIF
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email ID *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" pattern="[0-9]{10}" required>
                    <small style="color: var(--text-muted); font-size: 0.85rem;">Enter 10-digit mobile number</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" minlength="6" required>
                    <small style="color: var(--text-muted); font-size: 0.85rem;">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password *</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" minlength="6" required>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Register</button>
                    <a href="student-login.php" class="btn btn-secondary">Already have an account?</a>
                    <a href="index.html" class="btn btn-secondary">Back to Home</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="navigation.js"></script>
    <script>
        // Client-side validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            // Check file sizes
            const passportPhoto = document.getElementById('passportPhoto').files[0];
            const idCard = document.getElementById('idCard').files[0];
            
            if (passportPhoto && passportPhoto.size > 2 * 1024 * 1024) {
                e.preventDefault();
                alert('Passport photo file size must be less than 2MB');
                return false;
            }
            
            if (idCard && idCard.size > 2 * 1024 * 1024) {
                e.preventDefault();
                alert('ID card photo file size must be less than 2MB');
                return false;
            }
        });
        
        // Phone number validation
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    </script>
</body>
</html>
