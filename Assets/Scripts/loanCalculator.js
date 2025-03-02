// Constants and settings
const interestRate = 0.08;
const successFee = 3000;
const lendInterestRate = 0.06;
const durationButtons = document.querySelectorAll('#durationButtons button');
let selectedDuration = 12;
let mode = "borrow";
let isLoaded = false;

// DOM Elements
const amountInput = document.getElementById('amount12');
const amountRange = document.getElementById('amountRange12');
const monthlyRepaymentSpan = document.getElementById('monthlyRepayment');
const totalRepaymentSpan = document.getElementById('totalRepayment');
const borrowBtn = document.getElementById('borrowBtn');
const lendBtn = document.getElementById('lendBtn');
const modeText = document.getElementById('modeText');
const repaymentMode = document.getElementById('repaymentMode');
const ctx = document.getElementById('loanChart').getContext('2d');

// Set initial values
amountRange.value = 50000;
amountInput.value = 50000;

// Initialize the chart with improved configuration
const chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Loan Amount', 'Interest', 'Success Fee'],
        datasets: [{
            data: [0, 0, successFee],
            backgroundColor: ['#007bff', '#6c757d', '#ffc107'],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { 
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: { size: 14 }
                }
            }
        },
        animation: {
            duration: 750,
            easing: 'easeInOutQuart'
        }
    }
});

// Main update function - keeping original name
function updateValues() {
    const loanAmount = parseFloat(amountInput.value);
    let interest, totalRepayment, monthlyRepayment;

    if (mode === "borrow") {
        interest = loanAmount * interestRate * (selectedDuration / 12);
        totalRepayment = loanAmount + interest + successFee;
        monthlyRepayment = totalRepayment / selectedDuration;

        chart.data.labels = ['Loan Amount', 'Interest', 'Success Fee'];
        chart.data.datasets[0].data = [loanAmount, interest, successFee];
    } else {
        interest = loanAmount * lendInterestRate * (selectedDuration / 12);
        totalRepayment = loanAmount + interest;
        monthlyRepayment = interest / selectedDuration;

        chart.data.labels = ['Principal', 'Interest'];
        chart.data.datasets[0].data = [loanAmount, interest];
    }

    // Update displays with formatted numbers
    monthlyRepaymentSpan.textContent = monthlyRepayment.toFixed(2);
    totalRepaymentSpan.textContent = totalRepayment.toFixed(2);
    
    chart.update();
}

// Enhanced input event listeners with validation
amountInput.addEventListener('input', () => {
    const value = Math.min(Math.max(parseFloat(amountInput.value) || 0, amountRange.min), amountRange.max);
    amountRange.value = value;
    amountInput.value = value;
    updateValues();
});

amountRange.addEventListener('input', () => {
    amountInput.value = amountRange.value;
    updateValues();
});

// Improved duration button handling
// Duration button listeners
durationButtons.forEach(button => {
    // Remove the initial loading check since it's causing the issue
    button.classList.remove('btn-primary', 'btn-selected'); // Remove any existing selected states
    button.classList.add('btn-outline-primary');
    
    // Only add selected state to 12 months initially
    if (parseInt(button.getAttribute('data-duration')) === 12) {
        button.classList.add('btn-selected');
        button.classList.remove('btn-outline-primary');
    }

    button.addEventListener('click', () => {
        // Remove selected state from all buttons
        durationButtons.forEach(btn => {
            btn.classList.remove('btn-selected', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        });

        // Add selected state to clicked button
        button.classList.add('btn-selected');
        button.classList.remove('btn-outline-primary');
        selectedDuration = parseInt(button.getAttribute('data-duration'));
        
        updateValues();
    });
});

// Mode toggle handlers
borrowBtn.addEventListener('click', () => {
    mode = "borrow";
    modeText.textContent = "borrow";
    repaymentMode.textContent = "repayable";
    
    // Update button states
    borrowBtn.classList.add('btn-selected', 'btn-primary');
    borrowBtn.classList.remove('btn-outline-primary');
    lendBtn.classList.remove('btn-selected', 'btn-primary');
    lendBtn.classList.add('btn-outline-primary');
    
    updateValues();
});

lendBtn.addEventListener('click', () => {
    mode = "lend";
    modeText.textContent = "lend";
    repaymentMode.textContent = "receivable";
    
    // Update button states
    lendBtn.classList.add('btn-selected', 'btn-primary');
    lendBtn.classList.remove('btn-outline-primary');
    borrowBtn.classList.remove('btn-selected', 'btn-primary');
    borrowBtn.classList.add('btn-outline-primary');
    
    updateValues();
});

// Initialize calculations
updateValues();