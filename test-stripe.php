<?php
require 'vendor/autoload.php';
$config = require 'config.php';

\Stripe\Stripe::setApiKey($config['stripe']['admin']['secret_key']);

try {
    echo "Creating test payment to add funds...\n";
    
    // Use a test token that triggers immediate balance availability
    $charge = \Stripe\Charge::create([
        'amount' => 50000000, // $500,000.00
        'currency' => 'usd',
        'source' => 'tok_bypassPending', // Special token for immediate balance availability
        'description' => 'Test charge to add platform funds'
    ]);
    
    echo "\nCharge successful!\n";
    echo "Charge ID: " . $charge->id . "\n";
    echo "Amount: $" . ($charge->amount / 100) . "\n";
    echo "Status: " . $charge->status . "\n";
    
    // Check platform balance
    echo "\nChecking platform balance...\n";
    $balance = \Stripe\Balance::retrieve();
    
    if (isset($balance->available[0])) {
        echo "Available balance: $" . ($balance->available[0]->amount / 100) . "\n";
    }
    if (isset($balance->pending[0])) {
        echo "Pending balance: $" . ($balance->pending[0]->amount / 100) . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($e instanceof \Stripe\Exception\InvalidRequestException) {
        echo "Error details: " . print_r($e->getJsonBody(), true) . "\n";
    }
}