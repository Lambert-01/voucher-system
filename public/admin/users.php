<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/User.php';

requireRole('admin');

$pdo = getDB();
$userModel = new User($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    try {
        if ($action === 'create') {
            $data = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? '',
                'status' => $_POST['status'] ?? 'active'
            ];

            if ($data['full_name'] === '' || $data['username'] === '' || $data['password'] === '') {
                throw new Exception('Full name, username, and password are required.');
            }

            if (!in_array($data['role'], ['boss', 'admin', 'cashier'], true)) {
                throw new Exception('Please select a valid role.');
            }

            if (!in_array($data['status'], ['active', 'blocked'], true)) {
                $data['status'] = 'active';
            }

            if (strlen($data['password']) < 8) {
                throw new Exception('Password must be at least 8 characters.');
            }

            if ($userModel->usernameExists($data['username'])) {
                throw new Exception('Username already exists.');
            }

            $userId = $userModel->create($data);
            logAudit($pdo, $_SESSION['user_id'], 'user_create', 'Created user: ' . $data['username']);
            showNotification('User created successfully.', 'success');
            redirectTo('/admin/users.php');
        }

        if ($action === 'status') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $target = $userModel->getById($userId);

            if (!$target || !in_array($status, ['active', 'blocked'], true)) {
                throw new Exception('Invalid user status request.');
            }

            if ($userId === (int)$_SESSION['user_id'] && $status === 'blocked') {
                throw new Exception('You cannot block your own account while logged in.');
            }

            $userModel->updateStatus($userId, $status);
            logAudit($pdo, $_SESSION['user_id'], 'user_status', 'Changed user status: ' . $target['username'] . ' to ' . $status);
            showNotification('User status updated.', 'success');
            redirectTo('/admin/users.php');
        }

        if ($action === 'reset_password') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $password = $_POST['password'] ?? '';
            $target = $userModel->getById($userId);

            if (!$target) {
                throw new Exception('User not found.');
            }

            if (strlen($password) < 8) {
                throw new Exception('New password must be at least 8 characters.');
            }

            $userModel->resetPassword($userId, $password);
            logAudit($pdo, $_SESSION['user_id'], 'user_password_reset', 'Reset password for user: ' . $target['username']);
            showNotification('Password reset successfully.', 'success');
            redirectTo('/admin/users.php');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$users = $userModel->getAll();

$title = 'Manage Users';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-users-cog"></i> Manage Users</h1>
        <p>Create staff accounts and control access before production launch.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error app-notification" data-notification-type="error" data-notification-message="<?= htmlspecialchars($error) ?>">
        <i class="fas fa-exclamation-triangle"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2><i class="fas fa-user-plus"></i> Create Staff User</h2>
    <form method="POST">
        <input type="hidden" name="action" value="create">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required placeholder="Staff full name">
            </div>
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required placeholder="Unique login username" autocomplete="off">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="boss">Boss</option>
                    <option value="admin">Admin</option>
                    <option value="cashier">Cashier</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Optional email">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" placeholder="Optional phone">
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create User</button>
    </form>
</div>

<div class="card">
    <h2><i class="fas fa-users"></i> Existing Users</h2>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                        <small><?= htmlspecialchars($user['email'] ?? '') ?> <?= htmlspecialchars($user['phone'] ?? '') ?></small>
                    </td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars(strtoupper($user['role'])) ?></span></td>
                    <td><span class="badge badge-<?= $user['status'] === 'active' ? 'active' : 'blocked' ?>"><?= htmlspecialchars($user['status']) ?></span></td>
                    <td><?= htmlspecialchars($user['last_login'] ?? 'Never') ?></td>
                    <td>
                        <form method="POST" style="display: inline-flex; gap: 6px; align-items: center; margin-bottom: 6px;">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="status" value="<?= $user['status'] === 'active' ? 'blocked' : 'active' ?>">
                            <button type="submit" class="btn btn-sm <?= $user['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>">
                                <?= $user['status'] === 'active' ? 'Block' : 'Activate' ?>
                            </button>
                        </form>
                        <form method="POST" style="display: inline-flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="password" name="password" placeholder="New password" minlength="8" required style="width: 150px;">
                            <button type="submit" class="btn btn-sm btn-secondary">Reset</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
