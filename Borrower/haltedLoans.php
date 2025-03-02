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
$errors = []; // Initialize an array to hold error messages
$id = $_SESSION['user_id'];
if (isset($_POST['activate'])) {
    $id = $_POST['loanId'];
    $check = Loan::changeStatus($id, "Pending");
    if ($check) {
        $message = "Loan Activated Successfully";
        header("Location: haltedLoans.php?e=" . urlencode($message));
        exit();
    } else {
        $message = "Something went Wrong";
        header("Location: haltedLoans.php?e=" . urlencode($message));
        exit;
    }
}

$loans = Loan::allLoansByUser($id, "halted");

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

    .action-dropdown .btn-activate {
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
            Halted Loan Applications
        </h2>
    </div>

    <?php
    $loanCheck = Loan::checkLoan();
    if ($loanCheck):
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
                <?php foreach ($loans as $loan): ?>
                    <tr>
                        <td>
                            <?php
                            // Calculate the total payable amount: principal + interest + processing fee
                            $totalAmount = $loan['loanAmount'] +
                                (($loan['noOfInstallments']/12) * $loan['loanAmount'] * ($loan['interstRate'] / 100)) +
                                ($loan['loanAmount'] * (2 / 100));
                            echo number_format($totalAmount, 2);
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($loan['interstRate']); ?>%</td>
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
                                            <button type="submit" name="activate" class="btn-activate">
                                                <i class="fas fa-check"></i> Activate Loan
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
                "searchPlaceholder": "Search halted loans..."
            },
            "pageLength": 10,
            "ordering": true,
            "responsive": true
        });
    });

    function myConfirm() {
        return confirm("Are you sure you want to activate this loan?");
    }
</script>
