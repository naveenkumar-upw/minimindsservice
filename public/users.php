<?php
require_once '../includes/auth.php';
initSession();
$user = requireSuperAdmin();

// Handle role update and password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        if (deleteUser($userId)) {
            $_SESSION['success'] = 'User deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete user. Cannot delete the last super admin.';
        }
        header('Location: /public/users.php');
        exit;
    } elseif (isset($_POST['user_id'], $_POST['role'])) {
        $userId = $_POST['user_id'];
        $newRole = $_POST['role'];
        
        if (updateUserRole($userId, $newRole)) {
            $_SESSION['success'] = 'User role updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update user role';
        }
        
        header('Location: /public/users.php');
        exit;
    } elseif (isset($_POST['user_id'], $_POST['new_password'])) {
        $userId = $_POST['user_id'];
        $newPassword = $_POST['new_password'];
        
        if (resetUserPassword($userId, $newPassword)) {
            $_SESSION['success'] = 'Password reset successfully';
        } else {
            $_SESSION['error'] = 'Failed to reset password';
        }
        
        header('Location: /public/users.php');
        exit;
    }
}

$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .page-title {
            color: var(--primary);
            margin-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 600;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
        }
        .table th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            font-size: 1.1rem;
        }
        .table th:first-child {
            border-top-left-radius: 8px;
        }
        .table th:last-child {
            border-top-right-radius: 8px;
        }
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .badge-role {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-user {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        .badge-admin {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
        }
        .badge-super-admin {
            background: linear-gradient(135deg, #198754, #146c43);
            color: white;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            margin-left: 8px;
        }
        .btn-secondary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #bb2d3b);
            color: white;
            margin-left: 8px;
        }
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .role-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-right: 10px;
            font-size: 0.95rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .alert-success {
            background: linear-gradient(135deg, #d1e7dd, #badbcc);
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c2c7);
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        .modal-content {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 1.5rem;
        }
        .modal-body {
            padding: 2rem;
        }
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid #eee;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .table td.timestamp {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1 class="page-title">User Management</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card-container">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge-role badge-<?php echo strtolower(str_replace('_', '-', $user['role'])); ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td class="timestamp"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td class="timestamp">
                                    <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role" class="role-select" onchange="this.form.submit()">
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                        </select>
                                    </form>
                                    <button class="btn btn-secondary" onclick="openResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                        Reset Password
                                    </button>
                                    <button class="btn btn-danger" onclick="openDeleteModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['role'] === 'super_admin' ? 'true' : 'false'; ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Reset password for user: <strong><span id="reset_username"></span></strong></p>
                    <form id="resetPasswordForm" method="POST">
                        <input type="hidden" id="reset_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="resetPasswordForm" class="btn btn-primary">Reset Password</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="delete_warning" class="alert alert-danger" style="display: none;">
                        <strong>Warning:</strong> You are about to delete a super admin user. This action cannot be undone and may affect system access.
                    </div>
                    <p>Are you sure you want to delete user: <strong><span id="delete_username"></span></strong>?</p>
                    <p>This action cannot be undone. Please type <strong>DELETE</strong> to confirm:</p>
                    <form id="deleteUserForm" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" id="delete_user_id" name="user_id">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="delete_confirm_input" oninput="validateDeleteConfirmation()">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="deleteUserForm" id="delete_submit" class="btn btn-danger" disabled>Delete User</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openResetPasswordModal(userId, username) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_username').textContent = username;
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        }

        function openDeleteModal(userId, username, isAdmin) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_username').textContent = username;
            document.getElementById('delete_warning').style.display = isAdmin ? 'block' : 'none';
            document.getElementById('delete_confirm_input').value = '';
            document.getElementById('delete_submit').disabled = true;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }

        function validateDeleteConfirmation() {
            const input = document.getElementById('delete_confirm_input');
            const submit = document.getElementById('delete_submit');
            submit.disabled = input.value.toLowerCase() !== 'delete';
        }
    </script>
</body>
</html>
