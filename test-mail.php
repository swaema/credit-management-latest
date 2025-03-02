<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer
$autoloadPaths = [
    'vendor/autoload.php',
    '../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to log test results
function logTestResult($message, $type = 'info') {
    $color = $type === 'error' ? 'red' : ($type === 'success' ? 'green' : 'black');
    echo "<p style='color: $color;'>" . date('H:i:s') . " - $message</p>";
}

// Function to send test email using PHPMailer
function sendTestMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sbw.hosenbocus@gmail.com'; 
        $mail->Password = 'jdxetthyweurpkcg';        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('dissertationsafefund@gmail.com', 'Admin');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $e) {
        throw new Exception("Mailer Error: " . $mail->ErrorInfo);
    }
}

// Test configuration
$testEmail = "dissertationsafefund@gmail.com"; 

echo "<h2>Mail Testing Suite (Basic Tests)</h2>";

// Test 1: Simple Text Email
echo "<div style='margin: 20px; padding: 15px; border: 1px solid #ddd;'>";
echo "<h3>Test 1: Simple Text Email</h3>";
try {
    $subject = "Test Email " . date('Y-m-d H:i:s');
    $body = "This is a simple test email sent at " . date('Y-m-d H:i:s');
    logTestResult("Attempting to send simple text email to $testEmail");
    $result = sendTestMail($testEmail, $subject, $body);
    if ($result) {
        logTestResult("Simple text email sent successfully!", 'success');
    }
} catch (Exception $e) {
    logTestResult("Error sending simple email: " . $e->getMessage(), 'error');
}
echo "</div>";

// Test 2: OTP Email
echo "<div style='margin: 20px; padding: 15px; border: 1px solid #ddd;'>";
echo "<h3>Test 2: OTP Email</h3>";
try {
    $otp = rand(100000, 999999);
    $subject = "Your OTP Code";
    $body = "Your OTP code is: $otp";
    logTestResult("Attempting to send OTP email to $testEmail");
    $result = sendTestMail($testEmail, $subject, $body);
    if ($result) {
        logTestResult("OTP email sent successfully!", 'success');
    }
} catch (Exception $e) {
    logTestResult("Error sending OTP email: " . $e->getMessage(), 'error');
}
echo "</div>";

// Test 3: HTML Email
echo "<div style='margin: 20px; padding: 15px; border: 1px solid #ddd;'>";
echo "<h3>Test 3: HTML Email</h3>";
try {
    $subject = "HTML Test Email";
    $body = "
        <html>
        <body>
            <h2>HTML Test Email</h2>
            <p>This is a test email with HTML formatting sent at " . date('Y-m-d H:i:s') . "</p>
            <ul>
                <li>Test Item 1</li>
                <li>Test Item 2</li>
            </ul>
        </body>
        </html>";
    logTestResult("Attempting to send HTML email to $testEmail");
    $result = sendTestMail($testEmail, $subject, $body);
    if ($result) {
        logTestResult("HTML email sent successfully!", 'success');
    }
} catch (Exception $e) {
    logTestResult("Error sending HTML email: " . $e->getMessage(), 'error');
}
echo "</div>";

// Summary
echo "<div style='margin: 20px; padding: 15px; background: #f5f5f5;'>";
echo "<h3>Test Summary</h3>";
echo "<ul>";
echo "<li>Test Email: " . htmlspecialchars($testEmail) . "</li>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Time Completed: " . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";
echo "<p>Note: This script tests basic email functionality without database dependencies.</p>";
echo "<p>For full testing including database-dependent features (loan notifications, etc.), please ensure the database is set up first.</p>";
echo "</div>";
?>