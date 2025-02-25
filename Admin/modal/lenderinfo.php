<?php
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';

function getLenderContributionsByLoanId($loanId) {
    $conn = Database::getConnection();

    $sql = "
        SELECT 
            lc.lenderContributionId, 
            lc.loanId, 
            lc.LoanPercent, 
            lc.LoanAmount, 
            lc.RecoveredPrincipal, 
            lc.ReturnedInterest, 
            lc.ExtraAmount, 
            lc.ExtraEarning, 
            u.id AS userId, 
            u.name, 
            u.email, 
            u.mobile, 
            u.address, 
            u.role, 
            u.status
        FROM lendercontribution AS lc
        INNER JOIN users AS u ON lc.lenderId = u.id
        WHERE lc.loanId = ?
    ";

    // Prepare the statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind the loanId parameter
    $stmt->bind_param("i", $loanId);

    // Execute the query
    $stmt->execute();

    // Fetch the results
    $result = $stmt->get_result();

    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    return $data;
}

$lenderContributions = getLenderContributionsByLoanId($loanId);
?>


  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-pIVp8AdKXoxqS5c5akVZ3z6YdNltIQ3tqPFlF1pS5Yd2Y49jVnG1tBsoqyIk7BX/4s0ZFy1X0mUEb6q3Ew8hUw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
      margin: 0;
      padding: 0;
      color: #333;
    }
    h2 {
      font-weight: 700;
      margin-bottom: 40px;
      text-align: center;
      color: #333;
    }
    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      background: #fff;
      transition: transform 0.3s, box-shadow 0.3s;
      animation: fadeIn 0.5s ease-in-out;
      margin-bottom: 30px;
    }
    .card:hover {
      transform: translateY(-10px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }
    .card-header {
      background: linear-gradient(135deg, #007bff, #0056b3);
      color: #fff;
      padding: 15px 20px;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
      font-size: 1.2rem;
      display: flex;
      align-items: center;
    }
    .card-header i {
      margin-right: 10px;
    }
    .card-body {
      padding: 20px;
      line-height: 1.6;
    }
    .card-body p {
      margin: 0 0 10px;
      font-size: 0.95rem;
    }
    .card-body p strong {
      color: #555;
    }
    .card-body hr {
      border-top: 1px solid #eee;
      margin: 15px 0;
    }
    .card-footer {
      background: #f1f1f1;
      padding: 10px 20px;
      border-bottom-left-radius: 15px;
      border-bottom-right-radius: 15px;
      font-size: 0.85rem;
      color: #666;
    }
    .card-footer i {
      margin-right: 5px;
    }
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @media (max-width: 767px) {
      .card-body {
        padding: 15px;
      }
      .card-header, .card-footer {
        padding: 12px 15px;
      }
    }
  </style>
  <div class="container">
      <h2><i class="fa fa-hand-holding-usd"></i> Lender Contributions</h2>
      <div class="row">
        <?php if (!empty($lenderContributions)) : ?>
          <?php foreach($lenderContributions as $contribution) : ?>
            <div class="col-md-6 col-lg-6">
              <div class="card">
                <div class="card-header">
                  <i class="fa fa-user"></i>
                  <?php echo htmlspecialchars($contribution['name']); ?>
                </div>
                <div class="card-body">
                  <!-- Lender Info -->
                  <p><i class="fa fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($contribution['email']); ?></p>
                  <p><i class="fa fa-phone"></i> <strong>Mobile:</strong> <?php echo htmlspecialchars($contribution['mobile']); ?></p>
                  <hr>
                  <!-- Loan Details -->
              <p><i class="fa fa-dollar-sign"></i> <strong>Amount Contributed:</strong> Rs<?php echo number_format($contribution['LoanAmount'], 2); ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else : ?>
          <div class="col-12">
            <div class="alert alert-warning text-center" role="alert">
              <i class="fa fa-exclamation-triangle"></i> No lender contributions found for this loan.
            </div>
          </div>
        <?php endif; ?>
      </div>
  </div>
