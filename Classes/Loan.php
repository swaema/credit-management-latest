<?php
if (file_exists('../Classes/Mail.php')) {
    include '../Classes/Mail.php';
} elseif (file_exists('Classes/Mail.php')) {
    include 'Classes/Mail.php';
} elseif (file_exists('Mail.php')) {
    include 'Mail.php';
}
require_once '../Classes/Stripe.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPal\PayoutsSDK\Payouts\PayoutsPostRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;

class Loan
{
    public $id;

    public $user_id;
    public $annualIncome;
    public $monthlySalary;
    public $loanamount;
    public $purpose;
    public $employementTenure;
    public $collteral;
    public $consent;
    public $status;
    public $requested_at;
    public $term;
    public $interst;
    public $termId;
    public $name;
    public $email;
    public $mobile;
    public $image;
    public $address;
    public $installments;
    public $grade;
    public $totalloan;



    public function __construct(

        $name = null,
        $email = null,
        $mobile = null,
        $image = null,
        $address = null,

        $id = null,
        $user_id = null,
        $annualIncome = null,
        $monthlySalary = null,
        $loanamount = null,
        $purpose = null,
        $employementTenure = null,
        $collteral = null,
        $consent = null,
        $status = null,
        $requested_at = null,
        $interst = null,
        $term = null,
        $termId = null,
        $installments = null,
        $grade = null,
        $totalloan=null
    ) {

        $this->id = $id;
        $this->user_id = $user_id;
        $this->annualIncome = $annualIncome;
        $this->monthlySalary = $monthlySalary;
        $this->loanamount = $loanamount;
        $this->purpose = $purpose;
        $this->termId = $termId;
        $this->employementTenure = $employementTenure;
        $this->collteral = $collteral;
        $this->consent = $consent;
        $this->status = $status;
        $this->requested_at = $requested_at;
        $this->interst = $interst;
        $this->term = $term;
        $this->name = $name;
        $this->email = $email;
        $this->mobile = $mobile;
        $this->image = $image;
        $this->address = $address;
        $this->installments = $installments;
        $this->grade = $grade;
        $this->totalloan=$totalloan;
    }
    public function dumpConstructorValues()
    {
        // var_dump($this->termId);
        // exit;
        // Using var_dump
        var_dump($this);
        exit;
        // Or you can use print_r for more readable output
        // print_r($this);
    }


    private function validateLoanFields()
    {
        // Check if any required field is empty
        if (
            empty($this->user_id) || empty($this->loanamount) || empty($this->annualIncome) || empty($this->monthlySalary) ||
            empty($this->purpose) || empty($this->employementTenure) || empty($this->consent)
        ) {
            return ("All required fields must be filled.");
        }

        // Validate the loan amount
        if (!is_numeric($this->loanamount) || $this->loanamount <= 0) {
            throw new Exception("Loan amount must be a positive number.");
        }

        // Validate the annual income
        if (!is_numeric($this->annualIncome) || $this->annualIncome <= 0) {
            throw new Exception("Annual income must be a positive number.");
        }

        // Validate the monthly salary
        if (!is_numeric($this->monthlySalary) || $this->monthlySalary <= 0) {
            throw new Exception("Monthly salary must be a positive number.");
        }
        if (!is_numeric($this->termId) || $this->termId <= 0) {
            throw new Exception("Term not selected");
        }

        // Validate the loan purpose
        if (strlen($this->purpose) > 255) {
            throw new Exception("Loan purpose must not exceed 255 characters.");
        }

        // Validate the employment tenure (must be a positive number)
        if (!is_numeric($this->employementTenure) || $this->employementTenure <= 0) {
            throw new Exception("Employment tenure must be a positive number.");
        }

        // Collateral is optional but must not exceed 255 characters if provided
        if (!empty($this->collteral) && strlen($this->collteral) > 255) {
            throw new Exception("Collateral description must not exceed 255 characters.");

        }
        // $this->dumpConstructorValues();
    }

