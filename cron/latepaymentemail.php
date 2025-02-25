<?php

use Kint\Kint;
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/Mail.php';
require_once '../Classes/LoanInstallments.php';

// Fetch all unique users who have overdue installments
$users = Loan::fetchLateEmails();

foreach ($users as $user) {
    $userId = $user['user_id'];
    $emailAddress = $user['Email'];
    
    // Retrieve all overdue installments with loan details for this user
    $installments = LoanInstallments::getOverduePendingInstallmentsWithLoanDetails($userId);
    
    if (!empty($installments)) {
        // Compose a nicely formatted email body
        $body  = "Dear Valued Customer,\n\n";
        $body .= "We hope this message finds you well. Our records indicate that the following loan installment(s) are overdue. Please review the details below:\n\n";

        foreach ($installments as $inst) {
            $body .= "----------------------------------------------------\n";
            $body .= "Loan Details:\n";
            $body .= "• Loan Purpose       : " . $inst['loanPurpose'] . "\n";
            $body .= "• Total Loan Amount  : $" . number_format($inst['loanAmount'], 2) . "\n";
            $body .= "• Installment Amount : $" . number_format($inst['InstallmentAmount'], 2) . "\n";
            $body .= "Overdue Installment:\n";
            $body .= "• Due Date           : " . $inst['pay_date'] . "\n";
            $body .= "• Payable Amount     : $" . number_format($inst['payable_amount'], 2) . "\n";
            $body .= "----------------------------------------------------\n\n";
        }

        $body .= "We kindly ask that you settle the pending payments at your earliest convenience.\n";
        $body .= "If you have any questions or require assistance, please feel free to contact our support team.\n\n";
        $body .= "Thank you for your prompt attention to this matter.\n\n";
        $body .= "Best Regards,\n";
        $body .= "Your Finance Team";

        // Send the consolidated email to the user
        Mail::sendMail('Reminder: Overdue Loan Installments', $body, $emailAddress);
    }
}

exit();
?>
