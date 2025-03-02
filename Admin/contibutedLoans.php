<?php
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication
if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit;
}

// Handle status messages
if (isset($_GET['status'])) {
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        let message = '';
        let icon = 'info';
        
        switch('" . $_GET['status'] . "') {
            case 'already_processed':
                message = 'This loan has already been approved and processed. No further action is needed.';
                icon = 'warning';
                break;
            case 'success':
                message = 'Loan approved and funds transferred successfully!';
                icon = 'success';
                break;
            case 'error':
                message = 'Error processing loan: " . addslashes($_GET['message'] ?? 'An error occurred') . "';
                icon = 'error';
                break;
        }
        
        if (message) {
            Swal.fire({
                title: message,
                icon: icon,
                showConfirmButton: true,
                confirmButtonText: 'Okay',
                confirmButtonColor: '#1e3c72'
            }).then((result) => {
                if (result.isConfirmed) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('status');
                    window.history.replaceState({}, '', url);
                }
            });
        }
    });
    </script>";
}

// Handle any messages/errors passed via GET
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}

// Handle loan approval
if (isset($_POST['accept'])) {
    try {
        error_log("Starting loan approval process");
        error_log("POST data received: " . print_r($_POST, true));

        $loanId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        
        // Get database connection
        $conn = Database::getConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Check loan status and funding
        $statusCheck = $conn->prepare("
            SELECT l.status, l.loanAmount, l.user_id, l.TotalLoan,
                   COALESCE(SUM(lc.LoanAmount), 0) as total_funded
            FROM loans l
            LEFT JOIN lendercontribution lc ON l.id = lc.loanId
            WHERE l.id = ?
            GROUP BY l.id
        ");
        $statusCheck->bind_param("i", $loanId);
        $statusCheck->execute();
        $loanInfo = $statusCheck->get_result()->fetch_assoc();

        if (!$loanInfo) {
            throw new Exception("Loan not found");
        }

        // Verify loan is accepted and fully funded
        if ($loanInfo['status'] !== 'Completed') {
            throw new Exception("Loan must be Completed before funding");
        }

        if ($loanInfo['total_funded'] < $loanInfo['loanAmount']) {
            throw new Exception("Loan amount not fully funded");
        }

        // Check for existing transfer
        $transferCheck = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM loan_transfers 
            WHERE loan_id = ? AND status = 'completed'
        ");
        $transferCheck->bind_param("i", $loanId);
        $transferCheck->execute();
        $transferResult = $transferCheck->get_result()->fetch_assoc();

        if ($transferResult['count'] > 0) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?status=already_processed');
            exit;
        }

        $amount = $loanInfo['loanAmount'];
        $installments = filter_var($_POST['noOfInstallments'], FILTER_SANITIZE_NUMBER_INT);

        // Load and verify Stripe configuration
        $config = require '../config.php';
        if (!isset($config['stripe']['admin']['secret_key'])) {
            throw new Exception("Stripe configuration is missing");
        }

        \Stripe\Stripe::setApiKey($config['stripe']['admin']['secret_key']);

        try {
            // Start transaction
            $conn->begin_transaction();

            // Create Stripe transfer
            $transfer = \Stripe\Transfer::create([
                'amount' => (int)($amount * 100),
                'currency' => 'gbp',
                'destination' => 'acct_1QthX6B2UpV2W2vM', // Borrower account ID
                'description' => "Loan disbursement for loan #" . $loanId,
                'metadata' => [
                    'loan_id' => $loanId,
                    'user_id' => $loanInfo['user_id'],
                    'amount' => $amount,
                    'installments' => $installments,
                    'type' => 'loan_disbursement'
                ]
            ]);

            error_log("Stripe transfer created: " . $transfer->id);

            // Update loan status to Funded
            if (!Loan::changeStatus($loanId, 'Funded')) {
                throw new Exception("Failed to update loan status");
            }

            // Record transfer
            $sql = "INSERT INTO loan_transfers (loan_id, amount, stripe_transfer_id, status) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception("Failed to prepare transfer statement: " . $conn->error);
            }

            $status = 'completed';
            $stripe_id = $transfer->id;

            if (!$stmt->bind_param("idss", $loanId, $amount, $stripe_id, $status)) {
                throw new Exception("Failed to bind transfer parameters: " . $stmt->error);
            }

            if (!$stmt->execute()) {
                throw new Exception("Failed to execute transfer statement: " . $stmt->error);
            }

            // Add notification for borrower
            $notificationSql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $notifyStmt = $conn->prepare($notificationSql);
            $notifyMessage = "Your loan has been funded and Rs" . number_format($amount, 2) . " has been disbursed to your account.";
            $notifyStmt->bind_param("is", $loanInfo['user_id'], $notifyMessage);
            $notifyStmt->execute();

            $stmt->close();
            $conn->commit();

            header('Location: ' . $_SERVER['PHP_SELF'] . '?status=success');
            exit;

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $conn->rollback();
            error_log("Stripe Error: " . $e->getMessage());
            header('Location: ' . $_SERVER['PHP_SELF'] . '?status=error&message=' . urlencode($e->getMessage()));
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("General Error: " . $e->getMessage());
        header('Location: ' . $_SERVER['PHP_SELF'] . '?status=error&message=' . urlencode($e->getMessage()));
        exit;
    }
}

