<?php
require_once 'includes/functions.php';

echo "<h1>Student Database Test</h1>";

try {
    // Test database connection
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Get all students
    $students = getAllStudents();
    
    if (empty($students)) {
        echo "<p style='color: orange;'>⚠ No students found in database.</p>";
        echo "<p><a href='student-register.php'>Register a new student</a></p>";
    } else {
        echo "<h2>Registered Students (" . count($students) . ")</h2>";
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>
                <th>ID</th>
                <th>Name</th>
                <th>Roll Number</th>
                <th>Course</th>
                <th>Email</th>
                <th>Status</th>
                <th>Issue/Reason</th>
                <th>Registration Date</th>
                <th>Actions</th>
              </tr>";
        
        foreach ($students as $student) {
            $statusColor = '';
            switch ($student['status']) {
                case 'approved': $statusColor = 'color: green;'; break;
                case 'rejected': $statusColor = 'color: red;'; break;
                case 'pending': $statusColor = 'color: orange;'; break;
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['student_name']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($student['roll_number']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($student['course'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td style='$statusColor'><strong>" . strtoupper($student['status']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($student['issue'] ?? 'No issue') . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($student['registration_date'])) . "</td>";
            echo "<td>";
            if ($student['status'] === 'pending') {
                echo "<a href='?approve=" . $student['id'] . "' style='color: green; margin-right: 10px;'>Approve</a>";
                echo "<a href='javascript:void(0)' onclick='rejectStudent(" . $student['id'] . ")' style='color: red;'>Reject</a>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Handle approve action
    if (isset($_GET['approve'])) {
        $studentId = (int)$_GET['approve'];
        if (updateStudentStatus($studentId, 'approved', 'No issue')) {
            echo "<script>alert('Student approved successfully!'); window.location.href='test-students.php';</script>";
        }
    }
    
    // Handle reject action with reason
    if (isset($_POST['reject_student'])) {
        $studentId = (int)$_POST['student_id'];
        $reason = sanitizeInput($_POST['rejection_reason']);
        if (empty($reason)) {
            $reason = 'No specific reason provided';
        }
        if (updateStudentStatus($studentId, 'rejected', $reason)) {
            echo "<script>alert('Student rejected successfully!'); window.location.href='test-students.php';</script>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?>

<!-- Rejection Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 400px;">
        <h3>Reject Student Registration</h3>
        <form method="POST">
            <input type="hidden" id="rejectStudentId" name="student_id">
            <div style="margin: 1rem 0;">
                <label for="rejectionReason">Reason for Rejection:</label><br>
                <textarea id="rejectionReason" name="rejection_reason" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;" placeholder="Enter reason for rejection..."></textarea>
            </div>
            <div style="text-align: right;">
                <button type="button" onclick="closeRejectModal()" style="margin-right: 10px; padding: 8px 16px; background: #ccc; border: none; border-radius: 5px;">Cancel</button>
                <button type="submit" name="reject_student" style="padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 5px;">Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function rejectStudent(studentId) {
    document.getElementById('rejectStudentId').value = studentId;
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejectionReason').value = '';
}

// Close modal when clicking outside
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>

<hr>
<h2>Quick Actions</h2>
<ul>
    <li><a href="student-register.php">Register New Student</a></li>
    <li><a href="student-login.php">Student Login</a></li>
    <li><a href="check-status.php">Check Status</a></li>
    <li><a href="setup.php">Database Setup</a></li>
    <li><a href="diagnostic.php">System Diagnostic</a></li>
    <li><a href="update_database_schema.php">Update Database Schema</a></li>
</ul>

<hr>
<h2>Test Login Credentials</h2>
<p>Use the roll numbers and passwords from the table above to test login.</p>
<p><strong>Note:</strong> Only students with "APPROVED" status can login successfully.</p>
