<?php
//profile.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/User.php';
require_once '../Classes/DocumentUploader.php';

if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
if (isset($_POST['profileUpdate'])) {
    try {
        // Sanitize user inputs
        $name = htmlspecialchars($_POST['name']);
        $email = htmlspecialchars($_POST['email']);
        $mobile = htmlspecialchars($_POST['phone']);
        $address = htmlspecialchars($_POST['address']);

        // Handle file upload
        $image = $_FILES['image']['name'];
        $userFolder = '../uploads/users/' . $_SESSION['user_id']; // Define folder path for the current user

        // Ensure the user folder is created

        // Create a unique image name using the current time
        $profileImageName = time() . "_" . basename($image);

        // Check if an image was uploaded and move it to the target directory
        $profileImagePath = ''; // Initialize this variable
        if (!empty($image)) {
            $targetPath = $userFolder . '/profile_image/' . $profileImageName; // Full file path
            $relativePath = 'uploads/users/' . $_SESSION['user_id'] . '/profile_image/' . $profileImageName; // Relative path

            // Ensure the profile_image directory exists
            $profileImageDir = $userFolder . '/profile_image';

            // Move the uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                // Store the relative path for database use
                $_SESSION['user_image'] = $relativePath;
                $profileImagePath = $relativePath;
            } else {
                throw new Exception("Failed to upload image.");
            }
        }

        // Pass the full file path to the UpdateProfile method (empty path if no image)
        $user = User::UpdateProfile($name, $email, $mobile, $address, $profileImagePath);

        // Update session variables only if the values have changed
        if (!isset($_SESSION['user_name']) || $_SESSION['user_name'] !== $name) {
            $_SESSION['user_name'] = $name;
        }
        if (!isset($_SESSION['user_email']) || $_SESSION['user_email'] !== $email) {
            $_SESSION['user_email'] = $email;
        }
        if (!isset($_SESSION['user_mobile']) || $_SESSION['user_mobile'] !== $mobile) {
            $_SESSION['user_mobile'] = $mobile;
        }
        if (!isset($_SESSION['user_address']) || $_SESSION['user_address'] !== $address) {
            $_SESSION['user_address'] = $address;
        }
        header('Location: index.php');
        exit;

    } catch (Exception $e) {
        // Catch and display any error
        var_dump($e->getMessage());
        exit;
    }
}



$errors = []; // Initialize an array to hold error messages
$id = $_SESSION['user_id'];
$user = User::find($id);
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
            User Profile
        </h2>
    </div>
    <div class="container-fluid">
        <div class="row mt-2 d-flex justify-content-center">
            <div class="col-lg-11 col-md-11 col-sm-11">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-6">
                            <label for="">Name</label>
                            <input type="text" class="form-control" name="name"
                                value="<?php echo htmlspecialchars($user->name) ?>" id="">
                        </div>
                        <div class="col-6">
                            <label for="">E-mail</label>
                            <input type="text" class="form-control" name="email"
                                value="<?php echo htmlspecialchars($user->email) ?>" id="">
                        </div>
                        <div class="col-6">
                            <label for="">Phone</label>
                            <input type="text" class="form-control" name="phone"
                                value="<?php echo htmlspecialchars($user->mobile) ?>" id="">
                        </div>
                        <div class="col-6">
                            <label for="">Address</label>
                            <input type="text" class="form-control" name="address"
                                value="<?php echo htmlspecialchars($user->address) ?>" id="">
                        </div>
                        <div class="col-6">
                            <label for="">Image</label>
                            <input type="file" class="form-control" name="image" id="">
                        </div>
                        <div class="col-6"></div>
                        <div class="col-6">
                            <img src="../<?php echo $_SESSION['user_image']; ?>"
                                value="<?php echo $_SESSION['user_image']; ?>" class="img-fluid" alt="">
                        </div>

                    </div>
                    <div class="row mt-2">
                        <div class="col-2">
                            <input type="submit" name="profileUpdate" class="btn btn-primary" value="Update">
                        </div>
                    </div>
                </form>
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