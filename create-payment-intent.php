<?php
// create-payment-intent.php

require 'vendor/autoload.php';

// Load configuration
$config = require 'config.php';

\Stripe\Stripe::setApiKey($config['stripe']['admin']['secret_key']);

// Get the amount from a POST request (amount in cents)
$data = json_decode(file_get_contents('php://input'), true);
$amount = isset($data['amount']) ? (int)$data['amount'] : 5000000; // default $50.00

try {
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount'   => $amount,
        'currency' => 'gbp',
    ]);

    header('Content-Type: application/json');
    echo json_encode(['clientSecret' => $paymentIntent->client_secret]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
