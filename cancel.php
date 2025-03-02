<?php
session_start();
header("Location: /Lender/LoanApplications.php?e=" . urlencode("Payment was cancelled."));
exit;