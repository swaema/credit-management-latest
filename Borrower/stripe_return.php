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
    
    // Get the user's Stripe account ID from your database
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT stripe_account_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && $result['stripe_account_id']) {
        // Verify the account status
        $account = \Stripe\Account::retrieve($result['stripe_account_id']);
        
        if ($account->details_submitted) {
            header('Location: index.php?success=Stripe account connected successfully');
        } else {
            header('Location: index.php?error=Please complete your Stripe account setup');
        }
    }
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    header('Location: index.php?error=' . urlencode($e->getMessage()));
}