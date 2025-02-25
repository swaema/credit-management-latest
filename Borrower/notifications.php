<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/Notifiactions.php';

if (!UserAuth::isBorrowerAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
$errors = [];
$id = $_SESSION['user_id'];


$noti = Notifiactions::showNotification($id);

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
            Notifications
        </h2>
    </div>
    <div class="container-fluid">
        <div class="row mt-2 d-flex justify-content-center">
            <div class="col-lg-11 col-md-11 col-sm-11">
                <div class="table-responsive p-5" style="background-color: #FFFFFF; border-radius: 10px; ">
                    <table id="example" class="table table-bordered">
                        <thead class="text-white" style="background-color: #142127;">
                            <tr class="text-white bg-dark text-center">
                                <th scope="col">User</th>
                                <th scope="col">Notification</th>
                                <th scope="col">Date and Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($noti) && count($noti) > 0): ?>
                                <?php foreach ($noti as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['message']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(date('d-m-Y', strtotime($item['notTime']))); ?>
                                            <br>
                                            <?php echo htmlspecialchars(date('g:i A', strtotime($item['notTime']))); ?>
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