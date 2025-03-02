<?php
require_once 'Classes/Database.php';
require_once 'Classes/User.php';
$token = "";
$message = "";
$error ="";
if (isset($_GET['token'])) {
    $token = $_GET['token'];
}
if (isset($_POST['resetPassowrd'])) {
    $newPassword = $_POST['newpassword'];
    $confirmPassowrd = $_POST['confirmpassword'];
    $pass = User::changePassword($newPassword, $confirmPassowrd, $token);
    if ($pass = 1) {
        header("Location: login.php?e=Password Updated Successfully");
    } else {
        $error = $pass;
    }

}

$check = User::checkToken($token);
include_once('Layout/head.php');
include_once('Layout/header.php');

if ($check != -1) {
    ?>
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
                    <h4 class="text-center mb-4">Reset Password</h4>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control" id="password1" name="newpassword" required
                                    autocomplete="off" placeholder="Enter your password">
                                <i class="bi bi-eye-fill position-absolute" id="togglePassword1"
                                    style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Confirm Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control" id="password2" name="confirmpassword" required
                                    autocomplete="off" placeholder="Enter your password">
                                <i class="bi bi-eye-fill position-absolute" id="togglePassword2"
                                    style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="resetPassowrd" class="btn btn-primary">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
} else if ($check == -1) {
    ?>
        <div class="container-fluid d-flex justify-content-center align-items-center" style="height: 70vh;">
            <div class="row justify-content-center">
                <div class="col-lg-12 col-xl-12">
                    <div class="card shadow-lg p-4 card-form" style="border-radius: 10px; width:25rem">
                        <h4 class="text-center mb-4">Password Reset Link Expired</h4>
                        <p class="text-center">It looks like your password reset link has expired. Please request a new link to
                            reset your password.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php
}
include_once('Layout/footer.php');
?>

<script>
    // Toggle password visibility for the New Password field
    const togglePassword1 = document.querySelector("#togglePassword1");
    const password1 = document.querySelector("#password1");

    togglePassword1.addEventListener("click", function () {
        // Toggle the password input type
        const type = password1.getAttribute("type") === "password" ? "text" : "password";
        password1.setAttribute("type", type);

        // Toggle the eye icon class (bi-eye vs bi-eye-slash)
        this.classList.toggle("bi-eye-fill");
        this.classList.toggle("bi-eye-slash-fill");
    });

    // Toggle password visibility for the Confirm Password field
    const togglePassword2 = document.querySelector("#togglePassword2");
    const password2 = document.querySelector("#password2");

    togglePassword2.addEventListener("click", function () {
        // Toggle the password input type
        const type = password2.getAttribute("type") === "password" ? "text" : "password";
        password2.setAttribute("type", type);

        // Toggle the eye icon class (bi-eye vs bi-eye-slash)
        this.classList.toggle("bi-eye-fill");
        this.classList.toggle("bi-eye-slash-fill");
    });
</script>