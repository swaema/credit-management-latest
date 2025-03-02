<?php

use Kint\Kint;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Error logging configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');
include_once('Classes/UserAuth.php');
require_once 'Classes/Database.php';
require_once 'Classes/User.php';
require_once 'Classes/Borrower.php';
require_once 'Classes/Lender.php';
require_once 'Classes/otp.php';

$message = "";
if (isset($_GET['e'])) {
    $message = $_GET['e'];
}

// Store return URL if coming from Stripe
if (isset($_GET['session_id'])) {
    $_SESSION['stripe_session_id'] = $_GET['session_id'];
    $_SESSION['return_to'] = 'paymentsuccess/contributesuccess.php';
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password11'];
    $login_result = UserAuth::login($email, $password);

    if ($login_result === 1) {
        // Check if we need to return to contribution success
        if (isset($_SESSION['return_to']) && isset($_SESSION['stripe_session_id'])) {
            $return_url = $_SESSION['return_to'];
            $session_id = $_SESSION['stripe_session_id'];
            unset($_SESSION['return_to']); // Clear the return URL
            header("Location: /$return_url?session_id=" . $session_id);
            exit();
        }

        // Normal login redirects
        if ($_SESSION['user_role'] == "admin") {
            header('Location: Admin/index.php');
        } else if ($_SESSION['user_role'] == "borrower") {
            header('Location: Borrower/index.php');
        } else if ($_SESSION['user_role'] == "lender") {
            header('Location: Lender/index.php');
        }
        exit();
    } else if ($login_result === -1) {
        $error_message = "Invalid email or password.";
    } else if ($login_result === 0) {
        $error_message = "Your account is still under review";
    } else if ($login_result === 2) {
        $error_message = "Your account has been suspended";
    }
    if (User::findByEmail2($email)['user_verfied'] != 'verified'){
        header("Location: http://safefund.mu/otp.php?s=OTP%20Sent%20Successfully&email=" . urlencode($email));
        exit();
    }
}

include_once('Layout/head.php');
include_once('Layout/header.php');
?>

<div class="border-top mt-1"></div>
<script>
    toastr.options = {
        "progressBar": true,
        "closeButton": true,
    };

    <?php if (!empty($message)): ?>
        toastr.error("<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>");
    <?php endif; ?>
</script>

<div class="container-fluid d-flex justify-content-center align-items-center" style="height: 70vh;">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-12">
            <div class="card shadow-lg p-4 card-form mt-3" style="border-radius: 10px; width:25rem">
                <h4 class="text-center mb-4">Login</h4>
                <!-- Show error message if login failed -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger text-center">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['s']) && !empty($_GET['s'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_GET['s'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

                <!-- Show info message if returning from Stripe -->
                <?php if (isset($_SESSION['stripe_session_id'])): ?>
                    <div class="alert alert-info text-center">
                        Please log in to complete your contribution
                    </div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="login-email" name="email" required placeholder="Enter your email">
                    </div>
                    <div class="mb-3 position-relative">
                        <label for="password11" class="form-label">Password</label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password11" 
                                name="password11"
                                required 
                                placeholder="Enter your password"
                            >
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword12">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-start mt-1">
                        <a class="text-center" href="forgetPassword.php">Forgot Password?</a>
                    </div>
                    <div class="d-grid gap-2 mt-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>

                    <div class="text-center mt-3">
                        <a href="" data-bs-toggle="modal" data-bs-target="#staticBackdrop">Not Registered?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// First script for toastr
toastr.options = {
    "progressBar": true,
    "closeButton": true,
};

<?php if (!empty($message)): ?>
    toastr.error("<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>");
<?php endif; ?>

// Password toggle functionality using jQuery
$(document).ready(function() {
    $("#togglePassword12").on("click", function(e) {
        e.preventDefault();
        const passwordField = $("#password11");
        const icon = $(this).find("i");
        
        if (passwordField.attr("type") === "password") {
            passwordField.attr("type", "text");
            icon.removeClass("bi-eye").addClass("bi-eye-slash");
        } else {
            passwordField.attr("type", "password");
            icon.removeClass("bi-eye-slash").addClass("bi-eye");
        }
    });

    // Stripe payment handling
    if (sessionStorage.getItem('stripe_payment_pending')) {
        toastr.info("Please log in to complete your contribution.");
        sessionStorage.removeItem('stripe_payment_pending');
    }
    
    <?php if (isset($_SESSION['stripe_session_id'])): ?>
        sessionStorage.setItem('stripe_payment_pending', 'true');
    <?php endif; ?>
});
</script>

<?php include_once('Layout/footer.php'); ?>