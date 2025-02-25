<!-- Enhanced Loan Details Modal -->
<div class="modal fade modal-custom" id="detailsModal<?php echo $loan['id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?php echo $loan['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel<?php echo $loan['id']; ?>">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    Loan Application Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-money-bill-wave text-primary me-2"></i>
                                <h6 class="mb-0">Loan Amount</h6>
                            </div>
                            <p class="h4 text-primary mb-0">Rs<?php echo number_format($loan['loanAmount'], 2); ?></p>
                        </div>

                        <div class="detail-row">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-percentage text-success me-2"></i>
                                <h6 class="mb-0">Interest Rate</h6>
                            </div>
                            <p class="h4 text-success mb-0"><?php echo htmlspecialchars($loan['interstRate'] ?? 0); ?>%</p>
                        </div>

                        <div class="detail-row">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-clock text-warning me-2"></i>
                                <h6 class="mb-0">Installments</h6>
                            </div>
                            <p class="h4 text-warning mb-0"><?php echo htmlspecialchars($loan['noOfInstallments'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="detail-row">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-briefcase text-info me-2"></i>
                                <h6 class="mb-0">Employment Tenure</h6>
                            </div>
                            <p class="h4 text-info mb-0"><?php echo htmlspecialchars($loan['employeementTenure'] ?? 'N/A'); ?> years</p>
                        </div>

                        <div class="detail-row">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-tasks text-secondary me-2"></i>
                                <h6 class="mb-0">Loan Purpose</h6>
                            </div>
                            <p class="h4 text-secondary mb-0"><?php echo htmlspecialchars($loan['loanPurpose'] ?? 'N/A'); ?></p>
                        </div>

                        <div class="detail-row">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar-alt text-muted me-2"></i>
                                <h6 class="mb-0">Application Date</h6>
                            </div>
                            <p class="h4 text-muted mb-0"><?php echo isset($loan['requested_at']) ? date('M d, Y', strtotime($loan['requested_at'])) : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="mt-4 p-3 bg-light rounded">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        <h6 class="mb-0">Loan Progress</h6>
                    </div>
                    <div class="custom-progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">Start Date</small>
                        <small class="text-muted">Expected Completion</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary action-btn" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>