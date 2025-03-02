<?php
// Initialize success and error messages
$messageSuccess = "";
$messageError = "";

// Check for success or error messages in the URL
if (isset($_GET['s'])) {
    $messageSuccess = $_GET['s'];
}
if (isset($_GET['e'])) {
    $messageError = $_GET['e'];
}

// Load configuration
$config = require 'config.php';

// Include layout files
include_once('Layout/head.php');
include_once('Layout/header.php');
include_once('Layout/carousal.php');
?>

<!-- Add Stripe.js before your content -->
<script src="https://js.stripe.com/v3/"></script>

<!-- Main Content -->
<div class="container-fluid px-0">
    <!-- Add Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Make a Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="payment-form">
                        <div id="card-element" class="mb-3">
                            <!-- Stripe Card Element will be inserted here -->
                        </div>
                        <div id="error-message" class="alert alert-danger" style="display: none;"></div>
                        <button type="submit" class="btn btn-primary w-100">Pay $5000.00</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Financing Solutions Section -->
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12">
                <h1 class="text-primary text-center fw-bold" style="font-family: 'Times New Roman', Times, serif;">
                    Discover our financing solutions
                </h1>
            </div>
        </div>

        <!-- Add a payment button -->
        <!-- <div class="row mb-4">
            <div class="col-12 text-center">
                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    Make a Test Payment
                </button>
            </div>
        </div> -->

        <div class="row g-4 justify-content-center">
            <!-- Micro Finance Card -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm hover-shadow transition-all">
                    <div class="card-header py-4" style="background-color: #A1C3E5;">
                        <h3 class="text-center mb-0 fw-bold">Micro Finance</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-center mb-0">Access our quick, short-term financial solution to bridge your immediate cash flow needs.</p>
                    </div>
                </div>
            </div>

            <!-- Working Capital Card -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm hover-shadow transition-all">
                    <div class="card-header py-4" style="background-color: #A3DDCB;">
                        <h3 class="text-center mb-0 fw-bold">Working Capital</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-center mb-0">Meet your working capital requirements to ensure uninterrupted business operations.</p>
                    </div>
                </div>
            </div>

            <!-- Supply Chain Card -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm hover-shadow transition-all">
                    <div class="card-header py-4" style="background-color: #F8C76D;">
                        <h3 class="text-center mb-0 fw-bold">Supply Chain</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-center mb-0">Get funds for local purchase or import of raw materials or goods.</p>
                    </div>
                </div>
            </div>

            <!-- Business Expansion Card -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm hover-shadow transition-all">
                    <div class="card-header py-4" style="background-color: #FA6557;">
                        <h3 class="text-center mb-0 fw-bold">Business Expansion</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-center mb-0">Unlock funds tailored for capex, project finance, asset finance, and more.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <p class="text-muted mb-2">About SafeFund</p>
                    <h2 class="text-primary fw-bold mb-4" style="font-family: 'Times New Roman', Times, serif;">
                        Simplifying SME financing in Mauritius
                    </h2>
                    <p class="lead" style="font-family: 'Times New Roman', Times, serif; line-height: 1.8;">
                        Launched in 2019, our mission is to simplify and democratise SME financing in Mauritius. As SMEs are the 
                        backbone of the Mauritian economy, we are committed to solving the SME financing gap, while providing a 
                        convenient and new asset class with attractive returns to individual and institutional investors.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Testimonials Section -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center mb-5">
                <p class="text-muted mb-2">Testimonials</p>
                <h2 class="text-primary fw-bold" style="font-family: 'Times New Roman', Times, serif;">
                    Trusted by SMEs and investors
                </h2>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-5">
                <div class="card h-100 border-0 shadow-sm" style="background-color: #85D5E5;">
                    <div class="card-body p-4 p-lg-5">
                        <p class="card-text mb-4 lead">
                            "Safefund helped me raise finance for Zakda Wrought Iron Ltd with unbelievable rapidity and simplicity. 
                            They came to my support and help me win large contracts on the back of their faith in my business and 
                            its growth prospects."
                        </p>
                        <h5 class="fw-bold text-white mb-0">Anonymous</h5>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100 border-0 shadow-sm" style="background-color: #85D5E5;">
                    <div class="card-body p-4 p-lg-5">
                        <p class="card-text mb-4 lead">
                            "Safefund allows me to give loans entrepreneurs from diverse sectors in Mauritius, within just 20 minutes. 
                            It has strong governance in place, predicated on in independent third party firm that scrutinises the 
                            entrepreneur's financials, and a strong credit committee that thoroughly screens all deals."
                        </p>
                        <h5 class="fw-bold text-white mb-0">Anonymous</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stripe Integration JavaScript -->
<script>
    // Initialize Stripe with your publishable key
    const stripe = Stripe('<?php echo $config['stripe']['admin']['publishable_key']; ?>');
    const elements = stripe.elements();
    
    // Create card Element and mount it
    const card = elements.create('card');
    card.mount('#card-element');

    // Handle form submission
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        // Disable the submit button to prevent multiple submissions
        const submitButton = form.querySelector('button');
        submitButton.disabled = true;
        
        try {
            // Create PaymentIntent on the server
            const response = await fetch('create-payment-intent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    amount: 5000000 // $50.00 in cents
                })
            });

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            // Confirm the payment
            const result = await stripe.confirmCardPayment(data.clientSecret, {
                payment_method: {
                    card: card
                }
            });

            if (result.error) {
                throw new Error(result.error.message);
            }

            // Payment successful
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            modal.hide();
            
            // Show success message
            window.location.href = '?s=' + encodeURIComponent('Payment successful!');
        } catch (error) {
            const errorElement = document.getElementById('error-message');
            errorElement.textContent = error.message;
            errorElement.style.display = 'block';
            submitButton.disabled = false;
        }
    });
</script>

<!-- Include Page End -->

<?php include 'Layout/footer.php' ?>

<!-- Toast Notifications -->
<?php if (!empty($messageSuccess)): ?>
    <script>
        toastr.options = {
            "progressBar": true,
            "closeButton": true,
        }
        toastr.success("<?php echo $messageSuccess; ?>");
    </script>
<?php elseif (!empty($messageError)): ?>
    <script>
        toastr.options = {
            "progressBar": true,
            "closeButton": true,
        }
        toastr.error("<?php echo $messageError; ?>");
    </script>
<?php endif; ?>

<!-- Include Footer -->
<?php include_once('Layout/footer.php'); ?>

<!-- Styles -->
<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.transition-all {
    transition: all 0.3s ease;
}
.card {
    border: none;
    border-radius: 10px;
    overflow: hidden;
}
.card-header {
    border-bottom: none;
}
.lead {
    font-size: 1.1rem;
}
@media (min-width: 992px) {
    .lead {
        font-size: 1.25rem;
    }
}

/* Stripe element styles */
#card-element {
    padding: 1rem;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    background-color: white;
}
</style>