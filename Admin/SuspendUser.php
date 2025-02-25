<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$errors = [];
$users = User::allByStatus('suspend');
?>

<?php
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<style>
    .dashboard-container {
        background-color: #f8f9fa;
        min-height: 100vh;
        padding: 2rem;
    }

    .page-header {
        background: linear-gradient(135deg, #4b6cb7, #182848);
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .page-title {
        color: white;
        margin: 0;
        font-weight: 600;
        text-align: center;
    }

    .notification-alert {
        background-color: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border-left: 4px solid #3b82f6;
        animation: slideIn 0.5s ease-out;
    }

    .users-table-container {
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .custom-table thead th {
        background-color: #182848;
        color: white;
        font-weight: 600;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
        border: none;
    }

    .custom-table tbody tr {
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .custom-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .custom-table tbody td {
        padding: 1rem;
        background-color: white;
        border-top: 1px solid #f3f4f6;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
    }

    .custom-table tbody td:first-child {
        border-left: 1px solid #f3f4f6;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }

    .custom-table tbody td:last-child {
        border-right: 1px solid #f3f4f6;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .user-image {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e5e7eb;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-inactive {
        background-color: #fef3c7;
        color: #92400e;
    }

    .action-dropdown .dropdown-toggle {
        background-color: #f3f4f6;
        border: none;
        color: #374151;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .action-dropdown .dropdown-toggle:hover {
        background-color: #e5e7eb;
    }

    .action-dropdown .dropdown-menu {
        border: none;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        padding: 0.5rem;
        min-width: 200px;
    }

    .action-btn {
        width: 100%;
        text-align: left;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        margin-bottom: 0.25rem;
        border: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-activate {
        background-color: #10b981;
        color: white;
    }

    .btn-details {
        background-color: #3b82f6;
        color: white;
    }

    .action-btn:hover {
        filter: brightness(110%);
        transform: translateY(-1px);
    }

    .custom-modal .modal-content {
        border-radius: 15px;
        border: none;
    }

    .custom-modal .modal-header {
        background-color: #182848;
        color: white;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        padding: 1rem 1.5rem;
    }

    .custom-modal .modal-body {
        padding: 1.5rem;
    }

    .document-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        padding: 1rem;
    }

    .document-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .document-image:hover {
        transform: scale(1.05);
    }

    @keyframes slideIn {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($error)): ?>
        <div class="notification-alert">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h2 class="page-title">
            Suspended Users Management
        </h2>
    </div>

    <div class="users-table-container">
        <table class="custom-table" id="example">
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
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
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
                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $user['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i> Actions
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $user['id']; ?>">
                                    <li>
                                        <form method="post" action="">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <button type="submit" class="action-btn btn-activate" name="active">
                                                <i class="fas fa-check-circle"></i> Activate Account
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <!-- Modal trigger button -->
                                        <button type="button" class="action-btn btn-details" data-bs-toggle="modal" data-bs-target="#exampleModal<?php echo $user['id']; ?>">
                                            <i class="fas fa-info-circle"></i> View Details
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal markup placed outside the table -->
<?php foreach ($users as $userItem): ?>
    <div class="modal fade custom-modal" id="exampleModal<?php echo $userItem['id']; ?>" tabindex="-1" aria-labelledby="exampleModalLabel<?php echo $userItem['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel<?php echo $userItem['id']; ?>">
                        User Documents - <?php echo htmlspecialchars($userItem['name']); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $documents = User::getDocument($userItem['id']); ?>
                    <div class="document-grid">
                        <?php foreach ($documents as $document): ?>
                            <div class="document-item">
                                <img class="document-image"
                                     src="../<?php echo htmlspecialchars($document['path']); ?>"
                                     alt="User Document"
                                     onerror="this.onerror=null; this.src='../uploads/users/default/download.png';">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include_once('Layout/footer.php'); ?>

<script>
    $(document).ready(function() {
        $('#example').DataTable({
            "dom": '<"top"f>rt<"bottom"ip><"clear">',
            "language": {
                "search": "<i class='fas fa-search'></i>",
                "searchPlaceholder": "Search suspended users..."
            },
            "pageLength": 10,
            "ordering": true,
            "responsive": true
        });
    });
</script>