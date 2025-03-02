<?php
// Borrowers.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/User.php';
require_once '../Classes/Mail.php';

if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit;
}

$error = isset($_GET['e']) ? $_GET['e'] : '';
$success = isset($_GET['s']) ? $_GET['s'] : '';

// Handle user updates
if (isset($_POST['updateUser'])) {
    try {
        $userId = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $mobile = filter_var($_POST['mobile'], FILTER_SANITIZE_STRING);
        $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);

        $updated = User::updateUser($userId, [
            'name'    => $name,
            'email'   => $email,
            'mobile'  => $mobile,
            'address' => $address
        ]);

        if ($updated) {
            header('Location: Borrowers.php?s=User updated successfully');
            exit;
        } else {
            throw new Exception("Failed to update user");
        }
    } catch (Exception $e) {
        header('Location: Borrowers.php?e=' . urlencode($e->getMessage()));
        exit;
    }
}

// Unified handler for status changes (active, suspend, blocked)
// Unified handler for status changes (active, suspend, blocked)
if (isset($_POST['changeStatus']) && isset($_POST['user_id']) && isset($_POST['status'])) {
    try {
        $userId = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $newStatus = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

        // Allowed statuses for borrowers
        $allowedStatuses = ['active', 'suspend', 'blocked'];
        if (!in_array($newStatus, $allowedStatuses)) {
            throw new Exception("Invalid status value");
        }

        $result = User::changeUserStatus($userId, $newStatus);
        if ($result) {
            $_SESSION['success'] = "User status has been updated to " . ucfirst($newStatus);
        } else {
            throw new Exception("Failed to update user status");
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        error_log("Status change error in Borrowers.php: " . $e->getMessage());
        $_SESSION['error'] = "Failed to change user status: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$users = User::allByRole("borrower");
?>

<?php include_once('Layout/head.php'); ?>
<?php include_once('Layout/sidebar.php'); ?>

<style>
    .dashboard-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #eef2f7 100%);
        min-height: 100vh;
        padding: 2rem;
    }
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .page-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        padding: 2rem;
        border-radius: 15px;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .table-container {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }
    .custom-table th {
        background: #1e3c72;
        color: white;
        padding: 1rem;
        font-weight: 500;
    }
    .custom-table td {
        vertical-align: middle;
        padding: 1rem;
    }
    .modal-custom .modal-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }
    .modal-custom .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        transition: all 0.3s;
        width: 120px;
    }
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .form-control {
        border-radius: 10px;
        padding: 0.75rem 1rem;
    }
    .btn-primary {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        border: none;
    }
</style>

