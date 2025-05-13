<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Include functions
require_once 'functions.php';

// Initialize variables
$email = isset($_GET['email']) ? filter_var(trim($_GET['email']), FILTER_SANITIZE_EMAIL) : '';
$status = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['init_unsubscribe'])) {
        // Step 1: Generate and send verification code
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Generate verification code
            $code = generateVerificationCode();
            $_SESSION['verification_code'] = $code;
            $_SESSION['verification_email'] = $email;

            // Send verification code via email
            $subject = 'Confirm Unsubscription';
            $message = "<p>To confirm your unsubscription, please use this code: <strong>$code</strong></p>";
            
            if (sendEmail($email, $subject, $message)) {
                $status = 'A verification code has been sent to your email. Please enter it below to confirm unsubscription.';
            } else {
                $status = 'Failed to send verification code. Please try again.';
            }
        } else {
            $status = 'Invalid email address.';
        }
    } elseif (isset($_POST['verify_code'])) {
        // Step 2: Verify the code and unsubscribe
        $code = trim($_POST['code']);
        $email = $_SESSION['verification_email'] ?? '';
        
        if (verifyCode($email, $code)) {
            if (unsubscribeEmail($email)) {
                $status = 'Successfully unsubscribed!';
                // Clear session variables
                unset($_SESSION['verification_code']);
                unset($_SESSION['verification_email']);
            } else {
                $status = 'Error unsubscribing or email not found.';
            }
        } else {
            $status = 'Invalid verification code.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe from XKCD Comics</title>
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
        label {
            display: block;
            margin-bottom: 5px;
        }
        input {
            padding: 8px;
            margin-bottom: 10px;
            width: 100%;
            max-width: 300px;
            box-sizing: border-box;
        }
        button {
            padding: 8px 15px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #c0392b;
        }
        .status {
            padding: 10px;
            margin-bottom: 15px;
            background-color: #f0f0f0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Unsubscribe from XKCD Comics</h1>

    <?php if (!empty($status)): ?>
        <div class="status">
            <?php echo htmlspecialchars($status); ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['verification_code']) || $status === 'Invalid verification code.' || $status === 'Error unsubscribing or email not found.'): ?>
        <!-- Form to initiate unsubscription -->
        <form method="POST" action="">
            <label for="email">Email Address:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <button type="submit" name="init_unsubscribe">Request Unsubscribe</button>
        </form>
    <?php endif; ?>

    <?php if (isset($_SESSION['verification_code']) && $status !== 'Successfully unsubscribed!' && $status !== 'Invalid verification code.' && $status !== 'Error unsubscribing or email not found.'): ?>
        <!-- Form to enter verification code -->
        <form method="POST" action="">
            <label for="code">Verification Code:</label>
            <input type="text" name="code" id="code" maxlength="6" required>
            <button type="submit" name="verify_code">Verify and Unsubscribe</button>
        </form>
    <?php endif; ?>
</body>
</html>