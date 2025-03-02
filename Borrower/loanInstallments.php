<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/LoanInstallments.php';
require_once '../Classes/Loan.php';

if (!UserAuth::isBorrowerAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
// $insId = 3;
// LoanInstallments::payInstallments($insId);
// var_dump("e");
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
if (isset($_GET['insId'])) {
    $error = $_GET['insId'];
    var_dump('success');
}
if(isset($_POST['payIns'])){
    $insId = $_POST['installmentId'];
    LoanInstallments::payInstallments($insId);
}
$errors = []; // Initialize an array to hold error messages

$installments = LoanInstallments::userLoanInstllments();
// $loans = Loan::allLoansByUser($id, "Pending");

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
            Loan Installments

        </h2>
    </div>

    <div class="container-fluid">
        <div class="row mt-2 d-flex justify-content-center">
            <div class="col-lg-11 col-md-11 col-sm-11">
                <div class="table-responsive p-5" style="background-color: #FFFFFF; border-radius: 10px; ">
                    <table id="example" class="table table-bordered">
                        <thead class="text-white" style="background-color: #142127;">
                            <tr class="text-white bg-dark text-center">
                                <th scope="col">Applicant</th>
                                <th scope="col">Payable Date</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($installments) && count($installments) > 0): ?>
                                <?php foreach ($installments as $item): ?>
                                    <tr>
                                        <td scope="row">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($item['pay_date']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['payable_amount']); ?></td>
                                        <?php if($item['inStatus'] ==='Pending'){
                                            ?>
                                            <td><button class="btn btn-info text-white"><?php echo htmlspecialchars($item['inStatus']); ?></button></td>
                                        <?php
                                        }
                                        else if($item['inStatus'] ==='Defaulter'){
                                        ?>
                                        <td><button class="btn btn-danger text-white"><?php echo htmlspecialchars($item['inStatus']); ?></button></td>
                                        <?php
                                        }
                                        else if($item['inStatus']==='Paid'){
                                            ?>
                                        <td><button class="btn btn-success text-white"><?php echo htmlspecialchars($item['inStatus']); ?></button></td>
                                        <?php
                                        }
                                        ?>
                                        
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Click Here
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li>
                                                    <form action="" method="post">

                                                        <input type="hidden" name="installmentId" value="<?php echo htmlspecialchars($item['loanInstallmentsId'])?>">
                                                    <input type="submit" style="color: white;" name="payIns" class="btn btn-info"
                                                    value="Pay">
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