<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set a time limit to prevent script hanging
set_time_limit(30);

// Create a log file to track cron execution
$log_file = __DIR__ . '/cron_execution.log';
$log_message = date('Y-m-d H:i:s') . " - CRON job started (PID: " . getmypid() . ")\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

// Include the functions file
require_once __DIR__ . '/functions.php';

try {
    // Execute the function to send XKCD updates
    sendXKCDUpdatesToSubscribers();
    $log_message = date('Y-m-d H:i:s') . " - XKCD updates sent successfully\n";
} catch (Exception $e) {
    $log_message = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
}

file_put_contents($log_file, $log_message, FILE_APPEND);

// Ensure the script exits cleanly
exit(0);
?>