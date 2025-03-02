<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/LoanInstallments.php';

if (!UserAuth::isBorrowerAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
$errors = []; 
$id = $_SESSION['user_id'];



try {
    $loanInstallments = LoanInstallments::userLoanInstllments();
    if (!is_array($loanInstallments)) {
        $loanInstallments = [];
    }
} catch (Exception $e) {
    error_log("Error fetching loan installments: " . $e->getMessage());
    $loanInstallments = [];
    $error = "Error fetching payment history: " . $e->getMessage();
}

?>

<?php
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<div class="col-md-10 pb-5" style="background-color: #ECF0F4;">
    <div class="row text-center">
        <h5>
            <?php if (isset($error))
                echo $error; ?>
        </h5>
    </div>
    <div class="row d-flex justify-content-center">
        <div class="col-6">
            <?php $loan = Loan::checkLoan();
            if ($loan) {
                ?>
                <h6 class="alert alert-danger">You are defaulter for Loan</h6>
                <?php
            }
            ?>

        </div>
    </div>
    <div class="title text-center">
        <h2 class="h3 fw-bold mt-3 " style="font-family: sans-serif;">
            Payment History
          
        </h2>
    </div>
    
    <div class="container-fluid">
        <div class="row mt-2 d-flex justify-content-center">
            <div class="col-lg-11 col-md-11 col-sm-11">
                <div class="table-responsive p-5" style="background-color: #FFFFFF; border-radius: 10px; ">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Payable Amount</th>
                                <th>Pay Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($loanInstallments)): ?>
                                <?php foreach ($loanInstallments as $installment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($installment['payable_amount']); ?></td>
                                        <td><?php echo htmlspecialchars($installment['pay_date']); ?></td>
                                        <td>
                                            <?php 
                                                $status = htmlspecialchars($installment['inStatus']); 
                                                $statusClass = $status === 'Paid' ? 'text-success' : 'text-warning';
                                            ?>
                                            <span class="<?php echo $statusClass; ?>"><?php echo $status; ?></span>
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
</div>
</div>
<?php
include_once('Layout/footer.php');
?>
<script>
    $(document).ready(function () {
        $('#example').DataTable();
    });
    function myConfirm() {
        var result = confirm("Want to delete?");
        if (result == true) {
            return true;
        } else {
            return false;
        }
    }
</script>