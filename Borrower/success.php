<?php
require_once '../Classes/LoanInstallments.php';
if (isset($_GET['insId'])) {
    $insId = $_GET['insId'];
    LoanInstallments::updateStatus($insId);
}