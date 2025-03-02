<?php
/**
 * AdminFeeHandler - Manages the collection and distribution of admin fees
 * 
 * This class handles:
 * - Calculating admin fees (2% of payment amounts)
 * - Recording fees in admin_fees table
 * - Updating admin's consolidated fund
 * - Creating transaction records
 */

require_once 'Database.php';
require_once 'Stripe.php';

class AdminFeeHandler {
    private $db;
    private $adminId;
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->initAdminUser();
    }
    
    /**
     * Initialize admin user ID
     */
    private function initAdminUser() {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        
        if (!$admin) {
            throw new Exception("No admin user found in the system");
        }
        
        $this->adminId = $admin['id'];
    }
    
    /**
     * Handle the collection and distribution of admin fees
     * 
     * @param int $loanId The loan ID
     * @param float $installmentAmount The installment amount
     * @return bool True if successful
     * @throws Exception If any step fails
     */
    public function handleAdminFee($loanId, $installmentAmount) {
        $this->db->begin_transaction();
        
        try {
            // Calculate admin fee (2%)
            $adminFee = round($installmentAmount * 0.02, 2);
            
            // Record admin fee
            $feeStmt = $this->db->prepare("
                INSERT INTO admin_fees (loan_id, amount, status)
                VALUES (?, ?, 'collected')
            ");
            $feeStmt->bind_param("id", $loanId, $adminFee);
            $feeStmt->execute();
            
            // Update admin's consolidated fund
            $fundStmt = $this->db->prepare("
                INSERT INTO consoledatedfund (user_id, Amount, Earning)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                Amount = Amount + VALUES(Amount),
                Earning = Earning + VALUES(Earning)
            ");
            $fundStmt->bind_param("idd", $this->adminId, $adminFee, $adminFee);
            $fundStmt->execute();
            
            // Record transaction
            $transStmt = $this->db->prepare("
                INSERT INTO transactions 
                (user_id, type, amount, status, reference_id)
                VALUES (?, 'admin_fee', ?, 'completed', ?)
            ");
            $transStmt->bind_param("idi", $this->adminId, $adminFee, $loanId);
            $transStmt->execute();
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Admin fee processing error: " . $e->getMessage());
            throw new Exception("Failed to process admin fee: " . $e->getMessage());
        }
    }
    
    /**
     * Get total admin fees collected for a loan
     * 
     * @param int $loanId The loan ID
     * @return float Total fees collected
     */
    public function getTotalFeesForLoan($loanId) {
        $stmt = $this->db->prepare("
            SELECT SUM(amount) as total 
            FROM admin_fees 
            WHERE loan_id = ? AND status = 'collected'
        ");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['total'] ?? 0;
    }
}