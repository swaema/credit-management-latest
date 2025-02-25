<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
if (!UserAuth::isBorrowerAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}

$userid =  $_SESSION['user_id'];
include_once('Layout/head.php');
require_once '../Classes/Loan.php';

include_once('Layout/sidebar.php');
?>
<div class="col-md-10" style="background-color: #ECF0F4;">
    <div class="row d-flex justify-content-center">
        <div class="col-6">

        </div>
    </div>
    <div class="row mt-5 justify-content-between">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Active Loans</h5>
                    <p class="card-text display-6 text-center">
                        <?php echo Loan::getActiveLoansCount(); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Loans Taken</h5>
                    <p class="card-text display-6 text-center">
                        <?php echo Loan::getTotalLoansTaken(); ?>
                    </p>
                </div>
            </div>
        </div>
   
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pending Amount</h5>
                    <p class="card-text display-6 text-center">
                        <?php echo Loan::getPendingAmount($userid); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Overdue Loans</h5>
                    <p class="card-text display-6 text-center">
                        <?php echo Loan::getOverdueLoansCount() ?? 0; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include_once('Layout/footer.php');
?>
