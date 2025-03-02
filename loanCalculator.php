<?php
include_once('Layout/head.php');
include_once('Layout/header.php');
?>
<div class="container-fluid px-0 mx-0 mt-2">
    <div class="row">
        <div class="col-12">
            <div class="loan-calculator-header">
                <h1>Loan Calculator</h1>
                <h4>Use our loan calculator to see how much you can borrow or invest</h4>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6">
            <div class="calculator-card">
                <h5>I would like to</h5>
                <div class="btn-group" role="group" aria-label="Borrow or Lend">
                    <button id="borrowBtn" class="btn btn-primary btn-selected">Borrow</button>
                    <button id="lendBtn" class="btn btn-outline-primary">Lend</button>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">How much do you want to <span id="modeText">borrow</span>?</label>
                    <input type="number" id="amount12" class="form-control" min="1000" max="100000" step="1000">
                    <input type="range" id="amountRange12" class="form-range" min="1000" max="100000" step="1000">
                </div>
                <h6>For how long?</h6>
                <div id="durationButtons" class="duration-grid">
    <button class="btn btn-outline-primary btn-selected" data-duration="12">12 Months</button>
    <button class="btn btn-outline-primary" data-duration="18">18 Months</button>
    <button class="btn btn-outline-primary" data-duration="24">24 Months</button>
    <button class="btn btn-outline-primary" data-duration="36">36 Months</button>
    <button class="btn btn-outline-primary" data-duration="48">48 Months</button>
    <button class="btn btn-outline-primary" data-duration="60">60 Months</button>
</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="calculator-card results-card">
                <h5>MUR <span id="monthlyRepayment">0</span> /month</h5>
                <div class="chart-container">
                    <canvas id="loanChart"></canvas>
                </div>
                <h6>Total amount <span id="repaymentMode">repayable</span>: MUR <span id="totalRepayment">0</span></h6>
                <a href="Borrower/loanAppSavePage.php?amount=" class="apply-now-btn">Apply Now</a>
            </div>
        </div>
    </div>
</div>




<?php
include_once('Layout/footer.php');
?>
<script src="Assets/Scripts/loanCalculator.js"></script>
<script>
    let amountInput1 = document.getElementById('amountRange');
    let applyNowLink = document.querySelector('a[href*="loanAppSavePage.php"]');

    // Function to update the anchor href with the loan amount
    function updateLoanAmountLink() {
        let amountValue = amountInput1.value;
        applyNowLink.href = `Borrower/loanAppSavePage.php?amount=${amountValue}`;
    }

    // Listen for changes to the range slider and update the link
    amountInput1.addEventListener('input', updateLoanAmountLink);

    // Call the function initially to set the default value
    updateLoanAmountLink();
</script>