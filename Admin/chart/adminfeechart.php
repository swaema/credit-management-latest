<?php
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';

if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit;
}

$conn = Database::getConnection();

// Define the SQL query using a recursive CTE to generate the month series
$sql = "
WITH RECURSIVE month_series AS (
  SELECT DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 4 MONTH), '%Y-%m-01') AS month_date
  UNION ALL
  SELECT DATE_ADD(month_date, INTERVAL 1 MONTH)
  FROM month_series
  WHERE month_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')
)
SELECT 
  DATE_FORMAT(ms.month_date, '%M %Y') AS Month,
  COALESCE(SUM(li.admin_fee), 0) AS TotalAdminFee
FROM month_series ms
LEFT JOIN loaninstallments li 
  ON li.status = 'Paid'
  AND DATE_FORMAT(li.pay_date, '%Y-%m') = DATE_FORMAT(ms.month_date, '%Y-%m')
GROUP BY ms.month_date
ORDER BY ms.month_date
";

// Execute query
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch data into arrays
$months = [];
$adminFees = [];

while ($row = $result->fetch_assoc()) {
    $months[] = $row['Month'];
    $adminFees[] = $row['TotalAdminFee'];
}

// Close connection
$stmt->close();
$conn->close();

// Convert PHP arrays to JSON for JavaScript
$months_json = json_encode($months);
$adminFees_json = json_encode($adminFees);
?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Chart.js Library -->

    <div class="bg-light border rounded shadow p-4" style="width: 80%; margin: auto;">
        <canvas id="adminFeeChart"></canvas>
    </div>

    <script>
        // Get data from PHP
        var months = <?php echo $months_json; ?>;
        var adminFees = <?php echo $adminFees_json; ?>;

        // Create Chart.js Line Chart
        var ctx = document.getElementById('adminFeeChart').getContext('2d');
        var adminFeeChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Total Admin Fee',
                    data: adminFees,
                    backgroundColor: 'transparent',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2
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
