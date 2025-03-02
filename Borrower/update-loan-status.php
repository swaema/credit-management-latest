<?php
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/LoanInstallments.php';
require_once '../vendor/autoload.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $config = require '../config.php';
    \Stripe\Stripe::setApiKey($config['stripe']['borrower']['secret_key']);
    
    $paymentIntent = \Stripe\PaymentIntent::retrieve($data['paymentIntentId']);

    if ($paymentIntent->status === 'succeeded') {
        $result = LoanInstallments::updateStatus(
            $paymentIntent->metadata->loan_id,
            $paymentIntent->amount / 100,
            $paymentIntent->metadata->principal,
            $paymentIntent->metadata->interest,
            0 // admin fee
        );

        if (!$result) {
            throw new Exception('Failed to update loan status');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully'
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>