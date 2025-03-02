<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../Classes/Stripe.php';
require_once '../Classes/Database.php';
require_once '../Classes/UserAuth.php';

function logContributionEvent($message, $data = null) {
    $logFile = __DIR__ . '/contribution_success.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " - Data: " . json_encode($data, JSON_PRETTY_PRINT);
    }
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'] . (in_array($_SERVER['SERVER_PORT'], ['80', '443']) ? '' : ':' . $_SERVER['SERVER_PORT']);
    $dir = dirname(dirname($_SERVER['SCRIPT_NAME']));
    return rtrim($protocol . '://' . $host . $dir, '/');
}

try {
    logContributionEvent("Starting contribution success processing");

    // Check that the lender is authenticated
    if (!UserAuth::isLenderAuthenticated()) {
        throw new Exception('Authentication required');
    }

    // Verify that the session ID is provided (e.g., via GET parameter)
    if (!isset($_GET['session_id'])) {
        throw new Exception("Missing session ID");
    }
    $sessionId = $_GET['session_id'];
    logContributionEvent("Processing session", ['session_id' => $sessionId]);

    // Retrieve the Stripe session (only payment verification is handled here)
    $paymentInfo = StripePayment::captureOrder($sessionId);
    if (!$paymentInfo['status']) {
        throw new Exception("Payment verification failed: " . ($paymentInfo['error'] ?? 'Unknown error'));
    }

    $session = $paymentInfo['session'];
    $metadata = $session->metadata;
    $contributionAmount = $session->amount_total / 100; // Convert from cents

    // Validate that the required metadata exists
    if (!isset($metadata->loan_id) || !isset($metadata->percentage)) {
        throw new Exception("Missing required metadata");
    }
    $loanId      = $metadata->loan_id;
    $percentage  = $metadata->percentage;
    $extraAmount = $metadata->extra_amount ?? 0;
    $extraEarning= $metadata->extra_earning ?? 0;
    $lenderId    = $_SESSION['user_id'];

    // Get a database connection
    $db = Database::getConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Begin transaction with retry logic
    $maxRetries = 3;
    $retryCount = 0;
    $success    = false;
    
    while (!$success && $retryCount < $maxRetries) {
        try {
            $db->begin_transaction();

            // Retrieve loan details (including borrower info)
            $loanQuery = "SELECT l.*, u.email as borrower_email, u.stripe_account_id 
                          FROM loans l 
                          JOIN users u ON l.user_id = u.id 
                          WHERE l.id = ?";
            $loanStmt = $db->prepare($loanQuery);
            $loanStmt->bind_param("i", $loanId);
            $loanStmt->execute();
            $currentLoan = $loanStmt->get_result()->fetch_assoc();
            if (!$currentLoan) {
                throw new Exception("Loan not found");
            }

            // Check for an existing contribution recorded recently to avoid duplicates
            $existingCheck = $db->prepare("
                SELECT id 
                FROM transactions 
                WHERE user_id = ? 
                  AND type = 'investment' 
                  AND amount = ? 
                  AND reference_id = ? 
                  AND transaction_date >= DATE_SUB(NOW(), INTERVAL 8 SECOND)
            ");
            $existingCheck->bind_param("idi", $lenderId, $contributionAmount, $loanId);
            $existingCheck->execute();
            if ($existingCheck->get_result()->num_rows > 0) {
                $db->rollback();
                logContributionEvent("Duplicate contribution detected", [
                    'session_id' => $sessionId,
                    'loan_id'    => $loanId,
                    'amount'     => $contributionAmount
                ]);
                $successMessage = urlencode("Your contribution was already processed");
                header("Location: " . getBaseUrl() . "/Lender/LoanApplications.php?e=" . $successMessage);
                exit;
            }

            // Insert or update the lender contribution record
            $contributionQuery = "INSERT INTO lendercontribution 
                                  (lenderId, loanId, LoanPercent, LoanAmount, ExtraAmount, ExtraEarning) 
                                  VALUES (?, ?, ?, ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE 
                                  LoanPercent = LoanPercent + VALUES(LoanPercent),
                                  LoanAmount  = LoanAmount  + VALUES(LoanAmount),
                                  ExtraAmount = ExtraAmount + VALUES(ExtraAmount),
                                  ExtraEarning= ExtraEarning+ VALUES(ExtraEarning)";
            $contribStmt = $db->prepare($contributionQuery);
            $contribStmt->bind_param("iidddd", $lenderId, $loanId, $percentage, $contributionAmount, $extraAmount, $extraEarning);
            $contribStmt->execute();

            // Check total funding for the loan and process full funding if applicable
            $fundingQuery = "SELECT SUM(LoanAmount) as total_funded 
                             FROM lendercontribution 
                             WHERE loanId = ?";
            $fundingStmt = $db->prepare($fundingQuery);
            $fundingStmt->bind_param("i", $loanId);
            $fundingStmt->execute();
            $fundingResult = $fundingStmt->get_result()->fetch_assoc();

            if ($fundingResult['total_funded'] >= $currentLoan['loanAmount'] && $currentLoan['status'] !== 'Completed') {
                // Create installment schedule for full funding
                $installmentAmount = $currentLoan['InstallmentAmount'];
                $totalInterest     = ($currentLoan['TotalLoan'] - $currentLoan['loanAmount']);
                $monthlyInterest   = $totalInterest / $currentLoan['noOfInstallments'];
                $monthlyPrincipal  = $currentLoan['loanAmount'] / $currentLoan['noOfInstallments'];
                $adminFee          = $installmentAmount * 0.02;

                for ($i = 1; $i <= $currentLoan['noOfInstallments']; $i++) {
                    $payDate = date('Y-m-d', strtotime("+$i months"));
                    $installQuery = "INSERT INTO loaninstallments 
                                     (user_id, loan_id, payable_amount, pay_date, principal, interest, admin_fee, status) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
                    $installStmt = $db->prepare($installQuery);
                    $installStmt->bind_param(
                        "iidsiii",
                        $currentLoan['user_id'],
                        $loanId,
                        $installmentAmount,
                        $payDate,
                        $monthlyPrincipal,
                        $monthlyInterest,
                        $adminFee
                    );
                    $installStmt->execute();
                }

                // Update the loan status to 'Funded'
                $updateLoanQuery = "UPDATE loans SET status = 'Completed' WHERE id = ?";
                $updateLoanStmt = $db->prepare($updateLoanQuery);
                $updateLoanStmt->bind_param("i", $loanId);
                $updateLoanStmt->execute();

                // Notify the borrower that their loan is fully funded
                $fullFundingMsg = "Your loan has been fully funded! Total amount: $" . number_format($currentLoan['loanAmount'], 2);
                $notifyFullStmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $notifyFullStmt->bind_param("is", $currentLoan['user_id'], $fullFundingMsg);
                $notifyFullStmt->execute();
            }

            // Record the transaction for this contribution
            $transQuery = "INSERT INTO transactions 
                           (user_id, type, amount, status, reference_id) 
                           VALUES (?, 'investment', ?, 'completed', ?)";
            $transStmt = $db->prepare($transQuery);
            $transStmt->bind_param("idi", $lenderId, $contributionAmount, $loanId);
            $transStmt->execute();

            // Notify the borrower about the new contribution
            $contribMsg = "New contribution received: $" . number_format($contributionAmount, 2) . " (" . $percentage . "%)";
            $notifyStmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notifyStmt->bind_param("is", $currentLoan['user_id'], $contribMsg);
            $notifyStmt->execute();

            $db->commit();
            $success = true;
        } catch (Exception $e) {
            $db->rollback();
            $retryCount++;

            // If a duplicate entry error is caught by the database, exit gracefully
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                logContributionEvent("Duplicate contribution caught by database constraint", [
                    'session_id' => $sessionId,
                    'loan_id'    => $loanId
                ]);
                $successMessage = urlencode("Your contribution was processed successfully");
                header("Location: " . getBaseUrl() . "/Lender/LoanApplications.php?e=" . $successMessage);
                exit;
            }

            if ($retryCount >= $maxRetries) {
                throw new Exception("Failed to process contribution: " . $e->getMessage());
            }
            sleep(1);
        }
    }

    // Clear any session data used for the contribution
    unset($_SESSION['payment_session_id']);
    unset($_SESSION['payment_metadata']);
    unset($_SESSION['contribution_data']);

    logContributionEvent("Contribution processed successfully", [
        'loan_id'    => $loanId,
        'amount'     => $contributionAmount,
        'percentage' => $percentage
    ]);

    // Redirect the user with a success message
    $successMessage = urlencode("Successfully contributed $" . number_format($contributionAmount, 2));
    header("Location: " . getBaseUrl() . "/Lender/LoanApplications.php?e=" . $successMessage);
    exit;

} catch (Exception $e) {
    logContributionEvent("Error processing contribution", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    $errorMessage = urlencode("Contribution failed: " . $e->getMessage());
    header("Location: " . getBaseUrl() . "/Lender/LoanApplications.php?e=" . $errorMessage);
    exit;
}