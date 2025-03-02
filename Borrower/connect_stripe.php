<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../vendor/autoload.php';
require_once '../Classes/Database.php';
require_once '../Classes/User.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get config with Stripe keys
    $config = require '../config.php';
    \Stripe\Stripe::setApiKey($config['stripe']['borrower']['secret_key']);

    // Get current user's info
    $userId = $_SESSION['user_id'];
    $user = User::find($userId);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Your OAuth link with proper redirect
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    $oauth_url = 'https://connect.stripe.com/oauth/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => $config['stripe_connect_client_id'],
        'scope' => 'read_write',
        'redirect_uri' => $protocol . $host . '/borrower/stripe_callback.php',
        'state' => $userId, // Store the user ID in state
        'stripe_user[country]' => 'US',
        'stripe_user[email]' => $user->email
    ]);

    // Redirect to Stripe Connect
    header('Location: ' . $oauth_url);
    exit;

} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}