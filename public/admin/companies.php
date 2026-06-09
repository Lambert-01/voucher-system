<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/ui.php';
require_once __DIR__ . '/../../app/Models/Company.php';

requireRole('admin');

$pdo = getDB();
$companyModel = new Company($pdo);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            $companyModel->create($_POST);
            $success = 'Company created successfully';
            logAudit($pdo, $_SESSION['user_id'], 'company_create', 'Created company: ' . $_POST['company_name']);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'update') {
        try {
            $companyModel->update($_POST['id'], $_POST);
            $success = 'Company updated successfully';
            logAudit($pdo, $_SESSION['user_id'], 'company_update', 'Updated company ID: ' . $_POST['id']);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$companies = $companyModel->getAll();

$title = 'Manage Companies';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-building"></i> Manage Companies</h1>
        <p>Organizations purchasing vouchers</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2><i class="fas fa-plus-circle"></i> Add New Company</h2>
    <form method="POST">
        <input type="hidden" name="action" value="create">
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-building"></i> Company Name *</label>
                <input type="text" name="company_name" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user"></i> Contact Person</label>
                <input type="text" name="contact_person">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone</label>
                <input type="text" name="phone" placeholder="e.g. 0788123456">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" placeholder="company@example.com">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Address</label>
                <input type="text" name="address" placeholder="Company address">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-toggle-on"></i> Status</label>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary btn-large"><i class="fas fa-check"></i> Create Company</button>
    </form>
</div>

<div class="card">
    <h2><i class="fas fa-list"></i> Registered Companies (<?= count($companies) ?>)</h2>
    <?php if (count($companies) > 0): ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $company): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($company['company_name']) ?></strong></td>
                    <td><?= htmlspecialchars($company['contact_person'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($company['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($company['email'] ?? '-') ?></td>
                    <td><span class="badge <?= statusBadgeClass($company['status']) ?>"><?= statusLabel($company['status']) ?></span></td>
                    <td><?= formatDate($company['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-building"></i>
        <p>No companies registered yet</p>
        <p style="font-size: 0.875rem; margin-top: 8px;">Create your first company above</p>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