    // Validate the consent (must be either true or 1)



    public static function delete($id, $amount, $email)
    {
        try {

            $db = Database::getConnection(); // Get the database connection

            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Prepare the DELETE SQL query
            $query = "DELETE FROM loans WHERE id = ?";

            $stmt = $db->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing query: " . $db->error);
            }

            // Bind the id parameter to the query
            $stmt->bind_param("i", $id);

            // Execute the query
            if ($stmt->execute()) {
                // Successful deletion

                $subject = "Your Loan Interest Rate Has Been Rejected";
                $body = "
                Dear Customer,
            
                We regret to inform you that your loan application for the amount of \${$amount} has been rejected.
            
                Please feel free to contact us if you have any questions or need further clarification regarding your application.
            
                Thank you for considering us for your financial needs.
            
                Best regards,
                SafeFund Management
            ";


                $mail = Mail::sendMail($subject, $body, $email);
            }



        } catch (Exception $e) {
            error_log("Error deleting loan: " . $e->getMessage());
            return false; // Return false on failure
        }
    }
    public static function AcceptLoanbyAdmin($id, $amount, $email)
    {
        try {
            date_default_timezone_set('Indian/Mauritius');
            $accepteddate = date('Y-m-d');
            $loaninfo = Loan::getLoanById($id);
            $totalamount = $loaninfo['TotalLoan'];
            $noofinstallaments = $loaninfo['noOfInstallments'];
            $installament = $totalamount/$noofinstallaments;

            $db = Database::getConnection(); // Get the database connection

            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Prepare the UPDATE SQL query
            $updateQuery = "UPDATE loans SET status=?,InstallmentAmount=?,Accepted_Date=? WHERE id = ?";

            // Prepare the statement for updating the status
            $updateStmt = $db->prepare($updateQuery);
            $newStatus = "Accepted";

            // Bind the parameters (status and id)
            $updateStmt->bind_param("sssi", $newStatus,$installament,$accepteddate, $id);

            // Execute the query and check if it's successful
            if ($updateStmt->execute()) {
                // Successful update, send the approval email
                $subject = "Congratulations! Your Loan Application Has Been Approved";
                $body = "
                Dear Customer,
                
                We are pleased to inform you that your loan application for the amount of \${$amount} has been successfully approved.
                
                Our team will be in touch shortly with the next steps and the details of your loan disbursement. If you have any questions or require further assistance, please don't hesitate to contact us.
                
                Thank you for choosing us for your financial needs, and we look forward to serving you.
                
                Best regards,
                SafeFund Management
                ";

                // Send the email
                $mail = Mail::sendMail($subject, $body, $email);

                // Optional: check if email was successfully sent
                if (!$mail) {
                    throw new Exception("Email failed to send.");
                }

            } else {
                // Query failed
                throw new Exception("Failed to update loan status.");
            }

        } catch (Exception $e) {
            // Log the error with the correct message
            error_log("Error updating loan status: " . $e->getMessage());
            return false; // Return false on failure
        }

        return true; // Return true on success
    }

    public static function interstRate($id, $rate, $email)
    {

        $db = Database::getConnection();

        if ($db === null) {
            throw new Exception("Database connection failed");
        }


        $updateQuery = "UPDATE loans SET interstRate = ?,status=? WHERE id = ?";

        // Prepare the statement for updating the status
        $updateStmt = $db->prepare($updateQuery);
        $newStatus = "updated";

        $updateStmt->bind_param("isi", $rate, $newStatus, $id);
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to execute statement: " . $updateStmt->error);
        }
        $subject = "Your Loan Interest Rate Has Been Updated";
        $body = "
        Dear Customer,
    
        We would like to inform you that the interest rate on your loan has been updated.
    
        - New Interest Rate: {$rate}%
    
        This change affects the overall repayment terms of your loan. Please review your updated loan details and contact us if you have any questions or concerns.
    
        Thank you for your attention to this matter.
    
        Best regards,
        SafeFund Management
    ";


        $mail = Mail::sendMail($subject, $body, $email);


        if ($mail) {
            return "Interest Rate Updated";
        }
    }

    public function saveLoan()
    {
        try {

            // $this->dumpConstructorValues();
            $db = Database::getConnection();
            // var_dump($db);
            // exit;
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $this->validateLoanFields();
            $query = "INSERT INTO loans (
                `user_id`, `noOfInstallments`, `interstRate`, `grade`, `AnnualIncome`, 
                `loanAmount`, `loanPurpose`, `employeementTenure`, `status`, `requested_at`, `TotalLoan`
              ) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

                $stmt = $db->prepare($query);

                $stmt->bind_param(
                    "iiisiisisi",  // Correct bind types
                    $this->user_id,          // User ID (integer)
                    $this->installments,     // Number of installments (integer)
                    $this->interst,          // Interest Rate (string)
                    $this->grade,            // Grade (string)
                    $this->annualIncome,     // Annual Income (float/double)
                    $this->loanamount,       // Loan Amount (float/double)
                    $this->purpose,          // Loan Purpose (string)
                    $this->employementTenure,// Employment Tenure (string)
                    $this->status,           // Loan Status (string)
                    $this->totalloan         // Total Loan (float/double)
                );




            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);

            }


            if (!$this->id) {
                $this->id = $stmt->insert_id; // Get the last inserted ID if necessary
            }

            $stmt->close();


            return "Loan saved successfully.";
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;

        }
    }

    
    public static function newLenderPaymentMethod($amount)
    {
        try {
            $paypal = require_once('../Classes/paypal_config.php');
            // Step 1: Deduct money from lender's PayPal account and send to admin
            $payouts = new PayPal\Api\Payout();
            $senderBatchHeader = new PayPal\Api\PayoutSenderBatchHeader();
            $senderBatchHeader->setSenderBatchId(uniqid())
                ->setEmailSubject("Loan Contribution Successful");

            $senderItem = new PayPal\Api\PayoutItem();
            $senderItem->setRecipientType('EMAIL')
                ->setReceiver("sb-oav43o14717327@business.example.com") // Admin's PayPal sandbox email
                ->setAmount(new PayPal\Api\Currency([
                    'value' => ceil($amount),
                    'currency' => 'GBP'
                ]))
                ->setSenderItemId(uniqid());

            $payouts->setSenderBatchHeader($senderBatchHeader)
                ->addItem($senderItem);

            $output = $payouts->create(null, $paypal);

            // Step 2: Record the transaction in lendadtrans table
            // $stmt = $conn->prepare("INSERT INTO lendadtrans (loan_id, lender_username, admin_username, transaction_id, amount, currency, type, status) 
            //                         VALUES (:loan_id, :lender_username, :admin_username, :transaction_id, :amount, 'USD', 'lender_to_admin', 'completed')");
            // $stmt->execute([
            //     'loan_id' => $loan_id,
            //     'lender_username' => $lender_username,
            //     'admin_username' => 'admin',
            //     'transaction_id' => $output->getBatchHeader()->getPayoutBatchId(),
            //     'amount' => $amount
            // ]);

            // // Step 3: Update loan status if fully funded
            // $stmt = $conn->prepare("SELECT SUM(amount) AS total_funded FROM lendadtrans WHERE loan_id = :loan_id");
            // $stmt->execute(['loan_id' => $loan_id]);
            // $total_funded = $stmt->fetch()['total_funded'];

            // $stmt = $conn->prepare("SELECT amount FROM loans WHERE id = :loan_id");
            // $stmt->execute(['loan_id' => $loan_id]);
            // $loan_amount = $stmt->fetch()['amount'];

            // if ($total_funded >= $loan_amount) {
            //     $conn->prepare("UPDATE loans SET status = 'funded' WHERE id = :loan_id")->execute(['loan_id' => $loan_id]);
            // } else {
            //     $conn->prepare("UPDATE loans SET status = 'partially_funded' WHERE id = :loan_id")->execute(['loan_id' => $loan_id]);
            // }

            // $success = "Contribution successful! $amount has been sent to the admin's PayPal account.";
            return true;
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            var_dump("paypal error".$ex->getMessage());
            exit;
            // $error = "PayPal API Error: " . $ex->getData();
        } catch (Exception $e) {
            var_dump("error".$e->getMessage());
            exit;
            // $error = "Error: " . $e->getMessage();
        }
    }


    public static function contributeLoan($loanId, $lender_id,  $amountContributed)
    {
        try {

            $totalAmount = self::getLoanById($loanId)['loanAmount'];
         $totalloanpercent = self::getContributedLoan($loanId);
    $percentage = ($amountContributed / $totalAmount) * 100;
    if ($totalloanpercent+$percentage>100){
        echo "cannot contribute more than loan asked";
    }
            StripePayment::createOrder($amountContributed,"paymentsuccess/contributesuccess.php","cancel.php","mr test", "this is a description",["loanid" => $loanId, "lenderid" => $lender_id,"percentage" => $percentage]);
       exit();
           


        } catch (Exception $e) {
            var_dump("e");
            exit;


        }
    }
    
    public static function allLoansBorrower($status)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
    
            // Updated query to include loan funding status check
            $query = "
                SELECT l.*, l.id as l_id, u.name, u.email, u.mobile, u.image, u.address 
                FROM loans l
                INNER JOIN users u ON l.user_id = u.id
                WHERE l.status = ? AND l.user_id = ?";
                
            $stmt = $db->prepare($query);
            $stmt->bind_param("si", $status, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
    
            $loans = [];
            while ($data = $result->fetch_assoc()) {
                // Only include loans that can be paid
                if ($status === 'Funded') {
                    // Get installment details for this loan
                    $installmentQuery = "
                        SELECT COUNT(*) as total_installments,
                               COUNT(CASE WHEN status = 'Paid' THEN 1 END) as paid_installments
                        FROM loaninstallments 
                        WHERE loan_id = ?";
                    $instStmt = $db->prepare($installmentQuery);
                    $instStmt->bind_param("i", $data['id']);
                    $instStmt->execute();
                    $installmentData = $instStmt->get_result()->fetch_assoc();
                    
                    // Add installment data to loan details
                    $data['total_installments'] = $installmentData['total_installments'];
                    $data['paid_installments'] = $installmentData['paid_installments'];
                    $data['remaining_installments'] = $installmentData['total_installments'] - $installmentData['paid_installments'];
                }
                $loans[] = $data;
            }
    
            $stmt->close();
            return $loans;
        } catch (Exception $e) {
            error_log("Error fetching loans: " . $e->getMessage());
            return [];
        }
    }
    public static function changeStatus($id, $status)
    {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        $updateQuery = "UPDATE loans SET status = ? WHERE id = ?";

        // Prepare the statement for updating the status
        $updateStmt = $db->prepare($updateQuery);
        // Set the new status to 'Accepted' (or any status you want)


        // Bind parameters for the update: status, loan_id, user_id
        $updateStmt->bind_param("si", $status, $id);

        // var_dump($userId,$loanId);
        // exit;
        if (!$updateStmt->execute()) {
            return false;
        } else {
            return true;
        }

    }

    public static function allLoans($status)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "SELECT l.*, u.name, u.email, u.mobile, u.image, u.address FROM loans l INNER JOIN users u ON l.user_id = u.id WHERE l.status=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();

            $loans = [];
            while ($data = $result->fetch_assoc()) {

                $loans[] = $data;
            }

            $stmt->close();
            return $loans;
        } catch (Exception $e) {
            error_log("Error fetching loans: " . $e->getMessage());
            return [];
        }
    }

    public static function allActiveLoans($user = null)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
    
            $query = "SELECT l.*, u.name, u.email, u.mobile, u.image, u.address FROM loans l INNER JOIN users u ON l.user_id = u.id WHERE (l.status = 'Funded' OR l.status = 'completed' OR l.status = 'Accepted')";
    
            if ($user !== null) {
                $query .= " AND l.user_id = ?";
            }
    
            $stmt = $db->prepare($query);
    
            if ($user !== null) {
                $stmt->bind_param("i", $user);
            }
    
            $stmt->execute();
            $result = $stmt->get_result();
    
            $loans = [];
            while ($data = $result->fetch_assoc()) {
                $loans[] = $data;
            }
    
            $stmt->close();
            return $loans;
        } catch (Exception $e) {
            error_log("Error fetching loans: " . $e->getMessage());
            return [];
        }
    }
    


    public static function allLenderLoans($user)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
    
            $query = "SELECT l.*, u.name, u.email, u.mobile, u.image, u.address, lc.loanamount AS LoanContributed 
                      FROM loans l 
                      INNER JOIN users u ON l.user_id = u.id 
                      INNER JOIN lendercontribution lc ON lc.loanId = l.id 
                      WHERE lc.lenderId = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $user);
            $stmt->execute();
            $result = $stmt->get_result();
    
            $loans = [];
            while ($data = $result->fetch_assoc()) {
                $loans[] = $data;
            }
    
            $stmt->close();
            return $loans;
        } catch (Exception $e) {
            error_log("Error fetching loans: " . $e->getMessage());
            return [];
        }
    }
    


    public static function calculatePercent($id)
    {
        // Get the database connection
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }

        // Step 1: Fetch the total payable_amount from loaninstallments table
        $installmentQuery = "SELECT SUM(payable_amount) AS total_paid FROM loaninstallments WHERE loan_id = ? and status =?";
        $stmt = $db->prepare($installmentQuery);
        $paid = "Paid";
        $stmt->bind_param("is", $id, $paid);
        $stmt->execute();
        $stmt->bind_result($totalPaid);
        $stmt->fetch();
        $stmt->close();


        if ($totalPaid === null) {
            $totalPaid = 0;  // If no installments, assume total paid is 0
        }

        // Step 2: Fetch the loanAmount from the loans table
        $installmentQuery = "SELECT SUM(payable_amount) AS total_paid FROM loaninstallments WHERE loan_id = ?";
        $stmt = $db->prepare($installmentQuery);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($totalinstallment);
        $stmt->fetch();
        $stmt->close();

        if ($totalinstallment === null) {
            $totalinstallment = 0;
        }

        // Step 3: Calculate the percentage of the loan that has been paid
        $percentPaid = ($totalinstallment > 0) ? ($totalPaid / $totalinstallment) * 100 : 0;

        // Return the calculated percentages
        return $percentPaid;
        // Loan percentage paid


    }

    public static function checkLoan()
    {
        // Get database connection
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }

        // Fetch loan installments with overdue pay_date
        $query = "SELECT * FROM loans l 
        INNER JOIN loaninstallments li 
        ON li.loan_id = l.id 
        WHERE li.pay_date < NOW() AND li.`status` != ?";

        $stmt = $db->prepare($query);
        $payStatus = "Paid";

        // Bind the status parameter as a string
        $stmt->bind_param("s", $payStatus);

        $stmt->execute();
        $result = $stmt->get_result();
        $loans = [];
        // Process each result
        while ($data = $result->fetch_assoc()) {
            // Update the status to 'defaulter' if the pay_date is in the past
            $updateQuery = "UPDATE loaninstallments 
                            SET status = 'defaulter' 
                            WHERE loanInstallmentsId = ? AND pay_date < NOW() ";

            $updateStmt = $db->prepare($updateQuery);

            $updateStmt->bind_param("i", $data['loanInstallmentsId'], ); // Assuming 'id' is the primary key of loaninstallments
            $updateStmt->execute();
            $updateStmt->close();

            // Add the loan to the array of loans
            $loans[] = $data;
        }

        $stmt->close();

        return $loans; // Return the loans list
    }


    public static function allLoansByUser($user_id, $status)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
            $query = "SELECT l.*, u.name, u.email, u.mobile, u.image, u.address FROM loans l INNER JOIN users u ON l.user_id = u.id WHERE l.user_id=? AND l.status=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("is", $user_id, $status);
            $stmt->execute();
            $result = $stmt->get_result();

            $loans = [];
            while ($data = $result->fetch_assoc()) {
                $loans[] = $data;
            }


            $stmt->close();
            return $loans;
        } catch (Exception $e) {
            error_log("Error fetching loans: " . $e->getMessage());
            return [];
        }
    }
    // public static function getLoanById($id)
    // {
    //     try {
    //         $db = Database::getConnection();
    //         if ($db === null) {
    //             throw new Exception("Database connection failed");
    //         }
    //         // Modify the query to use loan ID as a parameter
    //         $query = "SELECT l.*, u.name, u.email, u.mobile, u.image, u.address 
    //               FROM loans l 
    //               INNER JOIN users u ON l.user_id = u.id 
    //               WHERE l.id = ?";

    //         $stmt = $db->prepare($query);
    //         $stmt->bind_param("i", $id); // Use the loan ID as the parameter
    //         $stmt->execute();
    //         $result = $stmt->get_result();

    //         // Fetch a single loan result
    //         $loan = $result->fetch_assoc();

    //         $stmt->close();
    //         return $loan;

    //     } catch (Exception $e) {
    //         error_log("Error fetching loan: " . $e->getMessage());
    //         return null;
    //     }
    // }

        /**
     * Get loan details including borrower's Stripe account ID
     * @param int $id The loan ID
     * @return array|null Loan details with stripe_account_id or null if not found
     */
    public static function getLoanById($id) {
        $db = Database::getConnection();
        $sql = "SELECT l.*, u.stripe_account_id 
                FROM loans l 
                JOIN users u ON l.user_id = u.id 
                WHERE l.id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function updateLoan($id, $loanAmount, $term, $noOfInstallments, $interstRate, $annualIncome, $loanPurpose, $employmentTenure)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Prepare the SQL query to update the loan details
            $query = "UPDATE loans 
                  SET loanAmount = ?, term = ?, noOfInstallments = ?, interstRate = ?, AnnualIncome = ?, loanPurpose = ?, employeementTenure = ? 
                  WHERE id = ?";

            // Prepare and bind the statement
            $stmt = $db->prepare($query);
            $stmt->bind_param("iiiiissi", $loanAmount, $term, $noOfInstallments, $interstRate, $annualIncome, $loanPurpose, $employmentTenure, $id);

            // Execute the query
            $stmt->execute();

            // Check if the update was successful
            if ($stmt->affected_rows > 0) {
                $stmt->close();
                return true; // Loan updated successfully
            } else {
                return false; // Loan not updated
            }

            // Close the statement


        } catch (Exception $e) {
            error_log("Error updating loan: " . $e->getMessage());
            return false;
        }
    }

    public static function allContributedLoans()
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "SELECT l.*, u.name, u.email, u.mobile, u.image, u.address,u.id as user_id,
       (SELECT SUM(LoanPercent) 
        FROM lendercontribution lc 
        WHERE lc.loanId = l.id) AS totalLoanPercent
            FROM loans l
            INNER JOIN users u ON l.user_id = u.id
            WHERE l.status = 'Accepted' OR l.status = 'Funded' OR l.status = 'Completed'
            ORDER BY totalLoanPercent DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();

            $loans = [];
            while ($data = $result->fetch_assoc()) {

                $loans[] = $data;
            }

            $stmt->close();
            return $loans;
        } catch (Exception $e) {
            error_log("Error fetching loans: " . $e->getMessage());
            return [];
        }
    }
    public static function getContributedLoan($loanId)
    {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }

        $query = "SELECT SUM(LoanPercent) AS totalLoanPercent FROM `lendercontribution` WHERE loanId = ?";

        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }

        $stmt->bind_param("i", $loanId); // Use the loan ID as the parameter
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch the loan percentage sum
        $loan = $result->fetch_assoc();
        $totalLoanPercent = $loan['totalLoanPercent'] ?? 0; // Handle if null

        $stmt->close();

        return $totalLoanPercent;

    }



    public function deleteLoan()
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "DELETE FROM loans WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $this->id);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error deleting loan: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }
    public static function getActiveLoansCount()
    {
        try {
            $db = Database::getConnection();
            $id = $_SESSION['user_id'];
            $query = "SELECT COUNT(Distinct id) AS count FROM loans where user_id = $id and  status = 'Accepted'";
            $result = $db->query($query);
            return $result->fetch_assoc()['count'];
        } catch (Exception $e) {
            error_log("Error fetching active loans count: " . $e->getMessage());
            return 0;
        }
    }

    public static function getTotalLoansTaken()
    {
        try {
            $db = Database::getConnection();
            $id = $_SESSION['user_id'];
            $query = "SELECT COUNT(Distinct id) AS count FROM loans where user_id = $id";
            $result = $db->query($query);
            return $result->fetch_assoc()['count'];
        } catch (Exception $e) {
            error_log("Error fetching total loans count: " . $e->getMessage());
            return 0;
        }
    }

    public static function getPendingAmount()
    {
        try {
            $db = Database::getConnection();
            $id = $_SESSION['user_id'];
            $query = "SELECT SUM(payable_amount) AS pending FROM loaninstallments WHERE status = 'Pending' and user_id = $id";
            $result = $db->query($query);
            return $result->fetch_assoc()['pending'] ?? 0;
        } catch (Exception $e) {
            error_log("Error fetching pending amount: " . $e->getMessage());
            return 0;
        }
    }

    public static function getOverdueLoansCount()
    {
        try {
            $db = Database::getConnection();
            $id = $_SESSION['user_id'];
            $query = "SELECT SUM(payable_amount) AS overdue FROM loaninstallments WHERE status = 'defaulter' and user_id = $id";

            $result = $db->query($query);
            return $result->fetch_assoc()['overdue'];
        } catch (Exception $e) {
            error_log("Error fetching overdue loans count: " . $e->getMessage());
            return 0;
        }
    }

    public static function getInstallmentCount($loanId)
    {
        try {
            $db = Database::getConnection();
            $query = "SELECT COUNT(*) AS count FROM loaninstallments WHERE loan_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc()['count'];
        } catch (Exception $e) {
            error_log("Error fetching installment count: " . $e->getMessage());
            return 0;
        }
    }

    public static function calculateInstallmentDate($loan_id, $user_id)
    {
        try {
            $db = Database::getConnection();
            $query = "SELECT pay_date FROM loaninstallments 
                      WHERE status = 'Pending' 
                      AND loan_id = ? 
                      AND user_id = ? 
                      ORDER BY pay_date ASC 
                      LIMIT 1";
    
            $stmt = $db->prepare($query);
            $stmt->bind_param("ii", $loan_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows > 0) {
                $paydate = $result->fetch_assoc()['pay_date'];
                $today = date('Y-m-d');
    
                $remarks = (strtotime($today) > strtotime($paydate)) ? "Overdue" : "";
    
                return [
                    "pay_date" => $paydate,
                    "remarks" => $remarks
                ];
            } else {
                return ["message" => "No data found"];
            }
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
    
    

    
}