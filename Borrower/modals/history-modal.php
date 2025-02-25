<!-- History Modal -->
<div class="modal fade modal-custom" id="historyModal<?php echo $loan['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>Payment History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                $loanInstallments = LoanInstallments::loanInstallmentbyLoanId($loan['id']);
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($loanInstallments)): ?>
                                <?php foreach ($loanInstallments as $installment): ?>
                                    <tr>
                                        <td>Rs<?php echo number_format($installment['payable_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($installment['pay_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $installment['status'] == 'Paid' ? 'success' : 'warning'; ?>">
                                                <?php echo $installment['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No payment history available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>