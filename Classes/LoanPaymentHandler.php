<?php
// require '../vendor/autoload.php';
require_once 'Stripe.php';
require_once 'Database.php';

class LoanPaymentHandler {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }

    // When lender contributes
    public function handleLenderContribution($loanId, $lenderId, $amount, $percentage) {
        $this->db->begin_transaction();
        
        try {
            // Record contribution
            $stmt = $this->db->prepare("
                INSERT INTO lendercontribution 
                (lenderId, loanId, LoanPercent, LoanAmount, RecoveredPrincipal, ReturnedInterest)
                VALUES (?, ?, ?, ?, 0, 0)
            ");
            $stmt->bind_param("iidd", $lenderId, $loanId, $percentage, $amount);
            $stmt->execute();

            // Check if loan is fully funded
            $this->checkAndHandleFullFunding($loanId);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // Check if loan is fully funded and create installment schedule
    private function checkAndHandleFullFunding($loanId) {
        // Get loan details
        $stmt = $this->db->prepare("
            SELECT l.*, 
                   (SELECT SUM(LoanAmount) FROM lendercontribution WHERE loanId = l.id) as funded_amount
            FROM loans l 
            WHERE l.id = ?
        ");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();

        // If fully funded
        if ($loan['funded_amount'] >= $loan['loanAmount']) {
            // Create installment schedule
            $this->createInstallmentSchedule($loan);
        }
    }

    // Create installment schedule
    private function createInstallmentSchedule($loan) {
        $monthlyPayment = $loan['InstallmentAmount'];
        $interestRate = $loan['interstRate'];
        $numInstallments = $loan['noOfInstallments'];
        
        // Calculate components for each installment
        $totalAmount = $loan['loanAmount'];
        $monthlyInterestRate = $interestRate / 12 / 100;
        
        for ($i = 1; $i <= $numInstallments; $i++) {
            $interest = $totalAmount * $monthlyInterestRate;
            $principal = $monthlyPayment - $interest;
            $admin_fee = $monthlyPayment * 0.02; // 2% admin fee

            $payDate = date('Y-m-d', strtotime("+".($i + 1)." months"));
            
            $stmt = $this->db->prepare("
                INSERT INTO loaninstallments 
                (user_id, loan_id, payable_amount, pay_date, principal, interest, admin_fee, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            
            $stmt->bind_param(
                "iidsddd",
                $loan['user_id'],
                $loan['id'],
                $monthlyPayment,
                $payDate,
                $principal,
                $interest,
                $admin_fee
            );
            $stmt->execute();

            $totalAmount -= $principal;
        }
    }

    // Handle borrower's monthly payment
    public function handleInstallmentPayment($installmentId, $amount) {
        $this->db->begin_transaction();
        
        try {
            // Get installment details
            $stmt = $this->db->prepare("
                SELECT i.*, l.id as loan_id 
                FROM loaninstallments i
                JOIN loans l ON i.loan_id = l.id
                WHERE i.loanInstallmentsId = ?
            ");
            $stmt->bind_param("i", $installmentId);
            $stmt->execute();
            $installment = $stmt->get_result()->fetch_assoc();

            // Update installment status
            $stmt = $this->db->prepare("
                UPDATE loaninstallments 
                SET status = 'Paid' 
                WHERE loanInstallmentsId = ?
            ");
            $stmt->bind_param("i", $installmentId);
            $stmt->execute();

            // Distribute to lenders
            $this->distributePaymentToLenders($installment);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // Distribute payment to lenders
    private function distributePaymentToLenders($installment) {
        // Get all lenders for this loan
        $stmt = $this->db->prepare("
            SELECT * FROM lendercontribution 
            WHERE loanId = ?
        ");
        $stmt->bind_param("i", $installment['loan_id']);
        $stmt->execute();
        $lenders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode($lenders);;
exit();

        foreach ($lenders as $lender) {
            // Calculate lender's share
            $principalShare = $installment['principal'] * ($lender['LoanPercent'] / 100);
            $interestShare = $installment['interest'] * ($lender['LoanPercent'] / 100);

            // Update lender's consolidated fund
            $stmt = $this->db->prepare("
                INSERT INTO consoledatedfund (user_id, Amount, Earning)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                Amount = Amount + VALUES(Amount),
                Earning = Earning + VALUES(Earning)
            ");
            $stmt->bind_param("idd", $lender['lenderId'], $principalShare, $interestShare);
            $stmt->execute();

            // Update recovered amounts in lendercontribution
            $stmt = $this->db->prepare("
                UPDATE lendercontribution 
                SET RecoveredPrincipal = RecoveredPrincipal + ?,
                    ReturnedInterest = ReturnedInterest + ?
                WHERE lenderContributionId = ?
            ");
            $stmt->bind_param("ddi", $principalShare, $interestShare, $lender['lenderContributionId']);
            $stmt->execute();
        }
    }
}
?>