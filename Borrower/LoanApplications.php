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
$errors = []; 
$id = $_SESSION['user_id'];
if (isset($_POST['Delete'])) {
    $id = $_POST['loanId'];
    $deleted = Loan::changeStatus($id, "halted");
    if ($deleted) {
        $message = "Loan Hallted deleted";
        header("Location: LoanApplications.php?e=" . urlencode($message));
        exit();
    } else {
        $message = "Something went Wrong";
        header("Location: LoanApplications.php?e=" . urlencode($message));
        exit;
    }
}

$loans = Loan::allLoansByUser($id, "Pending");
?>

<?php
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<!-- Add custom CSS -->
<style>
.dashboard-container {
    background-color: #f8f9fa;
    min-height: 100vh;
    padding: 2rem;
}

.page-header {
    background: linear-gradient(135deg, #3a7bd5, #00d2ff);
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.page-title {
    color: white;
    margin: 0;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.add-loan-btn {
    background-color: rgba(255, 255, 255, 0.2);
    border: 2px solid white;
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.add-loan-btn:hover {
    background-color: white;
    color: #3a7bd5;
    transform: translateY(-2px);
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
    background-color: #f8f9fa;
    color: #374151;
    font-weight: 600;
    padding: 1rem;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.05em;
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
}

.custom-table tbody td:first-child {
    border-left: 1px solid #f3f4f6;
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}

.custom-table tbody td:last-child {
    border-right: 1px solid #f3f4f6;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-weight: 500;
    font-size: 0.875rem;
    text-align: center;
}

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.action-dropdown .dropdown-toggle {
    background-color: #f3f4f6;
    border: none;
    color: #374151;
    padding: 0.5rem 1rem;
    border-radius: 6px;
}

.action-dropdown .dropdown-menu {
    border: none;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 0.5rem;
}

.action-dropdown .dropdown-menu .btn {
    width: 100%;
    text-align: left;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    margin-bottom: 0.25rem;
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
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h2 class="page-title">
            Loan Applications
            <a href="loanAppSavePage.php" class="add-loan-btn">
                <i class="fas fa-plus"></i> New Application
            </a>
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
        <table class="custom-table" id="example">
            <thead>
                <tr>
                    <th>Amount</th>
                    <th>Employment Tenure</th>
                    <th>Purpose</th>
                    <th>Application Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($loans) && count($loans) > 0): ?>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td>
                                <?php echo number_format($loan['loanAmount'] + (($loan['noOfInstallments']/12) * $loan['loanAmount'] * ($loan['interstRate'] / 100)) + ($loan['loanAmount'] * (2 / 100)), 2); ?>
                            </td>
                            <td><?php echo htmlspecialchars($loan['employeementTenure']); ?></td>
                            <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($loan['requested_at'])); ?></td>
                            <td>
                                <span class="status-badge status-pending">
                                    <?php echo htmlspecialchars($loan['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="dropdown action-dropdown">
                                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li>
                                            <a class="btn btn-warning" href="LoanAppEdit.php?edit=<?php echo $loan['id'] ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <form method="post" action="" onsubmit="return myConfirm();">
                                                <input type="hidden" name="loanId" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                <button type="submit" name="Delete" class="btn btn-danger">
                                                    <i class="fas fa-pause"></i> Halt
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
            "searchPlaceholder": "Search loans..."
        },
        "pageLength": 10,
        "ordering": true,
        "responsive": true
    });
});

function myConfirm() {
    return confirm("Are you sure you want to halt this loan application?");
}
</script>