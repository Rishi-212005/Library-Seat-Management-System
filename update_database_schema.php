<?php
require_once 'config/database.php';

echo "<h2>Database Schema Update - Add Issue Column</h2>";

try {
    $pdo = getDBConnection();
    
    // Check if issue column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'issue'");
    if ($stmt->fetch()) {
        echo "<p style='color: orange;'>⚠ Issue column already exists!</p>";
    } else {
        // Add the issue column
        echo "<p>Adding 'issue' column to students table...</p>";
        $pdo->exec("ALTER TABLE students ADD COLUMN issue TEXT DEFAULT 'No issue' AFTER status");
        
        // Update existing records
        echo "<p>Updating existing records...</p>";
        $pdo->exec("UPDATE students SET issue = 'No issue' WHERE status = 'approved'");
        $pdo->exec("UPDATE students SET issue = 'No issue' WHERE status = 'pending'");
        
        echo "<p style='color: green;'>✓ Issue column added successfully!</p>";
    }
    
    // Show current table structure
    echo "<h3>Current Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE students");
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>Quick Links:</h3>
<ul>
    <li><a href="test-students.php">View Students</a></li>
    <li><a href="student-login.php">Student Login</a></li>
    <li><a href="setup.php">Database Setup</a></li>
</ul>
