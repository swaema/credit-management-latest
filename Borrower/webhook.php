<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../vendor/autoload.php';
require_once '../Classes/Database.php';
require_once '../Classes/User.php';

// Enable error reporting for debugging
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

try {
    // Get config with Stripe keys
    $config = require '../config.php';
    \Stripe\Stripe::setApiKey($config['stripe']['borrower']['secret_key']);

    // Get the webhook secret from config
    $endpoint_secret = $config['stripe_webhook_secret']; // Add this to your config.php

    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
        
        // Handle the event
        switch ($event->type) {
            case 'account.updated':
                $account = $event->data->object;
                handleAccountUpdate($account);
                break;
            
            case 'account.application.deauthorized':
                $account = $event->data->object;
                handleAccountDeauthorized($account);
                break;

            // Add more cases based on the webhook events you want to handle
            default:
                error_log('Received unknown event type ' . $event->type);
        }

        http_response_code(200);
    } catch(\UnexpectedValueException $e) {
        error_log('Webhook Error: ' . $e->getMessage());
        http_response_code(400);
        exit();
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        error_log('Webhook Signature Error: ' . $e->getMessage());
        http_response_code(400);
        exit();
    }
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    http_response_code(500);
    exit();
}

function handleAccountUpdate($account) {
    try {
        // You might want to update your database with the new account status
        $userId = getUserIdFromStripeAccountId($account->id);
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                // Update user's stripe status or other relevant information
                // $user->updateStripeStatus($account->details_submitted);
            }
        }
    } catch (Exception $e) {
        error_log('Error handling account update: ' . $e->getMessage());
    }
}

function handleAccountDeauthorized($account) {
    try {
        // Handle when a user disconnects their Stripe account
        $userId = getUserIdFromStripeAccountId($account->id);
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                // Clear user's stripe connection details
                // $user->clearStripeConnection();
            }
        }
    } catch (Exception $e) {
        error_log('Error handling account deauthorization: ' . $e->getMessage());
    }
}

function getUserIdFromStripeAccountId($stripeAccountId) {
    // Add your logic to get the user ID from the Stripe account ID
    // This would typically involve a database query
    // Return null if not found
    return null;
}