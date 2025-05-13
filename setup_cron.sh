#!/bin/bash
# This script sets up a CRON job to run cron.php every minute.

# Make the script executable
chmod +x "$0"

# Get the absolute path to the script's directory using BASH_SOURCE
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CRON_FILE="$SCRIPT_DIR/cron.php"

# Create empty registered_emails.txt if it doesn't exist
touch "$SCRIPT_DIR/registered_emails.txt"
chmod 666 "$SCRIPT_DIR/registered_emails.txt"

# Ensure cron_execution.log exists and is writable
touch "$SCRIPT_DIR/cron_execution.log"
chmod 666 "$SCRIPT_DIR/cron_execution.log"

# Define the CRON schedule (every minute)
CRON_SCHEDULE="* * * * *"

# Full command with PHP path
PHP_PATH=$(which php)
if [ -z "$PHP_PATH" ]; then
    echo "PHP not found. Please make sure PHP is installed and in your PATH."
    exit 1
fi

CRON_COMMAND="$CRON_SCHEDULE $PHP_PATH $CRON_FILE >> $SCRIPT_DIR/cron_execution.log 2>&1"

# Add CRON job (remove any existing entries for this file first)
(crontab -l 2>/dev/null | grep -v "$CRON_FILE"; echo "$CRON_COMMAND") | crontab -

# Verify CRON job was added
echo "Verifying CRON job installation..."
crontab -l | grep "$CRON_FILE" || { echo "Failed to add CRON job. Check crontab permissions."; exit 1; }

echo "CRON job successfully added to run $CRON_FILE every minute."
echo "Command: $CRON_COMMAND"

# Check if CRON daemon is running
if ! ps aux | grep -v grep | grep cron > /dev/null; then
    echo "CRON daemon not running. Attempting to start it..."
    # On macOS, enable CRON by ensuring the com.apple.cron plist is loaded
    sudo launchctl load -w /System/Library/LaunchDaemons/com.apple.cron.plist || {
        echo "Failed to start CRON daemon. Please start it manually using 'sudo launchctl load -w /System/Library/LaunchDaemons/com.apple.cron.plist' or check system settings."
        exit 1;
    }
    # Give it a moment to start
    sleep 2
    if ! ps aux | grep -v grep | grep cron > /dev/null; then
        echo "CRON daemon still not running. Please enable it manually."
        exit 1
    fi
fi

# For testing purposes - this will execute the script immediately
echo "Running the script now for testing..."
$PHP_PATH $CRON_FILE

echo "Test execution complete. Check cron_execution.log for execution details."