<?php
// Database setup script
require_once 'config/database.php';

echo "<h2>Library Management System - Database Setup</h2>";

try {
    // Test connection
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Check if tables exist
    $tables = ['students', 'seat_bookings', 'librarians', 'contact_submissions'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            $existingTables[] = $table;
        }
    }
    
    if (count($existingTables) === count($tables)) {
        echo "<p style='color: green;'>✓ All required tables exist!</p>";
        
        // Show table statistics
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p>• $table: $count records</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Some tables are missing. Please run the SQL script from database/create_database.sql</p>";
        echo "<p>Missing tables: " . implode(', ', array_diff($tables, $existingTables)) . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>Quick Links:</h3>";
    echo "<ul>";
    echo "<li><a href='student-register.php'>Student Registration</a></li>";
    echo "<li><a href='student-login.php'>Student Login</a></li>";
    echo "<li><a href='index.html'>Home Page</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}
?>
