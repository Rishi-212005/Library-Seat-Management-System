<?php
require_once 'includes/functions.php';
require_once 'includes/email-functions.php';

// Test email configuration
echo "<h2>Testing Email Configuration</h2>";

// Update these with your actual Gmail credentials
$testConfig = [
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => 587,
    'SMTP_USERNAME' => 'your-email@gmail.com', // Replace with your Gmail
    'SMTP_PASSWORD' => 'your-app-password', // Replace with your Gmail App Password
    'SMTP_FROM_EMAIL' => 'your-email@gmail.com', // Replace with your Gmail
];

echo "<h3>Current Configuration:</h3>";
echo "<ul>";
echo "<li>SMTP Host: " . SMTP_HOST . "</li>";
echo "<li>SMTP Port: " . SMTP_PORT . "</li>";
echo "<li>SMTP Username: " . SMTP_USERNAME . "</li>";
echo "<li>SMTP From Email: " . SMTP_FROM_EMAIL . "</li>";
echo "</ul>";

echo "<h3>Testing Email Send...</h3>";

// Test email sending
$result = testEmailConfiguration();

if ($result) {
    echo "<p style='color: green;'><strong>✅ SUCCESS!</strong> Test email sent successfully!</p>";
    echo "<p>Check your inbox at: " . SMTP_USERNAME . "</p>";
} else {
    echo "<p style='color: red;'><strong>❌ FAILED!</strong> Email could not be sent.</p>";
    echo "<p>Please check your Gmail credentials and App Password.</p>";
}

echo "<h3>Setup Instructions:</h3>";
echo "<ol>";
echo "<li>Go to your Google Account settings</li>";
echo "<li>Enable 2-Factor Authentication</li>";
echo "<li>Generate an App Password for 'Mail'</li>";
echo "<li>Update the credentials in includes/email-functions.php</li>";
echo "<li>Make sure 'Less secure app access' is enabled (if needed)</li>";
echo "</ol>";

echo "<h3>Recent Email Logs:</h3>";
$logs = getEmailLogs(10);
if (!empty($logs)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Date</th><th>To</th><th>Subject</th><th>Status</th><th>Error</th></tr>";
    foreach ($logs as $log) {
        echo "<tr>";
        echo "<td>" . $log['created_at'] . "</td>";
        echo "<td>" . htmlspecialchars($log['email_to']) . "</td>";
        echo "<td>" . htmlspecialchars($log['subject']) . "</td>";
        echo "<td style='color: " . ($log['status'] === 'sent' ? 'green' : 'red') . ";'>" . $log['status'] . "</td>";
        echo "<td>" . htmlspecialchars($log['error_message'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No email logs found.</p>";
}
?>
