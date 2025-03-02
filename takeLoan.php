</head>

<body>
  <div class="container">
    <div class="row d-flex justify-content-center">
      <div class="col-8 loan-form-card px-5 pb-5 mt-4">
        <!-- Step Progress Bar -->
        <div class="loan-step-container pt-4">
          <div class="loan-step loan-step-active" id="step1-indicator">Financing Demand</div>
          <div class="loan-step" id="step2-indicator">Personal Information</div>
          <div class="loan-step" id="step3-indicator">Consent</div>
        </div>

        <!-- Multi-step form -->
        <form method="post" id="multi-step-form">
          <!-- Step 1: Financing Need -->
          <div id="step1" class="loan-form-step loan-form-step-active">
            <div class="mb-3">
              <label for="fundingAmountRange" class="loan-form-label">Desired Funding Amount</label>
              <input type="range" class="loan-range" name="loanAmount" id="fundingAmountRange" min="1000" max="100000"
                step="1000" value="50000">
              <div id="fundingAmountDisplay" class="loan-amount-display">50000</div>
            </div>
            <?php

            ?>
            <div class="row justify-content-between">
              <div class="col-6">
                <label for="purpose" class="loan-form-label">Loan Purpose</label>
                <select class="loan-form-control" id="purpose" name="purpose" required>
                  <option value="credit_card">Credit Card</option>
                  <option value="debt_consolidation">Debt Consolidation</option>
                  <option value="home_improvement">Home Improvement</option>
                  <option value="major_purchase">Major Purchase</option>
                  <option value="medical">Medical</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-2 mt-4">
                <button type="button" class="loan-btn loan-btn-primary px-3" id="nextBtnStep1"
                  onclick="changeStep(1)">Next<span class="ms-2">&rarr;</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Step 2: Business Information -->
          <div id="step2" class="loan-form-step hidden">
            <div class="row">
              <div class="col-6">
                <label for="monthlySalary" class="loan-form-label">Monthly Salary</label>
                <input type="number" class="loan-form-control" name="monthlySalary" id="monthlySalary"
                  placeholder="Enter monthly salary" required>
                <label for="annualIncome" class="loan-form-label">Annual Income</label>
                <input type="number" class="loan-form-control" name="annualIncome" id="annualIncome"
                  placeholder="Enter annual income" required>
              </div>
              <div class="col-6">
                <label for="employmentTenure" class="loan-form-label">Employment Tenure (in years)</label>
                <input type="number" class="loan-form-control" name="employmentTenure" id="employmentTenure"
                  placeholder="Enter employment tenure" required>
              </div>
              <div class="col-6">
                <label for="purpose" class="loan-form-label">Loan Purpose</label>
                <input type="text" name="purpose" placeholder="Describe the purpose of the loan" class="loan-form-control"
                  id="purpose" required>
              </div>
            </div>
            <div class="row justify-content-between">
              <div class="col-2">
                <button type="button" class="loan-btn loan-btn-primary px-2" id="prevBtnStep2"
                  onclick="changeStep(-1)"><span class="me-2">&larr;</span>Previous</button>
              </div>
              <div class="col-2">
                <button type="button" class="loan-btn loan-btn-primary px-3" id="nextBtnStep2"
                  onclick="changeStep(1)">Next<span class="ms-2">&rarr;</span></button>
              </div>
            </div>
          </div>

          <!-- Step 3: Contact Details -->
          <div id="step3" class="loan-form-step hidden">
            <div class="row">
              <div class="col-6">
                <label for="collateral" class="loan-form-label">Collateral (if applicable)</label>
                <input type="text" class="loan-form-control" name="collateral" id="collateral"
                  placeholder="Enter collateral (if any)">
              </div>
              <div class="col-12 mt-3">
                <div class="loan-form-check">
                  <input class="loan-form-check-input" name="consent" type="checkbox" value="" id="consent" required>
                  <label class="loan-form-check-label" for="consent">
                    I consent to allow the organization to access my credit history and financial
                    records.
                  </label>
                </div>
              </div>
            </div>
            <div class="row justify-content-between">
              <div class="col-2">
                <button type="button" class="loan-btn loan-btn-primary px-2" id="prevBtnStep3"
                  onclick="changeStep(-1)"><span class="me-2">&larr;</span>Previous</button>
              </div>
              <div class="col-2">
                <!-- <input type="submit" style="border-radius: 25px;" class="loan-btn loan-btn-primary px-3" value="Submit->"
                  name="addLoanApp"> -->
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script>
    const steps = document.querySelectorAll('.loan-form-step');
    const indicators = document.querySelectorAll('.loan-step');

    function changeStep(direction) {
      const activeStep = document.querySelector('.loan-form-step-active');
      const currentIndex = Array.from(steps).indexOf(activeStep);
      const nextIndex = currentIndex + direction;

      if (nextIndex >= 0 && nextIndex < steps.length) {
        // Hide the current step
        activeStep.classList.remove('loan-form-step-active');
        steps[currentIndex].classList.add('hidden');

        // Show the next step
        steps[nextIndex].classList.remove('hidden');
        steps[nextIndex].classList.add('loan-form-step-active');

        // Update the indicator
        indicators[currentIndex].classList.remove('loan-step-active');
        indicators[nextIndex].classList.add('loan-step-active');
      }
    }

    // Update funding amount display
    const fundingAmountRange = document.getElementById('fundingAmountRange');
    const fundingAmountDisplay = document.getElementById('fundingAmountDisplay');

    fundingAmountRange.addEventListener('input', function () {
      fundingAmountDisplay.textContent = fundingAmountRange.value;
    });
  </script>