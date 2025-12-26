<?php
// This script should be run via cron job every 5-10 minutes
// Example cron job: */10 * * * * /usr/bin/php /path/to/your/project/auto-cancel-late-bookings.php

require_once 'includes/functions.php';

// Log file for tracking auto-cancellations
$logFile = 'logs/auto-cancel.log';

// Ensure logs directory exists
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

try {
    $cancelledCount = autoCancelLateBookings();
    
    $logMessage = date('Y-m-d H:i:s') . " - Auto-cancelled {$cancelledCount} late bookings\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    if ($cancelledCount > 0) {
        echo "Auto-cancelled {$cancelledCount} late bookings at " . date('Y-m-d H:i:s') . "\n";
    }
    
} catch (Exception $e) {
    $errorMessage = date('Y-m-d H:i:s') . " - Error in auto-cancel: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
    echo "Error: " . $e->getMessage() . "\n";
}
?>
