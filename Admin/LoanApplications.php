<?php

//LoanApplication.php
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/Mail.php';
require_once '../vendor/autoload.php';
require_once("../Classes/Mail.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Set JSON header if this is an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' &&
    isset($_POST['accept'])) {

    header('Content-Type: application/json');

    try {
        // Validate inputs
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

        if (!$id || !$email || !$amount) {
            throw new Exception("Missing required fields");
        }

        $db = Database::getConnection();

        // Get loan details
        $loanQuery = "SELECT l.*, u.name as borrower_name, u.id as borrower_id, 
                      l.noOfInstallments, l.loanPurpose, l.grade, l.TotalLoan,
                      l.InstallmentAmount, l.interstRate
                      FROM loans l 
                      JOIN users u ON l.user_id = u.id 
                      WHERE l.id = ?";
        $stmt = $db->prepare($loanQuery);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $loanDetails = $stmt->get_result()->fetch_assoc();

        if (!$loanDetails) {
            throw new Exception("Loan not found");
        }

        $db->begin_transaction();

        try {
            // Update loan status
            $totalLoan = $loanDetails['TotalLoan'];
            $installments = $loanDetails['noOfInstallments'];
            $monthlyPayment = $totalLoan / $installments;

            // Update loan status
            $updateLoanSql = "UPDATE loans SET 
                status = 'Accepted',
                InstallmentAmount = ?,
                updated_at = NOW()
                WHERE id = ?";

            $stmt = $db->prepare($updateLoanSql);
            $stmt->bind_param("di", $monthlyPayment, $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update loan status");
            }

            // Initialize lender contribution
            $initContribSql = "INSERT INTO lendercontribution 
            (loanId, lenderId, LoanPercent, LoanAmount, RecoveredPrincipal, ReturnedInterest) 
            VALUES (?, 0, 0, 0, 0, 0)";  // Using 0 as a placeholder lenderId for initialization
            $stmt = $db->prepare($initContribSql);
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to initialize loan contribution tracking");
            }

            // Create notification
            $notificationSql = "INSERT INTO notifications (user_id, message, created_at) 
                              VALUES (?, ?, NOW())";
            $notificationMsg = "Your loan application for $" . number_format($loanDetails['loanAmount'], 2) .
                " has been approved. Risk Grade: " . $loanDetails['grade'] .
                ". The loan is now available for funding by lenders.";

            $stmt = $db->prepare($notificationSql);
            $stmt->bind_param("is", $loanDetails['borrower_id'], $notificationMsg);
            if (!$stmt->execute()) {
                throw new Exception("Failed to create notification");
            }

            // Record transaction
            $transactionSql = "INSERT INTO transactions 
                              (user_id, type, amount, status, reference_id) 
                              VALUES (?, 'loan_fee', ?, 'pending', ?)";
            $stmt = $db->prepare($transactionSql);
            $stmt->bind_param("idi", $loanDetails['borrower_id'], $loanDetails['loanAmount'], $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to record transaction");
            }

            $db->commit();

            // Send email
            $subject = "Loan Application Approved";
            $message = "Dear " . $loanDetails['borrower_name'] . ",\n\n" .
                "Your loan application has been approved!\n\n" .
                "Loan Details:\n" .
                "- Loan ID: " . $id . "\n" .
                "- Amount: $" . number_format($loanDetails['loanAmount'], 2) . "\n" .
                "- Interest Rate: " . $loanDetails['interstRate'] . "%\n" .
                "- Term: " . $loanDetails['noOfInstallments'] . " months\n" .
                "- Monthly Payment: $" . number_format($loanDetails['InstallmentAmount'], 2) . "\n" .
                "- Risk Grade: " . $loanDetails['grade'] . "\n\n" .
                "Your loan is now available for funding by lenders. " .
                "You'll be notified once the full amount has been funded.\n\n" .
                "Thank you for choosing our services.";

            Mail::sendMail($subject, $message, $email);

            echo json_encode([
                'success' => true,
                'message' => 'Loan application approved successfully',
                'title' => 'Success!',
                'icon' => 'success'
            ]);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Error processing loan: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'title' => 'Error',
            'icon' => 'error'
        ]);
        exit();
    }
}

// Add this right after session_start()
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST data received: ' . print_r($_POST, true));
}

// Load configuration
$config = require '../config.php';

// Initialize Stripe
\Stripe\Stripe::setApiKey($config['stripe']['admin']['secret_key']);

if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit;
}

$error = isset($_GET['e']) ? $_GET['e'] : '';

