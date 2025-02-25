<?php
// webhook.php
require_once '../config.php';
require_once '../vendor/autoload.php';

$config = require '../config.php';
\Stripe\Stripe::setApiKey($config['stripe']['admin']['secret_key']);

// Retrieve the raw payload from Stripe
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$endpoint_secret = ''; 

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the checkout.session.completed event
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    
    // Payment is complete. Now process transfers and any post-payment logic.
    // For example, call your transfer functions here:
    // StripePayment::transferFunds($amount, $destinationAccountId);
    
    error_log("Webhook received: Payment completed for session " . $session->id);
    
    // Continue processing as needed...
}

http_response_code(200);
