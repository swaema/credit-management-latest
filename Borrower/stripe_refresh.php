<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../vendor/autoload.php';
require_once '../Classes/Database.php';

$config = require '../config.php';
\Stripe\Stripe::setApiKey($config['stripe']['borrower']['secret_key']);

try {
    $userId = $_SESSION['user_id'];
    
    // Get the user's Stripe account ID
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT stripe_account_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || !$result['stripe_account_id']) {
        throw new Exception("Stripe account not found");
    }

    // Get your current domain
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . $domain;

    // Create new account link
    $accountLink = \Stripe\AccountLink::create([
        'account' => $result['stripe_account_id'],
        'refresh_url' => $baseUrl . '/borrower/stripe_refresh.php',
        'return_url' => $baseUrl . '/borrower/stripe_return.php',
        'type' => 'account_onboarding',
    ]);

    // Redirect to new onboarding URL
    header('Location: ' . $accountLink->url);
    exit;
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    header('Location: index.php?error=' . urlencode($e->getMessage()));
}