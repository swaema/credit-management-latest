<?php

use Kint\Kint;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../Classes/Stripe.php';
require '../Classes/Database.php';
require '../Classes/Mail.php';


function logRepayment($message, $data = null) {
    error_log("Repayment Processing: " . $message . ($data ? " - Data: " . json_encode($data) : ""));
    
    try {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/repayment.log';
        if (is_dir($logDir) && is_writable($logDir)) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] $message";
            if ($data !== null) {
                $logMessage .= " - Data: " . json_encode($data, JSON_PRETTY_PRINT);
            }
            @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
        }
    } catch (Exception $e) {
        error_log("Logging failed: " . $e->getMessage());
    }
}

try {
    $base_url = sprintf(
        "%s://%s%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'],
        '/'
    );

    // Get database connection early for checks
    $db = Database::getConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Capture and validate payment
    $info = StripePayment::captureOrder();
    if (!$info['status']) {
        throw new Exception("Payment verification failed");
    }

    $paymentinfo = $info['session'];
    $payAmount = ($paymentinfo->amount_total) / 100;
    
    // Extract metadata
    $metadata = $paymentinfo->metadata;
    $loanId = $metadata->loanid;
    $principal = $metadata->principal;
    $interestRate = $metadata->interest;
    $interest = ($interestRate / 100) * $principal;
    $adminfee = $metadata->adminfee;
    $user_id = $_SESSION['user_id'];


    logRepayment("Processing repayment", [
        'loan_id' => $loanId,
        'amount' => $payAmount,
        'principal' => $principal,
        'interest' => $interest,
        'admin_fee' => $adminfee
    ]);

    $db->begin_transaction();
    try {
        $query = "UPDATE `loaninstallments` 
        SET `status` = 'Paid', `pay_date` = NOW()
        WHERE `loan_id` = ? 
        AND `user_id` = ? 
        AND `status` = 'Pending'
        ORDER BY `pay_date` ASC 
        LIMIT 1";

$stmt = $db->prepare($query);
if (!$stmt) {
  throw new Exception("Failed to prepare update statement: " . $db->error);
}

$stmt->bind_param("ii", $loanId, $user_id);

if (!$stmt->execute()) {
  throw new Exception("Failed to update installment status: " . $stmt->error);
}

        // Get and process contributors
        $contributorsQuery = "SELECT lc.*, u.email 
                            FROM lendercontribution lc 
                            JOIN users u ON lc.lenderId = u.id 
                            WHERE lc.loanId = ?";
        $contributorsStmt = $db->prepare($contributorsQuery);
        $contributorsStmt->bind_param("i", $loanId);
        $contributorsStmt->execute();
        $contributors = $contributorsStmt->get_result();

        $distributionAmount = $payAmount - $adminfee;
        while ($contributor = $contributors->fetch_assoc()) {
            $lenderShare = ($distributionAmount * $contributor['LoanPercent']) / 100;
            $principalShare = ($principal * $contributor['LoanPercent']) / 100;
            $interestShare = ($interest * $contributor['LoanPercent']) / 100;

            // Update consolidated fund
            $fundQuery = "INSERT INTO consoledatedfund (user_id, Amount, Earning) 
                         VALUES (?, ?, ?) 
                         ON DUPLICATE KEY UPDATE 
                         Amount = Amount + VALUES(Amount),
                         Earning = Earning + VALUES(Earning)";
            
            $fundStmt = $db->prepare($fundQuery);
            $fundStmt->bind_param("idd", 
                $contributor['lenderId'], 
                $lenderShare, 
                $interestShare
            );
            $fundStmt->execute();

            // Update lender contribution records
            $updateContribQuery = "UPDATE lendercontribution 
                                 SET RecoveredPrincipal = RecoveredPrincipal + ?,
                                     ReturnedInterest = ReturnedInterest + ?
                                 WHERE lenderId = ? AND loanId = ?";
            
            $updateContribStmt = $db->prepare($updateContribQuery);
            $updateContribStmt->bind_param("ddii", 
                $principalShare, 
                $interestShare, 
                $contributor['lenderId'], 
                $loanId
            );
            $updateContribStmt->execute();

            // Send notification email to lender
            $subject = "Loan Repayment Received";
            $body = "
            Dear Lender,

            A repayment of Rs" . number_format($lenderShare, 2) . " has been received for loan #{$loanId}.

            Breakdown:
            - Principal: Rs" . number_format($principalShare, 2) . "
            - Interest: Rs" . number_format($interestShare, 2) . "

            This amount has been added to your consolidated fund.

            Best regards,
            SafeFund Management
            ";
            
            Mail::sendMail($subject, $body, $contributor['email']);
        }

        // Process admin fee
        $adminQuery = "SELECT u.id, u.email 
                      FROM users u 
                      WHERE u.role = 'admin' 
                      LIMIT 1";
        $adminResult = $db->query($adminQuery);
        $admin = $adminResult->fetch_assoc();

        if ($admin) {
            // Add admin fee to consolidated fund
            $adminFundQuery = "INSERT INTO consoledatedfund (user_id, Amount, Earning) 
                             VALUES (?, ?, 0) 
                             ON DUPLICATE KEY UPDATE 
                             Amount = Amount + VALUES(Amount)";
            
            $adminFundStmt = $db->prepare($adminFundQuery);
            $adminFundStmt->bind_param("id", 
                $admin['id'], 
                $adminfee
            );
            $adminFundStmt->execute();

            // Record admin fee
            $adminFeeQuery = "INSERT INTO admin_fees (loan_id, amount, status) 
                            VALUES (?, ?, 'collected')";
            
            $adminFeeStmt = $db->prepare($adminFeeQuery);
            $adminFeeStmt->bind_param("id", $loanId, $adminfee);
            $adminFeeStmt->execute();
        }

        // Record transaction
        $transQuery = "INSERT INTO transactions 
                      (user_id, type, amount, status, reference_id) 
                      VALUES (?, 'repayment', ?, 'completed', ?)";
        $transStmt = $db->prepare($transQuery);
        $transStmt->bind_param("idi", $user_id, $payAmount, $loanId);
        $transStmt->execute();

        $db->commit();

        // Remove processing lock
        $db->query("DELETE FROM loan_processing_locks WHERE loan_id = " . $loanId);

        logRepayment("Payment processed successfully", [
            'loan_id' => $loanId,
            'amount' => $payAmount
        ]);

        $query = "
        UPDATE loans l
        JOIN (
            SELECT loan_id
            FROM loaninstallments
            WHERE loan_id = ?
            GROUP BY loan_id
            HAVING SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) = 0
        ) li ON l.id = li.loan_id
        SET l.status = 'Closed';
        ";
$closedStmt = $db->prepare($query);
$closedStmt->bind_param("i", $loanId);
$closedStmt->execute();



        // Redirect to success page
        header("Location: " . $base_url . "Borrower/ActiveLoan.php?status=success");
        exit();

    } catch (Exception $e) {
        $db->rollback();
        // Remove processing lock on error
        $db->query("DELETE FROM loan_processing_locks WHERE loan_id = " . $loanId);
        throw $e;
    }

} catch (Exception $e) {

    echo $e->getMessage();
    exit();
    // Clean up lock if it exists
    if (isset($db) && isset($loanId)) {
        $db->query("DELETE FROM loan_processing_locks WHERE loan_id = " . $loanId);
    }

    error_log("Payment processing error: " . $e->getMessage());
    $error_message = urlencode("Payment processing failed: " . $e->getMessage());
    
    // Log detailed error
    logRepayment("Payment processing failed", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    header("Location: " . $base_url . "Borrower/ActiveLoan.php?status=error&message=" . $error_message);
    exit();
}