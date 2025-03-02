<?php
require_once("../Classes/Mail.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['accept'])) {
    try {
        // Validate inputs
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

        if (!$id || !$email || !$amount) {
            throw new Exception("Missing required fields");
        }

        $db = Database::getConnection();
        
        // Get loan details with user information
        $loanQuery = "SELECT l.*, u.name as borrower_name, u.id as borrower_id, 
                      l.noOfInstallments, l.loanPurpose, l.grade, l.TotalLoan,
                      l.InstallmentAmount, l.interstRate
                      FROM loans l 
                      JOIN users u ON l.user_id = u.id 
                      WHERE l.id = ?";
        $stmt = $db->prepare($loanQuery);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $loanDetails = $stmt->get_result()->fetch_assoc();
        
        if (!$loanDetails) {
            throw new Exception("Loan not found");
        }

        $db->begin_transaction();

        try {
            // Update loan status to make it visible to lenders
            $updateLoanSql = "UPDATE loans SET 
                status = 'Accepted',
                InstallmentAmount = ?,
                updated_at = NOW()
                WHERE id = ?";
            $stmt = $db->prepare($updateLoanSql);

            // Calculate monthly payment
            $totalLoan = $loanDetails['TotalLoan'];
            $installments = $loanDetails['noOfInstallments'];
            $monthlyPayment = $totalLoan / $installments;
            
            // Bind both the monthly payment and loan ID
            $stmt->bind_param("di", $monthlyPayment, $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update loan status");
            }

            // Create initial lendercontribution record with 0%
            $initContribSql = "INSERT INTO lendercontribution 
                              (loanId, LoanPercent, LoanAmount, RecoveredPrincipal, ReturnedInterest) 
                              VALUES (?, 0, 0, 0, 0)";
            $stmt = $db->prepare($initContribSql);
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to initialize loan contribution tracking");
            }

            // Create notification for borrower
            $notificationSql = "INSERT INTO notifications (user_id, message, created_at) 
                              VALUES (?, ?, NOW())";
            $notificationMsg = "Your loan application for $" . number_format($loanDetails['loanAmount'], 2) . 
                             " has been approved. Risk Grade: " . $loanDetails['grade'] . 
                             ". The loan is now available for funding by lenders.";
            
            $stmt = $db->prepare($notificationSql);
            $stmt->bind_param("is", $loanDetails['borrower_id'], $notificationMsg);
            if (!$stmt->execute()) {
                throw new Exception("Failed to create notification");
            }

            // Record the transaction
            $transactionSql = "INSERT INTO transactions 
                              (user_id, type, amount, status, reference_id) 
                              VALUES (?, 'loan_fee', ?, 'pending', ?)";
            $stmt = $db->prepare($transactionSql);
            $stmt->bind_param("idi", $loanDetails['borrower_id'], $loanDetails['loanAmount'], $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to record transaction");
            }

            $db->commit();

            // Send email notification
            $subject = "Loan Application Approved";
            $message = "Dear " . $loanDetails['borrower_name'] . ",\n\n" .
                      "Your loan application has been approved!\n\n" .
                      "Loan Details:\n" .
                      "- Loan ID: " . $id . "\n" .
                      "- Amount: $" . number_format($loanDetails['loanAmount'], 2) . "\n" .
                      "- Interest Rate: " . $loanDetails['interstRate'] . "%\n" .
                      "- Term: " . $loanDetails['noOfInstallments'] . " months\n" .
                      "- Monthly Payment: $" . number_format($loanDetails['InstallmentAmount'], 2) . "\n" .
                      "- Risk Grade: " . $loanDetails['grade'] . "\n\n" .
                      "Your loan is now available for funding by lenders. " .
                      "You'll be notified once the full amount has been funded.\n\n" .
                      "Thank you for choosing our services.";

            Mail::sendMail($subject, $message, $email);; //ffffffffffffffffff

            echo json_encode([
                'success' => true,
                'message' => 'Loan application approved successfully',
                'title' => 'Success!',
                'icon' => 'success'
            ]);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Error processing loan: " . $e->getMessage());
        echo json_encode([
            'error' => $e->getMessage(),
            'title' => 'Error',
            'icon' => 'error'
        ]);
    }
}
?>