<?php
require '../vendor/autoload.php';
require_once 'Stripe.php';
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

class LoanInstallments
{
    public $id;

    public function dumpConstructorValues()
    {
        var_dump($this);
        exit;
    }
  
    public static function updateStatus($loanId, $payAmount, $principal, $interest, $adminfee)
    {
        error_log("Starting payment update. LoanID: $loanId, Amount: $payAmount");

        try {
            error_log("Starting updateStatus with amounts: " . json_encode([
                'payAmount' => $payAmount,
                'principal' => $principal,
                'interest' => $interest,
                'adminfee' => $adminfee
            ]));
    
            $payAmount = isset($payAmount) && is_numeric($payAmount) ? round((float)$payAmount, 2) : 0;
            $principal = isset($principal) && is_numeric($principal) ? (float)$principal : 0;
            $interest = isset($interest) && is_numeric($interest) ? (float)$interest : 0;
            $adminfee = isset($adminfee) && is_numeric($adminfee) ? (float)$adminfee : 0;
            
            error_log("About to call StripePayment::createOrder");
            
            // Get database connection
            $db = Database::getConnection();
            
            // Get current date
            $currentDate = date('Y-m-d');
            
            // Get user_id from session
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("User not logged in");
            }
            $userId = $_SESSION['user_id'];
            
            // Insert the payment record
            $query = "INSERT INTO loaninstallments (loan_id, user_id, payable_amount, pay_date, principal, interest, admin_fee, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'Paid')";
            
            $stmt = $db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $db->error);
            }
            
            $stmt->bind_param("iidsddd", 
                $loanId,
                $userId, 
                $payAmount, 
                $currentDate, 
                $principal, 
                $interest, 
                $adminfee
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert payment record: " . $stmt->error);
            }
            
            error_log("Payment record inserted successfully");
            
            // Now proceed with Stripe payment
            $result = StripePayment::createOrder(
                $payAmount,
                "/paymentsuccess/repaymentsuccess.php",
                "/cancel.php",
                "mr test",
                "this is a description",
                [
                    "loanid" => (int)$loanId,
                    "principal" => $principal,
                    "interest" => $interest,
                    "adminfee" => $adminfee
                ]
            );
            
            error_log("Stripe payment result: " . json_encode($result));
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error in updateStatus: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public static function payInstallments($insId)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Validate insId
            $insId = filter_var($insId, FILTER_VALIDATE_INT);
            if ($insId === false) {
                throw new Exception("Invalid installment ID");
            }

            // Retrieve installment details from database
            $query = "SELECT * FROM loaninstallments WHERE loanInstallmentsId = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $insId);
            $stmt->execute();
            $result = $stmt->get_result();
            $installment = $result->fetch_assoc();
            
            if (!$installment) {
                throw new Exception("Installment not found");
            }

            $payAmount = isset($installment['payable_amount']) && is_numeric($installment['payable_amount']) 
                ? (float)$installment['payable_amount'] 
                : 0;
            
            $stmt->close();

            // PayPal configuration
            $clientId = 'AeBgOimKzCJR4HozGn2UMxyeBvpiaojII2MuR4_XvWhIPXEwD5SbcWvjV0PhxI51vvq-9grpd0jLkOZ2';
            $clientSecret = 'EKn6WyCJI-cevNCMQLdnEtAx-Y302oHGXwa83dL1DHAFjJLSj2m0hCn1mjNHOYW_Ls2SV_oPnvoi8ovU';
            
            $environment = new SandboxEnvironment($clientId, $clientSecret);
            $client = new PayPalHttpClient($environment);

            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                "intent" => "CAPTURE",
                "purchase_units" => [[
                    "amount" => [
                        "currency_code" => "GBP",
                        "value" => number_format($payAmount, 2, '.', '')
                    ]
                ]],
                "application_context" => [
                    "cancel_url" => "http://localhost/Borrower/cancel.php",
                    "return_url" => "http://localhost/Borrower/loanInstallments.php?insId=" . $insId
                ]
            ];

            $response = $client->execute($request);
            
            if ($response->result->status === 'CREATED') {
                foreach ($response->result->links as $link) {
                    if ($link->rel === 'approve') {
                        header("Location: " . $link->href);
                        exit();
                    }
                }
            }
            
            throw new Exception("PayPal order creation failed. Status: " . $response->result->status);
            
        } catch (Exception $e) {
            error_log("Error processing payment: " . $e->getMessage());
            throw $e;
        }
    }

    public static function InstallmentAmountbyLoanId($loanid)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Validate loanid
            $loanid = filter_var($loanid, FILTER_VALIDATE_INT);
            if ($loanid === false) {
                throw new Exception("Invalid loan ID");
            }

            $query = "SELECT 
                        COALESCE(SUM(payable_amount), 0) AS total_paid,
                        MAX(pay_date) AS last_payment_date 
                     FROM loaninstallments 
                     WHERE loan_id = ? AND status = 'Paid'";

            $stmt = $db->prepare($query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $db->error);
            }

            $stmt->bind_param("i", $loanid);
            $stmt->execute();
            $result = $stmt->get_result();
            $installment = $result->fetch_assoc();
            $stmt->close();

            return $installment ?: ['total_paid' => 0, 'last_payment_date' => null];
            
        } catch (Exception $e) {
            error_log("Error in InstallmentAmountbyLoanId: " . $e->getMessage());
            throw $e;
        }
    }

    public static function loanInstallmentbyLoanId($loanid)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Validate loanid
            $loanid = filter_var($loanid, FILTER_VALIDATE_INT);
            if ($loanid === false) {
                throw new Exception("Invalid loan ID");
            }

            $query = "SELECT * FROM loaninstallments 
                     WHERE loan_id = ? AND status = ?";
                     
            $stmt = $db->prepare($query);
            $status = "Paid";
            $stmt->bind_param("is", $loanid, $status);
            $stmt->execute();
            $result = $stmt->get_result();
            $installments = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return $installments;
            
        } catch (Exception $e) {
            error_log("Error in loanInstallmentbyLoanId: " . $e->getMessage());
            throw $e;
        }
    }

    public static function userLoanInstllments()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("User not logged in");
            }
            
            $id = (int)$_SESSION['user_id'];
            
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "SELECT l.*, u.*, li.*, li.status as inStatus 
                     FROM loans l 
                     INNER JOIN loaninstallments li ON li.loan_id = l.id 
                     INNER JOIN users u ON u.id = li.user_id
                     WHERE li.user_id = ?";
                     
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $installments = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return $installments;
            
        } catch (Exception $e) {
            error_log("Error in userLoanInstllments: " . $e->getMessage());
            throw $e;
        }
    }
}