<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set up error logging
function logError($message, $data = null) {
    // Log to system error log
    error_log("Contribution Processing: " . $message . ($data ? " - Data: " . json_encode($data) : ""));

    try {
        $logDir = dirname(__DIR__) . '/logs';

        // Try to create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . '/contribution_debug.log';

        // Only attempt to write if directory exists and is writable
        if (is_dir($logDir) && is_writable($logDir)) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] $message";
            if ($data !== null) {
                $logMessage .= " - Data: " . json_encode($data, JSON_PRETTY_PRINT);
            }
            @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
        }
    } catch (Exception $e) {
        // Silently fail on logging errors - we already logged to error_log
    }
}

try {
    logError("Script started");

    // Check for required files
    $requiredFiles = [
        '../Classes/Stripe.php',
        '../Classes/Database.php',
        '../Classes/UserAuth.php'
    ];

    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file not found: $file");
        }
        require_once $file;
    }

    // Verify authentication
    if (!UserAuth::isLenderAuthenticated()) {
        throw new Exception('Authentication required');
    }

    // Get and parse input (JSON)
    $input = file_get_contents('php://input');
    logError("Received input", ['raw' => $input]);

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    // Validate required fields
    $requiredFields = ['amount', 'loan_id', 'percentage'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize and validate inputs
    $amount     = filter_var($data['amount'], FILTER_VALIDATE_FLOAT);
    $loanId     = filter_var($data['loan_id'], FILTER_VALIDATE_INT);
    $percentage = filter_var($data['percentage'], FILTER_VALIDATE_FLOAT);
    $lenderId   = (int)$_SESSION['user_id'];

    if (!$amount || !$loanId || !$percentage) {
        throw new Exception('Invalid input values');
    }

    // Get database connection
    $db = Database::getConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Start transaction
    $db->begin_transaction();

    try {
        // 1. Fetch loan and current funding
        $loanQuery = "
            SELECT l.*, 
                   COALESCE(SUM(lc.LoanAmount), 0) AS total_funded,
                   u.email AS borrower_email,
                   u.stripe_account_id
            FROM loans l
            LEFT JOIN lendercontribution lc ON l.id = lc.loanId
            JOIN users u ON l.user_id = u.id
            WHERE l.id = ?
            GROUP BY l.id
        ";

        $stmt = $db->prepare($loanQuery);
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $loanData = $stmt->get_result()->fetch_assoc();
        $maxAllowed = $loanData['loanAmount'] - $loanData['total_funded'];
        if (!$loanData) {
            throw new Exception('Loan not found');
        }

        // 2. Check loan status
        if (!in_array(strtolower($loanData['status']), ['accepted', 'approved'])) {
            throw new Exception('Loan is not available for contribution');
        }

        // 3. Calculate the remaining funding neededÏ
        $availableFunding = $loanData['loanAmount'] - $loanData['total_funded'];
        if ($availableFunding <= 0) {
            throw new Exception("Loan is already fully funded.");
        }

        if ($amount <= 0 || $amount > $maxAllowed) {
            throw new Exception(
                "Error: funding amount exceeding loan's accepted amount. 
                 Allowed range: greater than 0 and up to a maximum of Rs{$maxAllowed}."
            );
        }

        // 6. Store contribution data in session
        $_SESSION['contribution_data'] = [
            'amount'         => $amount,
            'loan_id'        => $loanId,
            'percentage'     => $percentage,
            'lender_id'      => $lenderId,
            'borrower_email' => $loanData['borrower_email']
        ];

        logError("Creating Stripe checkout session", $_SESSION['contribution_data']);

        // 7. Create Stripe checkout session
        $stripeResponse = StripePayment::createOrder(
            $amount,
            '/Lender/LoanApplications.php',        // success redirect (relative to your domain)
            '/Lender/LoanApplications.php',        // cancel redirect
            'Loan Contribution',
            "Contribution of {$percentage}% to Loan #{$loanId}",
            [
                'loan_id'   => $loanId,
                'lender_id' => $lenderId,
                'percentage'=> $percentage,
                'type'      => 'contribution'
            ],
            'contributesuccess.php'                // Webhook or success page to finalize
        );

        logError("Stripe response received", $stripeResponse);

        if (!isset($stripeResponse['url'])) {
            throw new Exception('Failed to create Stripe checkout session');
        }

        // 8. Commit DB transaction (no actual DB update yet, but we want to ensure no concurrency issues)
        $db->commit();

        // 9. Clear output buffer and respond with the Stripe URL
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'url'     => $stripeResponse['url']
        ]);
        exit;

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    logError("Error processing contribution", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Clear output buffer and send error response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
    exit;
}