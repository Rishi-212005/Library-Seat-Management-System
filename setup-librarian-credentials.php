<?php
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Setup Librarian Credentials</title>";
echo "<link rel='stylesheet' href='styles.css'></head><body>";
echo "<div class='container'>";
echo "<h2>🔧 Setup Librarian Credentials</h2>";

try {
    $pdo = getDBConnection();
    
    // Create librarians table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS librarians (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )
    ";
    
    $pdo->exec($createTableSQL);
    echo "<p style='color: green;'>✅ Librarians table created/verified successfully!</p>";
    
    // Insert/Update librarian credentials
    $username = 'USERNAME@2025';
    $password = 'PASSWORD@2025';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $fullName = 'System Librarian';
    $email = 'librarian@srsgovtcollege.edu.in';
    
    // Check if librarian already exists
    $checkStmt = $pdo->prepare("SELECT id FROM librarians WHERE username = ?");
    $checkStmt->execute([$username]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        // Update existing librarian
        $updateStmt = $pdo->prepare("
            UPDATE librarians 
            SET password = ?, full_name = ?, email = ?, status = 'active', updated_at = CURRENT_TIMESTAMP 
            WHERE username = ?
        ");
        $result = $updateStmt->execute([$hashedPassword, $fullName, $email, $username]);
        
        if ($result) {
            echo "<p style='color: blue;'>🔄 Librarian credentials updated successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update librarian credentials.</p>";
        }
    } else {
        // Insert new librarian
        $insertStmt = $pdo->prepare("
            INSERT INTO librarians (username, password, full_name, email, status) 
            VALUES (?, ?, ?, ?, 'active')
        ");
        $result = $insertStmt->execute([$username, $hashedPassword, $fullName, $email]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Librarian credentials created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create librarian credentials.</p>";
        }
    }
    
    // Display credentials
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid #bee5eb;'>";
    echo "<h3>🔐 Librarian Login Credentials</h3>";
    echo "<p><strong>Username:</strong> <code style='background: #f8f9fa; padding: 4px 8px; border-radius: 4px; color: #e83e8c;'>$username</code></p>";
    echo "<p><strong>Password:</strong> <code style='background: #f8f9fa; padding: 4px 8px; border-radius: 4px; color: #e83e8c;'>$password</code></p>";
    echo "<p style='color: #856404; margin-top: 15px;'><strong>⚠️ Important:</strong> Password is securely hashed in the database using PHP's password_hash() function.</p>";
    echo "</div>";
    
    // Test the credentials
    echo "<h3>🧪 Test Credentials</h3>";
    $testStmt = $pdo->prepare("SELECT username, full_name, email, status, created_at, last_login FROM librarians WHERE username = ?");
    $testStmt->execute([$username]);
    $testLibrarian = $testStmt->fetch();
    
    if ($testLibrarian) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb;'>";
        echo "<h4>✅ Credentials Test Result:</h4>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($testLibrarian['username']) . "</p>";
        echo "<p><strong>Full Name:</strong> " . htmlspecialchars($testLibrarian['full_name']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($testLibrarian['email']) . "</p>";
        echo "<p><strong>Status:</strong> " . htmlspecialchars($testLibrarian['status']) . "</p>";
        echo "<p><strong>Created:</strong> " . htmlspecialchars($testLibrarian['created_at']) . "</p>";
        echo "<p><strong>Last Login:</strong> " . ($testLibrarian['last_login'] ? htmlspecialchars($testLibrarian['last_login']) : 'Never') . "</p>";
        echo "</div>";
        
        // Test password verification
        if (password_verify($password, $hashedPassword)) {
            echo "<p style='color: green; font-weight: bold;'>✅ Password verification test: PASSED</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Password verification test: FAILED</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Could not retrieve test credentials from database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>🔗 Quick Links:</h3>";
echo "<ul>";
echo "<li><a href='librarian-login-new.html'>🔑 Test Librarian Login</a></li>";
echo "<li><a href='test-students.php'>👥 View Students</a></li>";
echo "<li><a href='setup.php'>⚙️ Database Setup</a></li>";
echo "<li><a href='diagnostic.php'>🔍 System Diagnostic</a></li>";
echo "</ul>";

echo "</div></body></html>";
?>
