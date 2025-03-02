const lendInterestRate = 0.0; 
let mode = "borrow"; 
let isLoaded = false;

const amountInput = document.getElementById('amount');
const amountRange = document.getElementById('amountRange');
const interestInput = document.getElementById('interestInput'); 
const termInput = document.getElementById('termInput'); 
const monthlyRepaymentSpan = document.getElementById('monthlyRepayment');
const totalRepaymentSpan = document.getElementById('totalRepayment');
const totalLoanInput = document.getElementById('totalloan');

const modeText = document.getElementById('modeText');
const repaymentMode = document.getElementById('repaymentMode');
const ctx = document.getElementById('loanChart').getContext('2d');

// Removed fixed success fee; processing fee will be calculated as 2% of loanAmount
// const successFee = 3000;

// Initialize the chart with initial data based on an example loanAmount (50000)
const initialLoanAmount = 50000;
const initialProcessingFee = initialLoanAmount * 0.02;
const chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Loan Amount', 'Interest', 'Processing Fee'],
        datasets: [{
            data: [initialLoanAmount, 0, initialProcessingFee],
            backgroundColor: ['#007bff', '#6c757d', '#ffc107'],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                display: true,
                labels: {
                    padding: 20,
                    font: { size: 14 }
                }
            }
        }
    }
});

// Function to update the values using the same formula as LoanHelper::calculateLoanDetails()
// For borrow mode:
//   interest = loanAmount * (selectedDuration/12) * (interestRate/100)
//   processingFee = loanAmount * 0.02
//   totalRepayment = loanAmount + interest + processingFee
//   monthlyRepayment = totalRepayment / selectedDuration
function updateValues() {
    const loanAmount = parseFloat(amountInput.value) || 0;
    const selectedInterestRate = parseFloat(interestInput.value) / 100 || 0;
    const selectedDuration = parseInt(termInput.value) || 36;

    // Calculate interest based on the duration in months
    const interest = loanAmount * selectedInterestRate * (selectedDuration / 12);

    // Calculate processing fee (2% of the loan amount)
    const processingFee = loanAmount * 0.02;

    // Calculate total repayment and monthly installment
    const totalRepayment = loanAmount + interest + processingFee;
    const monthlyRepayment = totalRepayment / selectedDuration;

    // Update displays with formatted values
    monthlyRepaymentSpan.textContent = monthlyRepayment.toFixed(2);
    totalRepaymentSpan.textContent = totalRepayment.toFixed(2);
    totalLoanInput.value = totalRepayment.toFixed(2);

    // Update chart data
    chart.data.datasets[0].data = [loanAmount, interest, processingFee];
    chart.update();
}

// Input field listeners
amountInput.addEventListener('input', () => {
    amountRange.value = amountInput.value;
    updateValues();
});

amountRange.addEventListener('input', () => {
    amountInput.value = amountRange.value;
    updateValues();
});

interestInput.addEventListener('input', updateValues);
termInput.addEventListener('input', updateValues);

// Initial update
updateValues();