<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

class Mail
{
    /**
     * Returns a pre-configured PHPMailer instance.
     *
     * @return PHPMailer
     * @throws Exception
     */
    private static function getMailer()
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = '*.a2hosting.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@safefunds.online';
        $mail->Password   = 'Dissertation27$';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->isHTML(true);
        $mail->setFrom('admin@safefunds.online', 'Admin');
        return $mail;
    }

    public static function AcceptMail($userId, $loanId, $installments)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "SELECT * FROM loans l 
                      INNER JOIN users u ON u.id = l.user_id 
                      WHERE u.id = ? AND l.id = ? 
                      LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ii", $userId, $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            $mailUser = $result->fetch_assoc();
            $stmt->close();

            $mail = self::getMailer();
            $mail->addAddress('umar150704@gmail.com');
            $mail->Subject = 'Mail for Loan Acceptance';
            $messageBody = 'Dear ' . $mailUser['name'] . ', your loan request for amount ' . $mailUser['loanAmount'] .
                ' for the purpose of ' . $mailUser['loanPurpose'] . ' has been accepted. The number of installments is ' . $installments . '.';
            $mail->Body = $messageBody;

            if ($mail->send()) {
                $insertQuery = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, ?)";
                $stmt = $db->prepare($insertQuery);
                $createdAt = date('Y-m-d H:i:s');
                $stmt->bind_param("iss", $userId, $messageBody, $createdAt);
                $stmt->execute();
                $stmt->close();
                return 1;
            } else {
                var_dump('Email sending failed');
                exit;
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    public static function PayInstallmentMail($insId)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "SELECT * FROM loaninstallments li 
                      INNER JOIN users u ON u.id = li.user_id 
                      WHERE li.loanInstallmentsId = ? 
                      LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $insId);
            $stmt->execute();
            $result = $stmt->get_result();
            $mailUser = $result->fetch_assoc();
            $stmt->close();

            $mail = self::getMailer();
            $mail->addAddress($mailUser['email']);
            $mail->Subject = 'Mail for Loan Installment Payment';
            $mail->Body = 'Dear ' . $mailUser['name'] . ', your installment for paydate ' . $mailUser['pay_date'] . ' has been paid.';

            if ($mail->send()) {
                $insertQuery = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, ?)";
                $stmt = $db->prepare($insertQuery);
                $createdAt = date('Y-m-d H:i:s');
                $stmt->bind_param("iss", $mailUser['user_id'], $mail->Body, $createdAt);
                $stmt->execute();
                $stmt->close();
                return 1;
            } else {
                var_dump('Email sending failed');
                exit;
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    public static function SendOtp($email, $otp)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $mail = self::getMailer();
            $mail->addAddress($email);
            $mail->Subject = 'Verification Code of SafeFund';
            $mail->Body = "Your OTP code is: " . $otp;

            return $mail->send() ? 1 : 0;
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    public static function ActiveStatusMail($email)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $mail = self::getMailer();
            $mail->addAddress($email);
            $mail->Subject = 'Account Verified';
            $mail->Body = "Dear user, your Email account is verified and your application is now under review. We will process it shortly. Thank you for your patience!";

            return $mail->send() ? 1 : 0;
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    public static function sendMail($subject, $body, $email)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "SELECT * FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $email);
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

        try {
            $mail = self::getMailer();
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->Body = $body;

            if ($mail->send()) {
                $insertQuery = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, ?)";
                $stmt = Database::getConnection()->prepare($insertQuery);
                $createdAt = date('Y-m-d H:i:s');
                $stmt->bind_param("iss", $data['id'], $mail->Body, $createdAt);
                $stmt->execute();
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
            $mail = self::getMailer();
            // Override sender for suspension emails if needed.
            $mail->setFrom('dissertationsafefund@gmail.com', 'SafeFund Admin');
            $mail->addAddress($email);
            $mail->Subject = 'Account Suspension Notice';
            $mail->Body = "Dear User,\n\nWe regret to inform you that your SafeFund account has been suspended due to " . $reason . ".\n\nIf you believe this is an error or would like to appeal this decision, please contact our support team.\n\nBest regards,\nSafeFund Team";

            return $mail->send() ? 1 : 0;
        } catch (Exception $e) {
            error_log("Error sending suspension email: " . $e->getMessage());
            return 0;
        }
    }
}