// Handle interest rate update
if (isset($_POST['rate'])) {
    try {
        $rate = filter_var($_POST['rateValue'], FILTER_VALIDATE_FLOAT);
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

        if ($rate === false || $id === false || !$email) {
            throw new Exception("Invalid input data");
        }

        $error = Loan::interstRate($id, $rate, $email);
    } catch (Exception $e) {
        $error = "Error updating interest rate: " . $e->getMessage();
    }
}

// Handle loan deletion
if (isset($_POST['deleteLoan'])) {
    try {
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

        if ($id === false || !$email || $amount === false) {
            throw new Exception("Invalid input data");
        }

        $check = Loan::delete($id, $amount, $email);
        if ($check) {
            $error = "Loan Rejected Successfully";
        }
    } catch (Exception $e) {
        $error = "Error rejecting loan: " . $e->getMessage();
    }
}

// Handle loan acceptance and payment
// (We keep this here as a fallback if someone does a non-AJAX post.)
if (isset($_POST['accept'])) {
    // Start output buffering
    ob_start();

    try {
        // Validate inputs
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

        if (!$id || !$email || !$amount) {
            throw new Exception("Missing required fields");
        }

        $db = Database::getConnection();

        // Get loan details with user information
        $loanQuery = "SELECT l.*, u.name as borrower_name, u.id as borrower_id, 
                      l.noOfInstallments, l.loanPurpose, l.grade, l.TotalLoan,
                      l.InstallmentAmount, l.interstRate
                      FROM loans l 
                      JOIN users u ON l.user_id = u.id 
                      WHERE l.id = ?";
        $stmt = $db->prepare($loanQuery);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $loanDetails = $stmt->get_result()->fetch_assoc();

        if (!$loanDetails) {
            throw new Exception("Loan not found");
        }

        $db->begin_transaction();

        try {
            // Update loan status to make it visible to lenders
            $updateLoanSql = "UPDATE loans SET 
                status = 'Accepted',
                InstallmentAmount = ?, 
                updated_at = NOW()
                WHERE id = ?";
            $stmt = $db->prepare($updateLoanSql);

            // Calculate monthly payment
            $totalLoan = $loanDetails['TotalLoan'];
            $installments = $loanDetails['noOfInstallments'];
            $monthlyPayment = $totalLoan / $installments;

            // Bind both the monthly payment and loan ID
            $stmt->bind_param("di", $monthlyPayment, $id);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update loan status");
            }

            // Create initial lendercontribution record with 0%
            $initContribSql = "INSERT INTO lendercontribution 
            (loanId, lenderId, LoanPercent, LoanAmount, RecoveredPrincipal, ReturnedInterest) 
            VALUES (?, 0, 0, 0, 0, 0)";
            $stmt = $db->prepare($initContribSql);
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to initialize loan contribution tracking");
            }

            // Create notification for borrower
            $notificationSql = "INSERT INTO notifications (user_id, message, created_at) 
                              VALUES (?, ?, NOW())";
            $notificationMsg = "Your loan application for $" . number_format($loanDetails['loanAmount'], 2) .
                " has been approved. Risk Grade: " . $loanDetails['grade'] .
                ". The loan is now available for funding by lenders.";

            $stmt = $db->prepare($notificationSql);
            $stmt->bind_param("is", $loanDetails['borrower_id'], $notificationMsg);
            if (!$stmt->execute()) {
                throw new Exception("Failed to create notification");
            }

            // Record the transaction
            $transactionSql = "INSERT INTO transactions 
                              (user_id, type, amount, status, reference_id) 
                              VALUES (?, 'loan_fee', ?, 'pending', ?)";
            $stmt = $db->prepare($transactionSql);
            $stmt->bind_param("idi", $loanDetails['borrower_id'], $loanDetails['loanAmount'], $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to record transaction");
            }

            $db->commit();

            // Send email notification
            $subject = "Loan Application Approved";
            $message = "Dear " . $loanDetails['borrower_name'] . ",\n\n" .
                "Your loan application has been approved!\n\n" .
                "Loan Details:\n" .
                "- Loan ID: " . $id . "\n" .
                "- Amount: $" . number_format($loanDetails['loanAmount'], 2) . "\n" .
                "- Interest Rate: " . $loanDetails['interstRate'] . "%\n" .
                "- Term: " . $loanDetails['noOfInstallments'] . " months\n" .
                "- Monthly Payment: $" . number_format($loanDetails['InstallmentAmount'], 2) . "\n" .
                "- Risk Grade: " . $loanDetails['grade'] . "\n\n" .
                "Your loan is now available for funding by lenders. " .
                "You'll be notified once the full amount has been funded.\n\n" .
                "Thank you for choosing our services.";

            Mail::sendMail($subject, $message, $email);

            // Clear output buffer and send success response
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Loan application approved successfully',
                'title' => 'Success!',
                'icon' => 'success'
            ]);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        // Clear output buffer and send error response
        ob_clean();
        error_log("Error processing loan: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'title' => 'Error',
            'icon' => 'error'
        ]);
        exit;
    }
}

