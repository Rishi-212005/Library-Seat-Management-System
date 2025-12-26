<?php
require_once 'config/database.php';

echo "<h2>Database Migration: Class to Course</h2>";

try {
    $pdo = getDBConnection();
    
    // Check if course column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'course'");
    if (!$stmt->fetch()) {
        echo "<p>Adding 'course' column...</p>";
        $pdo->exec("ALTER TABLE students ADD COLUMN course VARCHAR(20) AFTER roll_number");
    }
    
    // Check if class column exists and migrate data
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'class'");
    if ($stmt->fetch()) {
        echo "<p>Migrating data from 'class' to 'course'...</p>";
        
        // Map old class values to new course values
        $classMapping = [
            '1st Year' => 'B.A.',
            '2nd Year' => 'B.A.',
            '3rd Year' => 'B.A.',
            '4th Year' => 'B.A.',
            'Masters' => 'M.A.',
            'PhD' => 'M.Sc.'
        ];
        
        // Update existing records
        $stmt = $pdo->query("SELECT id, class FROM students WHERE course IS NULL OR course = ''");
        $students = $stmt->fetchAll();
        
        foreach ($students as $student) {
            $newCourse = $classMapping[$student['class']] ?? 'B.A.';
            $updateStmt = $pdo->prepare("UPDATE students SET course = ? WHERE id = ?");
            $updateStmt->execute([$newCourse, $student['id']]);
        }
        
        echo "<p>Migrated " . count($students) . " student records.</p>";
        
        // Optionally drop the old class column (uncomment if you want to remove it)
        // $pdo->exec("ALTER TABLE students DROP COLUMN class");
        // echo "<p>Dropped old 'class' column.</p>";
    }
    
    echo "<p style='color: green;'>✓ Migration completed successfully!</p>";
    echo "<p><a href='student-register.php'>Test Registration Form</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Migration failed: " . $e->getMessage() . "</p>";
}
?>
