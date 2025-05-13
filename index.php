<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Debug log
$debug_log = "Script started at " . date('Y-m-d H:i:s') . "\n";
$debug_log .= "Session started, ID: " . session_id() . "\n";

// Include functions
require_once 'functions.php';

$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_log .= "POST request received\n";
    $debug_log .= "POST data: " . print_r($_POST, true) . "\n";
    
    // Check if email submission
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $debug_log .= "Processing email submission\n";
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $debug_log .= "Email entered: $email\n";
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $code = generateVerificationCode();
            $debug_log .= "Generated Code: $code\n";
            $_SESSION['verification_code'] = $code;
            $_SESSION['verification_email'] = $email;
            $debug_log .= "Session Email: " . ($_SESSION['verification_email'] ?? 'not set') . "\n";
            $debug_log .= "Session Code: " . ($_SESSION['verification_code'] ?? 'not set') . "\n";
            
            // Try sending the email
            if (sendVerificationEmail($email, $code)) {
                $status = 'Verification code sent to your email.';
                $debug_log .= "Email sent successfully.\n";
            } else {
                $status = 'Failed to send verification code.';
                $debug_log .= "Failed to send email.\n";
                $debug_log .= "Mail error: " . (error_get_last()['message'] ?? 'No error message available') . "\n";
            }
        } else {
            $status = 'Invalid email address.';
            $debug_log .= "Invalid email: $email\n";
        }
    } 
    // Check if verification code submission
    elseif (isset($_POST['verification_code']) && !empty($_POST['verification_code'])) {
        $debug_log .= "Processing verification code submission\n";
        $code = trim($_POST['verification_code']);
        $email = $_SESSION['verification_email'] ?? '';
        $debug_log .= "Verifying Code: $code for Email: $email\n";
        $debug_log .= "Session Code: " . ($_SESSION['verification_code'] ?? 'not set') . "\n";
        $debug_log .= "Session Email: " . ($_SESSION['verification_email'] ?? 'not set') . "\n";
        
        if (verifyCode($email, $code)) {
            if (registerEmail($email)) {
                $status = 'Email registered successfully!';
                unset($_SESSION['verification_code']);
                unset($_SESSION['verification_email']);
                $debug_log .= "Email registered successfully.\n";
            } else {
                $status = 'Email already registered or error occurred.';
                $debug_log .= "Registration failed.\n";
            }
        } else {
            $status = 'Invalid verification code.';
            $debug_log .= "Verification failed.\n";
        }
    } else {
        $debug_log .= "No email or verification code in POST data\n";
    }
} else {
    $debug_log .= "Not a POST request, displaying form\n";
    $debug_log .= "Request method: " . $_SERVER['REQUEST_METHOD'] . "\n";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XKCD Comic Subscription</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        form {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        input {
            padding: 8px;
            margin-right: 10px;
        }
        button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .status {
            padding: 10px;
            margin-bottom: 15px;
            background-color: #f0f0f0;
            border-radius: 4px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            overflow: auto;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>XKCD Comic Subscription</h1>
    
    <?php if (!empty($status)): ?>
    <div class="status">
        <?php echo htmlspecialchars($status); ?>
    </div>
    <?php endif; ?>
    
    <?php if (!isset($_SESSION['verification_code']) || $status === 'Invalid verification code.' || $status === 'Email already registered or error occurred.'): ?>
        <!-- Email submission form -->
        <form method="POST" action="">
            <label for="email">Enter your email:</label>
            <input type="email" name="email" id="email" required>
            <button type="submit" id="submit-email">Submit</button>
        </form>
    <?php endif; ?>

    <?php if (isset($_SESSION['verification_code']) && $status !== 'Email registered successfully!' && $status !== 'Invalid verification code.' && $status !== 'Email already registered or error occurred.'): ?>
        <!-- Verification code form -->
        <form method="POST" action="">
            <label for="verification_code">Enter verification code:</label>
            <input type="text" name="verification_code" id="verification_code" maxlength="6" required>
            <button type="submit" id="submit-verification">Verify</button>
        </form>
    <?php endif; ?>

    <h2>Debug Log</h2>
    <pre><?php echo htmlspecialchars($debug_log); ?></pre>
</body>
</html>