// Ensure transfer table exists
$db = Database::getConnection();
$db->query("CREATE TABLE IF NOT EXISTS loan_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    stripe_transfer_id VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id)
)");

$errors = [];
$id = $_SESSION['user_id'];
$loans = Loan::allLoans("Pending");
$loans = array_merge($loans,Loan::allLoans("updated"));

include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>
<style>
    :root {
        --primary-color: #006d72;
        --secondary-color: #142127;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --light-bg: #f8f9fa;
    }

    .dashboard-container {
        background-color: var(--light-bg);
        min-height: 100vh;
        padding: 2rem;
    }

    .dashboard-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #00959c 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .loan-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .table-custom {
        background: white;
        border-radius: 10px;
    }

    .table-custom thead th {
        background: var(--secondary-color);
        color: white;
        padding: 1rem;
        border: none;
    }

    .applicant-card {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .applicant-image {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .amount-display {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
    }

    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        transition: all 0.3s ease;
    }

    .action-btn:hover {
        transform: translateY(-2px);
    }

    .modal-custom {
        border-radius: 15px;
    }

    .modal-custom .modal-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #00959c 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 1.5rem;
    }

    .modal-custom .modal-content {
        border: none;
        border-radius: 15px;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        height: 100%;
    }

    .detail-row {
        padding: 0.8rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }

    .dropdown-menu {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 0.5rem;
    }

    .dropdown-item {
        border-radius: 5px;
        padding: 0.5rem 1rem;
        margin: 0.2rem 0;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: var(--light-bg);
    }

    .alert {
        border-radius: 10px;
        border: none;
    }

    .payment-form .form-control {
        border-radius: 8px;
        padding: 0.75rem;
    }

    .payment-alert {
        border-left: 4px solid var(--primary-color);
        background-color: rgba(0,109,114,0.1);
        padding: 1rem;
        border-radius: 0 8px 8px 0;
    }
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($error) && !empty(trim($error))): ?>
        <div class="alert <?php echo strpos($error, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h2 class="h3 mb-2">Loan Applications Dashboard</h2>
        <p class="mb-0">Review and manage pending loan applications</p>
    </div>

    <div class="loan-card">
        <div class="table-responsive">
            <table id="example" class="table table-hover table-custom align-middle">
                <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Contact Information</th>
                    <th>Loan Details</th>
                    <th>Employment</th>
                    <th>Purpose</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (is_array($loans) && count($loans) > 0): ?>
                    <?php foreach ($loans as $loan):
                        $totalAmount = $loan['loanAmount'] +
                            (($loan['noOfInstallments'] / 12) * $loan['loanAmount'] * ($loan['interstRate'] / 100)) +
                            ($loan['loanAmount'] * (2 / 100));
                        ?>
                        <tr>
                            <td>
                                <div class="applicant-card">
                                    <img class="applicant-image"
                                         src="../<?php echo htmlspecialchars($loan['image']); ?>"
                                         alt="<?php echo htmlspecialchars($loan['name']); ?>"
                                         onerror="this.src='../uploads/users/default/download.png'">
                                    <div>
                                        <strong><?php echo htmlspecialchars($loan['name']); ?></strong>
                                        <div class="text-muted small">ID: <?php echo htmlspecialchars($loan['id']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($loan['mobile']); ?></div>
                                <div><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($loan['email']); ?></div>
                                <div><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($loan['address']); ?></div>
                            </td>
                            <td>
                                <div class="amount-display">Rs<?php echo number_format($totalAmount, 2); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($loan['noOfInstallments']); ?> installments</div>
                                <div class="text-muted small"><?php echo htmlspecialchars($loan['interstRate']); ?>% interest</div>
                            </td>
                            <td>
                                <span class="badge bg-info rounded-pill"><?php echo htmlspecialchars($loan['employeementTenure']); ?> years</span>
                            </td>
                            <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                            <td>
                                <span class="status-badge bg-warning"><?php echo htmlspecialchars($loan['status']) ?></span>
                            </td>
                            <?php 
                            if ($loan['status'] == 'Pending'){
                            ?>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm action-btn dropdown-toggle"
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog me-1"></i> Manage
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button class="dropdown-item" data-bs-toggle="modal"
                                                    data-bs-target="#loan-<?php echo htmlspecialchars($loan['id']); ?>">
                                                <i class="fas fa-eye me-2"></i> View Details
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item"
                                                    onclick="showEditInterestModal('<?php echo htmlspecialchars($loan['id']); ?>')">
                                                <i class="fas fa-percent me-2"></i> Edit Interest Rate
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-success"
                                                    onclick="showApprovalModal(
                                                            '<?php echo htmlspecialchars($loan['id']); ?>',
                                                            '<?php echo htmlspecialchars($loan['email']); ?>',
                                                            '<?php echo $loan['loanAmount']; ?>',
                                                            '<?php echo htmlspecialchars($loan['grade']); ?>',
                                                            '<?php echo htmlspecialchars($loan['loanPurpose']); ?>'
                                                        )">
                                                <i class="fas fa-check-circle me-2"></i> Approve Application
                                            </button>
                                        </li>
                                        <li>
                                            <form method="post" action="" onsubmit="return confirmReject();" class="dropdown-item">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                <input type="hidden" name="amount" value="<?php echo $loan['loanAmount']; ?>">
                                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($loan['email']); ?>">
                                                <button type="submit" class="btn btn-link text-danger p-0 w-100 text-start" name="deleteLoan">
                                                    <i class="fas fa-times-circle me-2"></i> Reject Loan
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Loan Details Modal -->
                                <div class="modal fade modal-custom" id="loan-<?php echo htmlspecialchars($loan['id']); ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-file-invoice me-2"></i>
                                                    Loan Application Details
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="row g-4">
                                                    <div class="col-md-6">
                                                        <div class="stat-card">
                                                            <h6 class="text-primary mb-4">Applicant Information</h6>
                                                            <div class="detail-row">
                                                                <span>Name</span>
                                                                <strong><?php echo htmlspecialchars($loan['name']); ?></strong>
                                                            </div>
                                                            <div class="detail-row">
                                                                <span>Contact</span>
                                                                <strong><?php echo htmlspecialchars($loan['mobile']); ?></strong>
                                                            </div>
                                                            <div class="detail-row">
                                                                <span>Email</span>
                                                                <strong><?php echo htmlspecialchars($loan['email']); ?></strong>
                                                            </div>
                                                            <div class="detail-row">
                                                                <span>Risk Category</span>
                                                                <span class="badge bg-<?php
                                                                echo $loan['grade'] == 'A' ? 'success' :
                                                                    ($loan['grade'] == 'B' ? 'warning' : 'danger');
                                                                ?>">
                                                                        Grade <?php echo htmlspecialchars($loan['grade']); ?>
                                                                    </span>
                                                            </div>
                                                            <div class="detail-row">
                                                                <span>Annual Income</span>
                                                                <strong>Rs<?php echo number_format($loan['AnnualIncome'], 2); ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="stat-card">
                                                            <h6 class="text-primary mb-4">Loan Information</h6>
                                                            <div class="detail-row">
                                                                <span>Principal Amount</span>
                                                                <strong>Rs<?php echo number_format($loan['loanAmount'], 2); ?></strong>
                                                            </div>
                                                            <div class="detail-row">
                                                                <span>Interest Rate</span>
                                                                <strong><?php echo $loan['interstRate']; ?>%</strong>
                                                            </div>
                                                            <div class="detail-row">
                                                                <span>Total Amount</span>
                                                                <strong>Rs<?php echo number_format($totalAmount, 2); ?></strong>
                                                            </div>
                                                            <div class="detail-row">
                                                                <span>Monthly Payment</span>
                                                                <strong>Rs<?php echo number_format($totalAmount / $loan['noOfInstallments'], 2); ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Interest Rate Modal -->
                                <div class="modal fade modal-custom" id="editInterestRate-<?php echo htmlspecialchars($loan['id']) ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-percent me-2"></i>
                                                    Edit Interest Rate
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <form action="" method="post" class="interest-rate-form">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($loan['email']); ?>">

                                                    <div class="mb-4">
                                                        <label class="form-label">Interest Rate (%)</label>
                                                        <input type="number"
                                                               class="form-control"
                                                               name="rateValue"
                                                               step="0.01"
                                                               min="0"
                                                               max="100"
                                                               value="<?php echo htmlspecialchars($loan['interstRate']); ?>"
                                                               required>
                                                    </div>

                                                    <div class="text-end">
                                                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="rate" class="btn btn-primary">Update Rate</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <?php }else{ ?>
<td></td>
                                <?php } ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Approval Modal (only one confirmation) -->
