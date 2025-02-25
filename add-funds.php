<?php
// add-funds.php
require 'vendor/autoload.php';
$config = require 'config.php';

\Stripe\Stripe::setApiKey($config['stripe']['admin']['secret_key']);

try {
    echo "Adding funds to platform balance...\n";
    
    // Create a charge that bypasses the pending balance
    $charge = \Stripe\Charge::create([
        'amount' => 5000000, // $50,000
        'currency' => 'gbp',
        'source' => 'tok_visa', // This will be immediately available
        'description' => 'Add funds to platform balance'
    ]);
    
    echo "Charge successful!\n";
    echo "Charge ID: " . $charge->id . "\n";
    echo "Amount: $" . ($charge->amount / 100) . "\n";
    
    // Check the new balance
    $balance = \Stripe\Balance::retrieve();
    
    echo "\nUpdated platform balance:\n";
    if (isset($balance->available[0])) {
        echo "Available: $" . ($balance->available[0]->amount / 100) . "\n";
    }
    if (isset($balance->pending[0])) {
        echo "Pending: $" . ($balance->pending[0]->amount / 100) . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}