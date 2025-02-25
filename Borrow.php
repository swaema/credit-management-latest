<!-- composer require paypal/paypal-payouts-sdk ~1.0.0 -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



if (isset($_POST['addLoanApp'])) {
  // var_dump("b");
  // exit;
  if (UserAuth::isBorrowerAuthenticated()) {
    try {
      $loanAmount = filter_var($_POST['loanAmount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
      $monthlySalary = filter_var($_POST['monthlySalary'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      $annualIncome = filter_var($_POST['annualIncome'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      $employmentTenure = filter_var($_POST['employmentTenure'], FILTER_SANITIZE_NUMBER_INT);
      $collateral = isset($_POST['collateral']) ? htmlspecialchars($_POST['collateral'], ENT_QUOTES, 'UTF-8') : null;
      $purpose = htmlspecialchars($_POST['purpose'], ENT_QUOTES, 'UTF-8');
      $consent = isset($_POST['consent']) ? 1 : 0;
      $now = date('Y-m-d H:i:s');
      $id = $_SESSION['user_id'];
      $loan = new Loan(
        id: null,               // Loan ID (auto-generated)
        user_id: $id,                // Assuming $id is the user ID (pass this correctly)
        annualIncome: $annualIncome,      // Annual Income
        monthlySalary: $monthlySalary,     // Monthly Salary
        loanamount: $loanAmount,        // Loan Amount
        purpose: $purpose,           // Loan Purpose
        employementTenure: $employmentTenure,  // Employment Tenure
        collteral: $collateral,        // Collateral (optional)
        consent: $consent,           // Consent (1 for given)
        status: 'pending',          // Loan status (default to 'Pending')
        requested_at: $now,              // Requested at timestamp
        termId: $termId,
      );
      // Save the loan application
      // $result = $loan->saveLoan();

      // Provide feedback to the user
      $error = $result ? "Loan application submitted successfully." : "Failed to submit loan application.";

    } catch (Exception $e) {
      $error = "Error: " . $e->getMessage();
    }

  } else {

    header('Location:login.php?e=You are not Logged in.');
    exit;
  }
}

include_once('Layout/head.php');
include_once('Layout/header.php');
?>
<div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active"
      aria-current="true" aria-label="Slide 1"></button>
    <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
    <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
  </div>
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="Assets/Images/slider-1.jpg" style="min-height: 630px" class="d-block w-100 carousal-img" alt="...">
      <div class="carousel-overlay"></div>
      <div class="carousel-caption d-none d-md-block text-start" style="top: 60px;">
        <h1>SME loans through <br>Peer-to-Peer lending</h1>
        <p>Get the business financing that you need without collateral. Simple online loan application. Fast approval.
        </p>
        <p><a class="btn btn-lg btn-primary" href="loanCalculator.php">Take a Loan</a></p>
      </div>
    </div>
    <div class="carousel-item">
      <img src="Assets/Images/slider-2.jpg" style="min-height: 630px" class="d-block w-100 carousal-img" alt="...">
      <div class="carousel-overlay"></div>

      <div class="carousel-caption d-none d-md-block text-start" style="top: 60px;">
        <h1>SME loans through <br>Peer-to-Peer lending</h1>
        <p>Get the business financing that you need without collateral. Simple online loan application. Fast approval.
        </p>
        <p><a class="btn btn-lg btn-primary" href="loanCalculator.php">Take a Loan</a></p>
      </div>
    </div>
    <div class="carousel-item">
      <img src="Assets/Images/slider-3.jpg" style="min-height: 630px" class="d-block w-100 carousal-img" alt="...">
      <div class="carousel-overlay"></div>

      <div class="carousel-caption d-none d-md-block text-start" style="top: 60px;">
        <h1>SME loans through <br>Peer-to-Peer lending</h1>
        <p>Get the business financing that you need without collateral. Simple online loan application. Fast approval.
        </p>
        <p><a class="btn btn-lg btn-primary" href="loanCalculator.php">Take a Loan</a></p>
      </div>
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
</div>
<div class="conatiner">

</div>
<?php
include_once('takeLoan.php');
?>
<div class="mt-5"></div>
<?php


include_once('Layout/footer.php'); ?>
<script src="Assets/Scripts/takeLoan.js"></script>