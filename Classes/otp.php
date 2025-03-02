<?php
class OTPHandler
{
    // Store OTP in session
    public static function storeOTPInSession($email, $otp)
    {
        if (self::checkOTPExistence($email)){
            self::removeOTPFromSession($email);
        }
        try {
            session_start();

            $_SESSION['otp_cache'][$email] = [
                'otp' => $otp,
                'created_at' => time(),
                'expires_at' => time() + 120 // 2 minutes
            ];

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function checkOTPExistence($email)
    {
        session_start();

        if (isset($_SESSION['otp_cache'][$email])) {
            return true;
        }

        return false;
    }

    public static function checkOTPExpired($email)
    {
        session_start();

        if (self::checkOTPExistence($email)) {
            $otpData = $_SESSION['otp_cache'][$email];
            $currentTime = time();

            if ($currentTime > $otpData['expires_at']) {
                return true;
            }

            return false;
        }

        return false;
    }

    public static function checkOTPValid($email)
    {
        session_start();

        if (self::checkOTPExistence($email) && !self::checkOTPExpired($email)) {
            return true;
        }

        return false;
    }

    public static function generateOTP($length = 6)
    {
        try {
            $otp = '';
            $characters = '0123456789';
            $charactersLength = strlen($characters);

            for ($i = 0; $i < $length; $i++) {
                $otp .= $characters[random_int(0, $charactersLength - 1)];
            }

            return $otp;
        } catch (Exception $e) {
            // Log the exception if needed
            return null;
        }
    }

    // Get stored OTP
    public static function getStoredOTP($email)
    {
        session_start();

        if (self::checkOTPExistence($email)) {
            return $_SESSION['otp_cache'][$email]['otp'];
        }

        return null; // Return null if OTP doesn't exist for the given email
    }

    // Remove OTP from session
    public static function removeOTPFromSession($email)
    {
        session_start();

        if (self::checkOTPExistence($email)) {
            unset($_SESSION['otp_cache'][$email]);
            return true; // OTP successfully removed
        }

        return false; // OTP doesn't exist for the given email
    }

    public static function getOTPExpiryTime($email)
    {
        session_start();
    
        if (self::checkOTPExistence($email)) {
            return $_SESSION['otp_cache'][$email]['expires_at'];
        }
    
        return null;
    }

}

?>
