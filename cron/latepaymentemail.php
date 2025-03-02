<?php

use Kint\Kint;
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/Mail.php';
require_once '../Classes/LoanInstallments.php';

// Fetch all unique users who have overdue installments
$users = Loan::fetchLateEmails();

foreach ($users as $user) {
    $userId       = $user['user_id'];
    $emailAddress = $user['Email'];
    
    // Retrieve all overdue installments with loan details for this user
    $installments = LoanInstallments::getOverduePendingInstallmentsWithLoanDetails($userId);
    
    if (!empty($installments)) {
        // Compose a nicely formatted HTML email body with inline CSS in a single div
        $body = '
        <div style="max-width:600px; margin:20px auto; padding:20px; font-family:Arial, sans-serif; color:#333; border:1px solid #e0e0e0; border-radius:8px; background-color:#ffffff;">
            <h2 style="text-align:center; color:#2a7ae2; margin-bottom:20px;">Loan Installment Reminder</h2>
            <p>Dear Valued Customer,</p>
            <p>We hope this message finds you well. Our records indicate that the following loan installment(s) are overdue. Please review the details below:</p>';
        
        // Loop through each installment and add its details
        foreach ($installments as $inst) {
            $body .= '
            <div style="border:1px solid #ccc; padding:15px; margin-bottom:15px; border-radius:5px; background-color:#fafafa;">
                <p style="margin:5px 0;"><strong>Loan Purpose:</strong> ' . htmlspecialchars($inst['loanPurpose']) . '</p>
                <p style="margin:5px 0;"><strong>Total Loan Amount:</strong> $' . number_format($inst['loanAmount'], 2) . '</p>
                <p style="margin:5px 0;"><strong>Installment Amount:</strong> $' . number_format($inst['InstallmentAmount'], 2) . '</p>
                <p style="margin:5px 0;"><strong>Due Date:</strong> ' . htmlspecialchars($inst['pay_date']) . '</p>
                <p style="margin:5px 0;"><strong>Payable Amount:</strong> $' . number_format($inst['payable_amount'], 2) . '</p>
            </div>';
        }
        
        $body .= '
            <p>We kindly ask that you settle the pending payments at your earliest convenience.</p>
            <p>If you have any questions or require assistance, please feel free to contact our support team.</p>
            <p>Thank you for your prompt attention to this matter.</p>
            <p>Best Regards,<br>Your Finance Team</p>
        </div>';
        
        // Send the consolidated email to the user.
        // Assuming Mail::sendMail accepts a fourth parameter to indicate HTML content.
        Mail::sendMail('Reminder: Overdue Loan Installments', $body, $emailAddress, true);
    }
}

exit();
?>
