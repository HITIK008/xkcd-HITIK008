<?php
// Removed session_start() - should be called by the entry script

// Include PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateVerificationCode(): string {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send an email using PHPMailer with Gmail SMTP.
 */
function sendEmail(string $to, string $subject, string $message): bool {
    $log_file = __DIR__ . '/cron_execution.log';
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hitikpatel13@gmail.com';
        $mail->Password = 'fhvw uhtu nqfc rvpt';    // Replace with your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        // Recipients
        $mail->setFrom('hitikpatel13@gmail.com', 'XKCD Subscription');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Email sent to $to\n", FILE_APPEND);
        return true;
    } catch (Exception $e) {
        $error = $mail->ErrorInfo;
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to send email to $to: $error\n", FILE_APPEND);
        return false;
    }
}

/**
 * Send a verification code to an email.
 */
function sendVerificationEmail(string $email, string $code): bool {
    $subject = 'Your Verification Code';
    $message = "<p>Your verification code is: <strong>$code</strong></p>";
    return sendEmail($email, $subject, $message);
}

/**
 * Register an email by storing it in a file.
 */
function registerEmail(string $email): bool {
    $file = __DIR__ . '/registered_emails.txt';
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    if (in_array($email, $emails)) {
        return false;
    }
    return file_put_contents($file, $email . PHP_EOL, FILE_APPEND) !== false;
}

/**
 * Unsubscribe an email by removing it from the list.
 */
function unsubscribeEmail(string $email): bool {
    $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) {
        return false;
    }
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $emails = array_filter($emails, function($e) use ($email) {
        return trim($e) !== trim($email);
    });
    return file_put_contents($file, implode(PHP_EOL, $emails) . (empty($emails) ? '' : PHP_EOL)) !== false;
}

/**
 * Verify the provided code against the one sent.
 */
function verifyCode(string $email, string $code): bool {
    return isset($_SESSION['verification_code']) && 
           isset($_SESSION['verification_email']) &&
           $_SESSION['verification_email'] === $email && 
           $_SESSION['verification_code'] === $code;
}

/**
 * Fetch random XKCD comic and format data as HTML.
 */
function fetchAndFormatXKCDData(): string {
    $maxComic = 2000;
    $randomId = mt_rand(1, $maxComic);
    $url = "https://xkcd.com/$randomId/info.0.json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    $log_file = __DIR__ . '/cron_execution.log';
    if (!$response) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to fetch XKCD comic: $curl_error\n", FILE_APPEND);
        return "<h2>XKCD Comic</h2><p>Failed to fetch comic</p>";
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to parse XKCD comic data\n", FILE_APPEND);
        return "<h2>XKCD Comic</h2><p>Failed to parse comic data</p>";
    }
    
    $img = htmlspecialchars($data['img']);
    
    return "<h2>XKCD Comic</h2>
    <img src=\"$img\" alt=\"XKCD Comic\">";
}

/**
 * Send the formatted XKCD updates to registered emails.
 */
function sendXKCDUpdatesToSubscribers(): void {
    $file = __DIR__ . '/registered_emails.txt';
    $log_file = __DIR__ . '/cron_execution.log';
    if (!file_exists($file)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - No registered emails file found\n", FILE_APPEND);
        return;
    }
    
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($emails)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - No registered emails found\n", FILE_APPEND);
        return;
    }
    
    $comicData = fetchAndFormatXKCDData();
    
    $subject = 'Your XKCD Comic';
    
    foreach ($emails as $email) {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Invalid email: $email\n", FILE_APPEND);
            continue;
        }
        
        // Hardcode the unsubscribe link for PHP's built-in server (port 8000, src/ as root)
        $unsubscribeLink = "http://localhost:8000/unsubscribe.php?email=" . urlencode($email);
        
        $message = $comicData . "<p><a href=\"$unsubscribeLink\" id=\"unsubscribe-button\">Unsubscribe</a></p>";
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Attempting to send email to $email\n", FILE_APPEND);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Email content: $message\n", FILE_APPEND);
        
        if (sendEmail($email, $subject, $message)) {
            // Add a 1-second delay to avoid Gmail SMTP throttling
            sleep(1);
        }
    }
}