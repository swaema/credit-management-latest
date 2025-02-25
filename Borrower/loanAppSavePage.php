<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';

if (!UserAuth::isBorrowerAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
$amount = 50000;
if (isset($_GET['amount'])) {
    $amount = $_GET['amount'];
}
$errors = []; // Initialize an array to hold error messages
$id = $_SESSION['user_id'];
if (isset($_POST['addLoanApp'])) {

    try {
        $installments = $_POST['installments'];
        $loanAmount = $_POST['amount'];
        $interst = $_POST['interst'];
        $totalloan=$_POST['totalloan'];
        // $termId = $_POST['term'];
        $annualIncome = filter_var($_POST['annualIncome'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $employmentTenure = filter_var($_POST['employmentTenure'], FILTER_SANITIZE_NUMBER_INT);

        $purpose = $_POST['purpose'];
       
        $now = date('Y-m-d H:i:s');

        $monthlyIncome = $annualIncome / 12;
        $dti = ($loanAmount / $monthlyIncome) * 100;
        $data = array(
            "loan_amnt" => (float) $loanAmount,
            "term" => (int) $installments,
            "int_rate" => (float) $interst,
            "emp_length" => $employmentTenure,
            "annual_inc" => (float) $annualIncome,
            "purpose" => $purpose,
            "dti" => (float) $dti
        );

        // Send data to Python API
        $api_url = 'http://127.0.0.1:5001/predict'; // Python API URL
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);
        $grade = "";
        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['credit_grade'])) {
                $grade = $result['credit_grade'];
            } else {
                echo "<div class='result'>Error: Unable to fetch prediction. Check your API.</div>";
                var_dump("Error: Unable to fetch prediction. Check your API.");
                exit;
            }
        } else {
            echo "<div class='result'>Error: API did not respond. Ensure the Python API is running.</div>";
            var_dump("Error: API did not respond. Ensure the Python API is running.");
            exit;
        }

        $loan = new Loan(
            id: null,               // Loan ID (auto-generated)
            user_id: $id,                // Assuming $id is the user ID (pass this correctly)
            annualIncome: $annualIncome,      // Annual Income
            // monthlySalary: $monthlySalary,     // Monthly Salary
            loanamount: $loanAmount,        // Loan Amount
            purpose: $purpose,           // Loan Purpose
           
            // collteral: $collateral,        // Collateral (optional)
           
            status: 'Pending',          // Loan status (default to 'Pending')
            requested_at: $now,              // Requested at timestamp

            installments: $installments,
            interst: $interst,
            grade: $grade,
            employementTenure: $employmentTenure,  // Employment Tenure
            totalloan: $totalloan

        );
        // Save the loan application
        $result = $loan->saveLoan();

        // Provide feedback to the user
        $error = $result ? "Loan application submitted successfully." : "Failed to submit loan application.";

    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}


$loans = Loan::allLoansByUser($id, "Pending");

?>
<style>
    .step-container {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .step {
        width: 33.33%;
        text-align: center;
        display: block;
    }

    .step-active {
        color: blue;
        font-weight: bold;
        border-bottom: 2px solid blue;
    }

    .funding-amount-display {
        font-size: 24px;
        color: blue;
        font-weight: bold;
        margin-top: 20px;
        text-align: right;
    }

    .form-select {
        margin-top: 10px;
    }

    .btn {
        margin-top: 20px;
    }

    .hidden {
        display: none;
    }

    /* Slider styling */
    input[type="range"] {
        -webkit-appearance: none;
        width: 100%;
        height: 10px;
        background: #ddd;
        outline: none;
        opacity: 0.9;
        transition: opacity .15s ease-in-out;
        margin-top: 10px;
    }

    input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        background: #3498db;
        cursor: pointer;
        border-radius: 50%;
    }

    /* Step transition effect */
    .form-step {
        opacity: 0;
        transform: translateX(50px);
        transition: all 0.4s ease;
    }

    /* Active class to fade in */
    .form-step-active {
        opacity: 1;
        transform: translateX(0);
    }
