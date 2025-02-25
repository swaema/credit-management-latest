<?php
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/LoanInstallments.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['loanId'])) {
        throw new Exception('Loan ID is required');
    }
    
    $loanId = $data['loanId'];
    $status = $data['status'] ?? 'completed';
    
    // Update the loan installment status
    $result = LoanInstallments::updateStatus($loanId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment status updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}