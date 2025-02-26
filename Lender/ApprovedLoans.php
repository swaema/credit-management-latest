<?php

use Kint\Kint;
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/LoanInstallments.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
if (!UserAuth::isLenderAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit();
}

// Initialize variables
$error = isset($_GET['e']) ? $_GET['e'] : '';
$id = $_SESSION['user_id'];
$loans = Loan::allLenderLoans($id);

// Include layout files
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<!-- Custom CSS for Enhanced UI (Modal styles remain unchanged) -->
<style>
    body {
        background-color: #f8f9fa;
    }
    .dashboard-header {
        background: linear-gradient(135deg, #142127 0%, #2a454f 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        text-align: center;
    }
    .dashboard-header h2 {
        margin: 0;
        font-weight: 600;
    }
    .loan-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .table thead tr {
        background-color: #142127;
        color: white;
    }
    .table tbody tr:hover {
        background-color: #f1f1f1;
    }
    /* Keep modal styles as before */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1050;
        width: 100%;
        height: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        outline: 0;
        background: rgba(0, 0, 0, 0);
    }
    .modal-open .modal {
        overflow-x: hidden;
        overflow-y: auto;
    }
    .modal-dialog {
        position: relative;
        width: auto;
        margin: 1.75rem auto;
        max-width: 600px;
        pointer-events: auto;
    }
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1040;
        width: 100vw;
        height: 100vh;
        background-color: #000;
    }
    .modal-backdrop.show {
        opacity: 0.5;
    }
    .modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        background-color: #fff;
        border: none;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        outline: 0;
    }
    .progress {
        background-color: rgba(0, 0, 0, 0.1);
    }
    .table th {
        font-weight: 600;
    }
    .btn-group .btn {
        margin: 0 2px;
    }
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .badge {
        font-weight: 500;
        padding: 0.5em 0.8em;
    }
    /* Additional styling for action buttons */
    .action-btn {
        border-radius: 20px;
        padding: 0.5rem 1rem;
        transition: all 0.3s;
    }
    .contact-info {
        font-size: 0.9rem;
        color: #666;
    }
    .contact-info i {
        width: 20px;
        color: #142127;
    }
</style>