</style>
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
    <div class="row">
        <div class="col-1">
            <a href="LoanApplications.php" type="button" class="btn btn-primary btn-sm ms-5">&larr;Back</a>
        </div>
    </div>

    <div class="title text-center">
        <h2 class="h3 fw-bold mt-3 " style="font-family: sans-serif;">
            Loan Application Form
        </h2>
    </div>
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-10 card px-5 pb-5 mt-4">
                <!-- Step Progress Bar -->
                <div class="step-container pt-4">
                    <div class="step step-active" id="step1-indicator">Financing Demand</div>
                    <div class="step" id="step2-indicator">Personal Information</div>
                    
                </div>

                <!-- Multi-step form -->
                <form method="post" id="multi-step-form">
                    <!-- Step 1: Financing Need -->
                    <div id="step1" class="form-step form-step-active">
                        <div class="row ">

                            <div class="col-12">
                                <div class="card p-4 text-center">
                                    <h5>MUR <span id="monthlyRepayment">0</span> /month</h5>
                                    <div class="chart-container">
                                        <canvas id="loanChart"></canvas>
                                    </div>
                                    <h6>Total amount <span id="repaymentMode">repayable</span>: MUR <span
                                            id="totalRepayment">0</span><input id="totalloan" type="hidden" name="totalloan"></h6>
                                </div>
                            </div>
                        </div>
                        <div class="row d-flex justify-content-center">
                            <div class="col-8 mt-3">
                                <div class="row">
                                    <input type="number" id="amount" name="amount" class="form-control" value="50000"
                                        min="1000" max="100000" step="1000">
                                    <input type="range" id="amountRange" class="form-range" value="50000" min="1000"
                                        max="100000" step="1000">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label for="" class="form-label"> Term(No of Installment)</label>
                                    <input type="number" min="0" id="termInput" max="60" name="installments" value="36"
                                        class="form-control" id="">
                                </div>
                                <div class="col-6">
                                    <label for="" class="form-label"> Interest Rate</label>
                                    <input type="number" min="0" id="interestInput" max="100" value="0" name="interst"
                                        class="form-control" id="">
                                </div>

                            </div>
                            <div class="row justify-content-end">
                                <div class="col-2 mt-4">
                                    <button type="button" class="btn btn-primary px-3" style="border-radius: 25px;"
                                        id="nextBtnStep1" onclick="changeStep(1)">Next<span class="ms-2">&rarr;</span>
                                    </button>
                                </div>
                            </div>


                        </div>


                    </div>

                    <!-- Step 2: Business Information -->
                    <div id="step2" class="form-step hidden">
                        <div class="row">

                            <div class="col-6">
                                <label for="annualIncome">Annual Income</label>
                                <input type="number" class="form-control" name="annualIncome" id="annualIncome"
                                    placeholder="Enter annual income" required>
                            </div>
                            <div class="col-6">
                                <label for="employmentTenure">Employment Tenure (in years)</label>
                                <input type="number" class="form-control" name="employmentTenure" id="employmentTenure"
                                    placeholder="Enter employment tenure" required>
                            </div>
                            <div class="col-6">
                                <label for="purpose">Loan Purpose</label>
                                <select class="form-control" id="purpose" name="purpose" required>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debt_consolidation">Debt Consolidation</option>
                                    <option value="home_improvement">Home Improvement</option>
                                    <option value="major_purchase">Major Purchase</option>
                                    <option value="medical">Medical</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- <div class="col-6">
                                <label for="purpose">No of Installmets</label>
                                <input type="number" name="installments" placeholder="No. of Installments"
                                    class="form-control" id="installments" required id="">
                            </div> -->
                        </div>
                        <div class="row justify-content-between">
                            <div class="col-2">
                                <button type="button" class="btn btn-primary  px-2" style="border-radius: 25px;"
                                    id="prevBtnStep2" onclick="changeStep(-1)"><span
                                        class="me-2">&larr;</span>Previous</button>
                            </div>
                            <div class="col-2">
                                <input type="submit" style="border-radius: 25px;" class="btn btn-primary px-3"
                                    value="Submit->" name="addLoanApp">
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Contact Details -->
                    
                </form>
            </div>
        </div>
    </div>



</div>
</div>
</div>

<script src="../Assets/Scripts/loanCalulator1.js"></script>
<?php
include_once('Layout/footer.php');
?>
<script>
    const steps = document.querySelectorAll('.form-step');
    const indicators = document.querySelectorAll('.step');

    function changeStep(direction) {
        const activeStep = document.querySelector('.form-step-active');
        const currentIndex = Array.from(steps).indexOf(activeStep);
        const nextIndex = currentIndex + direction;

        if (nextIndex >= 0 && nextIndex < steps.length) {
            // Hide the current step
            activeStep.classList.remove('form-step-active');
            steps[currentIndex].classList.add('hidden');

            // Show the next step
            steps[nextIndex].classList.remove('hidden');
            steps[nextIndex].classList.add('form-step-active');

            // Update the indicator
            indicators[currentIndex].classList.remove('step-active');
            indicators[nextIndex].classList.add('step-active');
        }
    }


</script>
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