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

// Fixed success fee
const successFee = 3000;

// Initialize the chart
const chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Loan Amount', 'Interest', 'Success Fee'],
        datasets: [{
            data: [50000, 0, successFee],
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

// Function to update the values
function updateValues() {
    const loanAmount = parseFloat(amountInput.value) || 0;
    const selectedInterestRate = parseFloat(interestInput.value) / 100 || 0; 
    const selectedDuration = parseInt(termInput.value) || 36;

    // Calculate interest
    const interest = loanAmount * selectedInterestRate * (selectedDuration / 12);
    
    // Calculate total and monthly repayment
    const totalRepayment = loanAmount + interest + successFee;
    const monthlyRepayment = totalRepayment / selectedDuration;

    // Update displays
    monthlyRepaymentSpan.textContent = monthlyRepayment.toFixed(2);
    totalRepaymentSpan.textContent = totalRepayment.toFixed(2);
    totalLoanInput.value = totalRepayment.toFixed(2);

    // Update chart data
    chart.data.datasets[0].data = [loanAmount, interest, successFee];
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