<div class="modal fade modal-custom" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">
                    <i class="fas fa-check-circle me-2"></i>
                    Confirm Loan Application Approval
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="approval-form" method="post" action="process_loan_approval.php" class="approval-form">
                    <input type="hidden" name="id" id="loan-id">
                    <input type="hidden" name="email" id="loan-email">
                    <input type="hidden" name="amount" id="loan-amount">
                    <input type="hidden" name="stripe_account" id="stripe-account" value="acct_1Qiui44dkpK0VyUu">

                    <div class="mb-4">
                        <label class="form-label fw-bold">Application Details</label>
                        <div class="bg-light p-3 rounded">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <strong>Requested Amount:</strong><br/>
                                    <span id="display-amount"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Risk Grade:</strong><br/>
                                    <span id="display-grade" class="badge"></span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-12">
                                    <strong>Borrower:</strong><br/>
                                    <span id="display-email"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <strong>Purpose:</strong><br/>
                                    <span id="display-purpose"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-4">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1"></i>
                            <div>
                                <strong>What happens after approval:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Loan becomes visible to potential lenders</li>
                                    <li>Lenders can start contributing to the loan</li>
                                    <li>Borrower will be notified of the approval</li>
                                    <li>No funds will be disbursed until fully funded</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="accept" class="btn btn-primary action-btn">
                            <i class="fas fa-check me-2"></i>
                            Approve Application
                        </button>
                        <button type="button" class="btn btn-light action-btn" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<script>
