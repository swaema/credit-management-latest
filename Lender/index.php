<?php
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = Database::getConnection();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (!UserAuth::isLenderAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
$id = $_SESSION['user_id'];
$activeLoans = 0;
$totalProfit = 0;
// Fetch dashboard data
$amount = $conn->query("SELECT `Amount` FROM `consoledatedfund` WHERE `user_id` = $id")->fetch_assoc()['Amount']??0;
$totalLoans = $conn->query("SELECT COUNT(DISTINCT id) AS count FROM loans where status = 'Pending'")->fetch_assoc()['count']??0;
$totalBorrowers = $conn->query("SELECT COUNT(DISTINCT id) AS count FROM users where `role` ='borrower' AND `user_verfied`='verified' and status ='Active'")->fetch_assoc()['count']??0;
$profit = $conn->query("SELECT `Earning` FROM `consoledatedfund` WHERE `user_id` = $id")->fetch_assoc()['Earning']??0;
$consolidatedAmount = Loan::getConsolidatedAmount($id);
$contributeTotal = Loan::getContributeTotal($id);

include_once('Layout/head.php');
include_once('Layout/sidebar.php'); 
?>

<div class="container bg-light pb-5 mt-5" style="background-color: #f8f9fa;">
    <div class="container my-5">
        <div class="dashboard-header mb-5">
            <h1 class="text-center position-relative" style="color: #2c3e50; font-weight: 700;">
                Loan Dashboard
                <div class="position-absolute w-25" style="height: 4px; background: linear-gradient(to right, #3498db, #2ecc71); bottom: -10px; left: 50%; transform: translateX(-50%);"></div>
            </h1>
        </div>

        <div class="row g-4 mb-8">
            <!-- Available Amount Card -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm hover-card" style="border-radius: 15px; transition: transform 0.3s ease;">
                    <div class="card-body p-4" style="background: linear-gradient(135deg, #3498db, #2980b9); border-radius: 15px;">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-wallet fa-2x me-3 text-white"></i>
                            <h5 class="card-title mb-0 text-white">Amount Available</h5>
                        </div>
                        <p class="card-text display-4 text-center text-white mb-0">Rs<?php echo number_format($amount, 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Loans Card -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm hover-card" style="border-radius: 15px; transition: transform 0.3s ease;">
                    <div class="card-body p-4" style="background: linear-gradient(135deg, #2ecc71, #27ae60); border-radius: 15px;">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-file-invoice-dollar fa-2x me-3 text-white"></i>
                            <h5 class="card-title mb-0 text-white">Total Loans</h5>
                        </div>
                        <p class="card-text display-4 text-center text-white mb-0"><?php echo $totalLoans; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Borrowers Card -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm hover-card" style="border-radius: 15px; transition: transform 0.3s ease;">
                    <div class="card-body p-4" style="background: linear-gradient(135deg, #f1c40f, #f39c12); border-radius: 15px;">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-users fa-2x me-3 text-white"></i>
                            <h5 class="card-title mb-0 text-white">Total Borrowers</h5>
                        </div>
                        <p class="card-text display-4 text-center text-white mb-0"><?php echo $totalBorrowers; ?></p>
                    </div>
                </div>
            </div>

            <!-- Profit Earned Card -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm hover-card" style="border-radius: 15px; transition: transform 0.3s ease;">
                    <div class="card-body p-4" style="background: linear-gradient(135deg, #e74c3c, #c0392b); border-radius: 15px;">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-chart-line fa-2x me-3 text-white"></i>
                            <h5 class="card-title mb-0 text-white">Profit Earned</h5>
                        </div>
                        <p class="card-text display-4 text-center text-white mb-0">Rs<?php echo number_format($profit, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="container row mt-6 bg-light shadow border rounded p-4" style="width: 80%; margin: auto;">
        <canvas id="profitChart"></canvas>
    </div>
</div>

<style>
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.card-body {
    position: relative;
    overflow: hidden;
}

.card-body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
    z-index: 1;
}

.card-body > * {
    position: relative;
    z-index: 2;
}

@media (max-width: 768px) {
    .col-md-3 {
        margin-bottom: 20px;
    }
    
    .display-4 {
        font-size: 2.5rem;
    }
}
</style>

<!-- Include Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('profitChart').getContext('2d');
const profitChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Consolidated Amount', 'Contribute Total'],
        datasets: [{
            label: 'Amount (Rs)',
            data: [<?php echo $consolidatedAmount; ?>, <?php echo $contributeTotal; ?>],
            backgroundColor: [
                'rgba(52, 152, 219, 0.2)',
                'rgba(46, 204, 113, 0.2)'
            ],
            borderColor: [
                'rgba(52, 152, 219, 1)',
                'rgba(46, 204, 113, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php include_once('Layout/footer.php'); ?>
