<?php
header('Content-Type: application/json');
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/Notifiactions.php';
require_once('../Classes/Mail.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!UserAuth::isAdminAuthenticated()) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated',
        'title' => 'Error',
        'icon' => 'error'
    ]);
    exit;
}

try {
    // Validate inputs
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

    if (!$id || !$email || !$amount) {
        throw new Exception("Missing required fields");
    }

    $db = Database::getConnection();

    // Get loan details
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

    // Check if loan is already accepted
    if ($loanDetails['status'] === 'Accepted') {
        throw new Exception("Loan has already been accepted");
    }

    $db->begin_transaction();

    try {
        // Get existing InstallmentAmount or calculate if not set
        $monthlyPayment = $loanDetails['InstallmentAmount'];
        if (!$monthlyPayment) {
            $totalLoan = $loanDetails['TotalLoan'];
            $installments = $loanDetails['noOfInstallments'];
            $monthlyPayment = round($totalLoan / $installments, 2);
        }

        // Update loan status to Accepted
        $updateLoanSql = "UPDATE loans SET 
            status = 'Accepted',
            InstallmentAmount = ?, 
            updated_at = NOW()
            WHERE id = ?";
        $stmt = $db->prepare($updateLoanSql);
        $stmt->bind_param("di", $monthlyPayment, $id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update loan status");
        }

        // Initialize lender contribution tracking
        $initContribSql = "INSERT INTO lendercontribution 
            (loanId, lenderId, LoanPercent, LoanAmount, RecoveredPrincipal, ReturnedInterest) 
            VALUES (?, 0, 0, 0, 0, 0)";
        $stmt = $db->prepare($initContribSql);
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to initialize loan contribution tracking");
        }

        // Create notification for borrower
        $notificationSql = "INSERT INTO notifications (user_id, message, created_at) 
                          VALUES (?, ?, NOW())";
        $notificationMsg = "Your loan application for $" . number_format($loanDetails['loanAmount'], 2) .
            " has been accepted. Risk Grade: " . $loanDetails['grade'] .
            ". Your loan is now visible to potential lenders and available for funding.";

        $stmt = $db->prepare($notificationSql);
        $stmt->bind_param("is", $loanDetails['borrower_id'], $notificationMsg);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create notification");
        }

        // Record transaction
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
        $subject = "Loan Application Accepted";
        $message = "Dear " . $loanDetails['borrower_name'] . ",\n\n" .
            "Your loan application has been accepted!\n\n" .
            "Loan Details:\n" .
            "- Loan ID: " . $id . "\n" .
            "- Amount: Rs " . number_format($loanDetails['loanAmount'], 2) . "\n" .
            "- Interest Rate: " . $loanDetails['interstRate'] . "%\n" .
            "- Term: " . $loanDetails['noOfInstallments'] . " months\n" .
            "- Monthly Payment: Rs " . number_format($monthlyPayment, 2) . "\n" .
            "- Risk Grade: " . $loanDetails['grade'] . "\n\n" .
            "Next Steps:\n" .
            "1. Your loan is now visible to potential lenders\n" .
            "2. Lenders can start contributing to your loan\n" .
            "3. You'll be notified once your loan is fully funded\n" .
            "4. Funds will be disbursed after full funding is achieved\n\n" .
            "Thank you for choosing our services.\n\n" .
            "Best regards,\nYour Lending Team";

        Mail::sendMail($subject, $message, $email);

        echo json_encode([
            'success' => true,
            'message' => 'Loan application has been accepted and is now visible to lenders.',
            'title' => 'Success!',
            'icon' => 'success'
        ]);

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error processing loan acceptance: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'title' => 'Error',
        'icon' => 'error'
    ]);
}
?>