// Initialize DataTable
$(document).ready(function () {
    $('#example').DataTable({
        order: [[6, 'desc']], // Sort by application date by default
        pageLength: 10,
        responsive: true
    });

    // Initialize all tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Function to confirm loan rejection
function confirmReject() {
    return Swal.fire({
        title: 'Confirm Rejection',
        text: 'Are you sure you want to reject this loan application?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reject it'
    }).then((result) => {
        return result.isConfirmed;
    });
}

// Function to show edit interest rate modal
function showEditInterestModal(loanId) {
    const modalElement = document.getElementById(`editInterestRate-${loanId}`);
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

// Function to show approval modal
function showApprovalModal(id, email, amount, grade, purpose) {
    // Set form values
    document.getElementById('loan-id').value = id;
    document.getElementById('loan-email').value = email;
    document.getElementById('loan-amount').value = amount;

    // Set display values
    document.getElementById('display-amount').textContent =
    'Rs ' + new Intl.NumberFormat('en-GB').format(amount);
    document.getElementById('display-email').textContent = email;
    document.getElementById('display-purpose').textContent = purpose;

    // Set risk grade with appropriate color
    const gradeSpan = document.getElementById('display-grade');
    gradeSpan.textContent = `Grade ${grade}`;
    gradeSpan.className = `badge bg-${
        grade === 'A' ? 'success' :
        grade === 'B' ? 'warning' :
        grade === 'C' ? 'danger' : 'secondary'
    }`;

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
    modal.show();
}

// Handle approval form submission (single confirmation via the modal)
document.getElementById('approval-form').addEventListener('submit', async function(event) {
    event.preventDefault();

    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

    try {
        const formData = new FormData(this);

        const response = await fetch('process_loan_approval.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Response Text:', responseText);
            throw new Error('Invalid server response');
        }

        if (data.error) {
            throw new Error(data.error);
        }

        // Show success alert
        await Swal.fire({
            title: data.title || 'Success!',
            text: data.message || 'Loan application has been approved successfully',
            icon: data.icon || 'success',
            confirmButtonColor: '#28a745'
        });

        window.location.reload();

    } catch (error) {
        console.error('Error:', error);

        // Show error alert
        await Swal.fire({
            title: 'Error!',
            text: error.message || 'Failed to approve application. Please try again.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-check me-2"></i>Approve Application';
    }
});

// Handle interest rate form submission
document.querySelectorAll('.interest-rate-form').forEach(form => {
    form.addEventListener('submit', function(event) {
        if (!confirm('Are you sure you want to update the interest rate?')) {
            event.preventDefault();
            return false;
        }
    });
});
</script>

