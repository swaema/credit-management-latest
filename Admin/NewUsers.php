<?php
session_start();
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/User.php';
require_once '../Classes/Borrower.php';
require_once '../Classes/Lender.php';

if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
if (isset($_POST['active'])) {
    $userId = $_POST['id'];
    User::activeUser($userId);
}
if (isset($_POST['block'])) {
    $userId = $_POST['id'];
    User::changeUserStatus($userId, "blocked");
}
if (isset($_POST['suspend'])) {
    $userId = $_POST['id'];
    User::changeUserStatus($userId, "suspend");
}
$errors = [];
$users = User::allByStatus('inactive');
?>

<?php
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<style>
    .dashboard-container {
        background-color: #f8f9fa !important;
        min-height: 100vh !important;
        padding: 2rem !important;
    }

    .page-header {
        background: linear-gradient(135deg, #1e3c72, #2a5298) !important;
        padding: 2rem !important;
        border-radius: 15px !important;
        margin-bottom: 2rem !important;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
    }

    .page-title {
        color: white !important;
        margin: 0 !important;
        font-weight: 600 !important;
        text-align: center !important;
    }

    .notification-alert {
        background-color: white !important;
        border-radius: 8px !important;
        padding: 1rem !important;
        margin-bottom: 1rem !important;
        border-left: 4px solid #3b82f6 !important;
    }

    .users-table-container {
        background-color: white !important;
        border-radius: 15px !important;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important;
        padding: 1.5rem !important;
    }

    .custom-table {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0 8px !important;
    }

    .custom-table thead th {
        background-color: #1e3c72 !important;
        color: white !important;
        font-weight: 600 !important;
        padding: 1rem !important;
        text-transform: uppercase !important;
        font-size: 0.875rem !important;
        letter-spacing: 0.05em !important;
        border: none !important;
    }

    .custom-table tbody tr {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02) !important;
    }

    .custom-table tbody td {
        padding: 1rem !important;
        background-color: white !important;
        border-top: 1px solid #f3f4f6 !important;
        border-bottom: 1px solid #f3f4f6 !important;
        vertical-align: middle !important;
    }

    .user-image {
        width: 50px !important;
        height: 50px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 2px solid #e5e7eb !important;
    }

    .status-badge {
        padding: 0.5rem 1rem !important;
        border-radius: 9999px !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        background-color: #f3f4f6 !important;
    }

    .status-inactive {
        background-color: #fef3c7 !important;
        color: #92400e !important;
    }

    .action-dropdown .dropdown-toggle {
        background-color: #f3f4f6 !important;
        border: none !important;
        color: #374151 !important;
        padding: 0.5rem 1rem !important;
        border-radius: 6px !important;
    }

    .action-btn {
        width: 100% !important;
        text-align: left !important;
        padding: 0.5rem 1rem !important;
        border-radius: 6px !important;
        margin-bottom: 0.25rem !important;
        border: none !important;
    }

    .btn-activate { background-color: #10b981 !important; color: white !important; }
    .btn-block { background-color: #6b7280 !important; color: white !important; }
    .btn-details { background-color: #3b82f6 !important; color: white !important; }
    .btn-suspend { background-color: #ef4444 !important; color: white !important; }

    /* Modal Styles */
    .modal-header {
        background-color: #1e3c72;
        color: white;
    }

    .modal-body {
        padding: 1.5rem;
    }

    /* Document Grid Styles */
    .document-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
        padding: 1rem;
    }

    .document-item {
        text-align: center;
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
    }

    .document-image {
        max-width: 100%;
        height: auto;
        max-height: 300px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        object-fit: contain;
    }

    .pdf-preview {
        width: 100%;
        height: 400px;
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .pdf-preview-container {
        width: 100%;
        position: relative;
    }

    .pdf-title {
        margin-top: 8px;
        font-size: 0.875rem;
        color: #4b5563;
        word-break: break-word;
    }

    /* Make modal larger for PDF preview */
    .modal-lg {
        max-width: 80% !important;
    }

    .file-icon-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .file-icon {
        font-size: 3rem;
        color: #4b5563;
    }

    .file-name {
        font-size: 0.875rem;
        color: #4b5563;
        word-break: break-word;
        text-align: center;
    }
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($error)): ?>
        <div class="notification-alert">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h2 class="page-title">New User Applications</h2>
    </div>

    <div class="users-table-container">
        <table class="custom-table" id="usersTable">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>User Type</th>
                <th>Mobile No.</th>
                <th>Address</th>
                <th>Status</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (is_array($users) && count($users) > 0): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                                <span class="status-badge">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                        </td>
                        <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                        <td><?php echo htmlspecialchars($user['address']); ?></td>
                        <td>
                                <span class="status-badge status-inactive">
                                    <?php echo htmlspecialchars($user['status'] ?? 'inactive'); ?>
                                </span>
                        </td>
                        <td>
                            <img class="user-image"
                                 src="../<?php echo htmlspecialchars($user['image']); ?>"
                                 alt="<?php echo htmlspecialchars($user['name']); ?>'s profile"
                                 onerror="this.onerror=null; this.src='../uploads/users/default/download.png';">
                        </td>
                        <td>
                            <div class="dropdown action-dropdown">
                                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i> Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <form method="post" action="">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <button type="submit" class="action-btn btn-activate" name="active">
                                                <i class="fas fa-check-circle"></i> Activate
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="post" action="">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <button type="submit" class="action-btn btn-block" name="block">
                                                <i class="fas fa-ban"></i> Block
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <button type="button" class="action-btn btn-details" data-bs-toggle="modal"
                                                data-bs-target="#exampleModal<?php echo $user['id'] ?>">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                    </li>
                                    <li>
                                        <form method="post" action="" onsubmit="return myConfirm();">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <button type="submit" class="action-btn btn-suspend" name="suspend">
                                                <i class="fas fa-user-slash"></i> Suspend
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="exampleModal<?php echo $user['id'] ?>" tabindex="-1"
                                 aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">User Documents - <?php echo htmlspecialchars($user['name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php
                                            $documents = User::getDocument($user['id']);
                                            if (empty($documents)):
                                                ?>
                                                <div class="text-center">
                                                    <p>No documents available for this user.</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="document-grid">
                                                    <?php foreach ($documents as $document):
                                                        $file_path = "../" . $document['path'];
                                                        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                                        $file_name = basename($document['path']);
                                                        ?>
                                                        <div class="document-item">
                                                            <?php if ($file_extension === 'pdf'): ?>
                                                                <div class="pdf-preview-container">
                                                                    <iframe
                                                                            src="<?php echo $file_path; ?>#toolbar=0"
                                                                            class="pdf-preview"
                                                                            title="PDF Preview">
                                                                    </iframe>
                                                                    <div class="pdf-title">
                                                                        <?php echo htmlspecialchars($file_name); ?>
                                                                    </div>
                                                                </div>
                                                            <?php elseif (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                                <img
                                                                        class="document-image"
                                                                        src="<?php echo $file_path; ?>"
                                                                        alt="User Document"
                                                                        onerror="this.onerror=null; this.src='../uploads/users/default/download.png';">
                                                            <?php else: ?>
                                                                <div class="file-icon-container">
                                                                    <i class="fas fa-file file-icon"></i>
                                                                    <span class="file-name"><?php echo htmlspecialchars($file_name); ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "responsive": true,
            "language": {
                "search": "<i class='fas fa-search'></i>",
                "searchPlaceholder": "Search users..."
            }
        });
    });

    function myConfirm() {
        return confirm("Are you sure you want to suspend this user's account?");
    }
</script>