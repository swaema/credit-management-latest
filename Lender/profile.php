<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/User.php';
require_once '../Classes/Borrower.php';
require_once '../Classes/DocumentUploader.php';

if (!UserAuth::isLenderAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
if (isset($_POST['profileUpdate'])) {
    try {
        // Sanitize user inputs
        $name    = htmlspecialchars($_POST['name']);
        $email   = htmlspecialchars($_POST['email']);
        $mobile  = htmlspecialchars($_POST['phone']);
        $address = htmlspecialchars($_POST['address']);

        // Define user folder
        $userFolder = '../uploads/users/' . $_SESSION['user_id'];

        // Handle profile image upload
        $profileImagePath = ''; // Initialize path for profile image
        if (!empty($_FILES['image']['name'])) {
            $image = $_FILES['image'];
            $profileImageName = time() . "_" . basename($image['name']);
            $profileImagePath = DocumentUploader::upload($image['tmp_name'], $userFolder . '/profile_image', $profileImageName);

            // Delete the previous profile image if it exists
            if (!empty($_SESSION['user_image']) && file_exists('../' . $_SESSION['user_image'])) {
                unlink('../' . $_SESSION['user_image']);
            }

            // Update session with the new profile image
            $_SESSION['user_image'] = str_replace('../', '', $profileImagePath);
        }

        // Update the user profile in the database
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

        // Load the existing borrower
        $borrower = new Borrower();
        $borrower->id = $_SESSION['user_id'];

        // Update borrower profile details
        $borrower->name    = $name;
        $borrower->email   = $email;
        $borrower->mobile  = $mobile;
        $borrower->address = $address;

        // Handle NIC, Utility Bills, and Salary Statements
        $documentTypes = ['nic', 'utility_bills', 'salary_statements'];

        foreach ($documentTypes as $docType) {
            if (!empty($_FILES[$docType]['name'][0])) {
                // Delete existing documents of this type
                $borrower->deleteDocumentsByType($docType);

                // Upload new documents
                $uploadedDocs = [];
                foreach ($_FILES[$docType]['tmp_name'] as $index => $tmpName) {
                    $fileName = time() . "_" . basename($_FILES[$docType]['name'][$index]);
                    $filePath = DocumentUploader::upload($tmpName, $userFolder . '/' . $docType, $fileName);
                    $uploadedDocs[] = str_replace('../', '', $filePath);
                }

                // Assign new documents to the borrower
                $borrower->documents[$docType] = $uploadedDocs;
            }
        }

        // Save updated borrower documents
        $borrower->save();

        // Redirect on success
        header('Location: index.php?s=Profile updated successfully.');
        exit;
    } catch (Exception $e) {
        // Handle errors
        $errorMessage = $e->getMessage();
        header("Location: index.php?e=$errorMessage");
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

<div class="container mt-5 pb-5" style="background-color: #ECF0F4;">
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
                    <!-- User Details -->
                    <div class="col-6">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user->name); ?>" id="name">
                    </div>
                    <div class="col-6">
                        <label for="email">E-mail</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user->email); ?>" id="email">
                    </div>                    
                </div>
                <div class="row">
                    <div class="col-6">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user->mobile); ?>" id="phone">
                    </div>
                    <div class="col-6">
                        <label for="address">Address</label>
                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($user->address); ?>" id="address">
                    </div>
                </div>
                <!-- Profile Image Section -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="profile_image">Update Profile Image</label>
                        <input type="file" class="form-control" accept=".png,.jpg,.jpeg,.gif" name="image" id="profile_image">
                        <div class="mt-2">
                            <h6>Current Profile Image:</h6>
                            <img src="../<?php echo $_SESSION['user_image']; ?>" class="img-fluid" style="height: 150px;" alt="Profile Image">
                        </div>
                    </div>
                    <!-- NIC Section -->
                        <div class="col-md-6">
                            <label for="nic">Update NIC (Front and Back)</label>
                            <input type="file" class="form-control" id="nic" name="nic[]" multiple accept="">
                            <div class="mt-2">
                                <h6>Uploaded NIC Documents:</h6>
                                <div class="row">
                                    <?php foreach (User::getDocument($id) as $document): ?>
                                        <?php if ($document['type'] == 'nic'): ?>
                                            <div class="col-6 mb-2">
                                                <?php 
                                                    $filePath = htmlspecialchars($document['path']);
                                                    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                                                ?>
                                                <?php if (in_array($fileExtension, $imageExtensions)): ?>
                                                    <img style="height: 100px;" src="../<?php echo $filePath; ?>" alt="NIC Image" class="img-thumbnail">
                                                <?php else: ?>
                                                    <a href="../<?php echo $filePath; ?>" class="btn btn-primary btn-sm" download>
                                                        Download NIC File
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Utility Bills Section -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="utility_bills">Update Utility Bills</label>
                            <input type="file" class="form-control" id="utility_bills" name="utility_bills[]" multiple accept="">
                            <div class="mt-2">
                                <h6>Uploaded Utility Bills:</h6>
                                <div class="row">
                                    <?php foreach (User::getDocument($id) as $document): ?>
                                        <?php if ($document['type'] == 'utility_bills'): ?>
                                            <div class="col-6 mb-2">
                                                <?php 
                                                    $filePath = htmlspecialchars($document['path']);
                                                    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                                                ?>
                                                <?php if (in_array($fileExtension, $imageExtensions)): ?>
                                                    <img style="height: 100px;" src="../<?php echo $filePath; ?>" alt="Utility Bill Image" class="img-thumbnail">
                                                <?php else: ?>
                                                    <a href="../<?php echo $filePath; ?>" class="btn btn-primary btn-sm" download>
                                                        Download Utility Bill
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Salary Statements Section -->
                        <div class="col-md-6">
                            <label for="salary_statements">Update Salary Statements (Last 6 Months)</label>
                            <input type="file" class="form-control" id="salary_statements" name="salary_statements[]" multiple accept="">
                            <div class="mt-2">
                                <h6>Uploaded Salary Statements:</h6>
                                <div class="row">
                                    <?php foreach (User::getDocument($id) as $document): ?>
                                        <?php if ($document['type'] == 'salary_statements'): ?>
                                            <div class="col-6 mb-2">
                                                <?php 
                                                    $filePath = htmlspecialchars($document['path']);
                                                    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                                                ?>
                                                <?php if (in_array($fileExtension, $imageExtensions)): ?>
                                                    <img style="height: 100px;" src="../<?php echo $filePath; ?>" alt="Salary Statement Image" class="img-thumbnail">
                                                <?php else: ?>
                                                    <a href="../<?php echo $filePath; ?>" class="btn btn-primary btn-sm" download>
                                                        Download Salary Statement
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Submit Button -->
                <div class="row my-2 py-3 text-center">
                    <div class="col-4">
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