<div class="col-md-10 container">
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h2>Current Active Loans</h2>
        <p>Review your current invested loans and view details or payment history</p>
    </div>

    <div class="loan-card">
        <div class="table-responsive">
            <table id="example" class="table table-bordered table-hover">
                <thead>
                <tr class="text-center">
                    <th>Applicant</th>
                    <th>Contact</th>
                    <th>Amount Contributed</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $profit = 0;
                if (is_array($loans) && count($loans) > 0):
                    foreach ($loans as $loan):
                        $interest = ($loan['loanAmount'] * $loan['interstRate']) / 100 - ($loan['loanAmount'] * (2 / 100));
                        $profit += $interest;
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img class="user-avatar me-2"
                                         src="../<?php echo htmlspecialchars($loan['image']); ?>"
                                         alt="<?php echo htmlspecialchars($loan['name']); ?>"
                                         onerror="this.src='../uploads/users/default/download.png';">
                                    <div>
                                        <strong><?php echo htmlspecialchars($loan['name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($loan['mobile']); ?></div>
                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($loan['email']); ?></div>
                                    <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($loan['address']); ?></div>
                                </div>
                            </td>
                            <td class="text-end align-middle"><?php echo number_format($loan['LoanContributed'], 2); ?></td>
                                                        <td class="align-middle"><?php echo date('Y-m-d', strtotime($loan['requested_at'])); ?></td>
                            <td class="text-center align-middle">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#historyModal<?php echo $loan['id']; ?>">
                                        <i class="fas fa-history me-1"></i> History
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm action-btn text-white ms-2" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $loan['id']; ?>">
                                        <i class="fas fa-info-circle me-1"></i> Details
                                    </button>
                                </div>

                                <!-- History Modal (as before) -->
                                <div class="modal fade" id="historyModal<?php echo $loan['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Payment History</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <?php $loanInstallments = LoanInstallments::loanInstallmentbyLoanId($loan['id']); ?>
                                                <div class="table-responsive">
                                                    <table class="table table-hover mb-0">
                                                        <thead>
                                                        <tr>
                                                            <th>Amount</th>
                                                            <th>Due Date</th>
                                                            <th>Status</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <?php if (!empty($loanInstallments)): ?>
                                                            <?php foreach ($loanInstallments as $installment): ?>
                                                                <tr>
                                                                    <td><?php echo number_format($installment['payable_amount'], 2); ?></td>
                                                                    <td><?php echo date('Y-m-d', strtotime($installment['pay_date'])); ?></td>
                                                                    <td>
                                                                        <span class="badge rounded-pill <?php echo $installment['status'] == 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                                                            <?php echo ucfirst($installment['status']); ?>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="3" class="text-center">No payment history available</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Details Modal (as before) -->
                                <div class="modal fade" id="detailsModal<?php echo $loan['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header" style="background-color: #006d72; color: white;">
                                                <h5 class="modal-title">Loan Details</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body" style="background-color: #f8f9fa;">
                                                <?php
                                                $percent = Loan::calculatePercent($loan['id']);
                                                $startDate = new DateTime($loan['requested_at']);
                                                $endDate = clone $startDate;
                                                $endDate->modify('+' . $loan['noOfInstallments'] . ' months');

                                                // Calculate loan details
                                                $totalInterest = (($loan['noOfInstallments'] / 12) * $loan['loanAmount'] * ($loan['interstRate'] / 100));
                                                $processingFee = $loan['loanAmount'] * 0.02; // 2% processing fee
                                                $totalAmount = $loan['loanAmount'] + $totalInterest + $processingFee;
                                                $monthlyPayment = $totalAmount / $loan['noOfInstallments'];
                                                ?>

                                                <div class="card mb-3">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Repayment Progress</h6>
                                                        <div class="progress mb-2" style="height: 20px;">
                                                            <div class="progress-bar bg-success"
                                                                 role="progressbar"
                                                                 style="width: <?php echo $percent; ?>%">
                                                                <?php echo $percent; ?>%
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-between text-muted small">
                                                            <span>Start: <?php echo $startDate->format('Y-m-d'); ?></span>
                                                            <span>End: <?php echo $endDate->format('Y-m-d'); ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row g-4">
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title">Principal Amount</h6>
                                                                <p class="card-text fw-bold text-primary">Rs<?php echo number_format($loan['loanAmount'], 2); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title">Monthly Payment</h6>
                                                                <p class="card-text fw-bold text-success">Rs<?php echo number_format($monthlyPayment, 2); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title">Interest Rate</h6>
                                                                <p class="card-text fw-bold"><?php echo $loan['interstRate']; ?>%</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title">Total Interest</h6>
                                                                <p class="card-text fw-bold text-danger">Rs<?php echo number_format($totalInterest, 2); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title">Installments</h6>
                                                                <p class="card-text fw-bold text-danger"><?php echo number_format($loan['noOfInstallments']); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title">Employment</h6>
                                                                <p class="card-text fw-bold text-danger"><?php echo number_format($loan['employeementTenure']); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="card mt-3">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Additional Information</h6>
                                                        <dl class="row mb-0">
                                                            <dt class="col-sm-5">Credit Grade</dt>
                                                            <dd class="col-sm-7"><?php echo htmlspecialchars($loan['grade']); ?></dd>

                                                            <dt class="col-sm-5">Annual Income</dt>
                                                            <dd class="col-sm-7">Rs<?php echo number_format($loan['AnnualIncome'], 2); ?></dd>

                                                            <dt class="col-sm-5">Processing Fee</dt>
                                                            <dd class="col-sm-7">Rs<?php echo number_format($processingFee, 2); ?> (2%)</dd>

                                                            <dt class="col-sm-5">Purpose</dt>
                                                            <dd class="col-sm-7"><?php echo htmlspecialchars($loan['loanPurpose']); ?></dd>
                                                        </dl>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Profit Display -->
    <?php
    try {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        $stmt = $db->prepare("SELECT `Earning` FROM `consoledatedfund` WHERE `user_id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $dbProfit = $row['Earning'];
        } else {
            $dbProfit = 0;
        }
        
        $stmt->close();
        $profit = $dbProfit ?? 0;
    } catch (Exception $e) {
        $profit = 0;
    }
    ?>
    <div class="card p-4">
        <h3 class="text-success mb-0">
            Total Profit: <span class="fw-bold">Rs<?php echo number_format($profit, 2); ?></span>
        </h3>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<!-- Scripts -->
<script>
    $(document).ready(function() {
        // Initialize DataTable with enhanced options
        $('#example').DataTable({
            responsive: true,
            order: [[7, 'desc']],
            pageLength: 10,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            language: {
                emptyTable: "No active loans available",
                info: "Showing _START_ to _END_ of _TOTAL_ loans",
                search: "Search loans:",
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>'
                }
            },
            columnDefs: [
                { orderable: false, targets: -1 },
                { className: "align-middle", targets: "_all" }
            ],
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control');
                $('.dataTables_length select').addClass('form-select');
            }
        });

        // Remove any existing modal event handlers to prevent double binding
        $('.modal').off('show.bs.modal hidden.bs.modal');

        // Force cleanup of any existing modals on page load
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
        $('.modal').removeClass('show').css('display', '');

        // Single function to handle modal cleanup
        function cleanupModal() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
            $('.modal').removeClass('show').css('display', '');
        }

        // Handle modal open with debounce to prevent double-trigger
        let modalTimeout;
        $(document).on('click', '[data-bs-toggle="modal"]', function(e) {
            e.preventDefault();
            clearTimeout(modalTimeout);

            const targetModal = $($(this).data('bs-target'));

            modalTimeout = setTimeout(() => {
                cleanupModal(); // Clean up any existing modals
                targetModal.modal('show');
            }, 50);
        });

        // Simplified modal show and hide handlers
        $('.modal').on('show.bs.modal', function(e) {
            const $modal = $(this);
            $('.modal').not($modal).modal('hide');
            $('.modal-backdrop').remove();
            $modal.css('z-index', 1050);
            $('body').addClass('modal-open');
        });

        $('.modal').on('hidden.bs.modal', function(e) {
            cleanupModal();
        });

        // Close modal on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                cleanupModal();
            }
        });

        // Progress bar animation
        $('.progress-bar').each(function() {
            let $this = $(this);
            let width = $this.css('width');
            $this.css('width', '0%')
                .animate({
                    width: width
                }, {
                    duration: 1000
                });
        });
    });
</script>
