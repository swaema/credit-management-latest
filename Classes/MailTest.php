<?php
// Define the root path
define('ROOT_PATH', __DIR__);

// Include required files
require_once ROOT_PATH . '/Mail.php';

// Set up autoloading
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    '/Applications/XAMPP/xamppfiles/htdocs/vendor/autoload.php'
];

foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

// Database configuration for XAMPP
class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            try {
                // XAMPP default settings
                $host = '127.0.0.1';  // Using IP instead of localhost
                $port = 8888;         // Default MySQL port
                $username = 'root';
                $password = '';
                $database = 'credit_management';

                // Create connection with error reporting
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

                self::$connection = new mysqli(
                    $host,
                    $username,
                    $password,
                    $database,
                    $port
                );

                // Set charset
                self::$connection->set_charset('utf8mb4');

            } catch (mysqli_sql_exception $e) {
                echo "Database connection error: " . $e->getMessage() . "\n";
                echo "Please ensure:\n";
                echo "1. XAMPP's MySQL service is running\n";
                echo "2. The credit_management database exists\n";
                echo "3. The port 8888 is not blocked\n";
                return null;
            }
        }
        return self::$connection;
    }
}

class MailTest {
    private static $testEmail = 'itachimav@gmail.com';

    public static function runAllTests() {
        try {
            echo "Starting Mail Class Tests...\n\n";

            // First, verify MySQL is running
            echo "Checking MySQL status...\n";
            $mysqlRunning = self::checkMySQLStatus();
            if (!$mysqlRunning) {
                echo "✗ MySQL is not running. Please start MySQL in XAMPP Control Panel.\n";
                return;
            }
            echo "✓ MySQL is running\n\n";

            // Test database connection
            echo "Testing database connection...\n";
            $db = Database::getConnection();
            if ($db) {
                echo "✓ Database connection successful\n\n";
            } else {
                echo "✗ Database connection failed\n\n";
                return;
            }

            // Now run mail tests
            self::testSendOtp();
            self::testActiveStatusMail();
            self::testSendMail();

            echo "\nAll tests completed!\n";
        } catch (Exception $e) {
            echo "Test suite failed: " . $e->getMessage() . "\n";
        }
    }

    private static function checkMySQLStatus() {
        try {
            $connection = @fsockopen('127.0.0.1', 8888);
            if (is_resource($connection)) {
                fclose($connection);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function testSendOtp() {
        echo "Testing SendOtp method...\n";
        try {
            $otp = rand(100000, 999999);
            $result = Mail::SendOtp(self::$testEmail, $otp);

            if ($result === 1) {
                echo "✓ SendOtp test passed (OTP: $otp)\n";
            } else {
                echo "✗ SendOtp test failed\n";
            }
        } catch (Exception $e) {
            echo "✗ SendOtp test failed with error: " . $e->getMessage() . "\n";
        }
    }

    private static function testActiveStatusMail() {
        echo "\nTesting ActiveStatusMail method...\n";
        try {
            $result = Mail::ActiveStatusMail(self::$testEmail);

            if ($result === 1) {
                echo "✓ ActiveStatusMail test passed\n";
            } else {
                echo "✗ ActiveStatusMail test failed\n";
            }
        } catch (Exception $e) {
            echo "✗ ActiveStatusMail test failed with error: " . $e->getMessage() . "\n";
        }
    }

    private static function testSendMail() {
        echo "\nTesting sendMail method...\n";
        try {
            $subject = "Test Email";
            $body = "This is a test email sent from the MailTest class.";

            echo "1. Getting database connection...\n";
            $db = Database::getConnection();
            if (!$db) {
                throw new Exception("Failed to get database connection");
            }
            echo "   ✓ Database connection successful\n";

            // Check if test user exists
            echo "2. Checking if test user exists...\n";
            $checkQuery = "SELECT id, email FROM users WHERE email = ?";
            $stmt = $db->prepare($checkQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare select statement: " . $db->error);
            }

            $stmt->bind_param("s", self::$testEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            echo "   Query executed. Found " . $result->num_rows . " rows\n";

            if ($result->num_rows === 0) {
                echo "3. Test user not found. Creating new user...\n";
                // Insert test user if doesn't exist
                $insertQuery = "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'user', 1)";
                $stmt = $db->prepare($insertQuery);
                if (!$stmt) {
                    throw new Exception("Failed to prepare insert statement: " . $db->error);
                }

                $testName = "Test User";
                $testPassword = password_hash("testpassword", PASSWORD_DEFAULT);
                $stmt->bind_param("sss", $testName, self::$testEmail, $testPassword);

                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert user: " . $stmt->error);
                }
                echo "   ✓ Test user created successfully with ID: " . $db->insert_id . "\n";
            } else {
                $userData = $result->fetch_assoc();
                echo "   ✓ Found existing test user with ID: " . $userData['id'] . "\n";
            }

            echo "4. Attempting to send email...\n";
            echo "   To: " . self::$testEmail . "\n";
            echo "   Subject: " . $subject . "\n";
            echo "   Body: " . $body . "\n";

            echo "   Sending mail...\n";
            $result = Mail::sendMail($subject, $body, self::$testEmail);
            echo "   Got result: " . var_export($result, true) . "\n";

            if ($result === 1) {
                echo "✓ sendMail test passed\n";
            } else {
                echo "✗ sendMail test failed\n";
                echo "   Return value: " . var_export($result, true) . "\n";

                // Check error log
                $errorLog = error_get_last();
                if ($errorLog) {
                    echo "   Last PHP error: " . var_export($errorLog, true) . "\n";
                }

                // Check if mail error info is available
                if (isset($mail) && $mail->ErrorInfo) {
                    echo "   Mail error info: " . $mail->ErrorInfo . "\n";
                }
            }
        } catch (Exception $e) {
            echo "✗ sendMail test failed with error: " . $e->getMessage() . "\n";
            echo "Error trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
}

// Run all tests
echo "=================================\n";
echo "Starting Mail Test Suite\n";
echo "=================================\n";
MailTest::runAllTests();
?>