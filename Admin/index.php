<?php
//index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';

// Authentication check
if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit();
}

// Database connection
$conn = Database::getConnection();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_SESSION['user_id'];

// Fetch dashboard data
$queries = [
    'activeLoans' => "SELECT COUNT(DISTINCT id) AS count FROM loans WHERE status = 'Accepted'",
    'totalLoans' => "SELECT COUNT(DISTINCT id) AS count FROM loans WHERE status = 'Pending'",
    'totalBorrowers' => "SELECT COUNT(DISTINCT id) AS count FROM users WHERE `role` = 'borrower' AND `user_verfied` = 'verified' AND status = 'Active'",
    'totalLenders' => "SELECT COUNT(DISTINCT id) AS count FROM users WHERE `role` = 'lender' AND `user_verfied` = 'verified' AND status = 'Active'",
    // 'profit' => "SELECT `Earning` FROM `consoledatedfund` WHERE `user_id` = $id"
    'profit' => "SELECT COALESCE(SUM(admin_fee), 0) as Earning 
    FROM loaninstallments 
    WHERE status = 'Paid'"
];

$dashboardData = [];
foreach ($queries as $key => $query) {
    $result = $conn->query($query);
    $dashboardData[$key] = ($key === 'profit') ? 
        ($result->fetch_assoc()['Earning'] ?? 0) : 
        ($result->fetch_assoc()['count'] ?? 0);
}

$conn->close();

include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<div class="col-md-10 pb-5" style="background-color: #ECF0F4;">
    <div class="container my-5">
        <div class="dashboard-header text-center mb-5">
            <h1 class="fw-bold">Loan Dashboard</h1>
            <div class="dashboard-header-underline"></div>
        </div>

        <div class="row g-4">
            <!-- Profit Card -->
            <div class="col-md-3">
                <div class="card dashboard-card h-100 bg-success text-white">
                    <div class="card-body d-flex flex-column align-items-center">
                        <div class="dashboard-icon mb-3">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                        <h5 class="card-title fw-bold">Profit</h5>
                        <p class="card-text display-4 mb-0"><?php echo number_format($dashboardData['profit']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Active Loans Card -->
            <div class="col-md-3">
                <div class="card dashboard-card h-100 bg-primary text-white">
                    <div class="card-body d-flex flex-column align-items-center">
                        <div class="dashboard-icon mb-3">
                            <i class="fas fa-file-invoice-dollar fa-2x"></i>
                        </div>
                        <h5 class="card-title fw-bold">Active Loans</h5>
                        <p class="card-text display-4 mb-0"><?php echo number_format($dashboardData['activeLoans']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Borrowers Card -->
            <div class="col-md-3">
                <div class="card dashboard-card h-100 bg-warning text-white">
                    <div class="card-body d-flex flex-column align-items-center">
                        <div class="dashboard-icon mb-3">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <h5 class="card-title fw-bold">Total Borrowers</h5>
                        <p class="card-text display-4 mb-0"><?php echo number_format($dashboardData['totalBorrowers']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Lenders Card -->
            <div class="col-md-3">
                <div class="card dashboard-card h-100 bg-danger text-white">
                    <div class="card-body d-flex flex-column align-items-center">
                        <div class="dashboard-icon mb-3">
                            <i class="fas fa-handshake fa-2x"></i>
                        </div>
                        <h5 class="card-title fw-bold">Total Lenders</h5>
                        <p class="card-text display-4 mb-0"><?php echo number_format($dashboardData['totalLenders']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-header-underline {
    width: 100px;
    height: 4px;
    background: linear-gradient(to right, #4CAF50, #2196F3);
    margin: 10px auto;
    border-radius: 2px;
}

.dashboard-card {
    transition: transform 0.3s ease-in-out;
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.dashboard-card:hover {
    transform: translateY(-5px);
}

.dashboard-icon {
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-body {
    padding: 1.5rem;
}

.display-4 {
    font-size: 2.5rem;
    font-weight: 600;
}
</style>

<?php include_once('Layout/footer.php'); ?>