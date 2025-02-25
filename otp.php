<?php
require_once 'Classes/User.php';
require_once 'Classes/Database.php';
require_once 'Classes/Mail.php';


$email = "";
if (isset($_GET['s'])) {
    $message = $_GET['s'];
}
if (isset($_GET['email'])) {
    $email = $_GET['email'];
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// echo '<pre>';
// print_r($_SESSION['otp_cache'][$email]['otp']);
// '</pre>';
$stored_otp = $_SESSION['otp_cache'][$email]['otp'];
if (isset($_POST['verifyOTP'])) {
    $submitted_otp = $_POST['otp'];
    if (!isset($_SESSION['otp_cache'][$email])) {
        header("Location: otp.php?e=No OTP found for this email");
        exit();
    }
    $created_at = $_SESSION['otp_cache'][$email]['created_at'];
    $expiry_time = 5 * 60;
    if ((time() - $created_at) > $expiry_time) {
        unset($_SESSION['otp_cache'][$email]);
        header("Location: otp.php?e=OTP has expired. Please request a new one");
        exit();
    }
    if ($submitted_otp == $stored_otp) {
        unset($_SESSION['otp_cache'][$email]);
        User::changeStatus($email);

        header("Location: login.php?s=OTP verified successfully");
        exit();
    } else {

        // header("Location: otp.php?e=Invalid OTP. Please try again");
        var_dump("error");
        exit();
    }
}
include_once('Layout/head.php');
include_once('Layout/header.php'); ?>

<div class="border-top mt-1"></div>
<!-- <p><?php //  echo $stored_otp; ?></p> -->
<div class="container-fluid d-flex justify-content-center align-items-center" style="height: 70vh;">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-12">
            <div class="card shadow-lg p-4 card-form" style="border-radius: 10px; width:25rem">
                <h4 class="text-center mb-4">OTP</h4>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger text-center">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Enter OTP</label>
                        <input type="number" class="form-control" id="otp" name="otp" required autocomplete="off"
                            placeholder="Enter OTP from Email">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="verifyOTP" class="btn btn-primary">Check OTP</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>



<script>
    const passwordInput = document.getElementById('password1');
    const toggle = document.getElementById('toggle');

    toggle.addEventListener('click', function () {
        // Toggle the password visibility
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Toggle the eye icon
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });
</script>

<?php include_once('Layout/footer.php'); ?>