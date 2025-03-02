<?php
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
$message = "";
$error = "";
if (isset($_GET['e'])) {
    $message = $_GET['e'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // $login_result = UserAuth::login($email, $password);
    $forget = User::forgetPassword($email);


}
include_once('Layout/head.php');
include_once('Layout/header.php'); ?>

<div class="border-top mt-1"></div>
<script>
    toastr.options = {
        "progressBar": true,
        "closeButton": true,
        // Other toastr options you want to add (if any)
    };

    // Only display toastr error if there is a message
    <?php if (!empty($message)): ?>
        toastr.success("<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>");
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        toastr.error("<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>");
    <?php endif; ?>
</script>

<div class="container-fluid d-flex justify-content-center align-items-center" style="height: 70vh;">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-12">
            <div class="card shadow-lg p-4 card-form" style="border-radius: 10px; width:25rem">
                <h4 class="text-center mb-4">Forget Password</h4>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger text-center">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required autocomplete="off"
                            placeholder="Enter your email">
                    </div>


                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Forget Password</button>
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