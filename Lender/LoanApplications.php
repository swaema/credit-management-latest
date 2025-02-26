<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!UserAuth::isLenderAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
$errors = [];
$id = $_SESSION['user_id'];

if (isset($_POST['addLoanApp'])) {
    try {
        $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $term = filter_var($_POST['term'], FILTER_SANITIZE_NUMBER_INT);
        $purpose = htmlspecialchars($_POST['purpose'], ENT_QUOTES, 'UTF-8');
        $now = date('Y-m-d H:i:s');
        $loan = new Loan(null, $id, null, null, null, null, null, $amount, $term, $purpose, 'Pending', $now);
        $result = $loan->saveLoan();
        $error = $result;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

if (isset($_POST['Contribute'])) {
    $loanId = filter_var($_POST['loanId'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $lender_id = $_SESSION['user_id'];
    $amountContributed = $_POST['amountContributed'];
    Loan::contributeLoan($loanId, $lender_id, $amountContributed);
}
$loans = Loan::allLoans("Accepted");
?>

<?php
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<style>
.loan-dashboard {
    background-color: #f8f9fa !important;
}
.loan-card {
    border-radius: 10px !important;
    box-shadow: 0 0 20px rgba(0,0,0,0.05) !important;
    border: none !important;
}
.loan-table {
    margin: 0 !important;
    border-collapse: separate !important;
    border-spacing: 0 8px !important;
}
.loan-table thead th {
    background-color:rgb(2, 9, 21) !important;
    color: white !important;
    font-weight: 600 !important;
    padding: 15px !important;
    border: none !important;
}
.loan-table tbody tr {
    background-color: white !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
    transition: transform 0.2s !important;
}
.loan-table tbody tr:hover {
    transform: translateY(-2px) !important;
}
.loan-table td {
    padding: 15px !important;
    vertical-align: middle !important;
    border: none !important;
}
.profile-img {
    width: 45px !important;
    height: 45px !important;
    border-radius: 50% !important;
    object-fit: cover !important;
    border: 2px solid #e9ecef !important;
}
.contact-info i {
    width: 20px !important;
    color: #6c757d !important;
}
.loan-modal .modal-content {
    border-radius: 15px !important;
    border: none !important;
}
.loan-stats-card {
    background: #fff !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
    height: 100% !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
}
.loan-progress {
    height: 8px !important;
    border-radius: 4px !important;
}
.contribution-form input {
    border-radius: 6px !important;
    border: 1px solid #ced4da !important;
    padding: 10px 15px !important;
}
.btn-contribute {
    padding: 10px 20px !important;
    font-weight: 500 !important;
}
.status-badge {
    padding: 5px 10px !important;
    border-radius: 20px !important;
    font-size: 12px !important;
    font-weight: 500 !important;
}
.alert-custom {
    border-radius: 8px !important;
    border: none !important;
    padding: 15px 20px !important;
}
</style>

<div class="container pb-5 loan-dashboard">
    <div class="container-fluid py-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-info alert-custom alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="loan-card">
            <div class="card-header bg-white py-3">
                <h2 class="h4 mb-0 fw-bold text-primary">Active Loan Applications</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="example" class="table loan-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Contact</th>
                                <th>Amount</th>
                                <th>Installments</th>
                                <th>Employment</th>
                                <th>Purpose</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($loans) && count($loans) > 0): ?>
                                <?php foreach ($loans as $loan): ?>
                                    <?php
                                    $loanAmount = $loan['loanAmount'];
                                    $interestRate = $loan['interstRate'];
                                    $interest = (($loan['noOfInstallments']/12) * $loanAmount * ($interestRate / 100));
                                    $totalAmount = $loanAmount + $interest + ($loanAmount * 0.02);
                                    $monthly = $totalAmount / $loan['noOfInstallments'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../<?php echo htmlspecialchars($loan['image']); ?>"
                                                     class="profile-img me-3"
                                                     onerror="this.src='../uploads/users/default/download.png';">
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($loan['name']); ?></div>
                                                    <small class="text-muted">ID: <?php echo htmlspecialchars($loan['user_id']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="contact-info">
                                                <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($loan['mobile']); ?></div>
                                                <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($loan['email']); ?></div>
                                                <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($loan['address']); ?></div>
                                            </div>
                                        </td>
                                        <td class="fw-bold">Rs<?php echo number_format($loanAmount, 2); ?></td>
                                        <td><?php echo htmlspecialchars($loan['noOfInstallments']); ?> months</td>
                                        <td><?php echo htmlspecialchars($loan['employeementTenure']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($loan['requested_at'])); ?></td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-primary btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#loan-<?php echo htmlspecialchars($loan['id']) ?>">
                                                <i class="fas fa-info-circle me-1"></i>View Details
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Loan Details Modal -->
                                    <div class="modal fade loan-modal" id="loan-<?php echo htmlspecialchars($loan['id']) ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">Loan Application Details</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-4">
                                                        <div class="col-md-6">
                                                            <div class="loan-stats-card">
                                                                <div class="card-body">
                                                                    <h6 class="text-primary mb-4">Loan Information</h6>
                                                                    <div class="mb-3">
                                                                        <label class="text-muted mb-1">Principal Amount</label>
                                                                        <div class="h5">Rs<?php echo number_format($loan['loanAmount'], 2); ?></div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="text-muted mb-1">Interest Rate</label>
                                                                        <div class="h5"><?php echo $loan['interstRate']; ?>%</div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="text-muted mb-1">Total Amount</label>
                                                                        <div class="h5">Rs<?php echo number_format($totalAmount, 2); ?></div>
                                                                    </div>
                                                                    <div>
                                                                        <label class="text-muted mb-1">Monthly Payment</label>
                                                                        <div class="h5">Rs<?php echo number_format($monthly, 2); ?></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="loan-stats-card">
                                                                <div class="card-body">
                                                                    <h6 class="text-primary mb-4">Borrower Details</h6>
                                                                    <div class="mb-3">
                                                                        <label class="text-muted mb-1">Full Name</label>
                                                                        <div class="h5"><?php echo $loan['name']; ?></div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="text-muted mb-1">Annual Income</label>
                                                                        <div class="h5">Rs<?php echo number_format($loan['AnnualIncome'], 2); ?></div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="text-muted mb-1">Employment Status</label>
                                                                        <div class="h5"><?php echo $loan['employeementTenure']; ?></div>
                                                                    </div>
                                                                    <div>
                                                                        <label class="text-muted mb-1">Risk Assessment</label>
                                                                        <div>
                                                                            <span class="status-badge bg-<?php echo $loan['grade'] === 'A' ? 'success' : ($loan['grade'] === 'B' ? 'warning' : 'danger'); ?>">
                                                                                Grade <?php echo $loan['grade']; ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <?php
                                                        $contributed = Loan::getContributedLoan($loan['id']);
                                                        $AmountContributed = $loanAmount * ($contributed / 100);
                                                        ?>

                                                        <?php if($AmountContributed < $loanAmount): ?>
                                                            <div class="col-12">
                                                                <div class="loan-stats-card">
                                                                    <div class="card-body">
                                                                        <h6 class="text-primary mb-4">Contribution Status</h6>
                                                                        <div class="progress loan-progress mb-3">
                                                                            <div class="progress-bar" role="progressbar"
                                                                                 style="width: <?php echo ($AmountContributed/$loanAmount) * 100; ?>%">
                                                                            </div>
                                                                        </div>
                                                                        <div class="text-end mb-4">
                                                                            <small class="text-muted">
                                                                                Funded: Rs <?php echo round($AmountContributed, 2); ?>
                                                                                of Rs <?php echo round($loanAmount, 2); ?>
                                                                            </small>
                                                                        </div>
                                                                        <form action="" method="post" class="contribution-form">
                                                                            <!-- Unique hidden and input fields for this loan -->
                                                                            <input
                                                                                value="<?php echo $AmountContributed; ?>"
                                                                                type="hidden"
                                                                                name="contriamount"
                                                                                id="contriamount_<?php echo $loan['id']; ?>">

                                                                            <input
                                                                                value="<?php echo $loan['email']; ?>"
                                                                                type="hidden"
                                                                                name="email"
                                                                                id="email_<?php echo $loan['id']; ?>">

                                                                            <input
                                                                                value="<?php echo $loan['id']; ?>"
                                                                                type="hidden"
                                                                                name="loanId"
                                                                                id="loanId_<?php echo $loan['id']; ?>">

                                                                            <input
                                                                                value="<?php echo $loanAmount; ?>"
                                                                                type="hidden"
                                                                                name="totalAmount"
                                                                                id="totalAmount_<?php echo $loan['id']; ?>">

                                                                            <div class="input-group">
                                                                                <input
                                                                                    type="number"
                                                                                    name="contribution_amount"
                                                                                    class="form-control"
                                                                                    id="contribution_amount_<?php echo $loan['id']; ?>"
                                                                                    placeholder="Enter your contribution amount"
                                                                                    min="1"
                                                                                    step="any"
                                                                                    required>
                                                                                <button
                                                                                    type="button"
                                                                                    class="btn btn-primary btn-contribute"
                                                                                    onclick="validateAndContribute(<?php echo $loan['id']; ?>)">
                                                                                    <i class="fas fa-hand-holding-usd me-2"></i>Contribute
                                                                                </button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<script>
$(document).ready(function() {
    $('#example').DataTable({
        responsive: true,
        order: [[6, 'desc']],
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        language: {
            search: '<i class="fas fa-search"></i>',
            searchPlaceholder: "Search loans..."
        },
        initComplete: function () {
            $('.dataTables_filter input').addClass('form-control');
        }
    });
});

// function validateAndContribute(loanId) {
//     // Get form elements
//     const amountInput = document.getElementById(`contribution_amount_${loanId}`);
//     const currentContributionInput = document.getElementById(`contriamount_${loanId}`);
//     const totalAmountInput = document.getElementById(`totalAmount_${loanId}`);
//     const loanIdInput = document.getElementById(`loanId_${loanId}`);
//     const emailInput = document.getElementById(`email_${loanId}`);

//     // Validate contribution amount
//     const amountValue = amountInput.value;
//     if (!amountValue) {
//         alert('Please enter a contribution amount');
//         amountInput.focus();
//         return false;
//     }

//     const contributionAmount = parseFloat(amountValue);
//     if (isNaN(contributionAmount) || contributionAmount <= 0) {
//         alert('Please enter a valid amount greater than 0');
//         amountInput.focus();
//         return false;
//     }

//     // Get current and total amounts
//     const currentContribution = parseFloat(currentContributionInput.value) || 0;
//     const totalLoanAmount = parseFloat(totalAmountInput.value);

//     // Calculate extra contribution and earnings
//     let extraAmount = 0;
//     if (currentContribution < totalLoanAmount) {
//         const availableFunding = totalLoanAmount - currentContribution;
//         if (contributionAmount > availableFunding) {
//             extraAmount = contributionAmount - availableFunding;
//         }
//     } else {
//         extraAmount = contributionAmount;
//     }

//     const bonusRate = 0.05;
//     const extraEarnings = extraAmount * bonusRate;

//     // Calculate percentage
//     const percentage = ((contributionAmount / totalLoanAmount) * 100).toFixed(2);

//     // Log contribution details
//     console.log(`You contributed $${contributionAmount.toFixed(2)} in total.`);
//     console.log(`Extra Contribution: $${extraAmount.toFixed(2)} yields extra earnings of $${extraEarnings.toFixed(2)}.`);

//     // Create and show processing overlay
//     const processingDiv = document.createElement('div');
//     processingDiv.id = 'processingOverlay';
//     processingDiv.style.cssText = `
//         position: fixed;
//         top: 0;
//         left: 0;
//         right: 0;
//         bottom: 0;
//         background: rgba(0,0,0,0.5);
//         display: flex;
//         align-items: center;
//         justify-content: center;
//         z-index: 9999;
//     `;
//     processingDiv.innerHTML = `
//         <div style="background: white; padding: 20px; border-radius: 5px; text-align: center;">
//             <div class="spinner-border text-primary" role="status"></div>
//             <div style="margin-top: 10px;">Processing contribution...</div>
//         </div>
//     `;
//     document.body.appendChild(processingDiv);

//     // Make API call
//     fetch('../paymentsuccess/process_contribution.php', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json',
//             'Accept': 'application/json'
//         },
//         body: JSON.stringify({
//             amount: contributionAmount,
//             loan_id: loanIdInput.value,
//             email: emailInput.value,
//             percentage: percentage,
//             extra_amount: extraAmount,
//             extra_earning: extraEarnings
//         }),
//         credentials: 'include'
//     })
//     .then(async response => {
//         console.log('Response status:', response.status);
//         const responseText = await response.text();
//         console.log('Raw response text:', responseText);

//         try {
//             // Only try to parse if we have content
//             if (responseText.trim()) {
//                 const data = JSON.parse(responseText);
//                 if (!response.ok) {
//                     throw new Error(data.error || 'Server error occurred');
//                 }
//                 return data;
//             } else {
//                 throw new Error('Empty response from server');
//             }
//         } catch (e) {
//             throw new Error(`Failed to parse response: ${responseText}`);
//         }
//     })
//     .then(data => {
//         console.log('Parsed response data:', data);
//         if (data.url) {
//             window.location.href = data.url;
//         } else {
//             throw new Error('No redirect URL provided');
//         }
//     })
//     .catch(error => {
//         console.error('Error details:', error);
//         document.getElementById('processingOverlay')?.remove();
//         alert(`Error: ${error.message}`);
//     });

//     return false;
// }
function validateAndContribute(loanId) {
    // Get form elements with error handling
    const elements = {
        amount: document.getElementById(`contribution_amount_${loanId}`),
        currentContribution: document.getElementById(`contriamount_${loanId}`),
        totalAmount: document.getElementById(`totalAmount_${loanId}`),
        loanId: document.getElementById(`loanId_${loanId}`),
        email: document.getElementById(`email_${loanId}`)
    };

    // Validate all elements exist
    for (const [key, element] of Object.entries(elements)) {
        if (!element) {
            console.error(`Missing element: ${key}`);
            alert('System error: Missing form element. Please refresh the page.');
            return false;
        }
    }

    // Validate contribution amount
    const contributionAmount = parseFloat(elements.amount.value);
    if (!elements.amount.value || isNaN(contributionAmount) || contributionAmount <= 0) {
        alert('Please enter a valid amount greater than 0');
        elements.amount.focus();
        return false;
    }

    // Get and validate current and total amounts
    const currentContribution = parseFloat(elements.currentContribution.value) || 0;
    const totalLoanAmount = parseFloat(elements.totalAmount.value);

    if (isNaN(totalLoanAmount)) {
        console.error('Invalid total loan amount:', elements.totalAmount.value);
        alert('System error: Invalid loan amount. Please refresh the page.');
        return false;
    }

    // Calculate contribution details
    const availableFunding = totalLoanAmount - currentContribution;
    let extraAmount = 0;

    // Remove the available funding check
    if (contributionAmount > availableFunding) {
        extraAmount = contributionAmount - availableFunding;
    } else {
        extraAmount = 0;
    }

    const bonusRate = 0.05; // 5% bonus on extra amount
    const extraEarnings = extraAmount * bonusRate;
    const percentage = ((Math.min(contributionAmount, availableFunding) / totalLoanAmount) * 100).toFixed(2);

    console.log({
        totalContribution: `$${contributionAmount.toFixed(2)}`,
        normalContribution: `$${Math.min(contributionAmount, availableFunding).toFixed(2)}`,
        extraAmount: `$${extraAmount.toFixed(2)}`,
        extraEarnings: `$${extraEarnings.toFixed(2)}`,
        percentage: `${percentage}%`
    });

    // Create processing overlay
    const processingOverlay = createProcessingOverlay();
    document.body.appendChild(processingOverlay);

    // Prepare request data
    const requestData = {
        amount: contributionAmount,
        loan_id: elements.loanId.value,
        email: elements.email.value,
        percentage: parseFloat(percentage),
        extra_amount: extraAmount,
        extra_earning: extraEarnings
    };

    // Make API call
    fetch('../paymentsuccess/process_contribution.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(requestData),
        credentials: 'include'
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        const responseText = await response.text();
        console.log('Response:', {
            status: response.status,
            contentType: contentType,
            text: responseText
        });

        // Try to parse response as JSON
        let data;
        try {
            if (responseText.trim()) {
                data = JSON.parse(responseText);
            } else {
                throw new Error('Empty response from server');
            }
        } catch (e) {
            throw new Error(`Invalid response format: ${responseText}`);
        }

        // Check for error response
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Server error occurred');
        }

        return data;
    })
    .then(data => {
        if (!data.url) {
            throw new Error('No redirect URL provided');
        }
        window.location.href = data.url;
    })
    .catch(error => {
        console.error('Contribution error:', error);
        processingOverlay.remove();

        // Show user-friendly error message
        Swal.fire({
            icon: 'error',
            title: 'Contribution Failed',
            text: error.message || 'An error occurred while processing your contribution. Please try again.',
            confirmButtonColor: 'rgb(3, 7, 13)'
        });
    });

    return false;
}

// Helper function to create processing overlay
function createProcessingOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'processingOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;

    overlay.innerHTML = `
        <div class="bg-white p-5 rounded-lg shadow-lg text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="text-lg">Processing your contribution...</div>
        </div>
    `;

    return overlay;
}

/**
 * Example alternate function (not currently wired up).
 * If you need it, make sure to pass the loanId and adjust the DOM lookups similarly.
 */
function validateLoanPercent() {
    // Get input values
    const amountInput = document.getElementById('loanpercent');
    const amountValue = amountInput ? amountInput.value.trim() : '';

    console.log('Attempting contribution with amount:', amountValue);

    if (!amountValue) {
        alert('Please enter an amount');
        return false;
    }

    // The rest of this function would also need dynamic IDs if used in multiple modals
    const contributionAmount = parseFloat(amountValue);
    const currentContribution = parseFloat(document.getElementById('contriamount').value) || 0;
    const totalLoanAmount = parseFloat(document.getElementById('totalAmount').value);
    const loanId = document.getElementById('loanId').value;
    const email = document.getElementById('email').value;

    console.log('Parsed values:', {
        contributionAmount,
        currentContribution,
        totalLoanAmount,
        loanId,
        email
    });

    if (isNaN(contributionAmount) || contributionAmount <= 0) {
        alert('Please enter a valid amount');
        return false;
    }

    if (contributionAmount + currentContribution > totalLoanAmount) {
        alert(`The maximum contribution allowed is $${(totalLoanAmount - currentContribution).toFixed(2)}`);
        return false;
    }

    const percentage = ((contributionAmount / totalLoanAmount) * 100).toFixed(2);

    // Show processing message
    const processingMessage = document.createElement('div');
    processingMessage.id = 'processingMessage';
    processingMessage.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 9999;
    `;
    processingMessage.textContent = 'Processing your contribution...';
    document.body.appendChild(processingMessage);

    // Make the API call
    fetch('../paymentsuccess/process_contribution.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            amount: contributionAmount,
            loan_id: loanId,
            email: email,
            percentage: percentage
        }),
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data);
        if (data.url) {
            window.location.href = data.url;
        } else {
            throw new Error(data.error || 'Payment processing failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.body.removeChild(processingMessage);
        alert(error.message || 'An error occurred while processing your contribution');
    });

    return false;
}

// Check for returning from payment
window.addEventListener('load', function() {
    const contributionData = sessionStorage.getItem('contributionData');
    if (contributionData && window.location.search.includes('success')) {
        const data = JSON.parse(contributionData);
        Swal.fire({
            icon: 'success',
            title: 'Payment Successful!',
            text: `Successfully contributed $${data.amount} (${data.percentage}%)`,
            confirmButtonColor: 'rgb(3, 7, 13)'
        }).then(() => {
            sessionStorage.removeItem('contributionData');
            sessionStorage.removeItem('contributionInProgress');
        });
    }
});

// Additional styling for DataTables
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 20px !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px !important;
            padding: 8px 16px !important;
            padding-left: 40px !important;
            border: 1px solid #dee2e6 !important;
            width: 300px !important;
        }

        .dataTables_wrapper .dataTables_filter i {
            position: absolute !important;
            left: 15px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #6c757d !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background:rgb(3, 7, 13) !important;
            border-color:rgb(3, 10, 20) !important;
            color: white !important;
            border-radius: 20px !important;
            padding: 5px 15px !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:not(.current) {
            border-radius: 20px !important;
            padding: 5px 15px !important;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 20px !important;
            padding: 5px 10px !important;
            border: 1px solid #dee2e6 !important;
        }

        .dataTables_wrapper .dataTables_info {
            color: #6c757d !important;
        }

        .swal2-popup.swal2-modal.swal2-loading {
            background-color: rgba(255, 255, 255, 0.9) !important;
        }
    `;
    document.head.appendChild(style);
});
</script>
