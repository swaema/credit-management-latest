<!-- Payment Details Modal -->
<div class="modal fade modal-custom" id="paymentModal<?php echo $loan['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice me-2"></i>Loan Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <?php 
                $percent = Loan::calculatePercent($loan['id']);
                
                // Fix for DateTime deprecation
                $acceptedDate = !empty($loan['Accepted_Date']) ? $loan['Accepted_Date'] : date('Y-m-d');
                $date = new DateTime($acceptedDate);
                $enddate = clone $date;
                $enddate->modify('+' . $loan['noOfInstallments'] . ' months');
                
                // Fix for potential null values in installment calculation
                $installment = LoanInstallments::InstallmentAmountbyLoanId($loan['id']);
                $totalPaid = isset($installment['total_paid']) ? floatval($installment['total_paid']) : 0;
                
                // Calculate remaining balance with null check
                $remainingBalance = Loan::getPendingAmount();
                ?>

                <!-- Progress Section -->
                <div class="info-card mb-4">
                    <h6 class="text-primary mb-3">Payment Progress</h6>
                    <div class="progress custom-progress mb-2">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $percent ?>%" 
                             aria-valuenow="<?php echo $percent ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small>Total Progress</small>
                        <small class="fw-bold"><?php echo number_format((float)$percent, 2) ?>%</small>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Timeline Section -->
                    <div class="col-md-6">
                        <div class="info-card h-100">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calendar me-2"></i>Loan Timeline
                            </h6>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Start Date</span>
                                <strong><?php echo $date->format('M d, Y') ?></strong>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span>End Date</span>
                                <strong><?php echo $enddate->format('M d, Y') ?></strong>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Term Length</span>
                                <strong><?php echo $loan['noOfInstallments'] ?> months</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details Section -->
                    <div class="col-md-6">
                        <div class="info-card h-100">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-dollar-sign me-2"></i>Payment Details
                            </h6>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Principal Amount</span>
                                <strong>Rs<?php echo number_format((float)$loan['loanAmount'], 2) ?></strong>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Interest Rate</span>
                                <strong><?php echo number_format((float)$loan['interstRate'], 2) ?>%</strong>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span>Monthly Payment</span>
                                <strong>Rs<?php echo number_format((float)$loan['InstallmentAmount'], 2) ?></strong>
                            </div>
                         
                        </div>
                    </div>

                    <!-- Payment Status Section -->
                    <div class="col-12">
                        <div class="info-card">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-chart-pie me-2"></i>Payment Status
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="detail-row">
                                        <span>Amount Paid</span>
                                        <strong class="d-block mt-1 text-success">
                                            $<?php echo number_format((float)$totalPaid, 2) ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            
            </div>

            <div class="modal-footer flex-column">
        <div class="w-100 text-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
</div>

<style>
.payment-form .form-control {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.2s;
}

.payment-form .form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.payment-btn {
    padding: 10px 24px;
    font-weight: 500;
    transition: all 0.3s;
}

.payment-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
}

.payment-summary .card {
    border: none;
    border-radius: 8px;
    transition: all 0.3s;
}

.payment-summary .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11);
}
.StripeElement {
    background-color: white;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #ced4da;
    box-shadow: 0 1px 3px 0 #e6ebf1;
    -webkit-transition: box-shadow 150ms ease;
    transition: box-shadow 150ms ease;
    margin-bottom: 1rem;
}

.StripeElement--focus {
    box-shadow: 0 1px 3px 0 #cfd7df;
}

.StripeElement--invalid {
    border-color: #fa755a;
}

.StripeElement--webkit-autofill {
    background-color: #fefde5 !important;
}
</style>
        </div>
    </div>
</div>

<script>
// Global Stripe initialization
const stripeInstance = Stripe('<?php echo $config['stripe']['admin']['publishable_key']; ?>');
const elementInstances = new Map();

// Initialize Stripe Elements for a specific loan
function initializeStripeElements(loanId) {
    if (elementInstances.has(loanId)) {
        return elementInstances.get(loanId);
    }

    const elements = stripeInstance.elements();
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        }
    });

    elementInstances.set(loanId, cardElement);
    return cardElement;
}

// Handle payment form initialization
function initializePaymentForm(form) {
    const loanId = form.querySelector('input[name="loanId"]').value;
    const cardElement = initializeStripeElements(loanId);
    const cardElementMount = document.getElementById(`card-element-${loanId}`);
    const displayError = document.getElementById(`card-errors-${loanId}`);

    if (cardElementMount) {
        cardElement.mount(`#card-element-${loanId}`);

        // Handle real-time validation errors
        cardElement.on('change', function(event) {
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Handle form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');

            try {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                const formData = new FormData(form);
                const response = await fetch('process-payment.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.url) {
                    window.location.href = result.url;
                } else {
                    throw new Error(result.error || 'Payment processing failed');
                }

            } catch (error) {
                console.error('Payment Error:', error);
                displayError.textContent = error.message;
                
                // Show error alert
                Swal.fire({
                    title: 'Error!',
                    text: error.message,
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Make Payment';
            }
        });
    }
}

// Initialize all payment forms when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.payment-form').forEach(initializePaymentForm);
});

// Handle modal show event to ensure proper card element mounting
document.addEventListener('shown.bs.modal', function(event) {
    const modal = event.target;
    const form = modal.querySelector('.payment-form');
    if (form) {
        const loanId = form.querySelector('input[name="loanId"]').value;
        const cardElement = elementInstances.get(loanId);
        if (cardElement) {
            cardElement.mount(`#card-element-${loanId}`);
        }
    }
});
</script>