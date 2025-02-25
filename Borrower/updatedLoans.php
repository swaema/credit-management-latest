<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';

if (!UserAuth::isBorrowerAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}

if (isset($_GET['e'])) {
    $error = $_GET['e'];
}

class LoanHelper {
    public static function calculateLoanDetails($loanAmount, $interestRate, $numberOfInstallments) {
        // Base loan amount
        $principal = $loanAmount;
        
        // Calculate interest amount
        $interestAmount = ($numberOfInstallments/12) * $loanAmount * ($interestRate / 100);
        
        // Processing fee (2%)
        $processingFee = $loanAmount * (2 / 100);
        
        // Total amount
        $totalAmount = $principal + $interestAmount + $processingFee;
        
        // Monthly installment
        $monthlyInstallment = $totalAmount / $numberOfInstallments;
        
        return [
            'principal' => $principal,
            'interestAmount' => $interestAmount,
            'processingFee' => $processingFee,
            'totalAmount' => $totalAmount,
            'monthlyInstallment' => $monthlyInstallment
        ];
    }
}

// Initialize session storage for original loan states if not exists
if (!isset($_SESSION['original_loan_states'])) {
    $_SESSION['original_loan_states'] = [];
}

// Store original state when loan is updated
function storeLoanOriginalState($loan) {
    if (!isset($_SESSION['original_loan_states'][$loan['id']])) {
        $_SESSION['original_loan_states'][$loan['id']] = [
            'interstRate' => $loan['interstRate'],
            'TotalLoan' => $loan['TotalLoan'],
            'InstallmentAmount' => $loan['InstallmentAmount']
        ];
    }
}

$errors = [];
$id = $_SESSION['user_id'];

if (isset($_POST['Delete'])) {
    $id = $_POST['loanId'];
    $deleted = Loan::changeStatus($id, "halted");
    if ($deleted) {
        $message = "Loan successfully Halted";
        header("Location: updatedLoans.php?e=" . urlencode($message));
        exit();
    } else {
        $message = "Loan successfully deleted";
        header("Location: updatedLoans.php?e=" . urlencode($message));
        exit;
    }
}

if (isset($_POST['accept'])) {
    $id = $_POST['loanId'];
    $check = Loan::changeStatus($id, "Pending");
    if ($check) {
        $message = "Updated Loan Accepted successfully";
        header("Location: updatedLoans.php?e=" . urlencode($message));
        exit();
    } else {
        $message = "Something went wrong";
        header("Location: updatedLoans.php?e=" . urlencode($message));
        exit;
    }
}

$loans = Loan::allLoansByUser($id, "updated");

// Include header and sidebar
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<style>
.dashboard-container {
    background-color: #f8f9fa;
    min-height: 100vh;
    padding: 2rem;
}

