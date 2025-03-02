<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';

if (!UserAuth::isBorrowerAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit;
}

// Initialize variables
$error = isset($_GET['e']) ? $_GET['e'] : '';
$success = isset($_GET['s']) ? $_GET['s'] : '';
$id = isset($_GET['edit']) ? (int)$_GET['edit'] : -1;

// Handle form submission
if (isset($_POST['upLoanApp'])) {
    try {
        // Validate inputs
        $loanAmount = filter_var($_POST['loanAmount'], FILTER_VALIDATE_FLOAT);
        $interestRate = filter_var($_POST['interst'], FILTER_VALIDATE_FLOAT);
        $annualIncome = filter_var($_POST['annualIncome'], FILTER_VALIDATE_FLOAT);
        $employmentTenure = filter_var($_POST['employmentTenure'], FILTER_VALIDATE_INT);
        $installments = filter_var($_POST['installments'], FILTER_VALIDATE_INT);
        $purpose = filter_var($_POST['purpose'], FILTER_SANITIZE_STRING);

        if (!$loanAmount || !$interestRate || !$annualIncome || !$employmentTenure || !$installments) {
            throw new Exception("Please provide valid numeric values for all fields.");
        }

        $db = Database::getConnection();
        
        // Update the loan
        $sql = "UPDATE loans SET 
                loanAmount = ?,
                interstRate = ?,
                AnnualIncome = ?,
                employeementTenure = ?,
                noOfInstallments = ?,
                loanPurpose = ?,
                updated_at = NOW()
                WHERE id = ? AND user_id = ?";

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $db->error);
        }

        $userId = $_SESSION['user_id'];
        $stmt->bind_param(
            "dddiisii",
            $loanAmount,
            $interestRate,
            $annualIncome,
            $employmentTenure,
            $installments,
            $purpose,
            $id,
            $userId
        );

        if ($stmt->execute()) {
            header("Location: LoanApplications.php?s=Loan updated successfully");
            exit;
        } else {
            throw new Exception("Failed to update loan: " . $stmt->error);
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch loan details
try {
    $db = Database::getConnection();
    $sql = "SELECT * FROM loans WHERE id = ? AND user_id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $db->error);
    }

    $userId = $_SESSION['user_id'];
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $loan = $result->fetch_assoc();
    
    if (!$loan) {
        header("Location: LoanApplications.php?e=Loan not found");
        exit;
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<div class="col-md-10 pb-5" style="background-color: #ECF0F4;">
    <!-- Error/Success Messages -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Back Button -->
    <div class="row">
        <div class="col-1">
            <a href="LoanApplications.php" class="btn btn-primary btn-sm ms-5">&larr; Back</a>
        </div>
    </div>

    <!-- Title -->
    <div class="title text-center">
        <h2 class="h3 fw-bold mt-3" style="font-family: sans-serif;">
            Edit Loan Application
        </h2>
    </div>

    <!-- Loan Form -->
    <div class="container" id="loanForm">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <form action="" method="post" class="needs-validation" novalidate>
                    <div class="row mt-3">
                        <div class="col-md-6 mb-3">
                            <label for="loanAmount" class="form-label">Loan Amount</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="loanAmount" 
                                   id="loanAmount" 
                                   value="<?php echo htmlspecialchars($loan['loanAmount']); ?>"
                                   required>
                            <div class="invalid-feedback">Please enter a valid loan amount.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="interst" class="form-label">Interest Rate (%)</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="interst" 
                                   id="interst" 
                                   min="0" 
                                   step="0.01"
                                   value="<?php echo htmlspecialchars($loan['interstRate']); ?>"
                                   required>
                            <div class="invalid-feedback">Please enter a valid interest rate.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="annualIncome" class="form-label">Annual Income</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="annualIncome" 
                                   id="annualIncome"
                                   value="<?php echo htmlspecialchars($loan['AnnualIncome']); ?>"
                                   required>
                            <div class="invalid-feedback">Please enter your annual income.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="employmentTenure" class="form-label">Employment Tenure (years)</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="employmentTenure" 
                                   id="employmentTenure"
                                   value="<?php echo htmlspecialchars($loan['employeementTenure']); ?>"
                                   required>
                            <div class="invalid-feedback">Please enter your employment tenure.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="purpose" class="form-label">Loan Purpose</label>
                            <select class="form-control" id="purpose" name="purpose" required>
                                <?php
                                $purposes = [
                                    'credit_card' => 'Credit Card',
                                    'debt_consolidation' => 'Debt Consolidation',
                                    'home_improvement' => 'Home Improvement',
                                    'major_purchase' => 'Major Purchase',
                                    'medical' => 'Medical',
                                    'other' => 'Other'
                                ];
                                foreach ($purposes as $value => $label) {
                                    $selected = ($loan['loanPurpose'] == $value) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">Please select a loan purpose.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="installments" class="form-label">Number of Installments</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="installments" 
                                   id="installments"
                                   value="<?php echo htmlspecialchars($loan['noOfInstallments']); ?>"
                                   required>
                            <div class="invalid-feedback">Please enter the number of installments.</div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" name="upLoanApp" class="btn btn-primary">
                                Update Loan Application
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<script>
// Form validation
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')

    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Prevent negative values
document.querySelectorAll('input[type="number"]').forEach(function(input) {
    input.addEventListener('input', function() {
        if (this.value < 0) {
            this.value = 0;
        }
    });
});
</script>