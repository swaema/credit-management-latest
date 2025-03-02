<?php
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/LoanInstallments.php';
require_once '../Classes/Stripe.php';

header('Content-Type: application/json');

try {
    $config = require '../config.php';
    error_log("Config loaded. Processing payment...");

    error_log("Received payment request: " . print_r($_POST, true));

    if (!isset($_POST['loanId'])) {
        throw new Exception('Loan ID is required');
    }

    $loanId = $_POST['loanId'];
    $payAmount = $_POST['payamount'];
    
    $db = Database::getConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    $pendingInstallment = $db->prepare("
    SELECT loanInstallmentsId 
    FROM loaninstallments 
    WHERE loan_id = ?
      AND status = 'pending'
");

$pendingInstallment->bind_param("s", $loanId);
$pendingInstallment->execute();
$result = $pendingInstallment->get_result();

if ($result->num_rows == 0) {
    throw new Exception('No pending installment found for this loan.');
}

    // Get loan details
    $loaninfo = Loan::getLoanById($loanId);
    if (!$loaninfo) {
        throw new Exception('Loan not found');
    }

    // Calculate fees
    $adminFee = round($payAmount * 0.02, 2); // 2% admin fee
    $remainingAmount = $payAmount - $adminFee;
    
    // Create metadata array
    $metadata = [
        'loanid' => $loanId,
        'principal' => $_POST['principal'],
        'interest' => $_POST['interest'],
        'adminfee' => $adminFee
    ];

    // Log metadata
    error_log("Prepared metadata: " . print_r($metadata, true));

    // Add transaction lock
    $lockQuery = "INSERT IGNORE INTO loan_processing_locks (loan_id) VALUES (?)";
    $lockStmt = $db->prepare($lockQuery);
    $lockStmt->bind_param("i", $loanId);
    $lockStmt->execute();
    
    if ($lockStmt->affected_rows === 0) {
        // Another process is handling this payment
        sleep(2); // Wait briefly and check payment status again
        $existingPayment->execute();
        if ($existingPayment->get_result()->num_rows > 0) {
            throw new Exception('Payment is being processed by another request');
        }
    }

    try {
        // Set Stripe API key
        \Stripe\Stripe::setApiKey($config['stripe']['admin']['secret_key']);

        // Create Stripe checkout session for borrower's payment
        $result = StripePayment::createOrder(
            $payAmount,
            '/paymentsuccess/repaymentsuccess.php',
            '/cancel.php',
            'Loan Payment',
            "Payment for loan #$loanId",
            $metadata,
            'repaymentsuccess.php'
        );

        error_log("Stripe checkout session created: " . print_r($result, true));

        if (!$result['success']) {
            throw new Exception($result['error'] ?? 'Failed to create payment session');
        }

        // Start database transaction
        $db->begin_transaction();

        try {
            // Record pending transaction first
            $transactionQuery = "INSERT INTO transactions 
                               (user_id, type, amount, status, reference_id) 
                               VALUES (?, 'repayment', ?, 'pending', ?)";
            $stmt = $db->prepare($transactionQuery);
            $stmt->bind_param("idi", $loaninfo['user_id'], $payAmount, $loanId);
            $stmt->execute();

            // Record pending admin fee
            $adminFeeQuery = "INSERT INTO admin_fees (loan_id, amount, status) 
                            VALUES (?, ?, 'pending')";
            $stmt = $db->prepare($adminFeeQuery);
            $stmt->bind_param("id", $loanId, $adminFee);
            $stmt->execute();

            // Get lender contributions
            $lenderQuery = "SELECT * FROM lendercontribution WHERE loanId = ?";
            $stmt = $db->prepare($lenderQuery);
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
            $lenders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Record pending lender payments
            $lenderPaymentQuery = "INSERT INTO payment_transfers 
                                 (stripe_transfer_id, amount, destination, status) 
                                 VALUES (?, ?, ?, 'pending')";
            foreach ($lenders as $lender) {
                $lenderShare = ($lender['LoanPercent'] / 100) * $remainingAmount;
                $stmt = $db->prepare($lenderPaymentQuery);
                $dummy_transfer_id = ''; // placeholder
                $stmt->bind_param("sds", $dummy_transfer_id, $lenderShare, $config['stripe']['lender']['account_id']);
                $stmt->execute();
            }

            $db->commit();

            // Remove processing lock
            $db->query("DELETE FROM loan_processing_locks WHERE loan_id = " . $loanId);

            echo json_encode($result);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            // Remove processing lock on error
            $db->query("DELETE FROM loan_processing_locks WHERE loan_id = " . $loanId);
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Stripe error: " . $e->getMessage());
        // Remove processing lock on Stripe error
        $db->query("DELETE FROM loan_processing_locks WHERE loan_id = " . $loanId);
        throw $e;
    }

} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Record error
    try {
        $errorQuery = "INSERT INTO payment_errors (error_message) VALUES (?)";
        $stmt = $db->prepare($errorQuery);
        $errorMessage = $e->getMessage() . "\n" . $e->getTraceAsString();
        $stmt->bind_param("s", $errorMessage);
        $stmt->execute();
    } catch (Exception $logError) {
        error_log("Failed to log error: " . $logError->getMessage());
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}