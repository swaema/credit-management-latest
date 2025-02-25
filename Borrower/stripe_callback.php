<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../vendor/autoload.php';
require_once '../Classes/Database.php';
require_once '../Classes/User.php';

try {
    $config = require '../config.php';
    \Stripe\Stripe::setApiKey($config['stripe']['borrower']['secret_key']);

    // Verify state matches
    if (!isset($_GET['state']) || !isset($_GET['code'])) {
        throw new Exception('Invalid callback parameters');
    }

    $userId = $_GET['state'];
    $code = $_GET['code'];

    // Exchange code for access token
    $response = \Stripe\OAuth::token([
        'grant_type' => 'authorization_code',
        'code' => $code,
    ]);

    // Get the connected account ID
    $connectedAccountId = $response->stripe_user_id;

    // Store the connected account ID
    $db = Database::getConnection();
    $sql = "UPDATE users SET stripe_account_id = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $connectedAccountId, $userId);
    $stmt->execute();

    // Redirect back to your site
    header('Location: index.php?success=Stripe account connected successfully');
    exit;

} catch (\Stripe\Exception\OAuth\OAuthErrorException $e) {
    error_log('OAuth Error: ' . $e->getMessage());
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}