.page-header {
    background: linear-gradient(135deg, #2c5364, #203a43);
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.page-title {
    color: white;
    margin: 0;
    font-weight: 600;
    text-align: center;
}

.defaulter-alert {
    background-color: #fee2e2;
    border-left: 4px solid #ef4444;
    color: #991b1b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    animation: slideIn 0.5s ease-out;
}

.loan-table-container {
    background-color: white;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
}

.custom-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.custom-table thead th {
    background-color: #203a43;
    color: white;
    font-weight: 600;
    padding: 1rem;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.05em;
    border: none;
}

.custom-table tbody tr {
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.custom-table tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.custom-table tbody td {
    padding: 1rem;
    background-color: white;
    border-top: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.loan-changes {
    background-color: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 0.5rem;
}

.change-indicator {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    margin-left: 0.5rem;
}

.increase {
    background-color: #fee2e2;
    color: #991b1b;
}

.decrease {
    background-color: #dcfce7;
    color: #166534;
}

.loan-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin: 1.5rem 0;
    background-color: white;
    padding: 1.25rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.detail-label {
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
    font-weight: 500;
}

.detail-value {
    color: #1e293b;
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.tooltip-icon {
    display: inline-block;
    margin-left: 0.25rem;
    color: #6b7280;
    cursor: help;
}

.change-summary {
    background-color: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.summary-header {
    background-color: #0ea5e9;
    color: white;
    padding: 0.75rem 1rem;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    font-weight: 600;
}

.summary-content {
    padding: 1rem;
}

.impact-overview {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.impact-label {
    font-weight: 500;
    color: #475569;
}

.timeline-section {
    background-color: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.timeline-header {
    background-color: #f8fafc;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 500;
    color: #475569;
}

.timeline-details {
    padding: 1rem;
}

.timeline-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px dashed #e2e8f0;
}

.timeline-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.breakdown-section {
    background-color: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 1.5rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.breakdown-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    margin: 1.25rem 0;
}

.breakdown-item {
    background-color: #f8fafc;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.breakdown-label {
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.breakdown-value {
    color: #1e293b;
    font-weight: 500;
    font-size: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.breakdown-value .percentage {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: normal;
}

.percentage {
    color: #64748b;
    font-size: 0.875rem;
}

.cost-change-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.change-header {
    font-weight: 500;
    color: #475569;
    margin-bottom: 0.5rem;
}

.change-details {
    background-color: white;
    padding: 0.75rem;
    border-radius: 4px;
}

.original-cost, .new-cost {
    color: #64748b;
    margin-bottom: 0.25rem;
}

.next-steps-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.steps-header {
    font-weight: 500;
    color: #475569;
    margin-bottom: 0.75rem;
}

.steps-content {
    background-color: white;
    padding: 0.75rem;
    border-radius: 4px;
}

.step-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-bottom: 1px dashed #e2e8f0;
}

.step-item:last-child {
    border-bottom: none;
}

.step-item i {
    color: #0ea5e9;
}

.action-dropdown .dropdown-toggle {
    background-color: #f3f4f6;
    border: none;
    color: #374151;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.action-dropdown .dropdown-toggle:hover {
    background-color: #e5e7eb;
}

.action-dropdown .dropdown-menu {
    border: none;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 0.5rem;
}

.action-dropdown .btn-halt {
    background-color: #ef4444;
    color: white;
    width: 100%;
    text-align: left;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    border: none;
    transition: all 0.3s ease;
}

.action-dropdown .btn-accept {
    background-color: #10b981;
    color: white;
    width: 100%;
    text-align: left;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    border: none;
    transition: all 0.3s ease;
}

.notification-alert {
    background-color: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid #3b82f6;
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($error)): ?>
        <div class="notification-alert">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h2 class="page-title">
            Updated Loan Applications
        </h2>
    </div>

    <?php 
    $loan = Loan::checkLoan();
    if ($loan): 
    ?>
        <div class="defaulter-alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <strong>Notice:</strong> You currently have an outstanding loan balance.
            </div>
        </div>
    <?php endif; ?>

    <div class="loan-table-container">
        <table class="custom-table table table-responsive table-bordered" id="example">
            <thead>
                <tr>
                    <th>Loan Details</th>
                    <th>Interest Rate</th>
                    <th>Employment Tenure</th>
                    <th>Purpose</th>
                    <th>Application Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($loans) && count($loans) > 0): ?>
                    <?php foreach ($loans as $loan): 
                        $currentDetails = LoanHelper::calculateLoanDetails(
                            $loan['loanAmount'],
                            $loan['interstRate'],
                            $loan['noOfInstallments']
                        );
                        
                        // Get original state if exists
                        $originalState = $_SESSION['original_loan_states'][$loan['id']] ?? null;
                        
                        // Store current state if not already stored
                        if ($loan['updated_at'] !== null && $originalState === null) {
                            storeLoanOriginalState($loan);
                            $originalState = $_SESSION['original_loan_states'][$loan['id']];
                        }

                        $currentInstallment = $loan['InstallmentAmount'] ?? $currentDetails['monthlyInstallment'];
                    ?>
                        <tr>
                            <td>
                                <div class="loan-details">
                                    <div class="amount-primary">
                                        <strong>Principal Amount:</strong> 
                                        <?php echo number_format($loan['loanAmount'], 2); ?>
                                    </div>
                                    
                                    <div class="loan-changes">
                                        <!-- Summary Banner -->
                                        <div class="change-summary">
                                            <div class="summary-header">
                                                <i class="fas fa-info-circle"></i> Loan Update Summary
                                            </div>
                                            <div class="summary-content">
                                                <?php
                                                $monthlyDiff = 0;
                                                $totalDiff = 0;
                                                if ($originalState) {
                                                    $monthlyDiff = $currentInstallment - $originalState['InstallmentAmount'];
                                                    $totalDiff = $currentDetails['totalAmount'] - $originalState['TotalLoan'];
                                                }
                                                ?>

                                            </div>
                                        </div>

                                        <!-- Payment Timeline -->
                                        <div class="timeline-section">
                                            <div class="timeline-header">
                                                <i class="fas fa-calendar-alt"></i> Payment Schedule
                                            </div>
                                            <div class="timeline-details">
                                                <div class="timeline-item">
                                                    <span class="label">Duration:</span>
                                                    <span class="value"><?php echo $loan['noOfInstallments']; ?> months</span>
                                                </div>
                                                <div class="timeline-item">
                                                    <span class="label">First Payment:</span>
                                                    <span class="value">
                                                        <?php 
                                                        $firstPaymentDate = date('M d, Y', strtotime('+1 month', strtotime($loan['requested_at'])));
                                                        echo $firstPaymentDate;
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="timeline-item">
                                                    <span class="label">Final Payment:</span>
                                                    <span class="value">
                                                        <?php 
                                                        $finalPaymentDate = date('M d, Y', strtotime('+' . $loan['noOfInstallments'] . ' months', strtotime($loan['requested_at'])));
                                                        echo $finalPaymentDate;
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="loan-details-grid">
                                            <div>
                                                <div class="detail-label">
                                                    Monthly Installment
                                                    <span class="tooltip-icon" title="Amount you need to pay each month">ⓘ</span>
                                                </div>
                                                <div class="detail-value">
                                                    <?php 
                                                    $currentInstallment = $loan['InstallmentAmount'] ?? $currentDetails['monthlyInstallment'];
                                                    echo number_format($currentInstallment, 2); 
                                                    ?>
                                                    <?php if ($originalState && $originalState['InstallmentAmount']): ?>
                                                        <span class="change-indicator <?php echo $currentInstallment > $originalState['InstallmentAmount'] ? 'increase' : 'decrease'; ?>">
                                                            <?php 
                                                            $diff = $currentInstallment - $originalState['InstallmentAmount'];
                                                            echo $diff > 0 ? '+' : '';
                                                            echo number_format($diff, 2); 
                                                            ?>/month
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <div class="detail-label">
                                                    Total Repayment
                                                    <span class="tooltip-icon" title="Total amount you'll pay including interest and fees">ⓘ</span>
                                                </div>
                                                <div class="detail-value">
                                                    <?php echo number_format($currentDetails['totalAmount'], 2); ?>
                                                    <?php if ($originalState && $originalState['TotalLoan']): ?>
                                                        <span class="change-indicator <?php echo $currentDetails['totalAmount'] > $originalState['TotalLoan'] ? 'increase' : 'decrease'; ?>">
                                                            <?php 
                                                            $diff = $currentDetails['totalAmount'] - $originalState['TotalLoan'];
                                                            echo $diff > 0 ? '+' : '';
                                                            echo number_format($diff, 2); 
                                                            ?> total
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="breakdown-section">
                                            <div class="detail-label">Loan Breakdown</div>
                                            <!-- Amount Breakdown -->
                                            <div class="breakdown-grid">
                                                <div class="breakdown-item">
                                                    <div class="breakdown-label text-center">Principal:</div>
                                                    <div class="breakdown-value text-center">
                                                        <?php echo number_format($currentDetails['principal'], 2); ?>
                                                       
                                                    </div>
                                                </div>
                                                <div class="breakdown-item">
                                                    <div class="breakdown-label text-center">Interest:</div>
                                                    <div class="breakdown-value text-center">
                                                        <?php echo number_format($currentDetails['interestAmount'], 2); ?>
                                                      
                                                    </div>
                                                </div>
                                                <div class="breakdown-item">
                                                    <div class="breakdown-label text-center">Processing Fee:</div>
                                                    <div class="breakdown-value text-center">
                                                        <?php echo number_format($currentDetails['processingFee'], 2); ?>
                                                     
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Total Cost Change -->
                                            <?php if ($originalState && $originalState['TotalLoan']): ?>
                                            <div class="cost-change-section">
                                                <div class="change-header">Total Cost Change</div>
                                                <div class="change-details">
                                                    <div class="original-cost">
                                                        Original Total: <?php echo number_format($originalState['TotalLoan'], 2); ?>
                                                    </div>
                                                    <div class="new-cost">
                                                        New Total: <?php echo number_format($currentDetails['totalAmount'], 2); ?>
                                                    </div>
                                                    <div class="difference <?php echo $totalDiff >= 0 ? 'increase' : 'decrease'; ?>">
                                                        <?php 
                                                        echo $totalDiff > 0 ? '+' : '';
                                                        echo number_format($totalDiff, 2); 
                                                        ?> 
                                                        (<?php echo number_format(($totalDiff / $originalState['TotalLoan']) * 100, 1); ?>% change)
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>


                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($loan['interstRate']); ?>%
                                <?php if ($originalState && $originalState['interstRate'] !== $loan['interstRate']): ?>
                                    <span class="change-indicator <?php echo $loan['interstRate'] > $originalState['interstRate'] ? 'increase' : 'decrease'; ?>">
                                        (was <?php echo $originalState['interstRate']; ?>%)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($loan['employeementTenure']); ?></td>
                            <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($loan['requested_at'])); ?></td>
                            <td>
                                <div class="dropdown action-dropdown">
                                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i> Actions
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li>
                                            <form method="post" action="" onsubmit="return myConfirm();">
                                                <input type="hidden" name="loanId" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                <button type="submit" name="Delete" class="btn-halt">
                                                    <i class="fas fa-pause"></i> Halt Loan
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="post" action="">
                                                <input type="hidden" name="loanId" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                <button type="submit" name="accept" class="btn-accept">
                                                    <i class="fas fa-check"></i> Accept Changes
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<script>
$(document).ready(function() {
    $('#example').DataTable({
        "dom": '<"top"f>rt<"bottom"ip><"clear">',
        "language": {
            "search": "<i class='fas fa-search'></i>",
            "searchPlaceholder": "Search updated loans..."
        },
        "pageLength": 10,
        "ordering": true,
        "responsive": true
    });
});

function myConfirm() {
    return confirm("Are you sure you want to halt this loan?");
}

// Initialize tooltips
$(document).ready(function(){
    $('[title]').tooltip();
});
</script>