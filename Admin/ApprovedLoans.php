<?php
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/LoanInstallments.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
$errors = [];
$id = $_SESSION['user_id'];
$loans = Loan::allActiveLoans();
?>

<?php
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
    
    .dashboard-header {
        background: linear-gradient(135deg, #142127 0%, #2a454f 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }
    
    .loan-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }
    
    .modal-custom .modal-header {
        background: linear-gradient(135deg, #142127 0%, #2a454f 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }
    
    .info-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .custom-progress {
        height: 10px;
        border-radius: 5px;
    }
    
    .action-btn {
        border-radius: 20px;
        padding: 0.5rem 1.2rem;
        transition: all 0.3s;
    }
    
    .detail-row {
        padding: 0.8rem;
        border-bottom: 1px solid #eee;
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

<div class="col-md-10 dashboard-container">
    <?php if (isset($error)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h2 class="mb-0">Active Loans Management</h2>
        <p class="text-light mb-0 mt-2">Monitor and manage all active loan applications</p>
    </div>

    <div class="loan-card p-4">
        <div class="table-responsive">
            <table id="example" class="table table-hover">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Contact Details</th>
                        <th>Loan Details</th>
                        <th>Employment</th>
                        <th>Purpose</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $profit = 0;
                    if (is_array($loans) && count($loans) > 0): 
                        foreach ($loans as $loan):
                            $interest = ($loan['loanAmount'] * $loan['interstRate']) / 100;
                            $profit += $interest;
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img class="user-avatar me-3" 
                                     src="../<?php echo htmlspecialchars($loan['image']); ?>"
                                     alt="<?php echo htmlspecialchars($loan['name']); ?>" 
                                     onerror="this.onerror=null; this.src='../uploads/users/default/download.png';">
                                <div>
                                    <strong><?php echo htmlspecialchars($loan['name']); ?></strong>
                                    <div class="text-muted small">ID: <?php echo htmlspecialchars($loan['user_id']); ?></div>
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
                        <td>
                            <div>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($loan['interstRate']); ?>% Interest</span>
                            </div>
                            <div class="mt-1">
                                <strong>Rs<?php echo number_format($loan['loanAmount'], 2); ?></strong>
                            </div>
                            <div class="text-muted small"><?php echo htmlspecialchars($loan['noOfInstallments']); ?> installments</div>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($loan['employeementTenure']); ?> years</span>
                        </td>
                        <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($loan['requested_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary btn-sm action-btn" 
                                        data-bs-toggle="modal" data-bs-target="#acecptloan-<?php echo htmlspecialchars($loan['id']) ?>">
                                    <i class="fas fa-history me-1"></i> History
                                </button>
                                <button type="button" class="btn btn-primary btn-sm action-btn ms-2" 
                                        data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $loan['id'] ?>">
                                    <i class="fas fa-info-circle me-1"></i> Details
                                </button>
                            </div>

                            <!-- History Modal -->
                            <div class="modal fade modal-custom" id="acecptloan-<?php echo htmlspecialchars($loan['id']) ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-history me-2"></i>Payment History - <?php echo htmlspecialchars($loan['name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php 
                                            $loanInstallments = LoanInstallments::loanInstallmentbyLoanId($loan['id']);
                                            ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Payment Amount</th>
                                                            <th>Date</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($loanInstallments)): ?>
                                                            <?php foreach ($loanInstallments as $installment): ?>
                                                                <tr>
                                                                    <td>
                                                                        <strong>Rs<?php echo number_format($installment['payable_amount'], 2); ?></strong>
                                                                    </td>
                                                                    <td><?php echo date('M d, Y', strtotime($installment['pay_date'])); ?></td>
                                                                    <td>
                                                                        <span class="badge bg-<?php echo $installment['status'] == 'Paid' ? 'success' : 'warning'; ?>">
                                                                            <?php echo $installment['status']; ?>
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

                            <!-- Details Modal -->
                            <div class="modal fade modal-custom" id="paymentModal<?php echo $loan['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-file-invoice me-2"></i>Loan Details - <?php echo $loan['name']; ?>
                                            </h5>
                                            <?php $percent = Loan::calculatePercent($loan['id']); ?>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="info-card mb-4">
                                                <h6 class="text-primary mb-3">Payment Progress</h6>
                                                <div class="progress custom-progress mb-2">
                                                    <div class="progress-bar bg-success" style="width: <?php echo min($percent, 100); ?>%"></div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <small>Amount Paid</small>
                                                    <small class="fw-bold"><?php echo number_format(min($percent, 100), 2) ?>%</small>
                                                </div>
                                            </div>

                                            <div class="row g-4">
                                                <div class="col-md-6">
                                                    <div class="info-card h-100">
                                                        <h6 class="text-primary mb-3">Loan Timeline</h6>
                                                        <div class="detail-row">
                                                            <div class="d-flex justify-content-between">
                                                                <span>Start Date</span>
                                                                <strong><?php echo date('M d, Y', strtotime($loan['requested_at'])); ?></strong>
                                                            </div>
                                                        </div>
                                                        <div class="detail-row">
                                                            <div class="d-flex justify-content-between">
                                                                <span>End Date</span>
                                                                <?php
                                                                $enddate = new DateTime($loan['requested_at']);
                                                                $enddate->modify('+' . $loan['noOfInstallments'] . ' months');
                                                                ?>
                                                                <strong><?php echo $enddate->format('M d, Y'); ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="info-card h-100">
                                                        <h6 class="text-primary mb-3">Credit Information</h6>
                                                        <div class="detail-row">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span>Credit Grade</span>
                                                                <span class="badge bg-<?php echo $loan['grade'] == 'A' ? 'success' : ($loan['grade'] == 'B' ? 'warning' : 'danger'); ?>">
                                                                    <?php echo $loan['grade']; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="detail-row">
                                                            <div class="d-flex justify-content-between">
                                                                <span>Annual Income</span>
                                                                <strong>Rs<?php echo number_format($loan['AnnualIncome'], 2); ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="info-card">
                                                        <h6 class="text-primary mb-3">Loan Details</h6>
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <div class="detail-row">
                                                                    <small class="text-muted">Principal Amount</small>
                                                                    <div class="fw-bold">Rs<?php echo number_format($loan['loanAmount'], 2); ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="detail-row">
                                                                    <small class="text-muted">Interest Rate</small>
                                                                    <div class="fw-bold"><?php echo $loan['interstRate']; ?>%</div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="detail-row">
                                                                    <small class="text-muted">Installments</small>
                                                                    <div class="fw-bold"><?php echo $loan['noOfInstallments']; ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="detail-row">
                                                                    <small class="text-muted">Purpose</small>
                                                                    <div class="fw-bold"><?php echo $loan['loanPurpose']; ?></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
</div>

<?php include_once('Layout/footer.php'); ?>

<!-- Initialize DataTable with enhanced options -->
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
                info: "Showing _START_ to _END_ of _TOTAL_ active loans",
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>'
                }
            },
            columnDefs: [
                { className: "align-middle", targets: "_all" }
            ],
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control');
                $('.dataTables_length select').addClass('form-select');
            }
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    function myConfirm() {
        return Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to delete this loan?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            return result.isConfirmed;
        });
    }
</script>