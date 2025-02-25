<?php

use Kint\Kint;
// Check for composer autoload in different possible locations
$possiblePaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];

$loaded = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    throw new Exception('Stripe SDK not found. Please run: composer require stripe/stripe-php');
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
class StripePayment {
    private static $config;
    private static $currency = 'GBP';

    private static function initConfig() {
        if (!self::$config) {
            self::$config = require dirname(__DIR__) . '/config.php';
        }
    }

    private static function getStripeKey($accountType = 'admin') {
        self::initConfig();
        if (!isset(self::$config['stripe'][$accountType])) {
            throw new Exception("Invalid account type: $accountType");
        }
        return self::$config['stripe'][$accountType]['secret_key'];
    }

    public static function createOrder($amount, $returnUrl, $cancelUrl, $Name, $Description, $metadata = [], $successEndpoint = 'repaymentsuccess.php') {
        self::initConfig();
        
        // Determine which account to use based on the payment type
        $isLenderContribution = $successEndpoint === 'contributesuccess.php';
        
        // For lender contributions, we use the admin's secret key
        // because we're creating the checkout session on behalf of the platform
        \Stripe\Stripe::setApiKey(self::$config['stripe']['admin']['secret_key']);
    
        // Get the base URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'] . (in_array($_SERVER['SERVER_PORT'], ['80','443']) ? '' : ':'.$_SERVER['SERVER_PORT']);
        $base_url = $protocol . "://" . $host;
    
        // Build the success and cancel URLs
        $success_url = $base_url . "/paymentsuccess/" . $successEndpoint . "?session_id={CHECKOUT_SESSION_ID}";
        $cancel_url = $base_url . $cancelUrl;
    
        if (isset($metadata['loan_id'])) {
            $success_url .= '&loan_id=' . $metadata['loan_id'];
        }
    
        error_log("Base URL: " . $base_url);
        error_log("Success URL: " . $success_url);
        error_log("Cancel URL: " . $cancel_url);
    
        try {
            $session_params = [
                "mode" => "payment",
                "locale" => "en",
                "payment_method_types" => ["card"],
                'customer_email' => $_SESSION['user_email'] ?? null,
                "success_url" => $success_url,
                "cancel_url" => $cancel_url,
                "line_items" => [[
                    "price_data" => [
                        "currency" => self::$currency,
                        "product_data" => [
                            "name" => $Name,
                            "description" => $Description,
                        ],
                        "unit_amount" => intval($amount * 100),
                    ],
                    "quantity" => 1,
                ]],
                "metadata" => array_merge($metadata, [
                    'payment_type' => $isLenderContribution ? 'lender_contribution' : 'borrower_payment',
                    'account_type' => $isLenderContribution ? 'lender' : 'admin'
                ])
            ];
    
            // For lender contributions, we need to specify how the funds will be handled
            if ($isLenderContribution) {
                // Calculate the application fee (2% of the contribution amount)
                $fee_amount = intval($amount * 0.02 * 100); // Convert to cents
    
                $session_params['payment_intent_data'] = [
                    'application_fee_amount' => $fee_amount,
                    'transfer_data' => [
                        'destination' => self::$config['stripe']['borrower']['account_id'],
                    ],
                    'on_behalf_of' => self::$config['stripe']['borrower']['account_id'],
                ];
            }
    
            $checkout_session = \Stripe\Checkout\Session::create($session_params);
    
            $_SESSION["payment_session_id"] = $checkout_session->id;
            $_SESSION["payment_metadata"] = $metadata;
            
            return [
                'success' => true,
                'session_id' => $checkout_session->id,
                'url' => $checkout_session->url
            ];
    
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public static function captureOrder($sessionId = null) {
        self::initConfig();
        
        try {
            // Retrieve session id from argument or session
            $sessionId = $sessionId ?? $_SESSION["payment_session_id"] ?? null;
            if (!$sessionId) {
                throw new Exception("No payment session ID found");
            }
            
            // Use admin key for payment retrieval
            \Stripe\Stripe::setApiKey(self::$config['stripe']['admin']['secret_key']);
            $session = \Stripe\Checkout\Session::retrieve([
                'id' => $sessionId,
                'expand' => ['payment_intent']
            ]);

            return [
                "status" => true,
                "session" => $session
            ];
            
        } catch (Exception $e) {
            error_log("Error capturing Stripe order: " . $e->getMessage());
            return [
                "status" => false,
                "error" => $e->getMessage()
            ];
        }
    }
    

    public static function transferFunds($loanId, $amount, $destinationAccountId, $currency = 'GBP') {
        self::initConfig();
        \Stripe\Stripe::setApiKey(self::$config['stripe']['admin']['secret_key']);
    
        try {
            $db = Database::getConnection();
            
            $checkStmt = $db->prepare("SELECT id FROM loan_transfers WHERE loan_id = ? AND status = 'completed'");
            $checkStmt->bind_param("i", $loanId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception("Transfer already processed for this loan");
            }
    
            $transfer = \Stripe\Transfer::create([
                'amount' => intval($amount * 100),
                'currency' => $currency,
                'destination' => $destinationAccountId,
                'description' => "Loan disbursement for loan #" . $loanId,
                'metadata' => [
                    'loan_id' => $loanId,
                    'transfer_type' => 'loan_disbursement'
                ]
            ]);
    
            $db->begin_transaction();
    
            try {
                // Record transfer
                $sql = "INSERT INTO loan_transfers (loan_id, amount, stripe_transfer_id, status) 
                        VALUES (?, ?, ?, 'completed')";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ids", $loanId, $amount, $transfer->id);
                $stmt->execute();
    
                // Update loan status
                $updateSql = "UPDATE loans SET status = 'Accepted' WHERE id = ?";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->bind_param("i", $loanId);
                $updateStmt->execute();
    
                $db->commit();
    
                return [
                    'success' => true,
                    'transfer' => $transfer
                ];
    
            } catch (\Exception $e) {
                $db->rollback();
                throw $e;
            }
    
        } catch (\Exception $e) {
            error_log("Transfer error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private static function recordTransfer($transferId, $amount, $destinationAccountId) {
        $db = Database::getConnection();
        $sql = "INSERT INTO payment_transfers (stripe_transfer_id, amount, destination, status) 
                VALUES (?, ?, ?, 'completed')";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('sds', $transferId, $amount, $destinationAccountId);
        $stmt->execute();
    }
}