<div class="col-md-10 dashboard-container">
    <?php if ($success || $error): ?>
        <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success ?: $error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">Borrowers Management</h2>
                <p class="text-light mb-0 mt-2">Manage and monitor all borrower accounts</p>
            </div>
           <!-- <div>
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#borrowerModal">
                    <i class="fas fa-plus me-2"></i>Add New Borrower
                </button>
            </div> -->
        </div>
        
        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-primary"><?php echo count($users); ?></h3>
                    <p class="text-muted mb-0">Total Borrowers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-success">
                        <?php echo count(array_filter($users, fn($u) => strtolower($u['status']) === 'active')); ?>
                    </h3>
                    <p class="text-muted mb-0">Active Borrowers</p>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table id="example" class="table custom-table table-hover">
            <thead>
                <tr>
                    <th>Borrower</th>
                    <th>Contact Details</th>
                    <th>Status</th>
                    <th>Documents</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($users) && count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img class="user-avatar me-3" 
                                         src="../<?php echo htmlspecialchars($user['image']); ?>"
                                         alt="<?php echo htmlspecialchars($user['name']); ?>" 
                                         onerror="this.src='../uploads/users/default/download.png';">
                                    <div>
                                        <strong class="d-block"><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <small class="text-muted">ID: <?php echo htmlspecialchars($user['id']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></div>
                                <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['mobile']); ?></div>
                                <div><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($user['address']); ?></div>
                                <div><i class="fas fa-calendar-alt me-2"></i>Joined Date: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower($user['status']) === 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo htmlspecialchars($user['status'] ?? 'inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <?php $documents = User::getDocument($user['id']); ?>
                                <?php if (!empty($documents)): ?>
                                    <button class="btn btn-outline-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#docsModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-file me-2"></i>View Documents
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">No documents</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="action-btn btn btn-outline-primary" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailsModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-eye me-2"></i>View
                                    </button>
                                    <!--<button class="action-btn btn btn-outline-warning ms-2 edit-user" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </button> -->
                                </div>
                                <!-- Inline Status Change Buttons -->
                                <div style="margin-top: 0.5rem;">
                                    <?php 
                                    $currentStatus = strtolower($user['status']);
                                    if ($currentStatus === 'active') : ?>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="suspend">
                                            <button type="submit" name="changeStatus" class="action-btn btn btn-outline-warning btn-sm">
                                                <i class="fas fa-pause-circle me-1"></i>Suspend
                                            </button>
                                        </form>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="blocked">
                                            <button type="submit" name="changeStatus" class="action-btn btn btn-outline-danger btn-sm">
                                                <i class="fas fa-ban me-1"></i>Block
                                            </button>
                                        </form>
                                    <?php elseif ($currentStatus === 'suspend') : ?>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" name="changeStatus" class="action-btn btn btn-outline-success btn-sm">
                                                <i class="fas fa-check-circle me-1"></i>Activate
                                            </button>
                                        </form>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="blocked">
                                            <button type="submit" name="changeStatus" class="action-btn btn btn-outline-danger btn-sm">
                                                <i class="fas fa-ban me-1"></i>Block
                                            </button>
                                        </form>
                                    <?php elseif ($currentStatus === 'blocked') : ?>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" name="changeStatus" class="action-btn btn btn-outline-success btn-sm">
                                                <i class="fas fa-check-circle me-1"></i>Activate
                                            </button>
                                        </form>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="suspend">
                                            <button type="submit" name="changeStatus" class="action-btn btn btn-outline-warning btn-sm">
                                                <i class="fas fa-pause-circle me-1"></i>Suspend
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Details Modal -->
                        <div class="modal fade" id="detailsModal<?php echo $user['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content modal-custom">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-user-circle me-2"></i>Borrower Profile
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <img src="../<?php echo htmlspecialchars($user['image']); ?>"
                                                     class="img-fluid rounded"
                                                     alt="Profile Image"
                                                     onerror="this.src='../uploads/users/default/download.png';">
                                            </div>
                                            <div class="col-md-8">
                                                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                                <p class="text-muted mb-4">Borrower Account</p>
                                                
                                                <div class="row g-3">
                                                    <div class="col-sm-6">
                                                        <label class="text-muted">Email</label>
                                                        <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="text-muted">Mobile</label>
                                                        <p class="mb-0"><?php echo htmlspecialchars($user['mobile']); ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="text-muted">Address</label>
                                                        <p class="mb-0"><?php echo htmlspecialchars($user['address']); ?></p>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="text-muted">Status</label>
                                                        <p class="mb-0">
                                                            <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                                                <?php echo htmlspecialchars($user['status'] ?? 'inactive'); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content modal-custom">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-edit me-2"></i>Edit Borrower
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="" method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="name" 
                                                       value="<?php echo htmlspecialchars($user['name']); ?>" 
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" 
                                                       class="form-control" 
                                                       name="email" 
                                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Mobile</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="mobile" 
                                                           value="<?php echo htmlspecialchars($user['mobile']); ?>" 
                                                           required>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Address</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                                    <textarea class="form-control" 
                                                              name="address" 
                                                              required
                                                              rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top-0">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="updateUser" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Modal -->
                        <?php if (!empty($documents)): ?>
                            <div class="modal fade" id="docsModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content modal-custom">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-file-alt me-2"></i>User Documents
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="list-group">
                                                <?php foreach ($documents as $document): ?>
                                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="fas fa-file-pdf me-2 text-danger"></i>
                                                            <?php echo htmlspecialchars(basename($document['path'])); ?>
                                                        </div>
                                                        <a href="../<?php echo htmlspecialchars($document['path']); ?>" 
                                                           class="btn btn-sm btn-primary"
                                                           download>
                                                            <i class="fas fa-download me-2"></i>Download
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- (Add New Borrower Modal remains unchanged) -->
<div class="modal fade" id="borrowerModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-custom">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Add New Borrower
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="signupForm" action="../signup.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="path" value="borrower">
                    <input type="hidden" name="role" value="borrower">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" pattern="^[a-zA-Z\s]+$" required>
                            <div class="invalid-feedback">Please enter a valid name (letters and spaces only)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>
                        <div class="col-md-6">
                           <label class="form-label">Password</label>
                           <div class="input-group">
                               <input type="password" class="form-control" name="password" id="password" 
                                      pattern="^(?=.*[A-Z])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$" required>
                               <span class="input-group-text">
                                   <i class="bi bi-eye-fill toggle-password" style="cursor: pointer;"></i>
                               </span>
                               <div class="invalid-feedback">Password must be 8-20 characters, include an uppercase letter and a special character</div>
                           </div>
                       </div>
                       <div class="col-md-6">
                           <label class="form-label">Confirm Password</label>
                           <input type="password" class="form-control" name="confirmPassword" required>
                           <div class="invalid-feedback">Passwords must match</div>
                       </div>
                       <div class="col-md-6">
                           <label class="form-label">Mobile Number</label>
                           <input type="text" class="form-control" name="mobile" pattern="^\d{5,15}$" required>
                           <div class="invalid-feedback">Please enter a valid mobile number (5-15 digits)</div>
                       </div>
                       <div class="col-12">
                           <label class="form-label">Address</label>
                           <textarea class="form-control" name="address" required></textarea>
                           <div class="invalid-feedback">Please enter your address</div>
                       </div>
                       <div class="col-md-6">
                           <label class="form-label">Profile Image</label>
                           <input type="file" class="form-control" name="profile_image" accept=".png,.jpg,.jpeg,.gif" required>
                           <div class="invalid-feedback">Please select a profile image</div>
                       </div>
                       <div class="col-md-6">
                           <label class="form-label">NIC (Front and Back)</label>
                           <input type="file" class="form-control" name="nic[]" multiple required>
                           <div class="invalid-feedback">Please upload NIC images</div>
                       </div>
                       <div class="col-md-6">
                           <label class="form-label">Utility Bills (At least 2)</label>
                           <input type="file" class="form-control" name="utility_bills[]" multiple required>
                           <div class="invalid-feedback">Please upload at least 2 utility bills</div>
                       </div>
                       <div class="col-md-6">
                           <label class="form-label">Salary Statements (Last 6 Months)</label>
                           <input type="file" class="form-control" name="salary_statements[]" multiple required>
                           <div class="invalid-feedback">Please upload salary statements</div>
                       </div>
                   </div>
                   <div class="modal-footer border-top-0 mt-4">
                       <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                       <button type="submit" name="BorrowerSignUp" class="btn btn-primary">
                           <i class="fas fa-user-plus me-2"></i>Create Borrower Account
                       </button>
                   </div>
               </form>
           </div>
       </div>
   </div>
</div>

<script>
$(document).ready(function() {
   // Initialize DataTable
   $('#example').DataTable({
       "order": [[0, "asc"]],
       "pageLength": 10,
       "responsive": true,
       "language": {
           "search": "<i class='fas fa-search'></i> Search:",
           "paginate": {
               "first": '<i class="fas fa-angle-double-left"></i>',
               "previous": '<i class="fas fa-angle-left"></i>',
               "next": '<i class="fas fa-angle-right"></i>',
               "last": '<i class="fas fa-angle-double-right"></i>'
           }
       },
       "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
              '<"row"<"col-sm-12"tr>>' +
              '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
       "drawCallback": function() {
           $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
       }
   });

   // Initialize tooltips
   var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
   tooltipTriggerList.map(function(tooltipTriggerEl) {
       return new bootstrap.Tooltip(tooltipTriggerEl);
   });

   // Password toggle functionality
   $('.toggle-password').click(function() {
       const passwordInput = $(this).closest('.input-group').find('input');
       const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
       passwordInput.attr('type', type);
       $(this).toggleClass('bi-eye-fill bi-eye-slash-fill');
   });

   // Form validation
   const forms = document.querySelectorAll('.needs-validation');
   Array.from(forms).forEach(form => {
       form.addEventListener('submit', event => {
           if (!form.checkValidity()) {
               event.preventDefault();
               event.stopPropagation();
           }
           form.classList.add('was-validated');
       }, false);
   });

   // Password confirmation validation
   const password = document.querySelector('input[name="password"]');
   const confirm = document.querySelector('input[name="confirmPassword"]');
   if (password && confirm) {
       confirm.addEventListener('input', function() {
           if (this.value !== password.value) {
               this.setCustomValidity('Passwords do not match');
           } else {
               this.setCustomValidity('');
           }
       });
   }

   // File upload validation
   document.querySelectorAll('input[type="file"]').forEach(input => {
       input.addEventListener('change', function(e) {
           const files = Array.from(e.target.files);
           const maxSize = 5 * 1024 * 1024; // 5MB
           
           const invalidFiles = files.filter(file => file.size > maxSize);
           
           if (invalidFiles.length > 0) {
               Swal.fire({
                   icon: 'error',
                   title: 'File Too Large',
                   text: 'Some files exceed 5MB. Please select smaller files.'
               });
               e.target.value = '';
           }

           // Additional validation for multiple files
           if (this.name === 'utility_bills[]' && files.length < 2) {
               this.setCustomValidity('Please upload at least 2 utility bills');
           } else if (this.name === 'salary_statements[]' && files.length < 6) {
               this.setCustomValidity('Please upload 6 months of salary statements');
           } else {
               this.setCustomValidity('');
           }
       });
   });

   // Edit user functionality
   $('.edit-user').on('click', function() {
       const userId = $(this).data('user-id');
       const modal = $(`#editModal${userId}`);
       // Optionally add AJAX here to refresh data
   });

   // Handle form errors and success messages
   if (document.querySelector('.alert')) {
       setTimeout(() => {
           $('.alert').fadeOut('slow');
       }, 5000);
   }
});
</script>

<?php include_once('Layout/footer.php'); ?>
