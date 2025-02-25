<?php
//
class Transaction {
    private $db;
    private $stripe;

    public function __construct() {
        $this->db = new Database();
        $config = require '../config.php';
        $this->stripe = new \Stripe\StripeClient($config['stripe']['admin']['secret_key']);
    }

    public function processLoanTransfer($loanId, $amount, $recipientEmail, $stripeAccountId) {
        try {
            // 1. Create a payment intent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'gbp',
                'transfer_data' => [
                    'destination' => $stripeAccountId,
                ],
                'metadata' => [
                    'loan_id' => $loanId,
                    'recipient_email' => $recipientEmail
                ]
            ]);

            // 2. Record the transaction in your database
            $sql = "INSERT INTO transactions (loan_id, amount, stripe_payment_id, status, recipient_email) 
                    VALUES (?, ?, ?, ?, ?)";
            $params = [$loanId, $amount, $paymentIntent->id, 'pending', $recipientEmail];
            $this->db->query($sql, $params);

            // 3. Create the transfer
            $transfer = $this->stripe->transfers->create([
                'amount' => $amount * 100,
                'currency' => 'gbp',
                'destination' => $stripeAccountId,
                'transfer_group' => 'LOAN_' . $loanId,
                'metadata' => [
                    'loan_id' => $loanId,
                    'payment_intent' => $paymentIntent->id
                ]
            ]);

            // 4. Update transaction status
            $sql = "UPDATE transactions SET 
                    stripe_transfer_id = ?, 
                    status = 'completed', 
                    completed_at = NOW() 
                    WHERE loan_id = ? AND stripe_payment_id = ?";
            $params = [$transfer->id, $loanId, $paymentIntent->id];
            $this->db->query($sql, $params);

            return [
                'success' => true,
                'payment_id' => $paymentIntent->id,
                'transfer_id' => $transfer->id
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log the error and update transaction status
            $sql = "UPDATE transactions SET 
                    status = 'failed', 
                    error_message = ? 
                    WHERE loan_id = ?";
            $params = [$e->getMessage(), $loanId];
            $this->db->query($sql, $params);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}