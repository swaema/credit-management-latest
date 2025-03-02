<?php
// Lenders.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/User.php';
require_once '../Classes/Lender.php';
require_once '../Classes/Mail.php';

if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit;
}

$error = isset($_GET['e']) ? $_GET['e'] : '';
$success = isset($_GET['s']) ? $_GET['s'] : '';

function logError($message) {
    $logFile = __DIR__ . '/../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Handle user updates
if (isset($_POST['updateUser'])) {
    try {
        $userId = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $mobile = filter_var($_POST['mobile'], FILTER_SANITIZE_STRING);
        $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);

        logError("Attempting to update user ID: " . $userId);
        
        // Assuming Lender::updateUser works similar to User::updateUser:
        $updated = Lender::updateUser($userId, [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address
        ]);

        if ($updated) {
            $_SESSION['success'] = "User updated successfully";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            throw new Exception("Failed to update user");
        }
    } catch (Exception $e) {
        logError("Error in update process: " . $e->getMessage());
        $_SESSION['error'] = "Error updating user: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle user status changes
// Handle user status changes
if (isset($_POST['changeStatus']) && isset($_POST['user_id']) && isset($_POST['status'])) {
    try {
        $userId = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $newStatus = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

        logError("Attempting to change status for user ID: " . $userId . " to status: " . $newStatus);

        // Validate the status value
        $allowedStatuses = ['active', 'inactive', 'blocked', 'suspend'];
        if (!in_array($newStatus, $allowedStatuses)) {
            throw new Exception("Invalid status value");
        }

        // Use User class method instead of direct database update
        if (User::changeUserStatus($userId, $newStatus)) {
            $statusMessage = "User status has been updated to " . ucfirst($newStatus);
            $_SESSION['success'] = $statusMessage;
        } else {
            throw new Exception("No changes made to user status");
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        logError("Status change error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to change user status: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// --------------------------------------------------------------------
// Fetch all lenders regardless of status
$db = new Database();
$conn = $db->getConnection();
$query = "SELECT * FROM users WHERE role = 'lender'";
$result = $conn->query($query);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
// --------------------------------------------------------------------
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
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }
    .status-active {
        background-color: #28a745;
        color: white;
    }
    .status-inactive {
        background-color: #6c757d;
        color: white;
    }
    .status-blocked {
        background-color: #dc3545;
        color: white;
    }
    .status-suspend {
        background-color: #ffc107;
        color: black;
    }
    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        transition: all 0.3s;
    }
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
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
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
        <div class="alert alert-<?php echo isset($_SESSION['success']) ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php 
                echo isset($_SESSION['success']) ? htmlspecialchars($_SESSION['success']) : htmlspecialchars($_SESSION['error']);
                unset($_SESSION['success']);
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h2 class="mb-0">Lenders Management</h2>
        <p class="text-light mb-0 mt-2">Manage and monitor all lender accounts</p>
        
        <div class="row mt-4">
            <!-- Total Lenders -->
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-primary"><?php echo count($users); ?></h3>
                    <p class="text-muted mb-0">Total Lenders</p>
                </div>
            </div>
            <!-- Active Lenders -->
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-success">
                        <?php echo count(array_filter($users, function($u) {
                            return strtolower(trim($u['status'])) === 'active';
                        })); ?>
                    </h3>
                    <p class="text-muted mb-0">Active Lenders</p>
                </div>
            </div>
            <!-- Suspended Lenders -->
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-warning">
                        <?php echo count(array_filter($users, function($u) {
                            $status = strtolower(trim($u['status']));
                            return $status === 'suspend' || $status === 'suspended';
                        })); ?>
                    </h3>
                    <p class="text-muted mb-0">Suspended Lenders</p>
                </div>
            </div>
            <!-- Blocked Lenders -->
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-danger">
                        <?php echo count(array_filter($users, function($u) {
                            $status = strtolower(trim($u['status']));
                            return $status === 'blocked' || $status === 'block';
                        })); ?>
                    </h3>
                    <p class="text-muted mb-0">Blocked Lenders</p>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table id="example" class="table custom-table table-hover">
            <thead>
                <tr>
                    <th>Lender</th>
                    <th>Details</th>
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
                                         src="../<?php echo htmlspecialchars($user['image'] ?: 'uploads/users/default/download.png'); ?>"
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
                                <span class="status-badge status-<?php echo strtolower(trim($user['status'])); ?>">
                                    <?php echo htmlspecialchars(ucfirst(trim($user['status']))); ?>
                                </span>
                            </td>
                            <td>
                                <?php $documents = Lender::getDocument($user['id']); ?>
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
                                   <!-- <button class="action-btn btn btn-outline-warning ms-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </button> -->
                                </div>
                                <!-- Status Change Buttons (No Dropdown) -->
                                <div style="margin-top: 0.5rem;">
                                    <?php 
                                    $currentStatus = strtolower(trim($user['status']));
                                    if ($currentStatus === 'active') : ?>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="suspend">
                                            <button type="submit" name="changeStatus" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-pause-circle me-1"></i>Suspend
                                            </button>
                                        </form>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="blocked">
                                            <button type="submit" name="changeStatus" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-ban me-1"></i>Block
                                            </button>
                                        </form>
                                    <?php elseif ($currentStatus === 'suspend' || $currentStatus === 'suspended') : ?>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" name="changeStatus" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-check-circle me-1"></i>Activate
                                            </button>
                                        </form>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="blocked">
                                            <button type="submit" name="changeStatus" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-ban me-1"></i>Block
                                            </button>
                                        </form>
                                    <?php elseif ($currentStatus === 'blocked' || $currentStatus === 'block') : ?>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" name="changeStatus" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-check-circle me-1"></i>Activate
                                            </button>
                                        </form>
                                        <form style="display:inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="suspend">
                                            <button type="submit" name="changeStatus" class="btn btn-outline-warning btn-sm">
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
                                            <i class="fas fa-user-circle me-2"></i>Lender Details
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <img src="../<?php echo htmlspecialchars($user['image'] ?: 'uploads/users/default/download.png'); ?>"
                                                     class="img-fluid rounded"
                                                     alt="Profile Image"
                                                     onerror="this.src='../uploads/users/default/download.png';">
                                            </div>
                                            <div class="col-md-8">
                                                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                                <p class="text-muted mb-4">Lender Account</p>
                                                
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
                                                            <span class="status-badge status-<?php echo strtolower(trim($user['status'])); ?>">
                                                                <?php echo htmlspecialchars(ucfirst(trim($user['status']))); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="text-muted">Joined Date</label>
                                                        <p class="mb-0">
                                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
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
                                            <i class="fas fa-edit me-2"></i>Edit Lender
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

<?php include_once('Layout/footer.php'); ?>

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
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