$errors = [];
$id = $_SESSION['user_id'];
$loans = Loan::allContributedLoans();

include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<!-- Custom CSS -->
<style>
    .dashboard-container {
        background-color: #f8f9fa;
        min-height: 100vh;
        padding: 2rem;
    }
    .card-loan {
        transition: transform 0.2s;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .card-loan:hover {
        transform: translateY(-5px);
    }
    .loan-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 10px 10px 0 0;
    }
    .table-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .custom-table {
        margin: 0;
    }
    .custom-table thead th {
        background: #1e3c72;
        color: white;
        padding: 1rem;
        font-weight: 500;
    }
    .custom-btn {
        border-radius: 20px;
        padding: 0.5rem 1.2rem;
        transition: all 0.3s;
    }
    .modal-custom {
        border-radius: 15px;
    }
    .modal-custom .modal-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }
    .user-image {
        border-radius: 50%;
        border: 3px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($error)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="loan-header mb-4">
        <h2 class="mb-0 text-white">Contributed Loan Applications</h2>
        <p class="text-light mb-0 mt-2">Manage and review all contributed loan applications</p>
    </div>

    <div class="table-container p-4">
        <table id="example" class="table custom-table table-hover">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Contact Details</th>
                    <th>Loan Details</th>
                    <th>Employment</th>
                    <th>Purpose</th>
                    <th>Contribution</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($loans) && count($loans) > 0): ?>
                    <?php foreach ($loans as $loan): ?>
                        <?php
                            $totalAmount = $loan['loanAmount'] +
                                (($loan['noOfInstallments']/12) * $loan['loanAmount'] * ($loan['interstRate'] / 100)) +
                                ($loan['loanAmount'] * (2 / 100));
                            
                            $monthly = $loan['noOfInstallments'] > 0 
                                ? ($totalAmount / $loan['noOfInstallments']) 
                                : 0;
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img
                                        class="user-image me-3" 
                                        style="width: 50px; height: 50px;"
                                        src="../<?php echo htmlspecialchars($loan['image']); ?>"
                                        alt="User Image"
                                        onerror="this.onerror=null; this.src='../uploads/users/default/download.png';"
                                    >
                                    <div>
                                        <strong><?php echo htmlspecialchars($loan['name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <i class="fas fa-phone me-2"></i>
                                    <?php echo htmlspecialchars($loan['mobile']); ?>
                                </div>
                                <div>
                                    <i class="fas fa-envelope me-2"></i>
                                    <?php echo htmlspecialchars($loan['email']); ?>
                                </div>
                                <div>
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($loan['address']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold">
                                    $<?php echo number_format($loan['loanAmount'], 2); ?>
                                </div>
                                <div class="text-muted">
                                    <?php echo htmlspecialchars($loan['noOfInstallments']); ?> installments
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($loan['employeementTenure']); ?> years
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($loan['loanPurpose']); ?>
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div 
                                        class="progress-bar bg-success"
                                        role="progressbar"
                                        style="width: <?php echo htmlspecialchars($loan['totalLoanPercent']); ?>%"
                                        aria-valuenow="<?php echo htmlspecialchars($loan['totalLoanPercent']); ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    >
                                        <?php echo htmlspecialchars(number_format($loan['totalLoanPercent'], 2)); ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button 
                                        type="button" 
                                        class="btn btn-primary custom-btn" 
                                        data-bs-toggle="modal"
                                        data-bs-target="#loan-<?php echo htmlspecialchars($loan['id']); ?>"
                                    >
                                        <i class="fas fa-info-circle me-1"></i> Details
                                    </button>

                                    <?php if (number_format(ceil($loan['totalLoanPercent']),0) >= 100 && $loan['status'] === 'Completed'): ?>
                                        <form 
                                            id="approveLoanForm-<?php echo $loan['id']; ?>"
                                            method="post" 
                                            action=""
                                            class="d-inline"
                                        >
                                            <input type="hidden" name="accept" value="1">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($loan['user_id']); ?>">
                                            <input type="hidden" name="monthly" value="<?php echo htmlspecialchars($monthly); ?>">
                                            <input type="hidden" name="noOfInstallments" value="<?php echo htmlspecialchars($loan['noOfInstallments']); ?>">
                                            <input type="hidden" name="totalLoanAmount" value="<?php echo htmlspecialchars($totalAmount); ?>">

                                            <button 
                                                type="button"
                                                class="btn btn-success custom-btn ms-2"
                                                onclick="confirmApproval('<?php echo $loan['id']; ?>')"
                                            >
                                                <i class="fas fa-check me-1"></i> Fund Loan
                                            </button>
                                        </form>
                                    <?php elseif ($loan['status'] === 'Funded'): ?>
                                        <span class="badge bg-success ms-2">Funded</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal for Loan Details -->
                        <div 
                            class="modal fade" 
                            id="loan-<?php echo htmlspecialchars($loan['id']); ?>" 
                            tabindex="-1" 
                            aria-hidden="true"
                        >
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content modal-custom">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-file-invoice me-2"></i>
                                            Loan Application Details
                                        </h5>
                                        <button 
                                            type="button" 
                                            class="btn-close btn-close-white" 
                                            data-bs-dismiss="modal" 
                                            aria-label="Close"
                                        ></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">Applicant Information</h6>
                                                        <ul class="list-unstyled">
                                                            <li class="mb-2">
                                                                <strong>Name:</strong> 
                                                                <?php echo htmlspecialchars($loan['name']); ?>
                                                            </li>
                                                            <li class="mb-2">
                                                                <strong>Annual Income:</strong> 
                                                                Rs <?php echo number_format($loan['AnnualIncome'], 2); ?>
                                                            </li>
                                                            <li class="mb-2">
                                                                <strong>Risk Grade:</strong> 
                                                                <span class="badge bg-<?php 
                                                                    echo $loan['grade'] === 'A' 
                                                                        ? 'success' 
                                                                        : ($loan['grade'] === 'B' 
                                                                            ? 'warning' 
                                                                            : 'danger');
                                                                ?>">
                                                                    <?php echo htmlspecialchars($loan['grade']); ?>
                                                                </span>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">Loan Details</h6>
                                                        <ul class="list-unstyled">
                                                            <li class="mb-2">
                                                                <strong>Principal Amount:</strong> 
                                                                Rs <?php echo number_format($loan['loanAmount'], 2); ?>
                                                            </li>
                                                            <li class="mb-2">
                                                                <strong>Interest Rate:</strong> 
                                                                <?php echo htmlspecialchars($loan['interstRate']); ?>%
                                                            </li>
                                                            <li class="mb-2">
                                                                <strong>Term:</strong> 
                                                                <?php echo htmlspecialchars($loan['noOfInstallments']); ?> months
                                                            </li>
                                                            <li class="mb-2">
                                                                <strong>Monthly Payment:</strong> 
                                                                Rs <?php echo number_format($monthly, 2); ?>
                                                            </li>
                                                            <li class="mb-2">
                                                                <strong>Total Amount:</strong> 
                                                                Rs <?php echo number_format($totalAmount, 2); ?>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">Loan Timeline</h6>
                                                        <div class="d-flex justify-content-between">
                                                            <div>
                                                                <strong>Start Date:</strong><br>
                                                                <?php 
                                                                    echo (new DateTime($loan['requested_at']))
                                                                        ->format('Y-m-d'); 
                                                                ?>
                                                            </div>
                                                            <div>
                                                                <strong>End Date:</strong><br>
                                                                <?php 
                                                                    $enddate = new DateTime($loan['requested_at']);
                                                                    $enddate->modify('+' . $loan['noOfInstallments'] . ' months');
                                                                    echo $enddate->format('Y-m-d'); 
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">Purpose</h6>
                                                        <p class="mb-0">
                                                            <?php echo htmlspecialchars($loan['loanPurpose']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <?php 
                                                        $loanId = $loan['id'];
                                                                                                                require_once('./modal/lenderinfo.php'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<!-- DataTable Initialization and Custom Scripts -->
<script>
$(document).ready(function() {
    $('#example').DataTable({
        responsive: true,
        pageLength: 10,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search loans...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ loans",
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                last: '<i class="fas fa-angle-double-right"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                previous: '<i class="fas fa-angle-left"></i>'
            }
        },
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control');
            $('.dataTables_length select').addClass('form-select');
        }
    });
});

function confirmApproval(loanId) {
    Swal.fire({
        title: 'Confirm Loan Funding',
        text: 'Are you sure you want to fund this loan? This action will transfer funds to the borrower.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Fund Loan',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing',
                text: 'Initiating fund transfer...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            document.getElementById('approveLoanForm-' + loanId).submit();
        }
    });
}

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
</script>