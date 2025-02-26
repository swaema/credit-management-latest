<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// require 'vendor/autoload.php';
class Mail
{
    public static function AcceptMail($userId, $loanId, $installments)
    {
        require 'vendor/autoload.php';
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Query to get the loan and user info
            $query = "SELECT * FROM loans l 
                  INNER JOIN users u ON u.id = l.user_id 
                  WHERE u.id = ? AND l.id = ? 
                  LIMIT 1";

            // Prepare the statement
            $stmt = $db->prepare($query);

            // Bind the parameters for the user ID and loan ID
            $stmt->bind_param("ii", $userId, $loanId);

            // Execute the query
            $stmt->execute();
            $result = $stmt->get_result();
            $mailUser = $result->fetch_assoc();

            // Close the statement
            $stmt->close();

            // Prepare the email message
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'mail.safefunds.online';
            $mail->SMTPAuth = true;
            $mail->isHTML(true);
            $mail->Username = 'admin@safefunds.online'; // Your Gmail address
            $mail->Password = 'Dissertation27$'; // Your App Password (use environment variables in production)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Email details
            $mail->setFrom('dissertationsafefund@gmail.com', 'Admin');
            $mail->addAddress('umar150704@gmail.com');
            $mail->Subject = 'Mail for Loan Acceptance';
            $messageBody = 'Dear ' . $mailUser['name'] . ', your loan request for amount ' . $mailUser['loanAmount'] .
                ' for the purpose of ' . $mailUser['loanPurpose'] . ' has been accepted. The number of installments is ' . $installments . '.';

            $mail->Body = $messageBody;

            // Send the email
            $check = $mail->send();

            if ($check) {
                // Insert the notification into the notifications table
                $insertQuery = "INSERT INTO `notifications` (`user_id`, `message`, `created_at`) 
                            VALUES (?, ?, ?)";

                // Prepare the statement for the insert query
                $stmt = $db->prepare($insertQuery);

                // Get the current timestamp
                $createdAt = date('Y-m-d H:i:s');

                // Bind the parameters for the notification (user_id, message, created_at)
                $stmt->bind_param("iss", $userId, $messageBody, $createdAt);

                // Execute the insert query
                $stmt->execute();

                // Close the statement after insert
                $stmt->close();

                return 1;
            } else {
                var_dump('e');
                exit;
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    public static function PayInstallmentMail($insId)
    {
                // Primary path to vendor autoload
                $primaryAutoloadPath = 'vendor/autoload.php';

                // Alternative path to vendor autoload (adjust this to your needs)
                $alternativeAutoloadPath =  '../vendor/autoload.php';
        
                if (file_exists($primaryAutoloadPath)) {
                    require $primaryAutoloadPath;
                } elseif (file_exists($alternativeAutoloadPath)) {
                    require $alternativeAutoloadPath;
                } else {
                    // Handle the case where neither autoload file is found
                    die('Autoload file not found. Please run "composer install".');
                }

        // var_dump($userId);
        // exit;
        try {

            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception(message: "Database connection failed");
            }
            $query = "SELECT * FROM loaninstallments li 
            INNER JOIN users u ON u.id = li.user_id 
            WHERE li.loanInstallmentsId = ? 
            LIMIT 1";

            // Prepare the statement
            $stmt = $db->prepare($query);

            // Bind parameters (if $userId and $loanId are integers)
            $stmt->bind_param("i", $insId);
            // Execute the query
            $stmt->execute();
            $result = $stmt->get_result();
            $mailUser = $result->fetch_assoc();
            // $messagebody = "";
            // Close the statement
            $stmt->close();
            $mail = new PHPMailer(true);

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'mail.safefunds.online';
            $mail->SMTPAuth = true;
            $mail->isHTML(true);
            $mail->Username = 'admin@safefunds.online'; // Your Gmail address
            $mail->Password = 'Dissertation27$'; // Your App Password (use environment variables in production)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->setFrom('dissertationsafefund@gmail.com', 'Admin');
            $mail->addAddress($mailUser['email']);
            $mail->Subject = 'Mail for Loan Acceptance';
            $mail->Body = 'Dear ' . $mailUser['name'] . ', your installment for paydate ' . $mailUser['pay_date'] .
                ' has been paid ' . '.';
            // $messagebody=$mail->Body;
            $check = $mail->send();
            if ($check) {
                // Insert the notification into the notifications table
                $insertQuery = "INSERT INTO `notifications` (`user_id`, `message`, `created_at`) 
                                VALUES (?, ?, ?)";

                // Prepare the statement for the insert query
                $stmt = $db->prepare($insertQuery);

                // Get the current timestamp
                $createdAt = date('Y-m-d H:i:s');

                // Bind the parameters for the notification (user_id, message, created_at)
                $stmt->bind_param("iss", $mailUser['user_id'], $mail->Body, $createdAt);

                // Execute the insert query
                $stmt->execute();

                // Close the statement after insert
                $stmt->close();
                return 1;

            } else {
                var_dump('e');
                exit;
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
            // echo "Failed to send email. Error: {$mail->ErrorInfo}";
        }

    }
    public static function SendOtp($email, $otp)
    {
        require 'vendor/autoload.php';

        // var_dump($userId);
        // exit;
        try {
            $subject = "Your OTP Verification Code";
            $message = "Your OTP code is: " . $otp;
            // $headers = "From: noreply@yourwebsite.com";

            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception(message: "Database connection failed");
            }
            $mail = new PHPMailer(true);

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'mail.safefunds.online';
            $mail->SMTPAuth = true;
            $mail->Username = 'admin@safefunds.online'; // Your Gmail address
            $mail->Password = 'Dissertation27$'; // Your App Password (use environment variables in production)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->isHTML(true);
            $mail->setFrom('dissertationsafefund@gmail.com', 'Admin');
            $mail->addAddress($email);
            $mail->Subject = 'Verification Code of SafeFund';
            $mail->Body = "Your OTP code is: " . $otp;

            // $messagebody=$mail->Body;
            $check = $mail->send();
            if ($check) {
                return 1;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
            // echo "Failed to send email. Error: {$mail->ErrorInfo}";
        }

    }
    public static function ActiveStatusMail($email)
    {
        require 'vendor/autoload.php';
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception(message: "Database connection failed");
            }
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'mail.safefunds.online';
            $mail->SMTPAuth = true;
            $mail->isHTML(true);
            $mail->Username = 'admin@safefunds.online'; // Your Gmail address
            $mail->Password = 'Dissertation27$'; // Your App Password (use environment variables in production)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->setFrom('dissertationsafefund@gmail.com', 'Admin');
            $mail->addAddress($email);
            $mail->Subject = 'Account Verified';
            $mail->Body = "Dear user, your Email account is verified and your application is now under review. We will process it shortly. Thank you for your patience!";
            $check = $mail->send();
            if ($check) {
                return 1;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }
    public static function sendMail($subject, $body, $email)
    {
        $data = null;
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Update query to fetch user by email
            $query = "SELECT * FROM users WHERE email = ?";
            $stmt = $db->prepare($query);

            // Bind the email parameter instead of id
            $stmt->bind_param("s", $email); // "s" for string (email)

            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            if (!$data) {
                throw new Exception("User not found");
            }
        } catch (Exception $e) {
            error_log("Error finding user: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
            return 0;
        }

        // Primary path to vendor autoload
        $primaryAutoloadPath = 'vendor/autoload.php';
        $alternativeAutoloadPath =  '../vendor/autoload.php';

        if (file_exists($primaryAutoloadPath)) {
            require $primaryAutoloadPath;
        } elseif (file_exists($alternativeAutoloadPath)) {
            require $alternativeAutoloadPath;
        } else {
            die('Autoload file not found. Please run "composer install".');
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'mail.safefunds.online';
            $mail->SMTPAuth = true;
            $mail->isHTML(true);
            $mail->Username = 'admin@safefunds.online'; // Your Gmail address
            $mail->Password = 'Dissertation27$'; // Your App Password (use environment variables in production)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->setFrom('dissertationsafefund@gmail.com', 'Admin');
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $check = $mail->send();
            if ($check) {
                // Insert the notification into the notifications table
                $insertQuery = "INSERT INTO notifications (user_id, message, created_at) 
                            VALUES (?, ?, ?)";

                // Prepare the statement for the insert query
                $stmt = $db->prepare($insertQuery);

                // Get the current timestamp
                $createdAt = date('Y-m-d H:i:s');

                // Bind the parameters for the notification (user_id, message, created_at)
                $stmt->bind_param("iss", $data['id'], $mail->Body, $createdAt);

                // Execute the insert query
                $stmt->execute();

                // Close the statement after insert
                $stmt->close();
                return 1;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    public static function sendSuspensionMail($email, $reason = "suspicious activity")
    {
        try {
            // Primary path to vendor autoload
            $primaryAutoloadPath = 'vendor/autoload.php';
            $alternativeAutoloadPath = '../vendor/autoload.php';

            if (file_exists($primaryAutoloadPath)) {
                require $primaryAutoloadPath;
            } elseif (file_exists($alternativeAutoloadPath)) {
                require $alternativeAutoloadPath;
            } else {
                die('Autoload file not found. Please run "composer install".');
            }

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'mail.safefunds.online';
            $mail->SMTPAuth = true;
            $mail->Username = 'admin@safefunds.online'; // Your Gmail address
            $mail->Password = 'Dissertation27$'; // Your App Password (use environment variables in production)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->isHTML(true);
            $mail->setFrom('dissertationsafefund@gmail.com', 'SafeFund Admin');
            $mail->addAddress($email);
            $mail->Subject = 'Account Suspension Notice';
            $mail->Body = "Dear User,\n\n"
                . "We regret to inform you that your SafeFund account has been suspended due to " . $reason . ".\n\n"
                . "If you believe this is an error or would like to appeal this decision, please contact our support team.\n\n"
                . "Best regards,\n"
                . "SafeFund Team";

            $check = $mail->send();
            return $check ? 1 : 0;
        } catch (Exception $e) {
            error_log("Error sending suspension email: " . $e->getMessage());
            return 0;
        }
    }
}